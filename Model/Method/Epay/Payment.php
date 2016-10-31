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
namespace Bambora\Online\Model\Method\Epay;

use \Bambora\Online\Model\Api\EpayApi;
use \Bambora\Online\Model\Api\EpayApiModels;
use \Magento\Sales\Model\Order\Payment\Transaction;
use Bambora\Online\Helper\BamboraConstants;

class Payment extends \Bambora\Online\Model\Method\AbstractPayment implements \Bambora\Online\Model\Method\IPayment
{
    const METHOD_CODE = 'bambora_epay';
    const METHOD_REFERENCE = 'bamboraEPayReference';

    protected $_code = self::METHOD_CODE;

    protected $_infoBlockType = 'Bambora\Online\Block\Info\View';

    /**
     * Payment Method feature
     */
    protected $_isGateway                   = true;
    protected $_canCapture                  = true;
    protected $_canCapturePartial           = true;
    protected $_canRefund                   = true;
    protected $_canRefundInvoicePartial     = true;
    protected $_canVoid                     = true;


    /**
     * @var \Bambora\Online\Model\Api\Epay\Request\Models\Auth
     */
    private $_auth;


    /**
     * Retrieve an url for merchant payment logoes
     *
     * @return string
     */
    public function getEpayPaymentTypeUrl()
    {
        /** @var \Bambora\Online\Model\Api\Epay\Action */
        $actionProvider = $this->_bamboraHelper->getEpayApi(EpayApi::API_ACTION);

        return $actionProvider->getPaymentLogoUrl($this->getAuth()->merchantNumber);
    }

    /**
     * Retrieve an url ePay Logo
     *
     * @return string
     */
    public function getEpayLogoUrl()
    {
        /** @var \Bambora\Online\Model\Api\Epay\Action */
        $actionProvider = $this->_bamboraHelper->getEpayApi(EpayApi::API_ACTION);

        return $actionProvider->getEpayLogoUrl();
    }

    /**
     * Retrieve an url for the ePay Checkout action
     *
     * @return string
     */
    public function getCheckoutUrl()
    {
        return $this->_urlBuilder->getUrl('bambora/epay/checkout', ['_secure' => $this->_request->isSecure()]);
    }


    /**
     * Retrieve an url for the ePay Decline action
     *
     * @return string
     */
    public function getCancelUrl()
    {
        return $this->_urlBuilder->getUrl('bambora/epay/cancel', ['_secure' => $this->_request->isSecure()]);
    }


    /**
     * Get ePay Auth object
     *
     * @return \Bambora\Online\Model\Api\Epay\Request\Models\Auth
     */
    public function getAuth()
    {
        if(!$this->_auth)
        {
            $storeId = $this->getStoreManager()->getStore()->getId();
            $this->_auth = $this->_bamboraHelper->generateEpayAuth($storeId);
        }

        return $this->_auth;
    }


    /**
     * Get Bambora Checkout payment window
     *
     * @param \Magento\Sales\Model\Order
     * @return \Bambora\Online\Model\Api\Epay\Request\Models\Url
     */
    public function getPaymentWindow($order)
    {
        if(!isset($order))
        {
            return null;
        }
        return $this->createPaymentRequest($order);
    }

