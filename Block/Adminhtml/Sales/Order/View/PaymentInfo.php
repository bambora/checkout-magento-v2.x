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
     * @var \Bambora\Online\Helper\Data
     */
    protected $_bamboraHelper;

    /**
     * PaymentInfo constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Bambora\Online\Helper\Data $bamboraHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Bambora\Online\Helper\Data $bamboraHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_registry = $registry;
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
                if(!empty($transactionId)) {
                    $message = "";
                    $transaction = $checkoutMethod->getTransaction($transactionId, $message);

                    if (isset($transaction)) {
                        $result = $this->createCheckoutTransactionHtml($transaction);
                    } elseif ($checkoutMethod->getConfigData(BamboraConstants::REMOTE_INTERFACE, $storeId) == 0) {
                        $result .= ' '.__("Please enable remote payment processing from the module configuration");
                    } else {
                        $result .= ' - ' .  $message;
                    }
                }
            }
        } elseif ($paymentMethod === EpayPayment::METHOD_CODE) {
            /** @var \Bambora\Online\Model\Method\Epay\Payment */
            $ePayMethod = $payment->getMethodInstance();

            if (isset($ePayMethod)) {
                $transactionId = $payment->getAdditionalInformation($ePayMethod::METHOD_REFERENCE);
                if(!empty($transactionId)) {
                    $message = "";
                    $transaction = $ePayMethod->getTransaction($transactionId, $message);

                    if (isset($transaction)) {
                        $result = $this->createEpayTransactionHtml($transaction, $order);
                    } elseif ($ePayMethod->getConfigData(BamboraConstants::REMOTE_INTERFACE, $storeId) == 0) {
                        $result .= ' - '.__("Please enable remote payment processing from the module configuration");
                    } else {
                        $result .= ' - ' .  $message;
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
    private function createCheckoutTransactionHtml($transaction)
    {
        $res = '<tr><td colspan="2" class="bambora_table_title">Bambora Checkout</td></tr>';

        $res .= '<tr><td>' . __("Transaction ID") . ':</td>';
        $res .= '<td>' . $transaction->id . '</td></tr>';

        $res .= '<tr><td>' . __("Amount") . ':</td>';
        $res .= '<td>' . $transaction->currency->code . "&nbsp;" . $this->_bamboraHelper->convertPriceFromMinorUnits($transaction->total->authorized, $transaction->currency->minorunits) . '</td></tr>';

        $res .= '<tr><td>' . __("Transaction date") . ':</td>';
        $res .= '<td>' . $this->formatDate($transaction->createdDate, \IntlDateFormatter::SHORT, true) . '</td></tr>';

        $res .= '<tr><td>' . __("Card type") . ':</td>';
        $res .= '<td>' . $transaction->information->paymentTypes[0]->displayName . $this->getPaymentLogoUrl($transaction->information->paymentTypes[0]->groupid). '</td></tr>';

        $res .= '<tr><td>' . __("Card number") . ':</td>';
        $res .= '<td>' . $transaction->information->primaryAccountnumbers[0]->number . '</td></tr>';

        $res .= '<tr><td>' . __("Surcharge fee") . ':</td>';
        $res .= '<td>' . $transaction->currency->code . "&nbsp;" .$this->_bamboraHelper->convertPriceFromMinorUnits($transaction->total->feeamount, $transaction->currency->minorunits) . '</td></tr>';

        $res .= '<tr><td>' . __("Captured") . ':</td>';
        $res .= '<td>' . $transaction->currency->code . "&nbsp;" .$this->_bamboraHelper->convertPriceFromMinorUnits($transaction->total->captured, $transaction->currency->minorunits) . '</td></tr>';

        $res .= '<tr><td>' . __("Refunded") . ':</td>';
        $res .= '<td>' . $transaction->currency->code . "&nbsp;" . $this->_bamboraHelper->convertPriceFromMinorUnits($transaction->total->credited, $transaction->currency->minorunits) . '</td></tr>';

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
    private function checkoutStatus($status)
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
    private function getPaymentLogoUrl($paymentId)
    {
        return '<img class="bambora_paymentcard" src="https://d3r1pwhfz7unl9.cloudfront.net/paymentlogos/'.$paymentId . '.png"';
    }

    /**
     * Create ePay Transaction HTML
     *
     * @param \Bambora\Online\Model\Api\Epay\Response\Models\TransactionInformationType $transactionInformation
     * @param \Magento\Sales\Model\Order $order
     * @return string
     */
    private function createEpayTransactionHtml($transactionInformation, $order)
    {
        $minorUnits = $this->_bamboraHelper->getCurrencyMinorunits($order->getBaseCurrencyCode());

        $res = '<tr><td colspan="2" class="bambora_table_title">ePay | Payment solutions</td></tr>';

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

        $res .= '<tr><td>' . __("Auth amount") . ':</td>';
        $res .= '<td>' . $order->getBaseCurrencyCode() . "&nbsp;" . $this->_bamboraHelper->convertPriceFromMinorUnits($transactionInformation->authamount, $minorUnits) .  '</td></tr>';

        if ($transactionInformation->authamount > 0) {
            $res .= '<tr><td>' . __("Authorized date") . ':</td>';
            $res .= '<td>' . $this->formatDate(str_replace('T', ' ', $transactionInformation->authdate)) . '</td></tr>';
        }

        $res .= '<tr><td>' . __("Captured amount") . ':</td>';
        $res .= '<td>' .$order->getBaseCurrencyCode() . "&nbsp;" . $this->_bamboraHelper->convertPriceFromMinorUnits($transactionInformation->capturedamount, $minorUnits) . '</td></tr>';

        if ($transactionInformation->capturedamount > 0) {
            $res .= '<tr><td>' . __("Captured date") . ':</td>';
            $res .= '<td>' . $this->formatDate(str_replace('T', ' ', $transactionInformation->captureddate)) . '</td></tr>';
        }

        $res .= '<tr><td>' . __("Credited amount") . ':</td>';
        $res .= '<td>' . $order->getBaseCurrencyCode() . '&nbsp;' . $this->_bamboraHelper->convertPriceFromMinorUnits($transactionInformation->creditedamount, $minorUnits). '</td></tr>';

        if ($transactionInformation->creditedamount > 0) {
            $res .= '<tr><td>' . __("Credited date") . ':</td>';
            $res .= '<td>' . $this->formatDate(str_replace('T', ' ', $transactionInformation->crediteddate)) . '</td></tr>';
        }

        $res .= '<tr><td>' . __("Surcharge fee") . ':</td>';
        $res .= '<td>' . $order->getBaseCurrencyCode() . "&nbsp;" . $this->_bamboraHelper->convertPriceFromMinorUnits($transactionInformation->fee, $minorUnits) . '</td></tr>';

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
