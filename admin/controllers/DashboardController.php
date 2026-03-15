<?php
namespace Admin;

class DashboardController
{
    public function index(): array
    {
        $now = time() + 7200;
        $tenMinAgo = $now - 600;

        $totalUsers = db_value("SELECT COUNT(*) FROM user");
        $totalCharacters = db_value("SELECT COUNT(*) FROM `character`");
        $totalGuilds = db_value("SELECT COUNT(*) FROM guild");
        $onlinePlayers = db_value("SELECT COUNT(*) FROM `character` WHERE ts_last_action >= $tenMinAgo");
        $bannedUsers = db_value("SELECT COUNT(*) FROM user WHERE ts_banned > 0");
        $totalGameCurrency = db_value("SELECT COALESCE(SUM(game_currency),0) FROM `character`");
        $totalPremiumCurrency = db_value("SELECT COALESCE(SUM(premium_currency),0) FROM user");

        $recentUsers = db_query("SELECT id, email, ts_creation, registration_ip, ts_banned, login_count FROM user ORDER BY ts_creation DESC LIMIT 10");
        $topCharacters = db_query("SELECT id, name, level, xp, honor, game_currency, guild_id, ts_last_action FROM `character` ORDER BY level DESC, xp DESC LIMIT 10");

        $uptimeResult = db_query("SHOW GLOBAL STATUS LIKE 'Uptime'");
        $uptimeSeconds = isset($uptimeResult[0]) ? (int)$uptimeResult[0]['Value'] : 0;
        $uptimeDays = floor($uptimeSeconds / 86400);
        $uptimeHours = floor(($uptimeSeconds % 86400) / 3600);
        $uptimeMinutes = floor(($uptimeSeconds % 3600) / 60);

        return [
            'totalUsers' => $totalUsers,
            'totalCharacters' => $totalCharacters,
            'totalGuilds' => $totalGuilds,
            'onlinePlayers' => $onlinePlayers,
            'bannedUsers' => $bannedUsers,
            'recentUsers' => $recentUsers,
            'topCharacters' => $topCharacters,
            'totalGameCurrency' => $totalGameCurrency,
            'totalPremiumCurrency' => $totalPremiumCurrency,
            'serverUptime' => "{$uptimeDays}d {$uptimeHours}h {$uptimeMinutes}m",
        ];
    }
}
