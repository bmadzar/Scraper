<?php
declare(strict_types=1);

namespace Scraper\Services;

use Amp\Artax\DefaultClient as ArtaxClient;
use Amp\Artax\HttpException;
use Amp\Artax\Response as ArtaxResponse;
use Scraper\Models\ScrapeResult;

class Scraper
{
    private const MAX_CONCURRENT_REQUESTS = 5;

    /**
     * Scrapes starting at $url.
     *
     * @param string   $url
     * @param int|null $maxDepth
     * @param int|null $maxResults
     * @return ScrapeResult[]
     * @throws \Throwable
     */
    public function scrape(string $url, ?int $maxDepth = 5, ?int $maxResults = 5): array
    {
        $client = new ArtaxClient();

        $urls    = []; // URL as key, depth as value
        $results = []; // URL as key, ScrapeResult as value

        // Start with the supplied $url, at depth 0
        $urls[$url] = 0;

        while ($urls && ($maxResults === null || \count($results) < $maxResults)) {

            if ($maxResults !== null) {
                // Fetch at most the remaining number of required pages to get to $maxResults, or the fixed maximum per iteration
                $limit = min($maxResults - \count($results), self::MAX_CONCURRENT_REQUESTS);
            } else {
                $limit = self::MAX_CONCURRENT_REQUESTS;
            }

            $scrapeUrls = \array_keys(\array_slice($urls, 0, $limit));

            $promises = [];
            foreach ($scrapeUrls as $url) {
                $promises[$url] = \Amp\call(function () use ($url, $client) {
                    $requestStart = \microtime(true);

                    try {
                        /** @var ArtaxResponse $response */
                        $response = yield $client->request($url);
                        $body     = yield $response->getBody();
                    } catch (HttpException $exception) {
                        $requestEnd = \microtime(true);

                        return new ScrapeResult(
                            $url,
                            null,
                            null,
                            null,
                            $requestEnd - $requestStart
                        );
                    }

                    $requestEnd = \microtime(true);

                    return new ScrapeResult(
                        $url,
                        $response->getStatus(),
                        $body,
                        $response->getHeaders(),
                        $requestEnd - $requestStart
                    );
                });
            }
            unset($url);

            $success = \Amp\Promise\wait(\Amp\Promise\all($promises));

            /** @var ScrapeResult $response */
            foreach ($success as $url => $response) {
                $results[$url] = $response;

                if ($maxDepth === null || $urls[$url] < $maxDepth) { // $urls[$url] will be the depth -- don't go deeper if we're already at $maxDepth
                    foreach ($results[$url]->getResolvedLinks() as $newUrl) {
                        // Strip off hash
                        $newUrl = (\strstr($newUrl, '#', true) ?: $newUrl);

                        // Prevent cycles, and do not go to other domains
                        if (!isset($results[$newUrl]) && \parse_url($newUrl, \PHP_URL_HOST) === \parse_url($url, \PHP_URL_HOST)) {
                            $urls[$newUrl] = $urls[$url] + 1;
                        }
                    }
                    unset($newUrl);
                }

                unset($urls[$url]);
            }
            unset($response, $scrapeResult);
        }

        return $results;
    }
}