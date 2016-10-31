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
namespace Bambora\Online\Model\Api\Epay;

use \Magento\Framework\DataObject;

class Base extends DataObject
{
    /**
     * List of ePay endpoints
     *
     * @return array
     */
    private $endpoints = array(
        'remote' => 'https://ssl.ditonlinebetalingssystem.dk/remote',
        'integration' => 'https://ssl.ditonlinebetalingssystem.dk/integration',
        'assets' => 'https://d3r1pwhfz7unl9.cloudfront.net/bambora'
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
    )
    {
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
     * Initilize a Soap Client
     *
     * @param string $wsdlUrl
     * @return \Zend\Soap\Client
     */
    protected function _initSoapClient($wsdlUrl)
    {
        $soapClient = new \Zend\Soap\Client($wsdlUrl);
        $soapClient->setSoapVersion(2);

        return $soapClient;
    }



}