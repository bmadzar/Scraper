<?php


namespace Scraper\Models;

use DOMDocument;
use DOMElement;
use InvalidArgumentException;
use TypeError;

/**
 * Class ScrapeResult
 *
 * @package Scraper
 */
class ScrapeResult
{
    private string $url;

    private ?int $responseCode;

    private ?string $responseBody;

    private ?array $responseHeaders;

    private float $loadTime;

    private ?DOMDocument $parsedResponseBody = null;

    /**
     * ScrapeResult constructor.
     *
     * @param string        $url
     * @param int|null      $responseCode
     * @param string|null   $responseBody
     * @param string[]|null $responseHeaders
     * @param float         $loadTime
     */
    public function __construct(string $url, ?int $responseCode, ?string $responseBody, ?array $responseHeaders, float $loadTime)
    {
        if ($responseHeaders !== null) {
            foreach ($responseHeaders as $key => $value) {
                if (\is_scalar($value)) {
                    $responseHeaders[$key] = (string)$value;
                } elseif (\is_array($value)) {
                    $responseHeaders[$key] = implode('|', $value);
                } else {
                    throw new TypeError();
                }
            }
            unset($value);
        }

        if ($responseCode !== null && ($responseCode < 100 || $responseCode >= 600)) {
            throw new InvalidArgumentException('Invalid HTTP response code.');
        }

        $this->url             = $url;
        $this->responseCode    = $responseCode;
        $this->responseBody    = $responseBody;
        $this->responseHeaders = $responseHeaders;
        $this->loadTime        = $loadTime;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getResponseCode(): ?int
    {
        return $this->responseCode;
    }

    public function getResponseBody(): ?string
    {
        return $this->responseBody;
    }

    public function getAllResponseHeaders(): ?array
    {
        return $this->responseHeaders;
    }

    public function getResponseHeader(string $header): ?string
    {
        return $this->responseHeaders[$header] ?? null;
    }

    public function getLoadTime(): float
    {
        return $this->loadTime;
    }

    private function parseResponseBody(): void
    {
        if ($this->responseBody === null) {
            return;
        }

        $errors = \libxml_use_internal_errors(true);

        \libxml_clear_errors();

        $dd = new DOMDocument();

        $status = $dd->loadHTML(
            $this->responseBody,
            \LIBXML_COMPACT |
            \LIBXML_NOCDATA |
            \LIBXML_NOENT |
            \LIBXML_NONET |
            \LIBXML_PARSEHUGE
        );

        \libxml_use_internal_errors($errors);

        $this->parsedResponseBody = $status ? $dd : null;
    }

    private function getParsedResponseBody(): ?DOMDocument
    {
        if ($this->parsedResponseBody === null) {
            $this->parseResponseBody();
        }

        return $this->parsedResponseBody;
    }

    public function getLinks(): array
    {
        $dd = $this->getParsedResponseBody();

        if ($dd === null) {
            return [];
        } else {
            $links = [];

            $anchors = $dd->getElementsByTagName('a');

            /** @var DOMElement $anchor */
            foreach ($anchors as $anchor) {
                $links[] = \urldecode($anchor->getAttribute('href'));
            }
            unset($anchor, $anchors);

            return \array_unique($links);
        }
    }

    private function getBaseUrl(): string
    {
        $dd = $this->getParsedResponseBody();

        $baseUrl = $this->getUrl();

        if ($dd !== null) {
            $baseEls = $dd->getElementsByTagName('base');

            /** @var DOMElement $baseEl */
            foreach ($baseEls as $baseEl) {
                $href = $baseEl->getAttribute('href');

                if ($href) {
                    $baseUrl = $href;
                    break;
                }
            }
            unset($baseEl, $href);
        }

        return \rtrim($baseUrl, '/');
    }

    private function resolveUrls(array $links): array
    {
        $baseUrl = $this->getBaseUrl();

        foreach ($links as $key => $link) {
            $urlParts = \parse_url($link);

            if (empty($urlParts['host']) && !empty($urlParts['path'])) {
                $links[$key] = $baseUrl . '/' . \ltrim($link, '/');
            } elseif (\substr($link, 0, 1) === '#') {
                $links[$key] = $baseUrl . $link;
            }
        }
        unset($key, $link);

        return \array_unique($links);
    }

    public function getResolvedLinks(): array
    {
        return $this->resolveUrls($this->getLinks());
    }

    public function getImages(): array
    {
        $dd = $this->getParsedResponseBody();

        if ($dd === null) {
            return [];
        } else {
            $images = [];

            $imgs = $dd->getElementsByTagName('img');

            /** @var DOMElement $img */
            foreach ($imgs as $img) {
                $images[] = \urldecode($img->getAttribute('src'));
            }
            unset($img);

            return \array_unique($images);
        }
    }

    public function getResolvedImages(): array
    {
        return $this->resolveUrls($this->getImages());
    }

    public function getTitle(): ?string
    {
        $dd = $this->getParsedResponseBody();

        if ($dd === null) {
            return null;
        } else {
            $titles = $dd->getElementsByTagName('title');

            /** @var DOMElement $title */
            foreach ($titles as $title) {
                return $title->textContent;
            }
            unset($title);

            return null;
        }
    }

    public function getTextContent(): ?string
    {
        $dd = $this->getParsedResponseBody();

        if ($dd === null) {
            return null;
        } else {
            $bodies = $dd->getElementsByTagName('body');

            if ($bodies->count() === 0) {
                return null;
            }

            $content = '';

            /** @var DOMElement $title */
            foreach ($bodies as $body) {
                $content .= $body->textContent;
            }
            unset($body);

            return $content;
        }
    }
}