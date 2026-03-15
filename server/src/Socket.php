<?php
namespace Srv;

class Socket
{
    const PUSH_URL = 'http://127.0.0.1:9999/push';
    const TIMEOUT = 2;

    // Push syncGame to a specific user (immediate refresh via custom SWF event)
    public static function syncGame($userId)
    {
        return static::push('syncGameImmediate', ['userId' => $userId]);
    }

    // Push syncGame to all connected clients (immediate refresh via custom SWF event)
    public static function syncGameAll()
    {
        return static::push('syncGameImmediate');
    }

    // Push syncGameAndGuild to a user
    public static function syncGameAndGuild($userId)
    {
        return static::push('syncGameAndGuild', ['userId' => $userId]);
    }

    // Push syncGuildLog to all guild members (optional chatMessage payload for instant display)
    public static function syncGuildLog($guildId, $excludeUserId = 0, $chatMessage = null)
    {
        $payload = $chatMessage ? ['chatMessage' => $chatMessage] : new \stdClass();
        $userIds = static::getGuildMemberUserIds($guildId);
        foreach ($userIds as $uid) {
            if ($uid == $excludeUserId) continue;
            static::push('syncGuildLog', ['userId' => $uid, 'payload' => $payload]);
        }
    }

    // Push syncWorldboss to all connected clients (real-time HP update)
    public static function syncWorldboss($eventId, $hpCurrent)
    {
        return static::push('syncWorldboss', [
            'payload' => [
                'worldboss_event_id' => (int)$eventId,
                'npc_hitpoints_current' => (int)$hpCurrent,
            ]
        ]);
    }

    // Broadcast slot machine win to all connected clients
    public static function syncSlotmachineChat($message)
    {
        return static::push('syncSlotmachineChat', ['payload' => ['slotmachineMessage' => json_encode($message)]]);
    }

    // Get user IDs of all guild members
    private static function getGuildMemberUserIds($guildId)
    {
        $stmt = DB::$connection->prepare("SELECT user_id FROM `character` WHERE guild_id = ?");
        $stmt->execute([$guildId]);
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    // Generic push
    public static function push($event, $options = [])
    {
        $data = ['event' => $event, 'payload' => $options['payload'] ?? new \stdClass()];

        if (isset($options['userId'])) {
            $data['userId'] = (int)$options['userId'];
        }
        if (isset($options['characterId'])) {
            $data['characterId'] = (int)$options['characterId'];
        }

        return static::send($data);
    }

    private static function send($data)
    {
        $json = json_encode($data);
        $ch = curl_init(self::PUSH_URL);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $json,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => self::TIMEOUT,
            CURLOPT_CONNECTTIMEOUT => self::TIMEOUT,
        ]);
        $result = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) return false;

        $response = json_decode($result, true);
        return $response['success'] ?? false;
    }
}
