<?php
namespace Bambora\Online\Helper;

use Bambora\Online\Helper\BamboraConstants;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Bambora\Online\Logger\BamboraLogger
     */
    protected $_bamboraLogger;

    /**
     * @var \Magento\Framework\Encryption\Encryptor
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
     * @param \Magento\Framework\Encryption\Encryptor $encryptor
     * @param \Magento\Framework\Module\ModuleListInterface $moduleList
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Bambora\Online\Logger\BamboraLogger $bamboraLogger,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Magento\Framework\Module\ModuleListInterface $moduleList
    ) {
        parent::__construct($context);
        $this->_bamboraLogger = $bamboraLogger;
        $this->_encryptor = $encryptor;
        $this->_moduleList = $moduleList;
    }

    /**
     * Gives back bambora_checkout configuration values
     *
     * @param string $field
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
     * @param string $field
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
     * @param string $field
     * @param string $paymentMethodCode
     * @param int|null $storeId
     * @param bool|false $flag
     * @return bool|mixed
     */
    public function getConfigData(
        $field,
        $paymentMethodCode,
        $storeId,
        $flag = false
    ) {
        $path = 'payment/' . $paymentMethodCode . '/' . $field;

        if (!$flag) {
            return $this->scopeConfig->getValue(
                $path,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeId
            );
        } else {
            return $this->scopeConfig->isSetFlag(
                $path,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeId
            );
        }
    }

    /**
     * Retrieve a Checkout Model class
     *
     * @param string $modelName
     * @return mixed
     */
    public function getCheckoutModel($modelName)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        return $objectManager->create($modelName);
    }

    /**
     * Decrypt data
     *
     * @param mixed $data
     * @return string
     */
    public function decryptData($data)
    {
        return $this->_encryptor->decrypt(trim((string)$data));
    }

    /**
     * Retrieve the shops local code
     *
     * @return string
     */
    public function getShopLocalCode()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $resolver = $objectManager->get(\Magento\Framework\Locale\Resolver::class);

        return $resolver->getLocale();
    }

    /**
     * Convert an amount to minorunits
     *
     * @param int $amount
     * @param int $minorunits
     * @param string $roundingMode
     * @return int
     */
    public function convertPriceToMinorunits($amount, $minorunits, $roundingMode)
    {
        if (empty($amount)) {
            return 0;
        }

        switch ($roundingMode) {
            case BamboraConstants::ROUND_UP:
                $amount = ceil($amount * pow(10, $minorunits));
                break;
            case BamboraConstants::ROUND_DOWN:
                $amount = floor($amount * pow(10, $minorunits));
                break;
            default:
                $amount = round($amount * pow(10, $minorunits));
                break;
        }
        return $amount;
    }

    /**
     * Convert an amount from minorunits
     *
     * @param int $amount
     * @param string $minorunits
     * @return float
     */
    public function convertPriceFromMinorunits($amount, $minorunits)
    {
        if ($amount == "" || $amount == null) {
            return 0;
        }

        return ($amount / pow(10, $minorunits));
    }

    /**
     * Return minorunits based on Currency Code
     *
     * @param string $currencyCode
     * @return int
     */
    public function getCurrencyMinorunits($currencyCode)
    {
        $currencyArray = [
        'TTD' => 0,
        'KMF' => 0,
        'ADP' => 0,
        'TPE' => 0,
        'BIF' => 0,
        'DJF' => 0,
        'MGF' => 0,
        'XPF' => 0,
        'GNF' => 0,
        'BYR' => 0,
        'PYG' => 0,
        'JPY' => 0,
        'CLP' => 0,
        'XAF' => 0,
        'TRL' => 0,
        'VUV' => 0,
        'CLF' => 0,
        'KRW' => 0,
        'XOF' => 0,
        'RWF' => 0,
        'IQD' => 3,
        'TND' => 3,
        'BHD' => 3,
        'JOD' => 3,
        'OMR' => 3,
        'KWD' => 3,
        'LYD' => 3
        ];

        return array_key_exists(
            $currencyCode,
            $currencyArray
        ) ? $currencyArray[$currencyCode] : 2;
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
        $priceHelper = $objectManager->create(\Magento\Framework\Pricing\Helper\Data::class);

        return $priceHelper->currency($amount, true, false);
    }

    /**
     * Generate Checkout Api key
     *
     * @param int $storeId
     * @return string
     */
    public function generateCheckoutApiKey($storeId)
    {
        $accesstoken = $this->getBamboraCheckoutConfigData(
            BamboraConstants::ACCESS_TOKEN,
            $storeId
        );
        $merchantNumber = $this->getBamboraCheckoutConfigData(
            BamboraConstants::MERCHANT_NUMBER,
            $storeId
        );
        $secrettoken = $this->decryptData(
            $this->getBamboraCheckoutConfigData(
                BamboraConstants::SECRET_TOKEN,
                $storeId
            )
        );

        $combined = "{$accesstoken}@{$merchantNumber}:{$secrettoken}";
        $encodedKey = base64_encode($combined);
        return "Basic {$encodedKey}";
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
        $productMetadata = $objectManager->get(\Magento\Framework\App\ProductMetadataInterface::class);
        $magentoVersion = $productMetadata->getVersion();
        $phpVersion = phpversion();
        return "Magento/{$magentoVersion} Module/{$bamboraVersion} PHP/{$phpVersion}";
    }

    /**
     * Generate the Hash string for callback
     *
     * @param string $rawString
     * @return string
     */
    public function getHashFromString($rawString)
    {
        $md5stamp = hash('md5', $rawString);
        return $md5stamp;
    }

    /**
     * Return if Checkout Api Result is valid
     *
     * @param \Bambora\Online\Model\Api\Checkout\Response\Base $response
     * @param mixed $id
     * @param bool $isBackoffice
     * @param string $message
     * @return bool
     */
    public function validateCheckoutApiResult(
        $response,
        $id,
        $isBackoffice,
        &$message
    ) {
        if (!isset($response) || $response === false || !isset($response->meta)) {
            //Error without description
            $message = "No answer from Bambora";
            $this->_bamboraLogger->addCheckoutError($id, $message);
            return false;
        } elseif (!$response->meta->result) {
            // Error with description
            $message = $isBackoffice ? $response->meta->message->merchant : $response->meta->message->enduser;
            $this->_bamboraLogger->addCheckoutError(
                $id,
                $response->meta->message->merchant
            );
            return false;
        }
        return true;
    }

        /**
         * Format the shop local code by replacing '_' with '-'
         *
         * @param mixed $lan
         * @return string
         */
    public function getFormattedShopLocalCode($lan = null)
    {
        if (!isset($lan)) {
            $lan = $this->getShopLocalCode();
        }
        $formattedLocalCode = str_replace('_', '-', $lan);
        return $formattedLocalCode;
    }

    /**
     * Create an Surcharge fee item
     *
     * @param mixed $baseFeeAmount
     * @param mixed $feeAmount
     * @param mixed $storeId
     * @param mixed $orderId
     * @param mixed $text
     * @return \Magento\Sales\Model\Order\Item
     */
    public function createSurchargeItem(
        $baseFeeAmount,
        $feeAmount,
        $storeId,
        $orderId,
        $text
    ) {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $feeItem = $objectManager->create(\Magento\Sales\Model\Order\Item::class);
        $feeItem->setSku(BamboraConstants::BAMBORA_SURCHARGE);
        $feeItem->setName($text);
        $feeItem->setBaseCost($baseFeeAmount);
        $feeItem->setBasePrice($baseFeeAmount);
        $feeItem->setBasePriceInclTax($baseFeeAmount);
        $feeItem->setBaseOriginalPrice($baseFeeAmount);
        $feeItem->setBaseRowTotal($baseFeeAmount);
        $feeItem->setBaseRowTotalInclTax($baseFeeAmount);
        $feeItem->setCost($feeAmount);
        $feeItem->setPrice($feeAmount);
        $feeItem->setPriceInclTax($feeAmount);
        $feeItem->setOriginalPrice($feeAmount);
        $feeItem->setRowTotal($feeAmount);
        $feeItem->setRowTotalInclTax($feeAmount);
        $feeItem->setProductType(\Magento\Catalog\Model\Product\Type::TYPE_VIRTUAL);
        $feeItem->setIsVirtual(1);
        $feeItem->setQtyOrdered(1);
        $feeItem->setStoreId($storeId);
        $feeItem->setOrderId($orderId);

        return $feeItem;
    }
}
