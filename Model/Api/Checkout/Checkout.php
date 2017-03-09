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
use Bambora\Online\Model\Api\CheckoutApiModels;

class Checkout extends Base
{
    /**
     * Create the checkout request
     *
     * @param \Bambora\Online\Model\Api\Checkout\Request\Checkout $setcheckoutrequest
     * @param string $apiKey
     * @return \Bambora\Online\Model\Api\Checkout\Response\Checkout
     */
    public function setCheckout($setcheckoutrequest, $apiKey)
    {
        try {
            $serviceUrl = $this->_getEndpoint(ApiEndpoints::ENDPOINT_CHECKOUT) . '/checkout';
            $jsonData = json_encode($setcheckoutrequest);
            $checkoutResponseJson = $this->_callRestService($serviceUrl, $jsonData, Base::POST, $apiKey);
            $checkoutResponseArray = json_decode($checkoutResponseJson, true);

            /** @var \Bambora\Online\Model\Api\Checkout\Response\Checkout */
            $checkoutResponse = $this->_bamboraHelper->getCheckoutApiModel(CheckoutApiModels::RESPONSE_CHECKOUT);
            $checkoutResponse->meta = $this->_mapMeta($checkoutResponseArray);
            $checkoutResponse->token = $checkoutResponseArray['token'];
            $checkoutResponse->url = $checkoutResponseArray['url'];

            return $checkoutResponse;
        } catch (\Exception $ex) {
            $this->_bamboraLogger->addCheckoutError("-1", $ex->getMessage());
            return null;
        }
    }
}
