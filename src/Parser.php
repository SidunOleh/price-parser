<?php

namespace PriceParser;

use DOMDocument;
use DOMXPath;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

defined('ABSPATH') or die;

class Parser
{
    private Client $client;

    private int $concurrency;

    private array $prices;

    public function __construct(Client $client)
    {
        $this->client = $client;
        $this->concurrency = 10;
    }

    public function parse(array $productIds): array
    {
        $this->prices = [];
        
        $pool = new Pool($this->client, $this->requests($productIds), [
            'concurrency' => $this->concurrency,
            'fulfilled' => [$this, 'fulfilled'],
            'rejected' => [$this, 'rejected'],
        ]);

        ($pool->promise())->wait();

        return $this->prices;
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

        $this->prices[$productId] = $priceTableHtml;
    }

    public function rejected(Exception $e, int $productId)
    {

    }
}