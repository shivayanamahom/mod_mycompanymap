<?php
declare(strict_types=1);
namespace Joomla\Registry {
    class Registry {
        public function __construct(private array $values) {}
        public function get(string $key, mixed $default = null): mixed { return $this->values[$key] ?? $default; }
    }
}
namespace {
    define('_JEXEC', 1);
    require dirname(__DIR__) . '/src/Helper/MycompanymapHelper.php';
    $helper = new \Joomla\Module\Mycompanymap\Site\Helper\MycompanymapHelper();
    $params = new \Joomla\Registry\Registry(['company_items' => [
        ['company_name' => 'A', 'company_city' => 'X', 'company_coords' => '55.751574, 37.573856'],
        ['company_name' => 'B', 'company_city' => 'X', 'company_coords' => '59.934280,30.335099'],
        ['company_name' => 'Invalid', 'company_city' => 'Y', 'company_coords' => 'not-a-number,56'],
        ['company_name' => 'Out of range', 'company_coords' => '91,56'],
    ]]);
    $items = $helper->getCompanies($params);
    $checks = [$items[0]->coords === [37.573856, 55.751574], $items[1]->coords === [30.335099, 59.93428], $items[2]->coords === [], $items[3]->coords === [], $helper->getUniqueCities($items) === ['X', 'Y']];
    if (in_array(false, $checks, true)) { fwrite(STDERR, "Helper checks failed\n"); exit(1); }
    echo "Coordinates, validation and city filter passed\n";
}
