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
namespace Bambora\Online\Model\Api\Epay;

use \Magento\Framework\DataObject;

class Base extends DataObject
{
    /**
     * List of ePay endpoints
     *
     * @return array
     */
    protected $endpoints = array(
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
