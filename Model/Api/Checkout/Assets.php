<?php
/**
 * Copyright (c) 2019. All rights reserved Bambora Online.
 *
 * This program is free software. You are allowed to use the software but NOT allowed to modify the software.
 * It is also not legal to do any changes to the software and distribute it in your own name / brand.
 *
 * All use of the payment modules happens at your own risk. We offer a free test account that you can use to test the module.
 *
 * @author    Bambora Online
 * @copyright Bambora Online (https://bambora.com)
 * @license   Bambora Online
 */
namespace Bambora\Online\Model\Api\Checkout;

use Bambora\Online\Model\Api\Checkout\ApiEndpoints;

class Assets extends Base
{
    /**
     * Get Bambora Checkout payment window js url
     *
     * @return string
     */
    public function getCheckoutWebSdkUrl()
    {
        $serviceEndpoint = $this->_getEndpoint(ApiEndpoints::ENDPOINT_CHECKOUT_CDN);
        return "{$serviceEndpoint}/checkout-sdk-web/latest/checkout-sdk-web.min.js";
    }

    /**
     * Get Checkout payment window js url
     *
     * @return string
     */
    public function getCheckoutIconUrl()
    {
        $serviceEndpoint = $this->_getEndpoint(ApiEndpoints::ENDPOINT_GLOBAL_ASSETS);
        return "{$serviceEndpoint}/worldline_icon_64x64.png";
    }
}
