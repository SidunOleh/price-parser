<?php

/**
 * Plugin Name: Price parser
 * Description: Parse products prices from www.precisionzone.net
 * Author: Sidun Oleh
 */

defined('ABSPATH') or die;

/**
 * Plugin root
 */
const PRICE_PARSER_ROOT = __DIR__;

/**
 * Composer autoloader
 */
require_once PRICE_PARSER_ROOT . '/vendor/autoload.php';