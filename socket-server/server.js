const http = require('http');
const crypto = require('crypto');
const net = require('net');
const { WebSocketServer, WebSocket } = require('ws');
const url = require('url');

const PORT = 9999;
const PROXY_PORT = 9998;
const PING_INTERVAL = 25000;
const PING_TIMEOUT = 60000;

// Connected clients: { visitorId: { ws, userId, sessionId, serverId, gameId, heartbeat } }
const clients = {};
// Map userId to visitorId for quick lookup
const userMap = {};

function generateSid() {
    return crypto.randomBytes(16).toString('base64url');
}

// Pending sessions from HTTP polling (waiting for WebSocket upgrade)
const pendingSessions = {};

// ============================================================
// Main HTTP + WebSocket server (port 9999)
// ============================================================
const server = http.createServer((req, res) => {
    const parsed = url.parse(req.url, true);

    // CORS headers
    res.setHeader('Access-Control-Allow-Origin', '*');
    res.setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
    res.setHeader('Access-Control-Allow-Headers', 'Content-Type');

    if (req.method === 'OPTIONS') {
        res.writeHead(200);
        res.end();
        return;
    }

    // Engine.IO polling handshake
    if (parsed.pathname === '/socket.io/' && parsed.query.transport === 'polling') {
        const sid = generateSid();
        const response = JSON.stringify({
            sid: sid,
            upgrades: ['websocket'],
            pingInterval: PING_INTERVAL,
            pingTimeout: PING_TIMEOUT
        });
        pendingSessions[sid] = { created: Date.now() };
        // EIO2 format: length:payload
        const payload = response.length + ':' + response;
        res.writeHead(200, { 'Content-Type': 'text/plain; charset=UTF-8' });
        res.end(payload);
        log('Polling handshake, sid=' + sid);
        return;
    }

    // PHP Push API
    if (parsed.pathname === '/push' && req.method === 'POST') {
        let body = '';
        req.on('data', chunk => body += chunk);
        req.on('end', () => {
            try {
                const data = JSON.parse(body);
                const result = handlePush(data);
                res.writeHead(200, { 'Content-Type': 'application/json' });
                res.end(JSON.stringify(result));
            } catch (e) {
                res.writeHead(400, { 'Content-Type': 'application/json' });
                res.end(JSON.stringify({ error: e.message }));
            }
        });
        return;
    }

    // Status endpoint
    if (parsed.pathname === '/status') {
        const clientList = {};
        for (const [vid, c] of Object.entries(clients)) {
            clientList[vid] = { userId: c.userId, serverId: c.serverId };
        }
        res.writeHead(200, { 'Content-Type': 'application/json' });
        res.end(JSON.stringify({ clients: clientList, userMap }));
        return;
    }

    res.writeHead(404);
    res.end('Not found');
});

// WebSocket server for Socket.IO connections
const wss = new WebSocketServer({ noServer: true });

server.on('upgrade', (req, socket, head) => {
    const parsed = url.parse(req.url, true);

    if (parsed.pathname === '/socket.io/') {
        wss.handleUpgrade(req, socket, head, (ws) => {
            handleSocketIOConnection(ws, parsed.query.sid);
        });
    } else {
        socket.destroy();
    }
});

function handleSocketIOConnection(ws, sid) {
    const visitorId = generateSid();
    log('WebSocket connected, visitor=' + visitorId + ', sid=' + sid);

    // After WebSocket upgrade, client sends "2probe", we respond "3probe"

    ws.on('message', (data) => {
        const msg = data.toString();

        // EIO-level messages
        if (msg === '2probe') {
            ws.send('3probe');
            log('Probe exchange done');
            return;
        }

        // EIO upgrade complete
        if (msg === '5') {
            log('Upgrade complete, sending Socket.IO CONNECT');
            // Send Socket.IO CONNECT packet (type 0, namespace /)
            ws.send('40');

            // After connect, ask client for info
            setTimeout(() => {
                sendEvent(ws, 'requestClientInfo', {});
            }, 100);

            // No server-side heartbeat — the SWF handles its own ping/pong
            return;
        }

        // Pong response from client (ignore, no server-side heartbeat)
        if (msg === '3') {
            return;
        }

        // Ping from client — respond with pong
        if (msg === '2') {
            ws.send('3');
            return;
        }

        // Socket.IO message (starts with "4")
        if (msg.charAt(0) === '4') {
            const packet = decodePacket(msg);
            if (!packet) return;

            log('Received packet: type=' + packet.type + ', data=' + JSON.stringify(packet.data));

            if (packet.type === 2) { // EVENT
                const eventName = packet.data[0];
                const eventData = packet.data[1] || {};

                // SWF wraps responses in "message" event via FlashSocket.send()
                // Format: 42["message",{"type":"requestClientInfoResponse","data":{...}}]
                let actualType = eventName;
                let actualData = eventData;

                if (eventName === 'message' && eventData && eventData.type) {
                    actualType = eventData.type;
                    actualData = eventData.data || {};
                }

                if (actualType === 'requestClientInfoResponse') {
                    const userId = actualData.user_id;
                    const sessionId = actualData.session_id;
                    const serverId = actualData.server_id;
                    const gameId = actualData.game_id;

                    log('Client registered: userId=' + userId + ', serverId=' + serverId);

                    clients[visitorId] = {
                        ws, userId, sessionId, serverId, gameId
                    };
                    userMap[String(userId)] = visitorId;

                    // Send clientRegistered
                    sendEvent(ws, 'clientRegistered', {});
                } else {
                    log('Unhandled event: ' + actualType);
                }
            }
        }
    });

    ws.on('close', () => {
        log('WebSocket closed, visitor=' + visitorId);
        const client = clients[visitorId];
        if (client) {
            delete userMap[client.userId];
            delete clients[visitorId];
        }
    });

    ws.on('error', (err) => {
        log('WebSocket error: ' + err.message);
    });
}

