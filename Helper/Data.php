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
     * @var \Magento\Framework\Encryption\EncryptorInterface
     */
    protected $_encryptor;

    /**
     * @var \Magento\Framework\Module\ModuleListInterface
     */
    protected $_moduleList;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Bambora\Online\Logger\BamboraLogger $bamboraLogger
     * @param \Magento\Framework\Encryption\EncryptorInterface $encryptor
     * @param \Magento\Framework\Module\ModuleListInterface $moduleList
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Bambora\Online\Logger\BamboraLogger $bamboraLogger,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Magento\Framework\Module\ModuleListInterface $moduleList
    )
    {
        parent::__construct($context);
        $this->_bamboraLogger = $bamboraLogger;
        $this->_encryptor = $encryptor;
        $this->_moduleList = $moduleList;
    }


    /**
     * Gives back bambora_checkout configuration values as flag
     *
     * @param $field
     * @param null|int $storeId
     * @return mixed
     */
    public function getBamboraCheckoutConfigDataFlag($field, $storeId = null)
    {
        return $this->getConfigData($field, 'bambora_checkout', $storeId, true);
    }

    /**
     * Gives back bambora_checkout configuration values
     *
     * @param $field
     * @param null|int $storeId
     * @return mixed
     */
    public function getBamboraCheckoutConfigData($field, $storeId = null)
    {
        return $this->getConfigData($field, 'bambora_checkout', $storeId);
    }

    /**
     * Retrieve information from payment configuration
     *
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
     * Retrieve a Checkout Api class
     *
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
     * Decrypt data
     *
     * @param mixed $data
     * @return string
     */
    public function decryptData($data)
    {
        return $this->_encryptor->decrypt(trim($data));
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
     * Retrieve the shops local code
     *
     * @return string
     */
    public function getShopLocalCode() {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $resolver = $objectManager->get('Magento\Framework\Locale\Resolver');

        return $resolver->getLocale();
    }

    /**
     * Convert an amount to minorunits
     *
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
     * Convert an amount from minorunits
     *
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
     * Return minorunits based on Currency Code
     *
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
     * Return if Checkout Api Result is valid
     *
     * @param $result
     * @return bool
     */
    public function validateCheckoutApiResult($result, $id,$isBackoffice, &$message)
    {
        if(!isset($result) || $result === false || !isset($result["meta"]))
        {
            //Error without description
            $message = "No answer from Bambora";
            $this->_bamboraLogger->addCheckoutError($id, $message);
            return false;
        }
        else if(!$result["meta"]["result"])
        {
            // Error with description
            $message = $isBackoffice ? $result['meta']['message']['merchant'] : $result['meta']['message']['enduser'];
            $this->_bamboraLogger->addCheckoutError($id,$result['meta']['message']['merchant']);
            return false;
        }
        return true;
    }

    /**
     * Generate Api key
     *
     * @param $storeId
     * @return string
     */
    public function generateApiKey($storeId)
    {
        $accesstoken = $this->getBamboraCheckoutConfigData('access_token', $storeId);
        $merchantNumber = $this->getBamboraCheckoutConfigData('merchant_number', $storeId);
        $secrettoken = $this->decryptData($this->getBamboraCheckoutConfigData('secret_token', $storeId));

        $combined = $accesstoken . '@' . $merchantNumber .':'. $secrettoken;
        $encodedKey = base64_encode($combined);
        $apiKey = 'Basic '.$encodedKey;

        return $apiKey;
    }

    /**
     * Returns the version of the module
     *
     * @return string
     */
    public function getModuleVersion()
    {
        return $this->_moduleList->getOne("Bambora_Online")['setup_version'];
    }


    
}