    /**
     * Create the ePay payment window Request url
     *
     * @param \Magento\Sales\Model\Order
     * @return \Bambora\Online\Model\Api\Epay\Request\Models\Url
     */
    public function createPaymentRequest($order)
    {
        $currency = $order->getBaseCurrencyCode();
        $minorUnits = $this->_bamboraHelper->getCurrencyMinorUnits($currency);
        $totalAmountMinorUnits = $this->_bamboraHelper->convertPriceToMinorUnits($order->getBaseTotalDue(), $minorUnits);
        $storeId = $order->getStoreId();

        /** @var \Bambora\Online\Model\Api\Epay\Request\Payment */
        $paymentRequest = $this->_bamboraHelper->getEpayApiModel(EpayApiModels::REQUEST_PAYMENT);
        $paymentRequest->encoding = "UTF-8";
        $paymentRequest->cms = $this->_bamboraHelper->getModuleHeaderInfo();
        $paymentRequest->windowState = $this->getConfigData(BamboraConstants::WINDOW_STATE, $storeId);
        $paymentRequest->merchantNumber = $this->getAuth()->merchantNumber;
        $paymentRequest->windowId = $this->getConfigData(BamboraConstants::PAYMENT_WINDOW_ID, $storeId);
        $paymentRequest->amount = $totalAmountMinorUnits;
        $paymentRequest->currency = $currency;
        $paymentRequest->orderId = $order->getIncrementId();
        $paymentRequest->acceptUrl = $this->_urlBuilder->getUrl('bambora/epay/accept', ['_secure' => $this->_request->isSecure()]);
        $paymentRequest->cancelUrl = $this->_urlBuilder->getUrl('bambora/epay/cancel', ['_secure' => $this->_request->isSecure()]);
        $paymentRequest->callbackUrl = $this->_urlBuilder->getUrl('bambora/epay/callback', ['_secure' => $this->_request->isSecure()]);
        $paymentRequest->instantCapture = $this->getConfigData(BamboraConstants::INSTANT_CAPTURE, $storeId);
        $paymentRequest->group = $this->getConfigData(BamboraConstants::PAYMENT_GROUP, $storeId);
        $paymentRequest->language = $this->_bamboraHelper->calcLanguage();
        $paymentRequest->ownReceipt = $this->getConfigData(BamboraConstants::OWN_RECEIPT, $storeId);
        $paymentRequest->timeout = 60;
        $paymentRequest->invoice = $this->createInvoice($order,$minorUnits);
        $paymentRequest->hash = $this->_bamboraHelper->calcEpayMd5Key($order, $paymentRequest);

        /** @var \Bambora\Online\Model\Api\Epay\Action */
        $actionProvider = $this->_bamboraHelper->getEpayApi(EpayApi::API_ACTION);
        $paymentUrl = $actionProvider->getPaymentWindowUrl($paymentRequest);

        return $paymentUrl;
    }

    /**
     * Create Invoice
     *
     * @param \Magento\Sales\Model\Order $order
     * @param int $minorUnits
     * @return string
     */
    public function createInvoice($order,$minorUnits)
    {
        if($this->getConfigData(BamboraConstants::ENABLE_INVOICE_DATA))
        {
            /** @var \Bambora\Online\Model\Api\Epay\Request\Models\Invoice */
            $invoice = $this->_bamboraHelper->getEpayApiModel(EpayApiModels::REQUEST_MODEL_INVOICE);

            $billingAddress = $order->getBillingAddress();
            /** @var \Bambora\Online\Model\Api\Epay\Request\Models\Customer */
            $customer = $this->_bamboraHelper->getEpayApiModel(EpayApiModels::REQUEST_MODEL_CUSTOMER);
            $customer->emailaddress = $billingAddress->getEmail();
            $customer->firstname = $billingAddress->getFirstname();
            $customer->lastname = $billingAddress->getLastname();
            $customer->address = $billingAddress->getStreet()[0];
            $customer->zip = $billingAddress->getPostcode();
            $customer->city = $billingAddress->getCity();
            $customer->country = $billingAddress->getCountryId();

            $invoice->customer = $customer;

            $sa = $order->getShippingAddress();
            /** @var \Bambora\Online\Model\Api\Epay\Request\Models\ShippingAddress */
            $shippingAddress = $this->_bamboraHelper->getEpayApiModel(EpayApiModels::REQUEST_MODEL_SHIPPINGADDRESS);
            $shippingAddress->firstname = $sa->getFirstname();
            $shippingAddress->lastname = $sa->getLastname();
            $shippingAddress->address = $sa->getStreet()[0];
            $shippingAddress->zip = $sa->getPostcode();
            $shippingAddress->city = $sa->getCity();
            $shippingAddress->country = $sa->getCountryId();

            $invoice->shippingaddress = $shippingAddress;
            $invoice->lines = array();

            $items = $order->getAllVisibleItems();
            foreach($items as $item)
            {
                $invoice->lines[] = array(
                        "id" =>$item->getSku(),
                        "description" => $this->removeSpecialCharacters($item->getDescription()),
                        "quantity" => intval($item->getQtyOrdered()),
                        "price" => $this->_bamboraHelper->convertPriceToMinorUnits($item->getBasePrice() - ($item->getBaseDiscountAmount() / intval($item->getQtyOrdered())), $minorUnits),
                        "vat" => $item->getTaxPercent()
                    );
            }
            // add shipment as line
            $shippingText = __("Shipping");
            $shippingDescription = $order->getShippingDescription();
            $invoice->lines[] = array(
                       "id" => $shippingText,
                       "description" => isset($shippingDescription) ? $shippingDescription : $shippingText,
                       "quantity" => 1,
                       "price" =>$this->_bamboraHelper->convertPriceToMinorUnits(($order->getBaseShippingAmount() - $order->getBaseShippingDiscountAmount()),$minorUnits),
                       "vat" =>$order->getBaseShippingTaxAmount() > 0 ? round(($order->getBaseShippingTaxAmount() / ($order->getBaseShippingInclTax() - $order->getBaseShippingDiscountAmount())) * 100) : 0
                   );

            return json_encode($invoice,JSON_UNESCAPED_UNICODE);
		}
		else
		{
			return "";
		}
    }

