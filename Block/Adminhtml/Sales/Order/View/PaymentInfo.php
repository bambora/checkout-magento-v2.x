<?php
/**
 * Copyright (c) 2019. All rights reserved Bambora Online.
 *
 * This program is free software. You are allowed to use the software but NOT allowed to modify the software.
 * It is also not legal to do any changes to the software and distribute it in your own name / brand.
 *
 * All use of the payment modules happens at your own risk. We offer a free test account that you can use to test the module.
 *
 * @author    Bambora Online
 * @copyright Bambora Online (https://bambora.com)
 * @license   Bambora Online
 */

namespace Bambora\Online\Block\Adminhtml\Sales\Order\View;

use Bambora\Online\Model\Method\Checkout\Payment as CheckoutPayment;
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
     * @var \Bambora\Online\Logger\BamboraLogger
     */
    protected $_bamboraLogger;

    /**
     * PaymentInfo constructor.
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Pricing\Helper\Data $priceHelper
     * @param \Bambora\Online\Helper\Data $bamboraHelper
     * @param \Bambora\Online\Logger\BamboraLogger $bamboraLogger
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Pricing\Helper\Data $priceHelper,
        \Bambora\Online\Helper\Data $bamboraHelper,
        \Bambora\Online\Logger\BamboraLogger $bamboraLogger,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_registry = $registry;
        $this->_priceHelper = $priceHelper;
        $this->_bamboraHelper = $bamboraHelper;
        $this->_bamboraLogger = $bamboraLogger;
    }

    /**
     * @return string
     */
    protected function _toHtml()
    {
        return ($this->getOrder()->getPayment()->getMethod(
            ) === CheckoutPayment::METHOD_CODE ) ;
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
        try {
            $result = __("Can not display transaction information");
            $order = $this->getOrder();
            $storeId = $order->getStoreId();
            $payment = $order->getPayment();
            $paymentMethod = $payment->getMethod();

            if ($paymentMethod === CheckoutPayment::METHOD_CODE) {
                $checkoutMethod = $payment->getMethodInstance();
                if (isset($checkoutMethod)) {
                    $transactionId = $payment->getAdditionalInformation(
                        $checkoutMethod::METHOD_REFERENCE
                    );
                    if (!empty($transactionId)) {
                        $message = "";
                        $transaction = $checkoutMethod->getTransaction(
                            $transactionId,
                            $storeId,
                            $message
                        );

                        if (isset($transaction)) {
                            $result = $this->createCheckoutTransactionHtml(
                                $transaction
                            );
                        } elseif ($checkoutMethod->getConfigData(
                                BamboraConstants::REMOTE_INTERFACE,
                                $storeId
                            ) == 0) {
                            $result .= ' ' . __(
                                    "Please enable remote payment processing from the module configuration"
                                );
                        } else {
                            $result .= ': ' . $message;
                        }
                    }
                }
            }
        } catch (\Exception $ex) {
            $errorMessage = $ex->getMessage();
            $result = __(
                "An error occurred while fetching the transaction information. Please see the Bambora log file for more information."
            );
            $this->_bamboraLogger->addCommonError(
                $transactionId,
                "An error occurred while fetching the transaction information. Error: {$errorMessage}"
            );
        }

        return $result;
    }

    /**
     * Display transaction data
     *
     * @return string
     */
    public function getTransactionLogData()
    {
        try {
            $result = __("Can not display transaction history.");
            $order = $this->getOrder();
            $storeId = $order->getStoreId();
            $payment = $order->getPayment();
            $paymentMethod = $payment->getMethod();

            if ($paymentMethod === CheckoutPayment::METHOD_CODE) {
                $checkoutMethod = $payment->getMethodInstance();
                if (isset($checkoutMethod)) {
                    $transactionId = $payment->getAdditionalInformation(
                        $checkoutMethod::METHOD_REFERENCE
                    );
                    if (!empty($transactionId)) {
                        $message = "";
                        $transactionOperations = $checkoutMethod->getTransactionOperations(
                            $transactionId,
                            $storeId,
                            $message
                        );
                        if (isset($transactionOperations)) {
                            $result = '<tr><td colspan="4" class="bambora_table_title">' . __(
                                    "Transaction History"
                                ) . '</td></tr>';
                            $result .= $this->createCheckoutTransactionOperationsHtml(
                                $transactionOperations,
                                $storeId,
                                $checkoutMethod
                            );
                        }
                    }
                }
            } else {
                $result = "";
            }
        } catch (\Exception $ex) {
            $errorMessage = $ex->getMessage();
            $result = __(
                "An error occurred while fetching the transaction history. Please see the Bambora log file for more information."
            );
            $this->_bamboraLogger->addCommonError(
                $transactionId,
                "An error occurred while fetching the transaction history. Error: {$errorMessage}"
            );
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
        $res = '<tr><td colspan="2" class="bambora_table_title">' . __(
                "Bambora Online Checkout - Transaction information"
            ) . '</td></tr>';

        $res .= '<tr><td>' . __("Transaction ID") . ':</td>';
        $res .= '<td>' . $transaction->id . '</td></tr>';

        if (is_array($transaction->information->acquirerReferences) && count(
                $transaction->information->acquirerReferences
            ) > 0) {
            $res .= '<tr><td>' . __("Acquirer Reference") . ':</td>';
            $res .= '<td>' . $transaction->information->acquirerReferences[0]->reference . '</td></tr>';
        }
        $res .= '<tr><td>' . __("Authorized amount") . ':</td>';
        $authAmount = $this->_bamboraHelper->convertPriceFromMinorunits(
            $transaction->total->authorized,
            $transaction->currency->minorunits
        );
        $res .= '<td>' . $this->_priceHelper->currency(
                $authAmount,
                true,
                false
            ) . '</td></tr>';

        $res .= '<tr><td>' . __("Transaction date") . ':</td>';
        $res .= '<td>' . $this->formatDate(
                $transaction->createdDate,
                \IntlDateFormatter::SHORT,
                true
            ) . '</td></tr>';

        if (is_array($transaction->information->paymenttypes) && count(
                $transaction->information->paymenttypes
            ) > 0) {
            $res .= '<tr><td>' . __("Card type") . ':</td>';
            $res .= '<td>' . $transaction->information->paymenttypes[0]->displayName . $this->getPaymentLogoUrl(
                    $transaction->information->paymenttypes[0]->groupid,
                    $transaction->information->paymenttypes[0]->displayName
                );
            if (is_array($transaction->information->wallets) && count(
                    $transaction->information->wallets
                ) > 0) {
                $wallet_name = $transaction->information->wallets[0]->name;
                if ($wallet_name == "MobilePay") {
                    $wallet_img_id = "13";
                }
                if ($wallet_name == "Vipps") {
                    $wallet_img_id = "14";
                }
                if ($wallet_name == "GooglePay") {
                    $wallet_img_id = "22";
                    $wallet_name   = "Google Pay";
                }
                if ($wallet_name == "ApplePay") {
                    $wallet_img_id = "21";
                    $wallet_name   = "Apple Pay";
                }
                if (isset($wallet_img_id)) {
                    $res .= $this->getPaymentLogoUrl($wallet_img_id, $wallet_name);
                }
            }
            $res .= '</td></tr>';
        }

        if (is_array($transaction->information->primaryAccountnumbers) && count(
                $transaction->information->primaryAccountnumbers
            ) > 0) {
            $res .= '<tr><td>' . __("Card number") . ':</td>';
            $res .= '<td>' . $transaction->information->primaryAccountnumbers[0]->number . '</td></tr>';
        }

        $res .= '<tr><td>' . __("Surcharge fee") . ':</td>';
        $surchargeFee = $this->_bamboraHelper->convertPriceFromMinorunits(
            $transaction->total->feeamount,
            $transaction->currency->minorunits
        );
        $res .= '<td>' . $this->_priceHelper->currency(
                $surchargeFee,
                true,
                false
            ) . '</td></tr>';

        $res .= '<tr><td>' . __("Captured") . ':</td>';
        $capturedAmount = $this->_bamboraHelper->convertPriceFromMinorunits(
            $transaction->total->captured,
            $transaction->currency->minorunits
        );
        $res .= '<td>' . $this->_priceHelper->currency(
                $capturedAmount,
                true,
                false
            ) . '</td></tr>';

        $res .= '<tr><td>' . __("Refunded") . ':</td>';
        $creditedAmount = $this->_bamboraHelper->convertPriceFromMinorunits(
            $transaction->total->credited,
            $transaction->currency->minorunits
        );
        $res .= '<td>' . $this->_priceHelper->currency(
                $creditedAmount,
                true,
                false
            ) . '</td></tr>';

        if (is_array($transaction->information->acquirers) && count(
                $transaction->information->acquirers
            ) > 0) {
            $res .= '<tr><td>' . __("Acquirer") . ':</td>';
            $res .= '<td>' . $transaction->information->acquirers[0]->name . '</td></tr>';
        }
        if (is_array($transaction->information->ecis) && count(
                $transaction->information->ecis
            ) > 0) {
            $res .= '<tr><td>' . __("ECI") . ':</td>';
            $res .= '<td>' . $this->getLowestECI(
                    $transaction->information->ecis
                ) . '</td></tr>';
        }
        if (is_array($transaction->information->exemptions) && count(
                $transaction->information->exemptions
            ) > 0) {
            $res .= '<tr><td>' . __("Exemption") . ':</td>';
            $res .= '<td>' . $this->getDistinctExemptions(
                    $transaction->information->exemptions
                ) . '</td></tr>';
        }
        $res .= '<tr><td>' . __("Status") . ':</td>';
        $res .= '<td>' . $this->checkoutStatus($transaction->status) . '</td></tr>';

        return $res;
    }

    /**
     * Create Checkout Transaction Log HTML
     *
     * @param \Bambora\Online\Model\Api\Checkout\Response\Models\TransactionOperation[] $transactionOperations
     * @param string $storeId
     * @param mixed $checkoutMethod
     * @return string
     */
    public function createCheckoutTransactionOperationsHtml(
        $transactionOperations,
        $storeId,
        &$checkoutMethod
    ) {
        $html = "";
        foreach ($transactionOperations as $operation) {
            $eventInfo = $this::getEventText($operation);

            if ($eventInfo['description'] != "") {
                $html .= '<tr class="bambora_transaction_row_header">';
                $html .= '<td>' . $this->formatDate(
                        $operation->createddate,
                        \IntlDateFormatter::SHORT,
                        true
                    ) . '</td>';
                $html .= '<td colspan="2"><strong>' . $eventInfo['title'] . '</strong></td>';
                $amount = $this->_bamboraHelper->convertPriceFromMinorunits(
                    $operation->amount,
                    $operation->currency->minorunits
                );
                if ($amount > 0) {
                    $html .= '<td>' . $this->_priceHelper->currency(
                            $amount,
                            true,
                            false
                        ) . '</td>';
                } else {
                    $html .= '<td>-</td>';
                }
                $html .= '</tr>';
                $html .= '<tr class="bambora_transaction_description">';
                $eventInfoExtra = "";
                if ($operation->status != 'approved') {
                    $eventInfoExtra = $this->getEventExtra(
                        $operation,
                        $storeId,
                        $checkoutMethod
                    );
                    $eventInfoExtra = '<div style="color:#ec6459;">' . $eventInfoExtra . '</div>';
                }
                $html .= '<td colspan="4"><i>' . $eventInfo['description'] . $eventInfoExtra . '</i></td>';
                $html .= '</tr>';
                if (isset($operation->transactionoperations) && count(
                        $operation->transactionoperations
                    ) > 0) {
                    $html .= $this->createCheckoutTransactionOperationsHtml(
                        $operation->transactionoperations,
                        $storeId,
                        $checkoutMethod
                    );
                }
            } else {
                if (isset($operation->transactionoperations) && count(
                        $operation->transactionoperations
                    ) > 0) {
                    $html .= $this->createCheckoutTransactionOperationsHtml(
                        $operation->transactionoperations,
                        $storeId,
                        $checkoutMethod
                    );
                }
            }
        }
        $html = str_replace("CollectorBank", "Walley", $html);
        return $html;
    }

    private function getCardAuthenticationBrandName($paymentGroupId)
    {
        switch ($paymentGroupId) {
            case 1:
                return "Dankort Secured by Nets";
            case 2:
                return "Verified by Visa";
            case 3:
            case 4:
                return "MasterCard SecureCode";
            case 5:
                return "J/Secure";
            case 6:
                return "American Express SafeKey";
            default:
                return "3D Secure";
        }
    }

    private function getDistinctExemptions($exemptions)
    {
        $exemptionValues = null;
        foreach ($exemptions as $exemption) {
            $exemptionValues[] = $exemption->value;
        }
        return implode(",", array_unique($exemptionValues));
    }

    private function getLowestECI($ecis)
    {
        foreach ($ecis as $eci) {
            $eciValues[] = $eci->value;
        }
        return min($eciValues);
    }


    private function getEventExtra($operation, $storeId, &$checkoutMethod)
    {
        $source = $operation->actionsource;
        $actionCode = $operation->actioncode;
        $merchantLabel = "";
        if (!empty($source)) {
            $message = "";
            $responseCode = $checkoutMethod->getResponseCodeDetails(
                $source,
                $actionCode,
                $storeId,
                $message
            );
            if (isset($responseCode)) {
                $merchantLabel = $responseCode->merchantlabel . " - " . $source . " " . $actionCode;
            }
        }
        return $merchantLabel;
    }


    /**
     *  Get event Log text.
     *
     * @param object $operation
     *
     * @return array
     */
    private function getEventText($operation)
    {
        $action = strtolower($operation->action);
        $subAction = strtolower($operation->subaction);
        $approved = $operation->status == 'approved';
        $threeDSecureBrandName = "";
        $eventInfo = array();

        if ($action === "authorize") {
            if (is_array($operation->paymenttypes) && count(
                    $operation->paymenttypes
                ) > 0) {
                $threeDSecureBrandName = $this->getCardAuthenticationBrandName(
                    $operation->paymenttypes[0]->id
                );
            }
            // Temporary renaming for Lindorff & Collector Bank to Walley required until implemented in Acquire
            $thirdPartyName = $operation->acquirername;
            $thirdPartyName = strtolower(
                $thirdPartyName
            ) !== ("lindorff" || "collectorbank")
                ? $thirdPartyName
                : "Walley";

            switch ($subAction) {
                case "threed":
                {
                    $title = $approved ? 'Payment completed (' . $threeDSecureBrandName . ')' : 'Payment failed (' . $threeDSecureBrandName . ')';
                    if (isset($operation->ecis[0]->value)) {
                        $eci = $operation->ecis[0]->value;
                    }
                    $statusText = $approved
                        ? "completed successfully."
                        : "failed.";
                    $description = "";
                    if ($eci === "7") {
                        $description = 'Authentication was either not attempted or unsuccessful. Either the card does not support' .
                            $threeDSecureBrandName . ' or the issuing bank does not handle it as a ' .
                            $threeDSecureBrandName . ' payment. Payment ' . $statusText . ' at ECI level ' . $eci;
                    }
                    if ($eci === "6") {
                        $description = 'Authentication was attempted but failed. Either cardholder or card issuing bank is not enrolled for ' .
                            $threeDSecureBrandName . '. Payment ' . $statusText . ' at ECI level ' . $eci;
                    }
                    if ($eci === "5") {
                        $description = $approved
                            ? 'Payment was authenticated at ECI level ' . $eci . ' via ' . $threeDSecureBrandName . ' and ' . $statusText
                            : 'Payment was did not authenticate via ' . $threeDSecureBrandName . ' and ' . $statusText;
                    }
                    $eventInfo['title'] = $title;
                    $eventInfo['description'] = $description;

                    return $eventInfo;
                }
                case "ssl":
                {
                    $title = $approved
                        ? 'Payment completed'
                        : 'Payment failed';

                    $description = $approved
                        ? 'Payment was completed and authorized via SSL.'
                        : 'Authorization was attempted via SSL, but failed.';

                    $eventInfo['title'] = $title;
                    $eventInfo['description'] = $description;
                    return $eventInfo;
                }

                case "recurring":
                {
                    $title = $approved
                        ? 'Subscription payment completed'
                        : 'Subscription payment failed';

                    $description = $approved
                        ? 'Payment was completed and authorized on a subscription.'
                        : 'Authorization was attempted on a subscription, but failed.';

                    $eventInfo['title'] = $title;
                    $eventInfo['description'] = $description;

                    return $eventInfo;
                }

                case "update":
                {
                    $title = $approved
                        ? 'Payment updated'
                        : 'Payment update failed';

                    $description = $approved
                        ? 'The payment was successfully updated.'
                        : 'The payment update failed.';

                    $eventInfo['title'] = $title;
                    $eventInfo['description'] = $description;

                    return $eventInfo;
                }

                case "return":
                {
                    $title = $approved
                        ? 'Payment completed'
                        : 'Payment failed';
                    $statusText = $approved
                        ? 'successful'
                        : 'failed';

                    $description = 'Returned from ' . $thirdPartyName . ' authentication with a ' . $statusText . ' authorization.';
                    $eventInfo['title'] = $title;
                    $eventInfo['description'] = $description;

                    return $eventInfo;
                }

                case "redirect":
                {
                    $statusText = $approved
                        ? "Successfully"
                        : "Unsuccessfully";
                    $eventInfo['title'] = 'Redirect to ' . $thirdPartyName;
                    $eventInfo['description'] = $statusText . ' redirected to ' . $thirdPartyName . ' for authentication.';

                    return $eventInfo;
                }
            }
        }
        if ($action === "capture") {
            $captureMultiText = (($subAction === "multi" || $subAction === "multiinstant") && $operation->currentbalance > 0)
                ? 'Further captures are possible.'
                : 'Further captures are no longer possible.';

            switch ($subAction) {
                case "full":
                {
                    $title = $approved
                        ? 'Captured full amount'
                        : 'Capture failed';

                    $description = $approved
                        ? 'The full amount was successfully captured.'
                        : 'The capture attempt failed.';

                    $eventInfo['title'] = $title;
                    $eventInfo['description'] = $description;

                    return $eventInfo;
                }
                case "fullinstant":
                {
                    $title = $approved
                        ? 'Instantly captured full amount'
                        : 'Instant capture failed';

                    $description = $approved
                        ? 'The full amount was successfully captured.'
                        : 'The instant capture attempt failed.';

                    $eventInfo['title'] = $title;
                    $eventInfo['description'] = $description;

                    return $eventInfo;
                }
                case "partly":
                case "multi":
                {
                    $title = $approved
                        ? 'Captured partial amount'
                        : 'Capture failed';

                    $description = $approved
                        ? 'The partial amount was successfully captured. ' . $captureMultiText
                        : 'The partial capture attempt failed.';

                    $eventInfo['title'] = $title;
                    $eventInfo['description'] = $description;
                    return $eventInfo;
                }
                case "partlyinstant":
                case "multiinstant":
                {
                    $title = $approved
                        ? 'Instantly captured partial amount'
                        : 'Instant capture failed';
                    $description = $approved
                        ? 'The partial amount was successfully captured. ' . $captureMultiText
                        : 'The instant partial capture attempt failed.';

                    $eventInfo['title'] = $title;
                    $eventInfo['description'] = $description;
                    return $eventInfo;
                }
            }
        }

        if ($action === "credit") {
            switch ($subAction) {
                case "full":
                {
                    $title = $approved
                        ? 'Refunded full amount'
                        : 'Refund failed';
                    $description = $approved
                        ? 'The full amount was successfully refunded.'
                        : 'The refund attempt failed.';

                    $eventInfo['title'] = $title;
                    $eventInfo['description'] = $description;
                    return $eventInfo;
                }
                case "partly":
                case "multi":
                {
                    $title = $approved
                        ? 'Refunded partial amount'
                        : 'Refund failed';


                    $refundMultiText = $subAction === "multi"
                        ? "Further refunds are possible."
                        : "Further refunds are no longer possible.";


                    $description = $approved
                        ? 'The amount was successfully refunded. ' . $refundMultiText
                        : 'The partial refund attempt failed.';

                    $eventInfo['title'] = $title;
                    $eventInfo['description'] = $description;
                    return $eventInfo;
                }
            }
        }
        if ($action === "delete") {
            switch ($subAction) {
                case "instant":
                {
                    $title = $approved
                        ? 'Canceled'
                        : 'Cancellation failed';

                    $description = $approved
                        ? 'The payment was canceled.'
                        : 'The cancellation failed.';

                    $eventInfo['title'] = $title;
                    $eventInfo['description'] = $description;
                    return $eventInfo;
                }
                case "delay":
                {
                    $title = $approved
                        ? 'Cancellation scheduled'
                        : 'Cancellation scheduling failed';

                    $description = $approved
                        ? 'The payment was canceled.'
                        : 'The cancellation failed.';

                    $eventInfo['title'] = $title;
                    $eventInfo['description'] = $description;

                    return $eventInfo;
                }
            }
        }
        $eventInfo['title'] = $action . ":" . $subAction;
        $eventInfo['description'] = null;

        return $eventInfo;
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
     * @param string $name
     * @return string
     */
    public function getPaymentLogoUrl($paymentId, $name)
    {
        return '<img class="bambora_paymentcard" src="https://d3r1pwhfz7unl9.cloudfront.net/paymentlogos/' . $paymentId . '.svg" alt ="' . $name . '">';
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
        $minorunits = $this->_bamboraHelper->getCurrencyMinorunits(
            $order->getBaseCurrencyCode()
        );

        $res = '<tr><td colspan="2" class="bambora_table_title">' . __(
                "Bambora Online ePay - Transaction information"
            ) . '</td></tr>';

        $res .= '<tr><td>' . __("Transaction status") . ':</td>';
        $res .= '<td>' . $this->_bamboraHelper->translatePaymentStatus(
                $transactionInformation->status
            ) . '</td></tr>';

        if (strcmp($transactionInformation->status, 'PAYMENT_DELETED') == 0) {
            $res .= '<tr><td>' . __("Deleted date") . ':</td>';
            $res .= '<td>' . $this->formatDate(
                    str_replace('T', ' ', $transactionInformation->deleteddate)
                ) . '</td></tr>';
        }

        $res .= '<tr><td>' . __("Order number") . ':</td>';
        $res .= '<td>' . $transactionInformation->orderid . '</td></tr>';

        $res .= '<tr><td>' . __("Acquirer") . ':</td>';
        $res .= '<td>' . $transactionInformation->acquirer . '</td></tr>';

        $res .= '<tr><td>' . __("Currency code") . ':</td>';
        $res .= '<td>' . $transactionInformation->currency . '</td></tr>';

        $res .= '<tr><td>' . __("3D Secure") . ':</td>';
        $res .= '<td>' . ($transactionInformation->msc ? __('Yes') : __(
                'No'
            )) . '</td></tr>';

        $res .= '<tr><td>' . __('Description') . ':</td>';
        $res .= '<td>' . $transactionInformation->description . '</td></tr>';

        $res .= '<tr><td>' . __("Cardholder") . ':</td>';
        $res .= '<td>' . $transactionInformation->cardholder . '</td></tr>';

        $res .= '<tr><td>' . __("Authorized amount") . ':</td>';
        $authAmount = $this->_bamboraHelper->convertPriceFromMinorunits(
            $transactionInformation->authamount,
            $minorunits
        );
        $authDate = $transactionInformation->authamount > 0 ? $this->formatDate(
            str_replace('T', ' ', $transactionInformation->authdate)
        ) : "";
        $res .= '<td>' . $this->_priceHelper->currency(
                $authAmount,
                true,
                false
            ) . "&nbsp;&nbsp;&nbsp;" . $authDate . '</td></tr>';

        $res .= '<tr><td>' . __("Captured amount") . ':</td>';
        $capturedAmount = $this->_bamboraHelper->convertPriceFromMinorunits(
            $transactionInformation->capturedamount,
            $minorunits
        );
        $capturedDate = $transactionInformation->capturedamount > 0 ? $this->formatDate(
            str_replace('T', ' ', $transactionInformation->captureddate)
        ) : "";
        $res .= '<td>' . $this->_priceHelper->currency(
                $capturedAmount,
                true,
                false
            ) . "&nbsp;&nbsp;&nbsp;" . $capturedDate . '</td></tr>';

        $res .= '<tr><td>' . __("Credited amount") . ':</td>';
        $creditedAmount = $this->_bamboraHelper->convertPriceFromMinorunits(
            $transactionInformation->creditedamount,
            $minorunits
        );
        $creditedDate = $transactionInformation->creditedamount > 0 ? $this->formatDate(
            str_replace('T', ' ', $transactionInformation->crediteddate)
        ) : "";
        $res .= '<td>' . $this->_priceHelper->currency(
                $creditedAmount,
                true,
                false
            ) . "&nbsp;&nbsp;&nbsp;" . $creditedDate . '</td></tr>';

        $res .= '<tr><td>' . __("Surcharge fee") . ':</td>';
        $surchargeAmount = $this->_bamboraHelper->convertPriceFromMinorunits(
            $transactionInformation->fee,
            $minorunits
        );
        $res .= '<td>' . $this->_priceHelper->currency(
                $surchargeAmount,
                true,
                false
            ) . '</td></tr>';

        //Fraud
        if ($transactionInformation->fraudStatus > 0) {
            $res .= '<tr><td>' . __("Fraud status") . ':</td>';
            $res .= '<td>' . $transactionInformation->fraudStatus . '</td></tr>';

            $res .= '<tr><td>' . __("Payer country code") . ':</td>';
            $res .= '<td>' . $transactionInformation->payerCountryCode . '</td></tr>';

            $res .= '<tr><td>' . __("Issued country code") . ':</td>';
            $res .= '<td>' . $transactionInformation->issuedCountryCode . '</td></tr>';
            if (isset($transactionInformation->FraudMessage)) {
                $res .= '<tr><td>' . __("Fraud message") . ':</td>';
                $res .= '<td>' . $transactionInformation->FraudMessage . '</td></tr>';
            }
        }

        $transactionHistory = $transactionInformation->history;
        $transactionHistoryInfo = isset($transactionHistory) ? $transactionHistory->TransactionHistoryInfo : null;
        if (isset($transactionHistoryInfo)) {
            $res .= '<br /><br />';
            $res .= '<tr><td colspan="2" class="bambora_table_title bambora_table_title_padding">' . __(
                    "History"
                ) . '</td></tr>';

            if (is_array($transactionHistoryInfo)) {
                foreach ($transactionHistoryInfo as $history) {
                    $res .= $this->createEpayHistoryItemHtml($history);
                }
            } else {
                $res .= $this->createEpayHistoryItemHtml($transactionHistoryInfo);
            }
        }
        return $res;
    }

    /**
     * Create the html for an History Item
     *
     * @param mixed $history
     * @return string
     */
    public function createEpayHistoryItemHtml($history)
    {
        $html = "";
        $html .= '<tr class="bambora_table_history_tr"><td class="bambora_table_history_td">' . str_replace(
                'T',
                ' ',
                $history->created
            ) . '</td>';
        $html .= '<td>';
        if (strlen($history->username) > 0) {
            $html .= ($history->username . ': ');
        }
        $html .= $history->eventMsg . '</td></tr>';

        return $html;
    }
}
