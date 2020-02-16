<?php
declare(strict_types=1);

namespace Scraper;

use Phalcon\Config;
use Phalcon\Loader;

/**
 * @global Config $config
 */

$loader = new Loader();

$loader->registerNamespaces([
    'Scraper\Models'   => $config->application->modelsDir,
    'Scraper\Services' => $config->application->servicesDir,
]);

$loader->register();

// Composer
if (\is_readable(__DIR__ . '/../../vendor/autoload.php')) {
    require_once __DIR__ . '/../../vendor/autoload.php';
}
