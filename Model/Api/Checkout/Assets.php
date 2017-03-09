<?php
/**
 * Copyright (c) 2017. All rights reserved Bambora Online.
 *
 * This program is free software. You are allowed to use the software but NOT allowed to modify the software.
 * It is also not legal to do any changes to the software and distribute it in your own name / brand.
 *
 * All use of the payment modules happens at your own risk. We offer a free test account that you can use to test the module.
 *
 * @author    Bambora Online
 * @copyright Bambora Online (http://bambora.com)
 * @license   Bambora Online
 *
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
    public function getCheckoutPaymentWindowJSUrl()
    {
        $url = $this->_getEndpoint(ApiEndpoints::ENDPOINT_CHECKOUT_ASSETS).'/paymentwindow-v1.min.js';

        return $url;
    }

    /**
     * Get Checkout payment window js url
     *
     * @return string
     */
    public function getCheckoutIconUrl()
    {
        $url = $this->_getEndpoint(ApiEndpoints::ENDPOINT_GLOBAL_ASSETS).'/bambora_icon_64x64.png';

        return $url;
    }
}
