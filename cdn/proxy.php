<?php
/**
 * CDN Proxy - Bypasses CORS restrictions for Akamai CDN assets
 * Proxies requests from localhost to hz-static.akamaized.net
 */

$CDN_BASE = 'http://hz-static.akamaized.net/';

$asset = isset($_GET['a']) ? $_GET['a'] : '';

if (empty($asset)) {
    http_response_code(400);
    echo 'Missing asset path';
    exit;
}

// Sanitize: prevent directory traversal
$asset = str_replace(['..', "\0"], '', $asset);

$url = $CDN_BASE . $asset;

$params = $_GET;
unset($params['a']);
if (!empty($params)) {
    $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($params);
}

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_MAXREDIRS      => 5,
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_HEADER         => true,
    CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);

if ($response === false) {
    http_response_code(502);
    echo 'CDN fetch failed: ' . curl_error($ch);
    curl_close($ch);
    exit;
}

curl_close($ch);

$headers = substr($response, 0, $headerSize);
$body = substr($response, $headerSize);

$contentType = null;
if (preg_match('/^Content-Type:\s*(.+)$/mi', $headers, $m)) {
    $contentType = trim($m[1]);
}

if (!$contentType) {
    $ext = strtolower(pathinfo(parse_url($asset, PHP_URL_PATH) ?: $asset, PATHINFO_EXTENSION));
    $mimeMap = [
        'swf'  => 'application/x-shockwave-flash',
        'xml'  => 'application/xml',
        'json' => 'application/json',
        'mp3'  => 'audio/mpeg',
        'ogg'  => 'audio/ogg',
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif'  => 'image/gif',
        'css'  => 'text/css',
        'js'   => 'application/javascript',
        'data' => 'application/octet-stream',
        'txt'  => 'text/plain',
        'wasm' => 'application/wasm',
    ];
    $contentType = isset($mimeMap[$ext]) ? $mimeMap[$ext] : 'application/octet-stream';
}

http_response_code($httpCode);
header('Content-Type: ' . $contentType);
header('Content-Length: ' . strlen($body));
header('Access-Control-Allow-Origin: *');
header('Cache-Control: public, max-age=86400');
echo $body;
