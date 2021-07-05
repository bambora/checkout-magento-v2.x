<?php
/**
 * Copyright (c) 2021. All rights reserved Bambora Online.
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
use Bambora\Online\Model\Api\CheckoutApiModels;

class Data extends Base
{
    /**
     * Create the data request
     *
     * @param string $source
     * @param string $actioncode
     * @param string $apiKey
     * @return \Bambora\Online\Model\Api\Checkout\Response\Models\ResponseCode
     */
    public function getResponseCodeDetails($source, $actioncode, $apiKey)
    {
        try {
            $serviceEndpoint = $this->_getEndpoint(ApiEndpoints::ENDPOINT_DATA);
            $serviceUrl = "{$serviceEndpoint}/responsecodes/{$source}/{$actioncode}";
            $jsonData = null;
            $dataResponseJson = $this->_callRestService($serviceUrl, $jsonData, Base::GET, $apiKey);
            $dataResponseJsonArray = json_decode($dataResponseJson, true);
            $dataResponse = $this->_bamboraHelper->getCheckoutApiModel(CheckoutApiModels::RESPONSE_MODEL_RESPONSE_CODE);
            $dataResponse->meta = $this->_mapMeta($dataResponseJsonArray);
            $dataResponse->merchantlabel = $dataResponseJsonArray['responsecode']['merchantlabel'];
            $dataResponse->actioncode = $dataResponseJsonArray['responsecode']['actioncode'];
            $dataResponse->source = $dataResponseJsonArray['responsecode']['source'];
            $dataResponse->enduserlabel = $dataResponseJsonArray['responsecode']['enduserlabel'];
            return $dataResponse;
        } catch (\Exception $ex) {
            $this->_bamboraLogger->addCommonError("-1", $ex->getMessage());
            return null;
        }
    }
}
