<?php

/**
 * Plugin Name: Price parser
 * Description: Parse products prices from www.precisionzone.net
 * Author: Sidun Oleh
 */

use PriceParser\Task;

defined('ABSPATH') or die;

/**
 * Plugin root
 */
const PRICE_PARSER_ROOT = __DIR__;

/**
 * Composer autoloader
 */
require_once PRICE_PARSER_ROOT . '/vendor/autoload.php';

/**
 * Schedule parse price event
 */
function schedule_parse_price_event() {
    if (! wp_next_scheduled('parse_price_event')) {
        wp_schedule_event(
            time(), 
            'daily', 
            'parse_price_event'
        );
    }
}

register_activation_hook(__FILE__, 'schedule_parse_price_event');

/**
 * Parse price
 */
add_action('parse_price_event', new Task);