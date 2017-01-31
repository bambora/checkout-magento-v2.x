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
use \Bambora\Online\Model\Api\EpayApi;
use \Bambora\Online\Model\Api\EpayApiModels;

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
        $message = "";
        if ($this->validateCallback($posted, $order, $message)) {
            $this->processCallback($posted, $order);
        } else {
            if (isset($order)) {
                $order->addStatusHistoryComment($message);
                $order->save();
            }
        }

        return $this->_callbackResult;
    }

    /**
     * Validate the callback
     *
     * @param mixed $posted
     * @param \Magento\Sales\Model\Order &$order
     * @param string &$message
     * @return bool
     */
    private function validateCallback($posted, &$order, &$message)
    {
        //Validate response
        if (!isset($posted)) {
            $message = "Response is null";
            $this->_callbackResult = $this->_getResult(Exception::HTTP_BAD_REQUEST, $message, null);
            return false;
        }

        //Validate parameteres
        if (!$posted['orderid'] || !$posted['txnid'] || !$posted['amount'] || !$posted['currency']) {
            $message = "Parameteres are missing. Request: " . json_encode($posted);
            $this->_callbackResult = $this->_getResult(Exception::HTTP_BAD_REQUEST, $message, null);
            return false;
        }

        //Validate Order
        $order = $this->_getOrderByIncrementId($posted['orderid']);
        if (!isset($order)) {
            $message = "The Order could be found or created";
            $this->_callbackResult = $this->_getResult(Exception::HTTP_BAD_REQUEST, $message, $posted['orderid']);
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
                $message = "Bambora MD5 check failed";
                $this->_callbackResult = $this->_getResult(Exception::HTTP_BAD_REQUEST, $message, $order->getIncrementId());

                return false;
            }
        }

        return true;
    }

    /**
     * Process the callback from Bambora
     * @param mixed $posted
     * @param \Bambora\Online\Model\Api\Epay\Response\Transaction $transactionResponse
     * @param \Magento\Sales\Model\Order $order
     * @return void
     */
    private function processCallback($posted, $order)
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

                $this->_processCallbackData($order,
                    $paymentMethod,
                    $ePayTransactionId,
                    EpayPayment::METHOD_REFERENCE,
                    $posted['paymenttype'],
                    $posted['cardno'],
                    $posted['txnfee'],
                    $minorUnits,
                    $this->_bamboraHelper->getBamboraEpayConfigData(BamboraConstants::ORDER_STATUS),
                    $payment,
                    array_key_exists('fraud', $posted) ? $posted['fraud'] : 0

                );

                $this->_callbackResult = $this->_getResult(Response::HTTP_OK, "Callback Success - Order created", $ePayTransactionId, $payment->getMethod());
            } else {
                $this->_callbackResult = $this->_getResult(Response::HTTP_OK, "Callback Success - Order already created", $ePayTransactionId, $payment->getMethod());
            }
        } catch (Exception $ex) {
            $payment->setAdditionalInformation(array(EpayPayment::METHOD_REFERENCE => ""));
            $payment->save();
            $message = "Callback Failed - " .$ex->getMessage();
            $this->_callbackResult = $this->_getResult(Exception::HTTP_INTERNAL_ERROR, $message, $ePayTransactionId);
        }
    }
}
