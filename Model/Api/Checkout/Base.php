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
        'merchant' => 'https://merchant-v1.api.epay.eu',
        'checkout' => 'https://api.v1.checkout.bambora.com',
        'transaction' => 'https://transaction-v1.api.epay.eu',
        'assets' => 'https://v1.checkout.bambora.com/Assets'
    );

    /**
     * @var \Bambora\Online\Helper\Data
     */
    protected $_bamboraHelper;

    /**
     * @param \Bambora\Online\Helper\Data $bamboraHelper
     * @param array $data
     */
    public function __construct(
        \Bambora\Online\Helper\Data $bamboraHelper,
         array $data = []
    )
    {
        parent::__construct($data);
        $this->_bamboraHelper = $bamboraHelper;
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
           'X-EPay-System: ' . $this->getModuleHeaderInfo()
       );

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST,$postOrGet);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($curl, CURLOPT_URL, $serviceUrl);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_FAILONERROR, false); //maby true
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        $result = curl_exec($curl);

        curl_close($curl);
        return $result;
    }
    /**
     * Returns the module name and version
     * @return string
     */
    private function getModuleHeaderInfo()
    {
        $bamboraVersion = $this->_bamboraHelper->getModuleVersion();
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $productMetadata = $objectManager->get('Magento\Framework\App\ProductMetadataInterface');
        $magentoVersion = $productMetadata->getVersion();
        $result = 'Magento/' . $magentoVersion. ' Module/'.$bamboraVersion;
        return $result;
    }
}