<?php
namespace Bambora\Online\Model\Api\Checkout;

use Bambora\Online\Model\Api\CheckoutApiModels;
use Magento\Framework\DataObject;

abstract class Base extends DataObject
{
    protected const string GET = 'GET';
    protected const string POST = 'POST';

    /**
     * @var array
     */
    protected $endpoints = [
        'merchant' => 'https://merchant-v1.api-eu.bambora.com',
        'checkout' => 'https://api.v1.checkout.bambora.com',
        'data' => 'https://data-v1.api-eu.bambora.com',
        'transaction' => 'https://transaction-v1.api-eu.bambora.com',
        'checkoutCDN' => 'https://static.bambora.com',
        'globalAssets' => 'https://static.bambora.com/assets/bambora'
    ];

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
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => $apiKey,
            'X-EPay-System' => $this->_bamboraHelper->getModuleHeaderInfo()
        ];

        $this->_curl->setHeaders($headers);

        if ($method === Base::GET) {
            $this->_curl->get($serviceUrl);
        } elseif ($method === Base::POST) {
            //For overwriting build in method and allow json encoded data as post fields
            $this->_curl->setOption(CURLOPT_POSTFIELDS, $jsonData);
            $this->_curl->post($serviceUrl, []);
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
        $message = $this->_bamboraHelper->getCheckoutModel(
            CheckoutApiModels::RESPONSE_MODEL_MESSAGE
        );
        $message->enduser = $response['meta']['message']['enduser'];
        $message->merchant = $response['meta']['message']['merchant'];
        $meta = $this->_bamboraHelper->getCheckoutModel(
            CheckoutApiModels::RESPONSE_MODEL_META
        );
        $meta->message = $message;
        $meta->result = $response['meta']['result'];

        return $meta;
    }
}