// Send a Socket.IO event: 42["eventName", data]
function sendEvent(ws, eventName, data) {
    if (ws.readyState !== WebSocket.OPEN) return;
    const payload = JSON.stringify([eventName, data]);
    ws.send('42' + payload);
}

// Decode Socket.IO packet from string like "42["eventName",{...}]"
function decodePacket(raw) {
    // Strip EIO prefix "4"
    const str = raw.substring(1);
    const type = parseInt(str.charAt(0));
    let i = 1;

    // Check for namespace
    let nsp = '/';
    if (str.charAt(i) === '/') {
        nsp = '';
        while (i < str.length) {
            const c = str.charAt(i);
            if (c === ',') { i++; break; }
            nsp += c;
            i++;
            if (i >= str.length) break;
        }
    }

    // Check for ack id
    let id = null;
    const next = str.charAt(i);
    if (next !== '' && !isNaN(parseInt(next))) {
        let idStr = '';
        while (i < str.length) {
            const c = str.charAt(i);
            if (isNaN(parseInt(c))) break;
            idStr += c;
            i++;
        }
        id = parseInt(idStr);
    }

    // Parse data
    let data = null;
    if (i < str.length) {
        try {
            data = JSON.parse(str.substring(i));
        } catch (e) {
            data = str.substring(i);
        }
    }

    return { type, nsp, id, data };
}

// Handle push from PHP
function handlePush(data) {
    const { event, userId, characterId, payload } = data;

    if (!event) {
        return { error: 'Missing event name' };
    }

    let sent = 0;

    if (userId) {
        // Send to specific user
        const vid = userMap[userId];
        if (vid && clients[vid]) {
            sendEvent(clients[vid].ws, event, payload || {});
            sent++;
        }
    } else if (characterId) {
        // Find user by character (need to check all clients)
        // For now, broadcast if no specific target
        for (const [vid, client] of Object.entries(clients)) {
            sendEvent(client.ws, event, payload || {});
            sent++;
        }
    } else {
        // Broadcast to all
        for (const [vid, client] of Object.entries(clients)) {
            sendEvent(client.ws, event, payload || {});
            sent++;
        }
    }

    log('Push: event=' + event + ', sent=' + sent);
    return { success: true, sent };
}

// ============================================================
// Ruffle Socket Proxy (port 9998)
// Tunnels flash.net.Socket TCP bytes through WebSocket
// ============================================================
const proxyServer = http.createServer((req, res) => {
    res.writeHead(404);
    res.end();
});

const proxyWss = new WebSocketServer({ server: proxyServer });

proxyWss.on('connection', (ws) => {
    log('Proxy: Ruffle client connected');

    // Create TCP connection to our Socket.IO server
    const tcp = new net.Socket();
    tcp.connect(PORT, '127.0.0.1', () => {
        log('Proxy: TCP connected to localhost:' + PORT);
    });

    // Keep proxy WebSocket alive with periodic pings
    const proxyPing = setInterval(() => {
        if (ws.readyState === WebSocket.OPEN) {
            ws.ping();
        }
    }, 15000);

    // Forward WebSocket data → TCP
    ws.on('message', (data) => {
        if (tcp.writable) {
            tcp.write(data);
        }
    });

    // Forward TCP data → WebSocket
    tcp.on('data', (data) => {
        if (ws.readyState === WebSocket.OPEN) {
            ws.send(data);
        }
    });

    tcp.on('close', () => {
        log('Proxy: TCP closed');
        clearInterval(proxyPing);
        ws.close();
    });

    tcp.on('error', (err) => {
        log('Proxy: TCP error: ' + err.message);
        clearInterval(proxyPing);
        ws.close();
    });

    ws.on('close', () => {
        log('Proxy: WebSocket closed');
        clearInterval(proxyPing);
        tcp.destroy();
    });

    ws.on('error', (err) => {
        log('Proxy: WebSocket error: ' + err.message);
        clearInterval(proxyPing);
        tcp.destroy();
    });
});

// ============================================================
// Cleanup stale sessions
// ============================================================
setInterval(() => {
    const now = Date.now();
    for (const [sid, session] of Object.entries(pendingSessions)) {
        if (now - session.created > 30000) {
            delete pendingSessions[sid];
        }
    }
}, 60000);

// ============================================================
// Start
// ============================================================
function log(msg) {
    const ts = new Date().toISOString().substring(11, 19);
    console.log('[' + ts + '] ' + msg);
}

server.listen(PORT, () => {
    log('Socket.IO server on port ' + PORT);
});

proxyServer.listen(PROXY_PORT, () => {
    log('Ruffle proxy on port ' + PROXY_PORT);
});
