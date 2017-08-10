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
namespace Bambora\Online\Block\Adminhtml\Sales\Order\View;

use Bambora\Online\Model\Method\Checkout\Payment as CheckoutPayment;
use Bambora\Online\Model\Method\Epay\Payment as EpayPayment;
use Bambora\Online\Helper\BamboraConstants;

class PaymentInfo extends \Magento\Backend\Block\Template
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $_registry;

    /**
     * @var \Magento\Framework\Pricing\Helper\Data
     */
    protected $_priceHelper;

    /**
     * @var \Bambora\Online\Helper\Data
     */
    protected $_bamboraHelper;

    /**
     * PaymentInfo constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Pricing\Helper\Data $priceHelper
     * @param \Bambora\Online\Helper\Data $bamboraHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Pricing\Helper\Data $priceHelper,
        \Bambora\Online\Helper\Data $bamboraHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_registry = $registry;
        $this->_priceHelper = $priceHelper;
        $this->_bamboraHelper = $bamboraHelper;
    }
    /**
     * @return string
     */
    protected function _toHtml()
    {
        return ($this->getOrder()->getPayment()->getMethod() === CheckoutPayment::METHOD_CODE || $this->getOrder()->getPayment()->getMethod() === EpayPayment::METHOD_CODE) ? parent::_toHtml() : '';
    }

    /**
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        return $this->_registry->registry('current_order');
    }

    /**
     * Display transaction data
     *
     * @return string
     */
    public function getTransactionData()
    {
        $result = __("Can not display transaction informations");
        $order = $this->getOrder();
        $storeId = $order->getStoreId();
        $payment = $order->getPayment();
        $paymentMethod = $payment->getMethod();

        if ($paymentMethod === CheckoutPayment::METHOD_CODE) {
            /** @var \Bambora\Online\Model\Method\Checkout\Payment */
            $checkoutMethod = $payment->getMethodInstance();

            if (isset($checkoutMethod)) {
                $transactionId = $payment->getAdditionalInformation($checkoutMethod::METHOD_REFERENCE);
                if (!empty($transactionId)) {
                    $message = "";
                    $transaction = $checkoutMethod->getTransaction($transactionId, $message);

                    if (isset($transaction)) {
                        $result = $this->createCheckoutTransactionHtml($transaction);
                    } elseif ($checkoutMethod->getConfigData(BamboraConstants::REMOTE_INTERFACE, $storeId) == 0) {
                        $result .= ' '.__("Please enable remote payment processing from the module configuration");
                    } else {
                        $result .= ': ' .  $message;
                    }
                }
            }
        } elseif ($paymentMethod === EpayPayment::METHOD_CODE) {
            /** @var \Bambora\Online\Model\Method\Epay\Payment */
            $ePayMethod = $payment->getMethodInstance();

            if (isset($ePayMethod)) {
                $transactionId = $payment->getAdditionalInformation($ePayMethod::METHOD_REFERENCE);
                if (!empty($transactionId)) {
                    $message = "";
                    $transaction = $ePayMethod->getTransaction($transactionId, $message);

                    if (isset($transaction)) {
                        $result = $this->createEpayTransactionHtml($transaction, $order);
                    } elseif ($ePayMethod->getConfigData(BamboraConstants::REMOTE_INTERFACE, $storeId) == 0) {
                        $result .= ' - '.__("Please enable remote payment processing from the module configuration");
                    } else {
                        $result .= ': ' .  $message;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Create Checkout Transaction HTML
     *
     * @param \Bambora\Online\Model\Api\Checkout\Response\Models\Transaction $transaction
     * @return string
     */
    public function createCheckoutTransactionHtml($transaction)
    {
        $res = '<tr><td colspan="2" class="bambora_table_title">'.__("Bambora Online Checkout - Transaction information").'</td></tr>';

        $res .= '<tr><td>' . __("Transaction ID") . ':</td>';
        $res .= '<td>' . $transaction->id . '</td></tr>';

        $res .= '<tr><td>' . __("Authorized amount") . ':</td>';
        $authAmount = $this->_bamboraHelper->convertPriceFromMinorunits($transaction->total->authorized, $transaction->currency->minorunits);
        $res .= '<td>' . $this->_priceHelper->currency($authAmount, true, false) . '</td></tr>';

        $res .= '<tr><td>' . __("Transaction date") . ':</td>';
        $res .= '<td>' . $this->formatDate($transaction->createdDate, \IntlDateFormatter::SHORT, true) . '</td></tr>';

        $res .= '<tr><td>' . __("Card type") . ':</td>';
        $res .= '<td>' . $transaction->information->paymentTypes[0]->displayName . $this->getPaymentLogoUrl($transaction->information->paymentTypes[0]->groupid). '</td></tr>';

        $res .= '<tr><td>' . __("Card number") . ':</td>';
        $res .= '<td>' . $transaction->information->primaryAccountnumbers[0]->number . '</td></tr>';

        $res .= '<tr><td>' . __("Surcharge fee") . ':</td>';
        $surchargeFee = $this->_bamboraHelper->convertPriceFromMinorunits($transaction->total->feeamount, $transaction->currency->minorunits);
        $res .= '<td>' . $this->_priceHelper->currency($surchargeFee, true, false)  . '</td></tr>';

        $res .= '<tr><td>' . __("Captured") . ':</td>';
        $capturedAmount = $this->_bamboraHelper->convertPriceFromMinorunits($transaction->total->captured, $transaction->currency->minorunits);
        $res .= '<td>' . $this->_priceHelper->currency($capturedAmount, true, false) . '</td></tr>';

        $res .= '<tr><td>' . __("Refunded") . ':</td>';
        $creditedAmount = $this->_bamboraHelper->convertPriceFromMinorunits($transaction->total->credited, $transaction->currency->minorunits);
        $res .= '<td>' . $this->_priceHelper->currency($creditedAmount, true, false) . '</td></tr>';

        $res .= '<tr><td>' . __("Acquirer") . ':</td>';
        $res .= '<td>' . $transaction->information->acquirers[0]->name . '</td></tr>';

        $res .= '<tr><td>' . __("Status") . ':</td>';
        $res .= '<td>' . $this->checkoutStatus($transaction->status) . '</td></tr>';


        return $res;
    }

    /**
     * Set the first letter to uppercase
     *
     * @param string $status
     * @return string
     */
    public function checkoutStatus($status)
    {
        if (!isset($status)) {
            return "";
        }
        $firstLetter = substr($status, 0, 1);
        $firstLetterToUpper = strtoupper($firstLetter);
        $result = str_replace($firstLetter, $firstLetterToUpper, $status);

        return $result;
    }

    /**
     * Create html for paymentLogoUrl
     *
     * @param mixed $paymentId
     * @return string
     */
    public function getPaymentLogoUrl($paymentId)
    {
        return '<img class="bambora_paymentcard" src="https://d3r1pwhfz7unl9.cloudfront.net/paymentlogos/'.$paymentId . '.svg"';
    }

    /**
     * Create ePay Transaction HTML
     *
     * @param \Bambora\Online\Model\Api\Epay\Response\Models\TransactionInformationType $transactionInformation
     * @param \Magento\Sales\Model\Order $order
     * @return string
     */
    public function createEpayTransactionHtml($transactionInformation, $order)
    {
        $minorunits = $this->_bamboraHelper->getCurrencyMinorunits($order->getBaseCurrencyCode());

        $res = '<tr><td colspan="2" class="bambora_table_title">'.__("Bambora Online ePay - Transaction information").'</td></tr>';

        $res .= '<tr><td>' . __("Transaction status") . ':</td>';
        $res .= '<td>' . $this->_bamboraHelper->translatePaymentStatus($transactionInformation->status) . '</td></tr>';

        if (strcmp($transactionInformation->status, 'PAYMENT_DELETED') == 0) {
            $res .= '<tr><td>' . __("Deleted date") . ':</td>';
            $res .= '<td>' . $this->formatDate(str_replace('T', ' ', $transactionInformation->deleteddate)) . '</td></tr>';
        }

        $res .= '<tr><td>' . __("Order number") . ':</td>';
        $res .= '<td>' . $transactionInformation->orderid . '</td></tr>';

        $res .= '<tr><td>' . __("Acquirer") . ':</td>';
        $res .= '<td>' . $transactionInformation->acquirer . '</td></tr>';

        $res .= '<tr><td>' . __("Currency code") . ':</td>';
        $res .= '<td>' . $transactionInformation->currency . '</td></tr>';

        $res .= '<tr><td>' . __("3D Secure") . ':</td>';
        $res .= '<td>' . ($transactionInformation->msc ? __('Yes') : __('No')) . '</td></tr>';

        $res .= '<tr><td>' . __('Description') . ':</td>';
        $res .= '<td>' . $transactionInformation->description . '</td></tr>';

        $res .= '<tr><td>' . __("Cardholder") . ':</td>';
        $res .= '<td>' . $transactionInformation->cardholder . '</td></tr>';

        $res .= '<tr><td>' . __("Authorized amount") . ':</td>';
        $authAmount = $this->_bamboraHelper->convertPriceFromMinorunits($transactionInformation->authamount, $minorunits);
        $authDate = $transactionInformation->authamount > 0 ? $this->formatDate(str_replace('T', ' ', $transactionInformation->authdate)) : "";
        $res .= '<td>' . $this->_priceHelper->currency($authAmount, true, false) . "&nbsp;&nbsp;&nbsp;" . $authDate .  '</td></tr>';

        $res .= '<tr><td>' . __("Captured amount") . ':</td>';
        $capturedAmount = $this->_bamboraHelper->convertPriceFromMinorunits($transactionInformation->capturedamount, $minorunits);
        $capturedDate = $transactionInformation->capturedamount > 0 ? $this->formatDate(str_replace('T', ' ', $transactionInformation->captureddate)) : "";
        $res .= '<td>' .$this->_priceHelper->currency($capturedAmount, true, false) . "&nbsp;&nbsp;&nbsp;" . $capturedDate . '</td></tr>';

        $res .= '<tr><td>' . __("Credited amount") . ':</td>';
        $creditedAmount = $this->_bamboraHelper->convertPriceFromMinorunits($transactionInformation->creditedamount, $minorunits);
        $creditedDate = $transactionInformation->creditedamount > 0 ? $this->formatDate(str_replace('T', ' ', $transactionInformation->crediteddate)) : "";
        $res .= '<td>' . $this->_priceHelper->currency($creditedAmount, true, false) . "&nbsp;&nbsp;&nbsp;" . $creditedDate . '</td></tr>';

        $res .= '<tr><td>' . __("Surcharge fee") . ':</td>';
        $surchargeAmount = $this->_bamboraHelper->convertPriceFromMinorunits($transactionInformation->fee, $minorunits);
        $res .= '<td>' . $this->_priceHelper->currency($surchargeAmount, true, false) . '</td></tr>';

        //Fraud
        if ($transactionInformation->fraudStatus > 0) {
            $res .= '<tr><td>' . __("Fraud status") . ':</td>';
            $res .= '<td>' .$transactionInformation->fraudStatus. '</td></tr>';

            $res .= '<tr><td>' . __("Payer country code") . ':</td>';
            $res .= '<td>' .$transactionInformation->payerCountryCode. '</td></tr>';

            $res .= '<tr><td>' . __("Issued country code") . ':</td>';
            $res .= '<td>' .$transactionInformation->issuedCountryCode. '</td></tr>';
            if (isset($transactionInformation->FraudMessage)) {
                $res .= '<tr><td>' . __("Fraud message") . ':</td>';
                $res .= '<td>' .$transactionInformation->FraudMessage. '</td></tr>';
            }
        }

        if (isset($transactionInformation->history) && isset($transactionInformation->history->TransactionHistoryInfo) && count($transactionInformation->history->TransactionHistoryInfo) > 0) {
            // Important to convert this item to array. If only one item is to be found in the array of history items
            // the object will be handled as non-array but object only.
            $historyArray = $transactionInformation->history->TransactionHistoryInfo;
            if (count($transactionInformation->history->TransactionHistoryInfo) == 1) {
                // convert to array
                $historyArray = array($transactionInformation->history->TransactionHistoryInfo);
            }
            $res .= '<br /><br />';
            $res .= '<tr><td colspan="2" class="bambora_table_title bambora_table_title_padding">' . __("History") . '</td></tr>';
            foreach ($historyArray as $history) {
                $res .= '<tr class="bambora_table_history_tr"><td class="bambora_table_history_td">' . str_replace('T', ' ', $history->created) . '</td>';
                $res .= '<td>';
                if (strlen($history->username) > 0) {
                    $res .= ($history->username . ': ');
                }
                $res .= $history->eventMsg . '</td></tr>';
            }
        }

        return $res;
    }
}
