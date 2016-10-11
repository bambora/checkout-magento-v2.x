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

class Callback extends \Bambora\Online\Controller\AbstractController
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

        //Validate MD5
        $shopMd5 = $this->_bamboraHelper->getBamboraCheckoutConfigData('md5key', $order->getStoreId());
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
            $this->setResult(Exception::HTTP_BAD_REQUEST, $message, $order->getIncrementId());
            return false;
        }

        //Validate orderId
        if($order->getIncrementId() != $transactionResponse->transaction->orderid)
        {
            $message = "The posted ordernumber does not match the transaction";
            $this->setResult(Exception::HTTP_BAD_REQUEST, $message, $order->getIncrementId());
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
        try
        {
            /** @var \Magento\Sales\Model\Order */
            $order = $this->_getOrderByIncrementId($posted['orderid']);
            $payment = $order->getPayment();

            $pspReference = $payment->getAdditionalInformation(CheckoutPayment::METHOD_REFERENCE);

            if(!isset($pspReference))
            {
                $payment->setTransactionId($bamboraTransactionId);
                $payment->setIsTransactionClosed(false);
                $payment->setAdditionalInformation(array(CheckoutPayment::METHOD_REFERENCE => $bamboraTransactionId));
                $transaction = $payment->addTransaction(Transaction::TYPE_AUTH);

                $order->setState(Order::STATE_PROCESSING);
                $status = $this->_bamboraHelper->getBamboraAdvancedConfigData('order_status',$this->_getOrder()->getStoreId());
                $order->setStatus($status);

                $message = __("Payment authorization was a success.");

                $payment->addTransactionCommentsToOrder($transaction, $message);
                $payment->setCcType($transactionResponse->transaction->information->paymentTypes[0]->displayName);
                $payment->setCcNumberEnc($transactionResponse->transaction->information->primaryAccountnumbers[0]->number);
                $payment->save();

                if($this->_bamboraHelper->getBamboraCheckoutConfigData('addfeetoshipping', $order->getStoreId()) && strlen($transactionResponse->transaction->total->feeamount) > 0)
                {
                    $feeAmount = floatval($this->_bamboraHelper->convertPriceFromMinorUnits($transactionResponse->transaction->total->feeamount, $transactionResponse->transaction->currency->minorunits));

                    //Update Base amounts
                    $baseShippingAmount = $order->getBaseShippingAmount();
                    $order->setBaseShippingAmount($baseShippingAmount + $feeAmount);
                    $baseGrandTotal = $order->getBaseGrandTotal();
                    $order->setBaseGrandTotal($baseGrandTotal + $feeAmount);

                    //Update Order amounts
                    $feeAmountConverted = $order->getStore()->getBaseCurrency()->convert($feeAmount,$order->getOrderCurrencyCode());

                    $shippingAmount = $order->getShippingAmount();
                    $order->setShippingAmount($shippingAmount + $feeAmountConverted);
                    $grandTotal = $order->getGrandTotal();
                    $order->setGrandTotal($grandTotal + $feeAmountConverted);

                    $feeMessage = __("Shipping and handling fee, added to order");
                    $order->addStatusHistoryComment($feeMessage);

                    $order->save();

                }

                if (!$order->getEmailSent() && $this->_bamboraHelper->getBamboraCheckoutConfigData('sendmailorderconfirmation',$order->getStoreId()))
                {
                    $this->_orderSender->send($order);
                    $order->addStatusHistoryComment(__('Order confirmation send to custommer'))
                        ->setIsCustomerNotified(1)
                        ->save();
                }

                $order->save();

                if($this->_bamboraHelper->getBamboraCheckoutConfigData('instantinvoice', $order->getStoreId()) == 1)
                {
                    if($order->canInvoice())
                    {
                        /** @var \Magento\Sales\Model\Order\Invoice */
                        $invoice = $order->prepareInvoice();
                        $invoice->setTransactionId($bamboraTransactionId);
                        $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_OFFLINE);
                        $invoice->register();
                        $invoice->save();
                        $transactionSave = $this->_objectManager->create('Magento\Framework\DB\Transaction')
                            ->addObject($invoice)
                            ->addObject($invoice->getOrder());
                        $transactionSave->save();

                        if($this->_bamboraHelper->getBamboraCheckoutConfigData('instantinvoicemail', $order->getStoreId()) == 1)
                        {
                            $invoice->setEmailSent(1);
                            $this->_invoiceSender->send($invoice);
                            $order->addStatusHistoryComment(__('Notified customer about invoice') .' #'. $invoice->getId())
                                ->setIsCustomerNotified(1)
                                ->save();
                        }
                    }
                }
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
