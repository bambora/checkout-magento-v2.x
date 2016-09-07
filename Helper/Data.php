<?php
/**
 * Bambora Online
 *
 * @category    Online Payment Gatway
 * @package     Bambora_Online_Checkout
 * @author      Bambora
 * @copyright   Bambora (http://bambora.com)
 */
namespace Bambora\Online\Helper;


class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Bambora\Online\Logger\BamboraLogger
     */
    protected $_bamboraLogger;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Bambora\Online\Logger\BamboraLogger $bamboraLogger
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Bambora\Online\Logger\BamboraLogger $bamboraLogger
    )
    {
        parent::__construct($context);
        $this->_bamboraLogger = $bamboraLogger;
    }

    /**
     * @desc Gives back bambora_checkout configuration values as flag
     * @param $field
     * @param null|int $storeId
     * @return mixed
     */
    public function getBamboraCheckoutConfigDataFlag($field, $storeId = null)
    {
        return $this->getConfigData($field, 'bambora_checkout', $storeId, true);
    }

    /**
     * @desc Gives back bambora_checkout configuration values
     * @param $field
     * @param null|int $storeId
     * @return mixed
     */
    public function getBamboraCheckoutConfigData($field, $storeId = null)
    {
        return $this->getConfigData($field, 'bambora_checkout', $storeId);
    }

    /**
     * @desc Retrieve information from payment configuration
     * @param $field
     * @param $paymentMethodCode
     * @param $storeId
     * @param bool|false $flag
     * @return bool|mixed
     */
    public function getConfigData($field, $paymentMethodCode, $storeId, $flag = false)
    {
        $path = 'payment/' . $paymentMethodCode . '/' . $field;

        if(!$flag)
        {
            return $this->scopeConfig->getValue($path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);
        }
        else
        {
            return $this->scopeConfig->isSetFlag($path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);
        }
    }


    /**
     * @desc Retrieve a Checkout Api class
     * @param $apiName
     * @return object
     */
    public function getCheckoutApi($apiName)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $product = $objectManager->create('Bambora\Online\Model\Api\Checkout\\'.$apiName);

        return $product;
    }

    /**
     * @desc Retrieve a Checkout Api Model class
     * @param $modelName
     * @return Object
     */
    public function getCheckoutApiModel($modelName)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $model = $objectManager->create('Bambora\Online\Model\Api\Checkout\Models\\'.$modelName);

        return $model;
    }

    /**
     * @desc Retrieve the shops local code
     * @return string
     */
    public function getShopLocalCode() {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $resolver = $objectManager->get('Magento\Framework\Locale\Resolver');

        return $resolver->getLocale();
    }

    /**
     * @desc Convert an amount to minorunits
     * @param $amount
     * @param $minorUnits
     * @param $defaultMinorUnits = 2
     * @return float
     */
    public function convertPriceToMinorUnits($amount, $minorUnits, $defaultMinorUnits = 2)
    {
        if($minorUnits == "" || $minorUnits == null)
            $minorUnits = $defaultMinorUnits;

        if($amount == "" || $amount == null)
            return 0;

        return round($amount,$minorUnits) * pow(10,$minorUnits);
    }

    /**
     * @desc Convert an amount from minorunits
     * @param $amount
     * @param $minorUnits
     * @param $defaultMinorUnits = 2
     * @return string
     */
    public function convertPriceFromMinorUnits($amount, $minorUnits, $defaultMinorUnits = 2)
    {
        if($minorUnits == "" || $minorUnits == null)
            $minorUnits = $defaultMinorUnits;

        if($amount == "" || $amount == null)
            return 0;

        return number_format($amount / pow(10,$minorUnits),$minorUnits);
    }

    /**
     * @desc Return minorunits based on Currency Code
     * @param $currencyCode
     * @return int
     */
    public function getCurrencyMinorunits($currencyCode)
    {
        switch($currencyCode)
        {
            case "TTD":
            case "KMF":
            case "ADP":
            case "TPE":
            case "BIF":
            case "DJF":
            case "MGF":
            case "XPF":
            case "GNF":
            case "BYR":
            case "PYG":
            case "JPY":
            case "CLP":
            case "XAF":
            case "TRL":
            case "VUV":
            case "CLF":
            case "KRW":
            case "XOF":
            case "RWF":
                return 0;

            case "IQD":
            case "TND":
            case "BHD":
            case "JOD":
            case "OMR":
            case "KWD":
            case "LYD":
                return 3;

            default:
                return 2;
        }

    }

    /**
     * @desc Return if Checkout Api Result is valid
     * @param $result
     * @return bool
     */
    public function validateCheckoutApiResult($result, $id)
    {
        if(!isset($result))
        {
            //Error
            $this->_bamboraLogger->addCheckoutError($id,"No answer from Bambora");

            return false;
        }
        else if(!$result["meta"]["result"])
        {
            // Error with description
            $this->_bamboraLogger->addCheckoutError($id,$result['meta']['message']['merchant']);

            return false;
        }

        return true;
    }

    /**
     * @desc Generate Api key
     * @param $storeId
     * @return string
     */
    public function generateApiKey($storeId)
    {
        $accesstoken = $this->getBamboraCheckoutConfigData('access_token', $storeId);
        $merchantNumber = $this->getBamboraCheckoutConfigData('merchant_number', $storeId);
        $secrettoken = $this->getBamboraCheckoutConfigData('secret_token', $storeId);

        $combined = $accesstoken . '@' . $merchantNumber .':'. $secrettoken;
        $encodedKey = base64_encode($combined);
        $apiKey = 'Basic '.$encodedKey;

        return $apiKey;
    }
}