    /**
     * Remove special characters
     *
     * @param string $value
     * @return string
     */
    private function removeSpecialCharacters($value)
	{
		return preg_replace('/[^\p{Latin}\d ]/u', '', $value);
	}


    /**
     * Check capture availability
     *
     * @return bool
     * @api
     */
    public function canCapture()
    {
        $isActivatedInConfig = $this->getConfigData(BamboraConstants::REMOTE_INTERFACE);
        $this->_canCapture = $isActivatedInConfig;
        return $this->_canCapture;
    }

    /**
     * Capture payment
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        try
        {
            parent::capture($payment, $amount);

            $transactionId = $payment->getAdditionalInformation($this::METHOD_REFERENCE);
            $order = $payment->getOrder();

            $currency = $order->getBaseCurrencyCode();
            $minorunits = $this->_bamboraHelper->getCurrencyMinorunits($currency);
            $amountMinorunits = $this->_bamboraHelper->convertPriceToMinorUnits($amount,$minorunits);

            /** @var \Bambora\Online\Model\Api\Epay\Action */
            $actionProvider = $this->_bamboraHelper->getEPayApi(EpayApi::API_ACTION);
            $captureResponse = $actionProvider->capture($amountMinorunits,$transactionId,$this->getAuth());

            $message = "";
            if(!$this->_bamboraHelper->validateEpayApiResult($captureResponse, $transactionId, $this->getAuth(), BamboraConstants::CAPTURE, $message))
            {
                throw new \Exception(__('The capture action failed.') . ' - '.$message);
            }

            $payment->setTransactionId($transactionId. '-' . Transaction::TYPE_CAPTURE)
                    ->setIsTransactionClosed(true)
                    ->setParentTransactionId($transactionId);

