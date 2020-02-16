<?php
declare(strict_types=1);

namespace Scraper;

use Phalcon\Mvc\Micro;
use Scraper\Models\ScrapeResult;

/**
 * @global Micro $app
 */

$app->get('/', function () {
    $url       = $this['request']->getQuery('url', null, null, true);
    $pageCount = $this['request']->getQuery('pageCount', 'int', 5, true);
    $maxDepth  = $this['request']->getQuery('maxDepth', 'int', 5, true);

    echo $this['view']->render('index', [
        'defaultUrl'       => $url,
        'defaultPageCount' => $pageCount,
        'defaultDepth'     => $maxDepth,
    ]);
});

$app->get('/scrape', function () use ($app) {
    $url       = $this['request']->getQuery('url', null, null, true);
    $pageCount = $this['request']->getQuery('pageCount', 'int', 5, true);
    $maxDepth  = $this['request']->getQuery('maxDepth', 'int', 5, true);

    if ($pageCount < 0) {
        $pageCount = 5;
    } elseif ($pageCount === null) {
        $pageCount = 25;
    }

    if ($maxDepth < 0) {
        $maxDepth = 5;
    } elseif ($pageCount === null) {
        $maxDepth = 25;
    }

    if (!$url) {
        $app->response->setStatusCode(400, "Bad Request")->sendHeaders();
    } else {
        $images        = [];
        $linksInternal = [];
        $linksExternal = [];
        $loadTime      = 0.0;
        $wordCount     = 0;
        $titleLength   = 0;
        $pageList      = [];

        $host = \parse_url($url, \PHP_URL_HOST);

        $scrapeResults = $this['scraper']->scrape($url, $maxDepth, $pageCount);

        /** @var ScrapeResult $scrapeResult */
        foreach ($scrapeResults as $scrapeResult) {
            $images = \array_merge($images, $scrapeResult->getResolvedImages());

            foreach ($scrapeResult->getResolvedLinks() as $link) {
                if (\parse_url($link, \PHP_URL_HOST) === $host) {
                    $linksInternal[] = $link;
                } else {
                    $linksExternal[] = $link;
                }
            }
            unset($link);

            $loadTime    += $scrapeResult->getLoadTime();
            $wordCount   += \str_word_count($scrapeResult->getTextContent() ?? '');
            $titleLength += \strlen($scrapeResult->getTitle() ?? '');

            $pageList[] = ['url' => $scrapeResult->getUrl(), 'responseCode' => $scrapeResult->getResponseCode()];
        }
        unset($scrapeResult);

        $count         = \count($scrapeResults);
        $images        = \array_unique($images);
        $linksInternal = \array_unique($linksInternal);
        $linksExternal = \array_unique($linksExternal);

        echo $this['view']->render('results', [
            'count'         => $count,
            'images'        => \count($images),
            'linksInternal' => \count($linksInternal),
            'linksExternal' => \count($linksExternal),
            'loadTime'      => $loadTime / $count,
            'wordCount'     => $wordCount / $count,
            'titleLength'   => $titleLength / $count,
            'pageList'      => $pageList,
        ]);
    }
});

$app->notFound(function () use ($app) {
    $app->response->setStatusCode(404, "Not Found")->sendHeaders();
});
