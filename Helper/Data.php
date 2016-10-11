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
namespace Bambora\Online\Helper;

use Bambora\Online\Model\Api\EpayApi;
use Bambora\Online\Model\Api\EpayApiModels;

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
     * Bambora Helper
     *
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
     * Gives back bambora_checkout configuration values
     *
     * @param $field
     * @param null|int $storeId
     * @return mixed
     */
    public function getBamboraEpayConfigData($field, $storeId = null)
    {
        return $this->getConfigData($field, 'bambora_epay', $storeId);
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
     * Gives back bambora_checkout configuration values
     *
     * @param $field
     * @param null|int $storeId
     * @return mixed
     */
    public function getBamboraAdvancedConfigData($field, $storeId = null)
    {
        return $this->getConfigData($field, 'bambora_advanced', $storeId);
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
     * @desc Retrieve a Checkout Api Model class
     * @param $modelName
     * @return Object
     */
    public function getCheckoutApiModel($modelName)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $model = $objectManager->create('Bambora\Online\Model\Api\Checkout\\'.$modelName);

        return $model;
    }

    /**
     * Retrieve a Checkout Api class
     *
     * @param $apiName
     * @return object
     */
    public function getEpayApi($apiName)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $product = $objectManager->create('Bambora\Online\Model\Api\Epay\\'.$apiName);

        return $product;
    }

    /**
     * @desc Retrieve a Checkout Api Model class
     * @param $modelName
     * @return Object
     */
    public function getEpayApiModel($modelName)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $model = $objectManager->create('Bambora\Online\Model\Api\Epay\\'.$modelName);

        return $model;
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
     * @return int
     */
    public function convertPriceToMinorUnits($amount, $minorUnits, $defaultMinorUnits = 2)
    {
        if($minorUnits == "" || $minorUnits == null)
        {
            $minorUnits = $defaultMinorUnits;
        }

        if($amount == "" || $amount == null)
        {
            return 0;
        }

        return $amount * pow(10,$minorUnits);;
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
        {
            $minorUnits = $defaultMinorUnits;
        }

        if($amount == "" || $amount == null)
        {
            return 0;
        }
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
     * Format currency
     *
     * @param float $amount
     * @return string
     */
    public function formatCurrency($amount)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $priceHelper = $objectManager->create('Magento\Framework\Pricing\Helper\Data');

        return $priceHelper->currency($amount, true, false);
    }

    /**
     * Generate Checkout Api key
     *
     * @param $storeId
     * @return string
     */
    public function generateCheckoutApiKey($storeId)
    {
        $accesstoken = $this->getBamboraCheckoutConfigData('accesstoken', $storeId);
        $merchantNumber = $this->getBamboraCheckoutConfigData('merchantnumber', $storeId);
        $secrettoken = $this->decryptData($this->getBamboraCheckoutConfigData('secrettoken', $storeId));

        $combined = $accesstoken . '@' . $merchantNumber .':'. $secrettoken;
        $encodedKey = base64_encode($combined);
        $apiKey = 'Basic '.$encodedKey;

        return $apiKey;
    }

    /**
     * Generate Epay Auth object
     *
     * @param int $storeId
     * @return \Bambora\Online\Model\Api\Epay\Request\Models\Auth
     */
    public function generateEpayAuth($storeId)
    {
        /** @var \Bambora\Online\Model\Api\Epay\Request\Models\Auth */
        $auth = $this->getEpayApiModel(EpayApiModels::REQUEST_MODEL_AUTH);
        $auth->merchantNumber = $this->getBamboraEpayConfigData('merchantnumber', $storeId);
        $auth->pwd = $this->decryptData($this->getBamboraEpayConfigData('remoteinterfacepassword', $storeId));

        return $auth;
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

    /**
     * Returns the module name and version
     *
     * @return string
     */
    public function getModuleHeaderInfo()
    {
        $bamboraVersion = $this->getModuleVersion();
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $productMetadata = $objectManager->get('Magento\Framework\App\ProductMetadataInterface');
        $magentoVersion = $productMetadata->getVersion();
        $result = 'Magento/' . $magentoVersion. ' Module/'.$bamboraVersion;
        return $result;
    }

    /**
     * Calculate Md5key hash
     *
     * @param \Magento\Sales\Model\Order $order
     * @param \Bambora\Online\Model\Api\Epay\Request\Payment $paymentRequest
     * @return string
     */
    public function calcEpayMd5Key($order, $paymentRequest)
    {
        $storeId = $order ? $order->getStoreId() : null;
		$md5stamp = md5(
                    $paymentRequest->encoding.
					$paymentRequest->cms.
					$paymentRequest->windowState.
                    $paymentRequest->merchantNumber.
                    $paymentRequest->windowId.
                    $paymentRequest->amount.
                    $paymentRequest->currency.
                    $paymentRequest->orderId.
                    $paymentRequest->acceptUrl.
                    $paymentRequest->cancelUrl.
                    $paymentRequest->callbackUrl.
                    $paymentRequest->instantCapture.
                    $paymentRequest->group.
                    $paymentRequest->language.
                    $paymentRequest->ownReceipt.
                    $paymentRequest->timeout.
                    $paymentRequest->invoice.
                    $this->getBamboraEpayConfigData('md5key',$storeId));

		return $md5stamp;
    }

    /**
     * Translate Payment status
     *
     * @param string $status
     * @return \Magento\Framework\Phrase
     */
    public function translatePaymentStatus($status)
    {
		if(strcmp($status, "PAYMENT_NEW") == 0)
		{
			return __("New");
		}
		elseif (strcmp($status, "PAYMENT_CAPTURED") == 0 || strcmp($status, "PAYMENT_EUROLINE_WAIT_CAPTURE") == 0 || strcmp($status, "PAYMENT_EUROLINE_WAIT_CREDIT") == 0)
		{
			return __("Captured");
		}
		elseif (strcmp($status, "PAYMENT_DELETED") == 0)
		{
			return __("Deleted");
		}
		else
		{
			return __("Unkown");
		}
    }

    /**
     * Return if Checkout Api Result is valid
     *
     * @param \Bambora\Online\Model\Api\Checkout\Response\Base $request
     * @param mixed $id
     * @param bool $isBackoffice
     * @param string &$message
     * @return bool
     */
    public function validateCheckoutApiResult($response, $id,$isBackoffice, &$message)
    {
        if(!isset($response) || $response === false || !isset($response->meta))
        {
            //Error without description
            $message = "No answer from Bambora";
            $this->_bamboraLogger->addCheckoutError($id, $message);
            return false;
        }
        else if(!$response->meta->result)
        {
            // Error with description
            $message = $isBackoffice ? $response->meta->message->merchant : $response->meta->message->enduser;
            $this->_bamboraLogger->addCheckoutError($id,$response->meta->message->merchant);
            return false;
        }
        return true;
    }

    /**
     * Return if Epay Api Result is valid
     *
     * @param \Bambora\Online\Model\Api\Epay\Response\Base $response
     * @param mixed $id
     * @param \Bambora\Online\Model\Api\Epay\Request\Models\Auth $auth
     * @param string &$message
     * @return bool
     */
    public function validateEpayApiResult($response, $id, $auth, $action, &$message)
    {
        if(!isset($response) || $response === false)
        {
            //Error without description
            $message = "No answer from ePay";
            $this->_bamboraLogger->addEpayError($id, $message);
            return false;
        }
        else if(!$response->result)
        {
            /** @var \Bambora\Online\Model\Api\Epay\Error */
            $errorProvicer = $this->getEpayApi(EpayApi::API_ERROR);

            if(isset($response->epayResponse) && $response->epayResponse != -1)
            {
                $message = $this->createEpayErrorText($response->epayResponse, $action, $errorProvicer, $auth);
                $this->_bamboraLogger->addEpayError($id,"Epay Error: {$message}");
            }
            if(isset($response->pbsResponse) && $response->pbsResponse != -1)
            {
                $message = "({$response->pbsResponse}): " . $errorProvicer->getPbsErrorText($response->pbsResponse,$this->calcLanguage($this->getShopLocalCode()), $auth);
                $this->_bamboraLogger->addEpayError($id,"PBS Error: {$message}");
            }
            return false;
        }
        return true;
    }

    /**
     * Create ePay error text
     *
     * @param mixed $errorId
     * @param mixed $action
     * @param mixed $errorProvicer
     * @param mixed $auth
     * @return string
     */
    public function createEpayErrorText($errorId, $action, $errorProvicer, $auth)
    {
        $message = "";

        if($action == 'capture')
        {
            $message = __("Transaction could not be captured by ePay")." ({$errorId}): ";
            if($errorId ==  -1002)
            {
                $message .= __("Forretningsnummeret findes ikke.");
            }
            elseif($errorId == -1003 || $errorId == -1006)
            {
                $message .= __("Der er ikke adgang til denne funktion (API / Remote Interface).");
            }
            else
            {
                $message .= $errorProvicer->getEpayErrorText($errorId, $this->calcLanguage($this->getShopLocalCode()), $auth);
            }
        }
        elseif($action == 'refund')
        {
            $message = __("Transaction could not be credited by ePay")." ({$errorId}): ";
            if($errorId == -1002)
            {
                $message .= __("The merchantnumber you are using does not exists or is disabled. Please log into your ePay account to verify your merchantnumber. This can be done from the menu: SETTINGS -> PAYMENT SYSTEM.");
            }
            elseif($errorId == -1003)
            {
                $message .= __("The IP address your system calls ePay from is UNKNOWN. Please log into your ePay account to verify enter the IP address your system calls ePay from. This can be done from the menu: API / WEBSERVICES -> ACCESS.");
            }
            elseif($errorId == -1006)
            {
                $message .= __("Your ePay account has not access to API / Remote Interface. This is only for ePay BUSINESS accounts. Please contact ePay to upgrade your ePay account.");
            }
            elseif($errorId == -1021)
            {
                $message .= __("An operation every 15 minutes can be performed on a transaction. Please wait 15 minutes and try again.");
            }
            else
            {
                $message .= $errorProvicer->getEpayErrorText($errorId, $this->calcLanguage($this->getShopLocalCode()), $auth);
            }
        }
        elseif($action == 'void')
        {
            $message = __("Transaction could not be deleted / void by ePay")." ({$errorId}): ";
            if($errorId == -1002)
            {
                $message .= __("The Merchant number does not exist.");
            }
            elseif($errorId == -1003 || $errorId == -1006)
            {
                $message .= __("There is no acces to this function (API / Remote Interface).");
            }
            else
            {
                $message .= $errorProvicer->getEpayErrorText($errorId, $this->calcLanguage($this->getShopLocalCode()), $auth);
            }
        }
        elseif($action == 'gettransaction')
        {
            $message = __("Could not get transaction from ePay")." ({$errorId}): ";
            $message .= $errorProvicer->getEpayErrorText($errorId, $this->calcLanguage($this->getShopLocalCode()), $auth);
        }

        return $message;
    }

    /**
     * Convert country code to a number
     *
     * @param mixed $lan
     * @return string
     */
    public function calcLanguage($lan = null)
	{
        if(!isset($lan))
        {
            $lan = $this->getShopLocalCode();
        }

		switch($lan)
		{
			case "da_DK":
				return '1';
			case "de_CH":
				return '7';
			case "de_DE":
				return '7';
			case "en_AU":
				return '2';
			case "en_GB":
				return '2';
			case "en_NZ":
				return '2';
			case "en_US":
				return '2';
			case "sv_SE":
				return '3';
			case "nn_NO":
				return '4';
            default:
                return '0';
		}
	}

    /**
     * Convert card id to name
     *
     * @param int $cardid
     * @return string
     */
    public function calcCardtype($cardid)
	{
		switch($cardid)
		{
			case 1:
				return 'Dankort / VISA/Dankort';
			case 2:
				return 'eDankort';
			case 3:
				return 'VISA / VISA Electron';
			case 4:
				return 'MasterCard';
			case 6:
				return 'JCB';
			case 7:
				return 'Maestro';
			case 8:
				return 'Diners Club';
			case 9:
				return 'American Express';
			case 10:
				return 'ewire';
			case 12:
				return 'Nordea e-betaling';
			case 13:
				return 'Danske Netbetalinger';
			case 14:
				return 'PayPal';
			case 16:
				return 'MobilPenge';
			case 17:
				return 'Klarna';
			case 18:
				return 'Svea';
			case 19:
				return 'SEB Direktbetalning';
			case 20:
				return 'Nordea E-payment';
			case 21:
				return 'Handelsbanken Direktbetalningar';
			case 22:
				return 'Swedbank Direktbetalningar';
			case 23:
				return 'ViaBill';
			case 24:
				return 'NemPay';
			case 25:
				return 'iDeal';
            default:
                return '';
		}
	}
}