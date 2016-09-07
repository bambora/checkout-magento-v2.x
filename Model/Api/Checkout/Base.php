<?php
/**
 * Bambora Online
 *
 * @category    Online Payment Gatway
 * @package     Bambora_Online_Checkout
 * @author      Bambora
 * @copyright   Bambora (http://bambora.com)
 */
namespace Bambora\Online\Model\Api\Checkout;

use \Magento\Framework\DataObject;

abstract class Base extends DataObject
{
    /**
     * List of Checkout endpoints
     *
     * @return array
     */
    private $endpoints = array(
        "merchant" => "https://merchant-v1.api.epay.eu",
        "checkout" => "https://api.v1.checkout.bambora.com",
        "assets" => "https://v1.checkout.bambora.com/Assets"
    );

    /**
     * Return the address of the endpoint type
     *
     * @param string $type
     * @return string
     */
    protected function _getEndpoint($type)
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
           'Authorization: ' . $apiKey
       );

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST,$postOrGet);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($curl, CURLOPT_URL, $serviceUrl);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_FAILONERROR, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        $result = curl_exec($curl);

        return $result;
    }
}