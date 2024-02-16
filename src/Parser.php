<?php

namespace PriceParser;

use DOMDocument;
use DOMXPath;
use Exception;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

class Parser
{
    public function __construct(
        private $client,
        private int $concurrency = 10
    )
    {

    }

    public function parse(array $productIds): void
    {
        $pool = new Pool($this->client, $this->requests($productIds), [
            'concurrency' => $this->concurrency,
            'fulfilled' => [$this, 'fulfilled'],
            'rejected' => [$this, 'rejected'],
        ]);

        ($pool->promise())->wait();
    }

    private function requests(array $productIds)
    {
        foreach ($productIds as $productId) {
            $sku = get_post_meta($productId, '_sku', true);
            $uri = "https://www.precisionzone.net/products/contact/{$sku}/";

            yield $productId => new Request('GET', $uri);
        }
    }

    public function fulfilled(Response $response, int $productId)
    {
        $html = $response->getBody()->getContents();
        $dom = new DOMDocument();
        @$dom->loadHTML($html);
        $xpath = new DOMXPath($dom);

        $priceTableNode = $xpath->query('.//table')[0];
        if (! $priceTableNode) {
            return;
        }
        $priceTableHtml = $dom->saveHTML($priceTableNode);

        wp_update_post([
            'ID' => $productId,
            'post_excerpt' => $priceTableHtml,
        ]);
    }

    public function rejected(Exception $e, int $productId)
    {

    }
}