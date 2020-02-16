<?php
declare(strict_types=1);

namespace Scraper;

use Phalcon\Config;

return new Config([
    'application' => [
        'modelsDir'   => APP_PATH . '/models/',
        'viewsDir'    => APP_PATH . '/views/',
        'servicesDir' => APP_PATH . '/services/',
        'baseUri'     => '/',
    ],
]);
