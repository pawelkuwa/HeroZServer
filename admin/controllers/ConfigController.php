<?php
namespace Admin;

use Srv\DB;
use Srv\Config;

class ConfigController
{
    public function index(): array
    {
        return $this->edit();
    }

    public function edit(): array
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_cache'])) {
            if (csrf_verify()) {
                $cleared = 0;
                $cacheDataDir = SERVER_DIR . '/cache/data';
                if (is_dir($cacheDataDir)) {
                    $files = glob($cacheDataDir . '/*.tmp');
                    foreach ($files as $file) {
                        if (is_file($file) && unlink($file)) {
                            $cleared++;
                        }
                    }
                }
                // Reset cache.json
                $cacheJson = SERVER_DIR . '/cache/cache.json';
                if (is_file($cacheJson)) {
                    file_put_contents($cacheJson, '{}');
                    $cleared++;
                }
                $_SESSION['flash'] = ['type' => 'success', 'message' => "Cache cleared. {$cleared} file(s) removed/reset."];
                header('Location: index.php?page=config');
                exit;
            }
        }

        // Gather server info
        $info = [
            'php_version'    => PHP_VERSION,
            'php_sapi'       => php_sapi_name(),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'N/A',
            'mysql_version'  => 'N/A',
            'db_name'        => Config::get('database.database', 'N/A'),
            'db_host'        => Config::get('database.hostname', 'N/A'),
            'db_prefix'      => Config::get('database.prefix', ''),
            'os'             => PHP_OS,
            'memory_limit'   => ini_get('memory_limit'),
            'max_execution'  => ini_get('max_execution_time'),
            'upload_max'     => ini_get('upload_max_filesize'),
        ];

        // Get MySQL version
        try {
            $result = DB::sql("SELECT VERSION() as v")->fetch(\PDO::FETCH_ASSOC);
            $info['mysql_version'] = $result['v'] ?? 'N/A';
        } catch (\Exception $e) {}

        $cacheCount = 0;
        $cacheSize = 0;
        $cacheDataDir = SERVER_DIR . '/cache/data';
        if (is_dir($cacheDataDir)) {
            $files = glob($cacheDataDir . '/*.tmp');
            $cacheCount = count($files);
            foreach ($files as $file) {
                $cacheSize += filesize($file);
            }
        }

        $dbStats = [
            'users'      => DB::table('user')->select()->count(),
            'characters' => DB::table('`character`')->select()->count(),
            'guilds'     => DB::table('guild')->select()->count(),
            'items'      => DB::table('items')->select()->count(),
            'messages'   => DB::table('messages')->select()->count(),
        ];

        // Config values (read-only)
        $configValues = [];
        try {
            $serverConfig = include(SERVER_DIR . '/config.php');
            if (is_array($serverConfig)) {
                $configValues = $serverConfig;
            }
        } catch (\Exception $e) {}

        return [
            'viewFile'     => ADMIN_DIR . '/views/config/edit.php',
            'info'         => $info,
            'cacheCount'   => $cacheCount,
            'cacheSize'    => $cacheSize,
            'dbStats'      => $dbStats,
            'configValues' => $configValues,
        ];
    }

    public function runScript(): array
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verify()) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Invalid request.'];
            header('Location: index.php?page=config');
            exit;
        }

        $script = trim($_POST['script'] ?? '');
        $arg = trim($_POST['arg'] ?? '');

        $allowed = [
            'worldboss'  => ['file' => 'process-worldboss.php',  'args' => ['spawn', 'process', 'check']],
            'tournament' => ['file' => 'process-tournament.php', 'args' => ['start', 'end', 'status']],
            'email'      => ['file' => 'process-email-queue.php', 'args' => ['']],
        ];

        if (!isset($allowed[$script]) || !in_array($arg, $allowed[$script]['args'])) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Invalid script or argument.'];
            header('Location: index.php?page=config');
            exit;
        }

        $phpCli = 'php';
        $extDir = ini_get('extension_dir');
        if ($extDir) {
            $dir = dirname($extDir) . DIRECTORY_SEPARATOR;
            foreach (['php.exe', 'php'] as $bin) {
                if (is_file($dir . $bin)) { $phpCli = $dir . $bin; break; }
            }
        }

        $scriptPath = SERVER_DIR . '/' . $allowed[$script]['file'];
        $cmd = escapeshellarg($phpCli) . ' ' . escapeshellarg($scriptPath);
        if ($arg !== '') {
            $cmd .= ' ' . escapeshellarg($arg);
        }

        $output = shell_exec($cmd);
        $output = trim($output ?: 'No output.');

        $label = $allowed[$script]['file'] . ($arg ? " {$arg}" : '');
        $_SESSION['flash'] = ['type' => 'success', 'message' => "Executed: {$label}"];
        $_SESSION['script_output'] = $output;

        header('Location: index.php?page=config');
        exit;
    }
}
