<?php

namespace PriceParser;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;

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
        
        (new Parser($client))->parse($productIds);
    }
}