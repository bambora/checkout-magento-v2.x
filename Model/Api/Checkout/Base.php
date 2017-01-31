<?php
/**
 * 888                             888
 * 888                             888
 * 88888b.   8888b.  88888b.d88b.  88888b.   .d88b.  888d888  8888b.
 * 888 "88b     "88b 888 "888 "88b 888 "88b d88""88b 888P"       "88b
 * 888  888 .d888888 888  888  888 888  888 888  888 888     .d888888
 * 888 d88P 888  888 888  888  888 888 d88P Y88..88P 888     888  888
 * 88888P"  "Y888888 888  888  888 88888P"   "Y88P"  888     "Y888888
 *
 * @category    Online Payment Gatway
 * @package     Bambora_Online
 * @author      Bambora Online
 * @copyright   Bambora (http://bambora.com)
 */
namespace Bambora\Online\Model\Api\Checkout;

use \Magento\Framework\DataObject;
use \Bambora\Online\Model\Api\CheckoutApiModels;

abstract class Base extends DataObject
{
    /**
     * List of Checkout endpoints
     *
     * @return array
     */
    private $endpoints = array(
        'merchant' => 'https://merchant-v1.api.epay.eu',
        'checkout' => 'https://api.v1.checkout.bambora.com',
        'transaction' => 'https://transaction-v1.api.epay.eu',
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
     * ePay Api
     *
     * @param \Bambora\Online\Helper\Data $bamboraHelper
     * @param \Bambora\Online\Logger\BamboraLogger $bamboraLogger
     * @param array $data
     */
    public function __construct(
        \Bambora\Online\Helper\Data $bamboraHelper,
        \Bambora\Online\Logger\BamboraLogger $bamboraLogger,
         array $data = []
    ) {
        parent::__construct($data);
        $this->_bamboraHelper = $bamboraHelper;
        $this->_bamboraLogger = $bamboraLogger;
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
     * @param string $postOrGet
     * @param string $apiKey
     * @return mixed
     */
    protected function _callRestService($serviceUrl, $jsonData, $postOrGet, $apiKey)
    {
        $headers = array(
           'Content-Type: application/json',
           'Content-Length: ' . isset($jsonData) ? strlen($jsonData) : 0,
           'Accept: application/json',
           'Authorization: ' . $apiKey,
           'X-EPay-System: ' .$this->_bamboraHelper->getModuleHeaderInfo()
       );

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $postOrGet);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($curl, CURLOPT_URL, $serviceUrl);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_FAILONERROR, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        $result = curl_exec($curl);

        curl_close($curl);
        return $result;
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
