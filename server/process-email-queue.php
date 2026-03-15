<?php
/**
 * CLI Email Queue Processor
 *
 * Usage:
 *   php process-email-queue.php [--batch=N]
 *
 * Schedule via cron (Linux) or Task Scheduler (Windows), or run manually.
 * Emails are pre-rendered with the user's locale at queue time.
 */
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die('CLI only.');
}

error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

define('IN_ENGINE', true);
define('BASE_DIR', dirname(__DIR__));
define('SERVER_DIR', __DIR__);

require_once SERVER_DIR . '/src/Utils/functions.php';
require_once SERVER_DIR . '/src/Utils/autoloader.php';
require_once BASE_DIR . '/vendor/autoload.php';

\Srv\Config::__init();
\Srv\DB::__init();

$batchSize = 0;
foreach ($argv as $arg) {
    if (preg_match('/^--batch=(\d+)$/', $arg, $m)) {
        $batchSize = (int)$m[1];
    }
}

$ts = date('Y-m-d H:i:s');
echo "[{$ts}] Processing email queue...\n";

$results = \Srv\Mail::processQueue($batchSize);

$ts = date('Y-m-d H:i:s');
echo "[{$ts}] Sent: {$results['sent']} | Failed: {$results['failed']} | Remaining: {$results['remaining']}\n";

if ($results['sent'] === 0 && $results['failed'] === 0) {
    echo "[{$ts}] Queue is empty.\n";
}
