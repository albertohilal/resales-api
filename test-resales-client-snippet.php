<?php
// Snippet temporal para verificar logging y Resales_Client
if (!defined('ABSPATH')) exit;

$c = Resales_Client::instance();

$locs = $c->get_locations(1);
$types = $c->get_property_types(1);

resales_log('INFO', 'test_locations_count', ['count' => count($locs)]);
resales_log('INFO', 'test_types_count', ['count' => count($types)]);
