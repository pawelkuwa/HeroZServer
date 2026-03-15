<?php
namespace Admin;

use Srv\DB;

class ItemsController
{
    private int $perPage = 50;

    public static array $typeMap = [
        1 => 'Mask', 2 => 'Cape', 3 => 'Suit', 4 => 'Belt',
        5 => 'Boots', 6 => 'Weapon', 7 => 'Gadget', 8 => 'Missiles',
        9 => 'Sidekick', 10 => 'Surprise', 11 => 'Reskill',
    ];

    public static array $qualityMap = [
        1 => ['name' => 'Common',  'color' => 'secondary'],
        2 => ['name' => 'Rare',    'color' => 'primary'],
        3 => ['name' => 'Epic',    'color' => 'purple'],
    ];

    private function loadTemplates(): array
    {
        static $cache = null;
        if ($cache !== null) return $cache;

        $gs = \Cls\GameSettings::getConstant('item_templates', []);
        if (!is_array($gs)) return [];

        $templates = [];
        foreach ($gs as $identifier => $data) {
            $templates[] = [
                'identifier'         => $identifier,
                'type'               => intval($data['type'] ?? 0),
                'quality'            => intval($data['quality'] ?? 1),
                'required_level'     => intval($data['required_level'] ?? 1),
                'display_options'    => intval($data['display_options'] ?? 0),
                'sew_price'          => intval($data['sew_price'] ?? 0),
                'item_set_identifier'=> $data['item_set_identifier'] ?? null,
                'item_pattern'       => $data['item_pattern'] ?? null,
                'is_license'         => !empty($data['is_license']),
                'is_event'           => !empty($data['is_event']),
            ];
        }
        $cache = $templates;
        return $templates;
    }

    public function index(): array
    {
        $templates = $this->loadTemplates();
        $totalAll = count($templates);

        $search = trim($_GET['search'] ?? '');
        $filterType = intval($_GET['type'] ?? 0);
        $filterQuality = intval($_GET['quality'] ?? 0);
        $filterPattern = trim($_GET['pattern'] ?? '');

        if ($search !== '') {
            $searchLower = strtolower($search);
            $templates = array_filter($templates, fn($t) => str_contains(strtolower($t['identifier']), $searchLower));
        }
        if ($filterType) {
            $templates = array_filter($templates, fn($t) => $t['type'] === $filterType);
        }
        if ($filterQuality) {
            $templates = array_filter($templates, fn($t) => $t['quality'] === $filterQuality);
        }
        if ($filterPattern !== '') {
            $patternLower = strtolower($filterPattern);
            $templates = array_filter($templates, fn($t) => strtolower($t['item_pattern'] ?? '') === $patternLower);
        }

        $templates = array_values($templates);
        $total = count($templates);

        $page = max(1, intval($_GET['p'] ?? 1));
        $totalPages = max(1, ceil($total / $this->perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $this->perPage;
        $slice = array_slice($templates, $offset, $this->perPage);

        $allTemplates = $this->loadTemplates();
        $patterns = [];
        foreach ($allTemplates as $t) {
            if (!empty($t['item_pattern'])) $patterns[$t['item_pattern']] = true;
        }
        ksort($patterns);

        return [
            'viewFile'      => ADMIN_DIR . '/views/items/list.php',
            'templates'     => $slice,
            'total'         => $total,
            'totalAll'      => $totalAll,
            'page'          => $page,
            'totalPages'    => $totalPages,
            'search'        => $search,
            'filterType'    => $filterType,
            'filterQuality' => $filterQuality,
            'filterPattern' => $filterPattern,
            'typeMap'       => self::$typeMap,
            'qualityMap'    => self::$qualityMap,
            'patterns'      => array_keys($patterns),
        ];
    }
}
