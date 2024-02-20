<?php

namespace PriceParser;

use DOMDocument;
use DOMXPath;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Psr\Log\LoggerInterface;

defined('ABSPATH') or die;

class Parser
{
    private Client $client;

    private LoggerInterface $logger;

    private int $concurrency;

    private array $prices;

    public function __construct(Client $client, LoggerInterface $logger)
    {
        $this->client = $client;
        $this->logger = $logger;
        $this->concurrency = 10;
    }

    public function parse(array $productIds): array
    {
        $this->prices = [];
        
        $pool = new Pool($this->client, $this->requests($productIds), [
            'concurrency' => $this->concurrency,
            'fulfilled' => function(Response $response, int $productId) {
                $this->fulfilled($response, $productId);
            },
            'rejected' => function(Exception $e, int $productId) {
                $this->rejected($e, $productId);
            },
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

    private function fulfilled(Response $response, int $productId)
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

    private function rejected(Exception $e, int $productId)
    {
        $this->logger->error($e->getMessage(), [
            'product_id' => $productId,
        ]);
    }
}