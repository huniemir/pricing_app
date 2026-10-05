<?php

/**
 * This file has been auto-generated
 * by the Symfony Routing Component.
 */

return [
    false, // $matchHost
    [ // $staticRoutes
        '/api/price/quote' => [[['_route' => 'app_price_quote', '_controller' => 'App\\Controller\\PriceController::quote'], null, ['POST' => 0], null, false, false, null]],
        '/api/price-list' => [[['_route' => 'app_pricelist_index', '_controller' => 'App\\Controller\\PriceListController::index'], null, ['GET' => 0], null, false, false, null]],
    ],
    [ // $regexpList
        0 => '{^(?'
                .'|/api/price\\-list/([^/]++)/([^/]++)(*:41)'
            .')/?$}sDu',
    ],
    [ // $dynamicRoutes
        41 => [
            [['_route' => 'app_pricelist_update', '_controller' => 'App\\Controller\\PriceListController::update'], ['format', 'paper'], ['PUT' => 0], null, false, true, null],
            [null, null, null, null, false, false, 0],
        ],
    ],
    null, // $checkCondition
];
