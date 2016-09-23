<?php
/**
 * Bambora Online
 *
 * @category    Online Payment Gatway
 * @package     Bambora_Online_Checkout
 * @author      Bambora
 * @copyright   Bambora (http://bambora.com)
 */
namespace Bambora\Online\Controller\Checkout;

use \Magento\Framework\Webapi\Exception;
use \Magento\Framework\Webapi\Response;
use \Magento\Sales\Model\Order;
use \Magento\Sales\Model\Order\Payment\Transaction;
use Bambora\Online\Model\Api\CheckoutApi;

class Callback extends AbstractCheckout
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
        $transactionInfo = array();
        $isValid = $this->validateCallback($posted, $transactionInfo);

        if($isValid)
        {
            $this->processCallback($posted, $transactionInfo);
        }

        return $this->_callbackResult;
    }


    /**
     * Process the callback from Bambora
     *
     * @return void
     */
    private function processCallback($posted,$transactionInfo)
    {
        $bamboraTransactionId = $posted['txnid'];
        try
        {
            $order = $this->_getOrderByIncrementId($posted['orderid']);
            $payment = $order->getPayment();
            $pspReference = $payment->getAdditionalInformation('bamboraReference');

            if(!isset($pspReference))
            {
                $payment->setTransactionId($bamboraTransactionId);

                $instantCapture = $this->_bamboraHelper->getBamboraCheckoutConfigData('instant_capture',$order->getStore()->getId());
                $shouldCloseTransaction = $instantCapture ? true : false;
                $payment->setIsTransactionClosed($shouldCloseTransaction);
                $payment->setAdditionalInformation(array('bamboraReference' => $bamboraTransactionId));
                $transaction = $payment->addTransaction(Transaction::TYPE_AUTH);



                $order->setState(Order::STATE_PROCESSING);
                $status = $this->_bamboraHelper->getBamboraCheckoutConfigData('order_status',$this->_getOrder()->getStoreId());
                $order->setStatus($status);


                $message = __("Payment authorization was a success.");

                $payment->addTransactionCommentsToOrder($transaction, $message);
                $paymentType = $transactionInfo['transaction']['information']['paymenttypes'][0]['displayname'];
                $payment->setCcType($paymentType);
                $payment->save();

                if (!$order->getEmailSent()) {
                    $this->_orderSender->send($order);
                    $order->setIsCustomerNotified(true);
                }

                $order->save();
                $this->setResult(Response::HTTP_OK, "Callback Success - Order created",$order->getIncrementId());
            }
            else
            {
                $this->setResult(Response::HTTP_OK, "Callback Success - Order already created",$order->getIncrementId());
            }

        }
        catch(Exception $ex)
        {
            $message = "Callback Failed - " .$ex->getMessage();
            $this->setResult(Exception::HTTP_INTERNAL_ERROR, $message,$bamboraTransactionId);
        }
    }

    /**
     * Validate the callback
     *
     * @return bool
     */
    private function validateCallback($posted, &$transactionInfo)
    {
        //Validate response
        if(!isset($posted) || !$posted['txnid'])
        {
            $message = isset($posted) ? "TransactionId is missing" : "Response is null";
            $this->setResult(Exception::HTTP_BAD_REQUEST, $message, $posted['orderid']);
            return false;
        }

        //Validate Order
        $order = $this->_getOrderByIncrementId($posted['orderid']);
        if(!isset($order))
        {
            $message = "The Order could be found or created";
            $this->setResult(Exception::HTTP_BAD_REQUEST,$message,$posted['orderid']);
            return false;
        }

        //Validate Transaction
        $transactionId = $posted['txnid'];
        $storeId = $order->getStoreId();
        $apiKey = $this->_bamboraHelper->generateApiKey($storeId);
        $merchantApi = $this->_bamboraHelper->getCheckoutApi(CheckoutApi::API_MERCHANT);

        $transactionInfo = $merchantApi->getTransaction($transactionId,$apiKey);

        //Validate transaction
        if(!isset($transactionInfo) || !$transactionInfo['meta']['result'])
        {
            $message = !isset($transactionInfo) ? "gettransactionInformation object is null" : $transactionInfo['meta']['message']['merchant'];
            $this->setResult(Exception::HTTP_BAD_REQUEST, $message, $order->getIncrementId());
            return false;
        }

        //Validate orderId
        if($order->getIncrementId() != $transactionInfo['transaction']['orderid'])
        {
            $message = "The posted ordernumber does not match the transaction";
            $this->setResult(Exception::HTTP_BAD_REQUEST, $message, $order->getIncrementId());
            return false;
        }

        //Validate MD5
        $shopMd5 = $this->_bamboraHelper->getBamboraCheckoutConfigData('md5key', $storeId);
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
                $this->setResult(Exception::HTTP_BAD_REQUEST, $message, $order->getIncrementId());

                return false;
            }
        }

        return true;
    }
    /**
     * Set Callback Response
     *
     * @param mixed $statusCode
     * @param mixed $message
     * @param mixed $id
     * @return void
     */
    private function setResult($statusCode,$message,$id)
    {
        $result = $this->_resultJsonFactory->create();
        $result->setHttpResponseCode($statusCode);

        $result->setData(
            ['id'=>$id,
            'message'=>$message]);

        if($statusCode === Response::HTTP_OK)
        {
            $this->_bamboraLogger->addCheckoutInfo($id,$message);
        }
        else
        {
            $this->_bamboraLogger->addCheckoutError($id,$message);
        }

        $this->_callbackResult = $result;
    }
}
