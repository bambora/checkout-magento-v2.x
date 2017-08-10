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

use \Magento\Framework\DataObject;
use \Bambora\Online\Model\Api\CheckoutApiModels;

abstract class Base extends DataObject
{
    const GET = 'GET';
    const POST = 'POST';
    /**
     * List of Checkout endpoints
     *
     * @return array
     */
    protected $endpoints = array(
        'merchant' => 'https://merchant-v1.api-eu.bambora.com',
        'checkout' => 'https://api.v1.checkout.bambora.com',
        'transaction' => 'https://transaction-v1.api-eu.bambora.com',
        'checkoutAssets' => 'https://v1.checkout.bambora.com/Assets',
        'globalAssets' => 'https://d3r1pwhfz7unl9.cloudfront.net/bambora'
    );

    /**
     * @var \Bambora\Online\Helper\Data
     */
    protected $_bamboraHelper;

    /**
     * @var \Bambora\Online\Logger\BamboraLogger
     */
    protected $_bamboraLogger;

    /**
     * @var \Magento\Framework\HTTP\Client\Curl
     */
    protected $_curl;

    /**
     * Bambora Api
     *
     * @param \Bambora\Online\Helper\Data $bamboraHelper
     * @param \Bambora\Online\Logger\BamboraLogger $bamboraLogger
     * @param \Magento\Framework\HTTP\Client\Curl $curl
     * @param array $data
     */
    public function __construct(
        \Bambora\Online\Helper\Data $bamboraHelper,
        \Bambora\Online\Logger\BamboraLogger $bamboraLogger,
        \Magento\Framework\HTTP\Client\Curl $curl,
         array $data = []
    ) {
        parent::__construct($data);
        $this->_bamboraHelper = $bamboraHelper;
        $this->_bamboraLogger = $bamboraLogger;
        $this->_curl = $curl;
    }

    /**
     * Return the address of the endpoint type
     *
     * @param string $type
     * @return string
     */
    public function _getEndpoint($type)
    {
        return $this->endpoints[$type];
    }

    /**
     * Sends the curl request to the given serviceurl
     *
     * @param string $serviceUrl
     * @param mixed $jsonData
     * @param string $method
     * @param string $apiKey
     * @return mixed
     */
    protected function _callRestService($serviceUrl, $jsonData, $method, $apiKey)
    {
        $contentLength = isset($jsonData) ? strlen($jsonData) : 0;
        $headers = array(
           'Content-Type' => 'application/json',
           'Content-Length' => $contentLength,
           'Accept' => 'application/json',
           'Authorization' => $apiKey,
           'X-EPay-System' => $this->_bamboraHelper->getModuleHeaderInfo()
       );

        $this->_curl->setHeaders($headers);
        $this->_curl->setOption(CURLOPT_HEADER, false);
        $this->_curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
        $this->_curl->setOption(CURLOPT_FAILONERROR, false);

        if ($method === Base::GET) {
            $this->_curl->get($serviceUrl);
        } elseif ($method === Base::POST) {
            //For overwriting build in method and allow json encoded data as post fields
            $this->_curl->setOption(CURLOPT_POSTFIELDS, $jsonData);
            $this->_curl->post($serviceUrl, array());
        } else {
            return null;
        }

        return $this->_curl->getBody();
    }

    /**
     * Map bambora checkout response meta json to meta object
     *
     * @param mixed $response
     * @return Response\Models\Meta|null
     */
    protected function _mapMeta($response)
    {
        if (!isset($response)) {
            return null;
        }
        /** @var \Bambora\Online\Model\Api\Checkout\Response\Models\Message */
        $message = $this->_bamboraHelper->getCheckoutApiModel(CheckoutApiModels::RESPONSE_MODEL_MESSAGE);
        $message->enduser = $response['meta']['message']['enduser'];
        $message->merchant = $response['meta']['message']['merchant'];

        /** @var \Bambora\Online\Model\Api\Checkout\Response\Models\Meta */
        $meta = $this->_bamboraHelper->getCheckoutApiModel(CheckoutApiModels::RESPONSE_MODEL_META);
        $meta->message = $message;
        $meta->result = $response['meta']['result'];

        return $meta;
    }
}
