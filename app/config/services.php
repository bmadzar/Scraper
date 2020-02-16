<?php
declare(strict_types=1);

namespace Scraper;

use Phalcon\Di\FactoryDefault;
use Phalcon\Mvc\View\Simple as View;
use Phalcon\Url as UrlResolver;
use Scraper\Services\Scraper;

/**
 * @global FactoryDefault $di
 */

$di->setShared('config', function () {
    return include APP_PATH . "/config/config.php";
});

$di->setShared('view', function () {
    $config = $this->getConfig();

    $view = new View();
    $view->setViewsDir($config->application->viewsDir);
    return $view;
});

$di->setShared('url', function () {
    $config = $this->getConfig();

    $url = new UrlResolver();
    $url->setBaseUri($config->application->baseUri);
    return $url;
});

$di->setShared('scraper', function () {
    return new Scraper();
});
