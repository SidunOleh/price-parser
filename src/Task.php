<?php

namespace PriceParser;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Wa72\SimpleLogger\FileLogger;

defined('ABSPATH') or die;

class Task
{
    public function __invoke()
    {
        set_time_limit(0);

        $productIds = get_posts([
            'post_type' => 'product',
            'numberposts' => -1,
            'fields' => 'ids',
        ]);

        $handlers = HandlerStack::create();
        $handlers->push(Middleware::retry(function($retries, $request, $response = null) {
            if ($response and $response->getStatusCode() == 200) {
                return false;
            } else {
                return $retries < 3;
            }
        }, function($retries) {
            return $retries * 1000;
        }));
        $client = new Client([
            'handler' => $handlers,
        ]);
        
        $logger = new FileLogger(PRICE_PARSER_ROOT . '/src/logs/error.log');
        
        $prices = (new Parser($client, $logger))
            ->parse($productIds);
        foreach ($prices as $productId => $pricesTable) {
            wp_update_post([
                'ID' => $productId,
                'post_excerpt' => $pricesTable,
            ]);
        }
    }
}