            return $this;
        }
        catch(\Exception $ex)
        {
            throw $ex;
        }
    }

    /**
     * Check Refund availability
     *
     * @return bool
     * @api
     */
    public function canRefund()
    {
        $isActivatedInConfig = $this->getConfigData(BamboraConstants::REMOTE_INTERFACE);
        $this->_canRefund = $isActivatedInConfig;
        return $this->_canRefund;
    }


    /**
     * Refund payment
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        try
        {
            parent::refund($payment, $amount);
            $transactionId = $payment->getAdditionalInformation($this::METHOD_REFERENCE);
            $order = $payment->getOrder();

            $currency = $order->getBaseCurrencyCode();
            $minorunits = $this->_bamboraHelper->getCurrencyMinorunits($currency);
            $amountMinorunits = $this->_bamboraHelper->convertPriceToMinorUnits($amount,$minorunits);

            /** @var \Bambora\Online\Model\Api\Epay\Action */
            $actionProvider = $this->_bamboraHelper->getEPayApi(EpayApi::API_ACTION);
            $creditResponse = $actionProvider->credit($amountMinorunits,$transactionId,$this->getAuth());

            $message = "";
            if(!$this->_bamboraHelper->validateEpayApiResult($creditResponse, $transactionId, $this->getAuth(), BamboraConstants::REFUND, $message))
            {
                throw new \Exception(__('The refund action failed.') . ' - '.$message);
            }

            $payment->setTransactionId($transactionId. '-' . Transaction::TYPE_REFUND)
                    ->setIsTransactionClosed(true)
                    ->setParentTransactionId($transactionId);

            return $this;
        }
        catch(\Exception $ex)
        {
            throw $ex;
        }
    }

    /**
     * Check Void availability
     *
     * @return bool
     * @api
     */
    public function canVoid()
    {
        $isActivatedInConfig = $this->getConfigData(BamboraConstants::REMOTE_INTERFACE);
        $this->_canVoid = $isActivatedInConfig;
        return $this->_canVoid;
    }

    /**
     * Void payment
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @return $this
     */
    public function void(\Magento\Payment\Model\InfoInterface $payment)
    {
        try
        {
            parent::void($payment);

            $transactionId = $payment->getAdditionalInformation($this::METHOD_REFERENCE);

            /** @var \Bambora\Online\Model\Api\Epay\Action */
            $actionProvider = $this->_bamboraHelper->getEPayApi(EpayApi::API_ACTION);
            $deleteResponse = $actionProvider->delete($transactionId, $this->getAuth());

            $message = "";
            if(!$this->_bamboraHelper->validateEpayApiResult($deleteResponse, $transactionId, $this->getAuth(), BamboraConstants::VOID, $message))
            {
                throw new \Exception(__('The void action failed.') . ' - '.$message);
            }

            $payment->setTransactionId($transactionId. '-' . Transaction::TYPE_VOID)
                    ->setIsTransactionClosed(true)
                    ->setParentTransactionId($transactionId);

            return $this;
        }
        catch(\Exception $ex)
        {
            throw $ex;
        }
    }

    private function _canVoid($txnId)
    {
        if(!$this->canVoid())
        {
            return false;
        }

        $transaction = $this->getTransaction($txnId);
        if(!isset($transaction) && $transaction->status !== '1')
        {
            return false;
        }

        return true;
    }

    /**
     * Cancel payment
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @return $this
     */
    public function cancel(\Magento\Payment\Model\InfoInterface $payment)
    {
        try
        {
            /** @var \Magento\Sales\Model\Order */
            $order = $payment->getOrder();
            foreach($order->getItems() as $item)
            {
                if($item->getSku() === BamboraConstants::BAMBORA_SURCHARGE)
                {
                    $item->setQtyCanceled(1);
                }
            }

            if($this->_canVoid($payment->getAdditionalInformation($this::METHOD_REFERENCE)))
            {
                $this->void($payment);
            }
            else
            {
                $this->_messageManager->addNotice(__('The order: %1 is canceled but the payment could not be voided', $order->getId()));
            }
        }
        catch(\Exception $ex)
        {
            throw $ex;
        }

        return $this;
    }

    /**
     * Get Bambora Checkout Transaction
     *
     * @param string $transactionId
     * @return \Bambora\Online\Model\Api\Epay\Response\Models\TransactionInformationType|null
     */
    public function getTransaction($transactionId)
    {
        try
        {
            if(!$this->getConfigData(BamboraConstants::REMOTE_INTERFACE))
            {
                return null;
            }
            /** @var \Bambora\Online\Model\Api\Epay\Action */
            $actionProvider = $this->_bamboraHelper->getEpayApi(EpayApi::API_ACTION);
            $transactionResponse = $actionProvider->getTransaction($transactionId,$this->getAuth());
            $message = "";
            if(!$this->_bamboraHelper->validateEpayApiResult($transactionResponse, $transactionId,$this->getAuth(), BamboraConstants::GET_TRANSACTION, $message))
            {
                return null;
            }

            return $transactionResponse->transactionInformation;
        }
        catch(\Exception $ex)
        {
            return null;
        }
    }
}