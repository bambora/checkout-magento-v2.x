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
namespace Bambora\Online\Controller\Checkout;

use \Magento\Framework\Webapi\Exception;
use \Magento\Framework\Webapi\Response;
use \Magento\Sales\Model\Order;
use \Magento\Sales\Model\Order\Payment\Transaction;
use \Bambora\Online\Model\Api\CheckoutApi;
use \Bambora\Online\Model\Method\Checkout\Payment as CheckoutPayment;
use \Bambora\Online\Model\Api\CheckoutApiModels;
use \Bambora\Online\Helper\BamboraConstants;


class Callback extends \Bambora\Online\Controller\AbstractActionController
{
    /**
     * @var \Magento\Framework\Controller\Result\Json
     */
    private $_callbackResult;

    /**
     * Callback Action
     */
    public function execute()
    {
        $posted = $this->getRequest()->getParams();

        /** @var \Bambora\Online\Model\Api\Checkout\Response\Transaction */
        $transactionResponse = $this->_bamboraHelper->getCheckoutApiModel(CheckoutApiModels::RESPONSE_TRANSACTION);

        $isValid = $this->validateCallback($posted, $transactionResponse);
        if($isValid)
        {
            $this->processCallback($posted, $transactionResponse);
        }

        return $this->_callbackResult;
    }

    /**
     * Validate the callback
     *
     * @param mixed $posted
     * @param \Bambora\Online\Model\Api\Checkout\Response\Transaction &$transactionResponse
     * @return bool
     */
    private function validateCallback($posted, &$transactionResponse)
    {
        //Validate response
        if(!isset($posted) || !$posted['txnid'])
        {
            $message = isset($posted) ? "TransactionId is missing" : "Response is null";
            $this->_callbackResult = $this->_getResult(Exception::HTTP_BAD_REQUEST, $message, $posted['orderid']);
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
        $shopMd5 = $this->_bamboraHelper->getBamboraCheckoutConfigData(BamboraConstants::MD5_KEY, $order->getStoreId());
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
                $this->_callbackResult = $this->setResult(Exception::HTTP_BAD_REQUEST, $message, $order->getIncrementId());
                return false;
            }
        }


        //Validate Transaction
        $transactionId = $posted['txnid'];
        $apiKey = $this->_bamboraHelper->generateCheckoutApiKey($order->getStoreId());

        /** @var \Bambora\Online\Model\Api\Checkout\Merchant */
        $merchantApi = $this->_bamboraHelper->getCheckoutApi(CheckoutApi::API_MERCHANT);

        $transactionResponse = $merchantApi->getTransaction($transactionId,$apiKey);

        //Validate transaction
        if(!isset($transactionResponse) || !$transactionResponse->meta->result)
        {
            $message = !isset($transactionResponse) ? "gettransactionInformation object is null" : $transactionResponse->meta->message->merchant;
            $this->_callbackResult = $this->_getResult(Exception::HTTP_BAD_REQUEST, $message, $order->getIncrementId());
            return false;
        }

        //Validate orderId
        if($order->getIncrementId() != $transactionResponse->transaction->orderid)
        {
            $message = "The posted ordernumber does not match the transaction";
            $this->_callbackResult = $this->_getResult(Exception::HTTP_BAD_REQUEST, $message, $order->getIncrementId());
            return false;
        }

        return true;
    }

    /**
     * Process the callback from Bambora
     *
     * @param mixed $posted
     * @param \Bambora\Online\Model\Api\Checkout\Response\Transaction $transactionResponse
     * @return void
     */
    private function processCallback($posted, $transactionResponse)
    {
        $bamboraTransactionId = $transactionResponse->transaction->id;

        /** @var \Magento\Sales\Model\Order */
        $order = $this->_getOrderByIncrementId($posted['orderid']);
        $payment = $order->getPayment();
        
        try
        {
            $pspReference = $payment->getAdditionalInformation(CheckoutPayment::METHOD_REFERENCE);
            if(empty($pspReference))
            {
                /** @var \Bambora\Online\Model\Method\Checkout\Payment */
                $paymentMethod = $this->_getPaymentMethodInstance($order->getPayment()->getMethod());

                $this->_processCallbackData($order,
                    $paymentMethod,
                    $bamboraTransactionId,
                    CheckoutPayment::METHOD_REFERENCE,
                    $transactionResponse->transaction->information->paymentTypes[0]->displayName,
                    $transactionResponse->transaction->information->primaryAccountnumbers[0]->number,
                    $transactionResponse->transaction->total->feeamount,
                    $transactionResponse->transaction->currency->minorunits,
                    $payment
                );

                $this->_callbackResult = $this->_getResult(Response::HTTP_OK, "Callback Success - Order created", $bamboraTransactionId, $payment->getMethod());
            }
            else
            {
                $this->_callbackResult = $this->_getResult(Response::HTTP_OK, "Callback Success - Order already created", $bamboraTransactionId, $payment->getMethod());
            }
        }
        catch(\Exception $ex)
        {
            $payment->setAdditionalInformation(array(CheckoutPayment::METHOD_REFERENCE => ""));
            $payment->save();
            $message = "Callback Failed: " .$ex->getMessage();
            $this->_callbackResult = $this->_getResult(Exception::HTTP_INTERNAL_ERROR, $message, $bamboraTransactionId);
        }
    }
}
