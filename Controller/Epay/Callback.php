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
use \Magento\Sales\Model\Order;
use \Magento\Sales\Model\Order\Payment\Transaction;
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
        if($this->validateCallback($posted))
        {
            $this->processCallback($posted);
        }

        return $this->_callbackResult;
    }

    /**
     * Validate the callback
     *
     * @param mixed $posted
     * @return bool
     */
    private function validateCallback($posted)
    {
        //Validate response
        if(!isset($posted))
        {
            $message = "Response is null";
            $this->_callbackResult = $this->_getResult(Exception::HTTP_BAD_REQUEST, $message, null);
            return false;
        }

        //Validate parameteres
        if(!$posted['orderid'] || !$posted['txnid'] || !$posted['amount'] || !$posted['currency'])
        {
            $message = "Parameteres are missing. Request: " . json_encode($posted);
            $this->_callbackResult = $this->_getResult(Exception::HTTP_BAD_REQUEST, $message,null);
            return false;
        }

        //Validate Order
        $order = $this->_getOrderByIncrementId($posted['orderid']);
        if(!isset($order))
        {
            $message = "The Order could be found or created";
            $this->_callbackResult = $this->_getResult(Exception::HTTP_BAD_REQUEST,$message,$posted['orderid']);
            return false;
        }

        //Validate MD5
        $shopMd5 = $this->_bamboraHelper->getBamboraEpayConfigData(BamboraConstants::MD5_KEY, $order->getStoreId());
        $var = "";
        if(strlen($shopMd5) > 0)
        {
            foreach($posted as $key => $value)
            {
                if($key != "hash")
                {
                    $var .= $value;
                }
            }

            $genstamp = md5($var . $shopMd5);
            if($genstamp != $posted["hash"])
            {
                $message = "Bambora MD5 check failed";
                $this->_callbackResult = $this->_getResult(Exception::HTTP_BAD_REQUEST, $message, $order->getIncrementId());

                return false;
            }
        }

        return true;
    }

    /**
     * Process the callback from Bambora
     *
     * @return void
     */
    private function processCallback($posted)
    {
        $ePayTransactionId = $posted['txnid'];

        /** @var \Magento\Sales\Model\Order */
        $order = $this->_getOrderByIncrementId($posted['orderid']);
        $payment = $order->getPayment();

        try
        {
            $pspReference = $payment->getAdditionalInformation(EpayPayment::METHOD_REFERENCE);
            if(!isset($pspReference))
            {
                /** @var \Bambora\Online\Model\Method\Epay\Payment */
                $paymentMethod = $this->_getPaymentMethodInstance($order->getPayment()->getMethod());
                $currency = $this->_bamboraHelper->convertIsoCode($posted['currency'], false);
                $minorUnits = $this->_bamboraHelper->getCurrencyMinorunits($currency);

                $this->_processCallbackData($order,
                    $paymentMethod,
                    $ePayTransactionId,
                    EpayPayment::METHOD_REFERENCE,
                    $this->_bamboraHelper->calcCardtype($posted['paymenttype']),
                    $posted['cardno'],
                    $posted['txnfee'],
                    $minorUnits,
                    $payment
                );


                $this->_callbackResult = $this->_getResult(Response::HTTP_OK, "Callback Success - Order created", $ePayTransactionId, $payment->getMethod());
            }
            else
            {
                $this->_callbackResult = $this->_getResult(Response::HTTP_OK, "Callback Success - Order already created", $ePayTransactionId, $payment->getMethod());
            }
        }
        catch(Exception $ex)
        {
            $payment->setAdditionalInformation(array(EpayPayment::METHOD_REFERENCE => ""));
            $payment->save();
            $message = "Callback Failed - " .$ex->getMessage();
            $this->_callbackResult = $this->_getResult(Exception::HTTP_INTERNAL_ERROR, $message,$ePayTransactionId);
        }
    }
}