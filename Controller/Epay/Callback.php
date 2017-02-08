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
namespace Bambora\Online\Controller\Epay;

use \Magento\Framework\Webapi\Exception;
use \Magento\Framework\Webapi\Response;
use \Bambora\Online\Model\Method\Epay\Payment as EpayPayment;
use \Bambora\Online\Helper\BamboraConstants;

class Callback extends \Bambora\Online\Controller\AbstractActionController
{
    /**
     * Callback Action
     */
    public function execute()
    {
        $posted = $this->getRequest()->getParams();

        /** @var \Magento\Sales\Model\Order */
        $order = null;
        $message = "Callback Failed: ";
        $responseCode = Exception::HTTP_BAD_REQUEST;
        if ($this->validateCallback($posted, $order, $message)) {
            $message = $this->processCallback($posted, $order, $responseCode);
        }

        $id = isset($order) ? $order->getIncrementId() : 0;
        $callBackResult = $this->_createCallbackResult($responseCode, $message, $id);
        if ($responseCode !== Response::HTTP_OK) {
            $this->_logError(EpayPayment::METHOD_CODE, $id, $message);
            if (isset($order)) {
                $order->addStatusHistoryComment($message);
                $order->save();
            }
        }
        return $callBackResult;
    }

    /**
     * Validate the callback
     *
     * @param mixed $posted
     * @param \Magento\Sales\Model\Order $order
     * @param string $message
     * @return bool
     */
    private function validateCallback($posted, &$order, &$message)
    {
        //Validate response
        if (!isset($posted)) {
            $message .= "Response is null";
            return false;
        }

        //Validate parameteres
        if (!$posted['orderid'] || !$posted['txnid'] || !$posted['amount'] || !$posted['currency']) {
            $message .= "Parameteres are missing. Request: " . json_encode($posted);
            return false;
        }

        //Validate Order
        $order = $this->_getOrderByIncrementId($posted['orderid']);
        if (!isset($order)) {
            $message .= "The Order could be found or created";
            return false;
        }

        //Validate Payment
        $payment = $order->getPayment();
        if (!isset($payment)) {
            $message .= "The Payment object is null";
            return false;
        }

        //Validate MD5
        $shopMd5 = $this->_bamboraHelper->getBamboraEpayConfigData(BamboraConstants::MD5_KEY, $order->getStoreId());
        if (!empty($shopMd5)) {
            $var = "";

            foreach ($posted as $key => $value) {
                if ($key != "hash") {
                    $var .= $value;
                }
            }

            $genstamp = md5($var . $shopMd5);
            if ($genstamp != $posted["hash"]) {
                $message .= "Bambora MD5 check failed";
                return false;
            }
        }

        return true;
    }

    /**
     * Process the callback from Bambora
     * @param mixed $posted
     * @param \Magento\Sales\Model\Order $order
     * @param int $responseCode
     * @return void
     */
    private function processCallback($posted, $order, &$responseCode)
    {
        $ePayTransactionId = $posted['txnid'];
        $payment = $order->getPayment();

        try {
            $pspReference = $payment->getAdditionalInformation(EpayPayment::METHOD_REFERENCE);
            if (empty($pspReference)) {
                /** @var \Bambora\Online\Model\Method\Epay\Payment */
                $paymentMethod = $this->_getPaymentMethodInstance($order->getPayment()->getMethod());
                $currency = $this->_bamboraHelper->convertIsoCode($posted['currency'], false);
                $minorUnits = $this->_bamboraHelper->getCurrencyMinorunits($currency);

                $paymentType = array_key_exists('paymenttype', $posted) ? $posted['paymenttype'] : "";
                $cardNumber = array_key_exists('cardno', $posted) ? $posted['cardno'] : "";
                $txnFee = array_key_exists('txnfee', $posted) ? $posted['txnfee'] : 0;
                $fraud = array_key_exists('fraud', $posted) ? $posted['fraud'] : 0;

                $this->_processCallbackData($order,
                     $paymentMethod,
                     $ePayTransactionId,
                     EpayPayment::METHOD_REFERENCE,
                     $paymentType,
                     $cardNumber,
                     $txnFee,
                     $minorUnits,
                     $this->_bamboraHelper->getBamboraEpayConfigData(BamboraConstants::ORDER_STATUS),
                     $payment,
                     $fraud

                 );

                $message = "Callback Success - Order created";
            } else {
                $message = "Callback Success - Order already created";
            }
            $responseCode = Response::HTTP_OK;
        } catch (\Exception $ex) {
            $order->setState(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT);
            $order->setStatus(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT);
            $payment->setAdditionalInformation(array(EpayPayment::METHOD_REFERENCE => ""));
            $payment->save();
            $order->save();

            $message = "Callback Failed - " .$ex->getMessage();
            $responseCode = Exception::HTTP_INTERNAL_ERROR;
        }

        return $message;
    }
}
