<?php
namespace Admin;

class EventsController
{
    private $gameSettingsPath;
    private $cacheJsonPath;
    private $cacheDataPath;

    public function __construct()
    {
        $this->gameSettingsPath = dirname(__DIR__, 2) . '/server/class/GameSettings.php';
        $this->cacheJsonPath = dirname(__DIR__, 2) . '/server/cache/cache.json';
        $this->cacheDataPath = dirname(__DIR__, 2) . '/server/cache/data';
    }

    public function index(): array
    {
        $events = $this->parseEvents();

        $now = date('Y-m-d H:i:s');
        $activeCount = 0;
        foreach ($events as &$evt) {
            $evt['is_active'] = ($evt['start_date'] <= $now && $evt['end_date'] >= $now);
            if ($evt['is_active']) $activeCount++;
        }
        unset($evt);

        return [
            'viewFile' => ADMIN_DIR . '/views/events/index.php',
            'events' => $events,
            'activeCount' => $activeCount,
            'totalCount' => count($events),
        ];
    }

    public function activate(): array
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verify()) {
            header('Location: index.php?page=events');
            exit;
        }

        $identifier = trim($_POST['identifier'] ?? '');
        $startDate = trim($_POST['start_date'] ?? '');
        $endDate = trim($_POST['end_date'] ?? '');

        if (empty($identifier) || empty($startDate) || empty($endDate)) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'All fields are required.'];
            header('Location: index.php?page=events');
            exit;
        }

        $startDate .= ' 00:00:00';
        $endDate .= ' 23:59:59';

        $content = file_get_contents($this->gameSettingsPath);
        if ($content === false) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Could not read GameSettings.php.'];
            header('Location: index.php?page=events');
            exit;
        }

        // Deactivate any currently active events first
        $events = $this->parseEvents();
        $now = date('Y-m-d H:i:s');
        foreach ($events as $evt) {
            if ($evt['identifier'] !== $identifier && $evt['start_date'] <= $now && $evt['end_date'] >= $now) {
                $content = $this->replaceDates($content, $evt['identifier'], '2012-01-01 00:00:00', '2012-01-02 00:00:00');
            }
        }

        // Activate the selected event
        $content = $this->replaceDates($content, $identifier, $startDate, $endDate);

        if (file_put_contents($this->gameSettingsPath, $content) === false) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Could not write GameSettings.php.'];
            header('Location: index.php?page=events');
            exit;
        }

        $this->clearCache();


        $label = $this->formatName($identifier);
        $_SESSION['flash'] = ['type' => 'success', 'message' => "Event \"{$label}\" activated ({$startDate} to {$endDate})."];
        header('Location: index.php?page=events');
        exit;
    }

    public function deactivate(): array
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verify()) {
            header('Location: index.php?page=events');
            exit;
        }

        $identifier = trim($_POST['identifier'] ?? '');
        if (empty($identifier)) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Event identifier is required.'];
            header('Location: index.php?page=events');
            exit;
        }

        $content = file_get_contents($this->gameSettingsPath);
        if ($content === false) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Could not read GameSettings.php.'];
            header('Location: index.php?page=events');
            exit;
        }

        $content = $this->replaceDates($content, $identifier, '2012-01-01 00:00:00', '2012-01-02 00:00:00');

        if (file_put_contents($this->gameSettingsPath, $content) === false) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Could not write GameSettings.php.'];
            header('Location: index.php?page=events');
            exit;
        }

        // Expire active event_quests records in DB so SWF stops showing the event
        $this->expireEventRecords($identifier);

        $this->clearCache();


        $label = $this->formatName($identifier);
        $_SESSION['flash'] = ['type' => 'success', 'message' => "Event \"{$label}\" deactivated."];
        header('Location: index.php?page=events');
        exit;
    }

    private function parseEvents(): array
    {
        $content = file_get_contents($this->gameSettingsPath);
        if ($content === false) return [];

        // Find the event_quests block
        $pos = strpos($content, '"event_quests"=>[');
        if ($pos === false) return [];

        $events = [];

        // Top-level event identifiers only (not objectives or sub-blocks)
        $eventIds = [
            'summer_event', 'halloween', 'xmas_event_1', 'xmas_event_2',
            'winterolympics_event', 'carnival_event', 'easter', 'ghostbusters_event',
            'worldcup_event_1', 'worldcup_event_2', 'birthday_event', 'octoberfest',
            'olympia_2016_rio',
        ];

        foreach ($eventIds as $identifier) {
            $needle = '"' . $identifier . '"=>[';
            $evtPos = strpos($content, $needle, $pos);
            if ($evtPos === false) continue;

            // Find the closing bracket of this event block
            $blockOpen = $evtPos + strlen($needle) - 1;
            $blockClose = $this->findClosingBracket($content, $blockOpen);
            if ($blockClose === false) continue;

            $block = substr($content, $evtPos, $blockClose - $evtPos);

            if (!preg_match('/"start_date"=>"([^"]*)"/', $block, $sd)) continue;
            if (!preg_match('/"end_date"=>"([^"]*)"/', $block, $ed)) continue;

            // Count objectives
            $objCount = 0;
            $objPos = strpos($block, '"objectives"=>[');
            if ($objPos !== false) {
                $objClose = $this->findClosingBracket($block, $objPos + strlen('"objectives"=>[') - 1);
                if ($objClose !== false) {
                    $objBlock = substr($block, $objPos, $objClose - $objPos);
                    $objCount = substr_count($objBlock, '"index"=>');
                }
            }

            $events[] = [
                'identifier' => $identifier,
                'name' => $this->formatName($identifier),
                'start_date' => $sd[1],
                'end_date' => $ed[1],
                'objectives' => $objCount,
            ];
        }

        return $events;
    }

    private function findClosingBracket(string $content, int $openPos): int|false
    {
        $depth = 0;
        $len = strlen($content);
        for ($i = $openPos; $i < $len; $i++) {
            if ($content[$i] === '[') $depth++;
            if ($content[$i] === ']') {
                $depth--;
                if ($depth === 0) return $i;
            }
        }
        return false;
    }

    private function replaceDates(string $content, string $identifier, string $startDate, string $endDate): string
    {
        // Search within event_quests block only (identifiers may exist elsewhere in the file)
        $eqPos = strpos($content, '"event_quests"=>[');
        $needle = '"' . $identifier . '"=>[';
        $evtPos = strpos($content, $needle, $eqPos ?: 0);
        if ($evtPos === false) return $content;

        // Find the full event block using bracket matching
        $blockOpen = $evtPos + strlen($needle) - 1;
        $blockClose = $this->findClosingBracket($content, $blockOpen);
        if ($blockClose === false) return $content;

        $block = substr($content, $evtPos, $blockClose - $evtPos + 1);

        // Replace only the first start_date and end_date within this event block
        $block = preg_replace('/"start_date"=>"[^"]*"/', '"start_date"=>"' . $startDate . '"', $block, 1);
        $block = preg_replace('/"end_date"=>"[^"]*"/', '"end_date"=>"' . $endDate . '"', $block, 1);

        return substr($content, 0, $evtPos) . $block . substr($content, $blockClose + 1);
    }

    private function expireEventRecords(string $identifier): void
    {
        // Set status=3 (expired) and end_date to now for all active records of this event
        $now = date('Y-m-d H:i:s');
        db_query("UPDATE event_quests SET status = 3, end_date = ? WHERE identifier = ? AND status = 1", [$now, $identifier]);
        // Clear character event_quest_id so SWF re-checks on next login
        db_query("UPDATE `character` c INNER JOIN event_quests eq ON c.event_quest_id = eq.id SET c.event_quest_id = 0 WHERE eq.identifier = ? AND eq.status = 3", [$identifier]);
    }

    private function clearCache(): void
    {
        if (file_exists($this->cacheJsonPath)) {
            @unlink($this->cacheJsonPath);
        }
        $files = glob($this->cacheDataPath . '/*.tmp');
        if ($files) {
            foreach ($files as $f) @unlink($f);
        }
    }

    private function formatName(string $identifier): string
    {
        return ucwords(str_replace('_', ' ', $identifier));
    }
}
