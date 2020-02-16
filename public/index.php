<?php
declare(strict_types=1);

namespace Scraper;

use Phalcon\Di\FactoryDefault;
use Phalcon\Mvc\Micro;

error_reporting(E_ALL);

const BASE_PATH = __DIR__ . '/..';
const APP_PATH  = BASE_PATH . '/app';

try {
    /**
     * The FactoryDefault Dependency Injector automatically registers the services that
     * provide a full stack framework. These default services can be overridden with custom ones.
     */
    $di = new FactoryDefault();

    /**
     * Include Services
     */
    require_once APP_PATH . '/config/services.php';

    /**
     * Get config service for use in inline setup below
     */
    $config = $di->getConfig();

    /**
     * Include Autoloader
     */
    require_once APP_PATH . '/config/loader.php';

    /**
     * Starting the application
     * Assign service locator to the application
     */
    $app = new Micro($di);

    /**
     * Include Application
     */
    require_once APP_PATH . '/app.php';

    /**
     * Handle the request
     */
    $app->handle($_SERVER['REQUEST_URI']);
} catch (\Exception $e) {
    \http_response_code(500);

    echo '<html><head><title>Internal Error</title></head><body><p>An internal error has occurred. Please try again later.</p></body></html>';
}
