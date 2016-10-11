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

class Callback extends \Bambora\Online\Controller\AbstractController
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
            $this->setResult(Exception::HTTP_BAD_REQUEST, $message, null);
            return false;
        }

        //Validate parameteres
        if(!$posted['orderid'] || !$posted['txnid'] || !$posted['amount'] || !$posted['currency'])
        {
            $message = "Parameteres are missing. Request: " . json_encode($posted);
            $this->setResult(Exception::HTTP_BAD_REQUEST, $message,null);
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
        $shopMd5 = $this->_bamboraHelper->getBamboraEpayConfigData('md5key', $order->getStoreId());
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
     * Process the callback from Bambora
     *
     * @return void
     */
    private function processCallback($posted)
    {
        $ePayTransactionId = $posted['txnid'];

        try
        {
            /** @var \Magento\Sales\Model\Order */
            $order = $this->_getOrderByIncrementId($posted['orderid']);
            $payment = $order->getPayment();
            $pspReference = $payment->getAdditionalInformation(EpayPayment::METHOD_REFERENCE);

            if(!isset($pspReference))
            {
                $payment->setTransactionId($ePayTransactionId);

                $instantCapture = $this->_bamboraHelper->getBamboraEpayConfigData('instantcapture',$order->getStore()->getId());
                $shouldCloseTransaction = $instantCapture ? true : false;
                $payment->setIsTransactionClosed($shouldCloseTransaction);
                $payment->setAdditionalInformation(array(EpayPayment::METHOD_REFERENCE => $ePayTransactionId));
                $transaction = $payment->addTransaction(Transaction::TYPE_AUTH);

                $order->setState(Order::STATE_PROCESSING);
                $status = $this->_bamboraHelper->getBamboraAdvancedConfigData('order_status',$this->_getOrder()->getStoreId());
                $order->setStatus($status);

                $message = __("Payment authorization was a success.");

                $payment->addTransactionCommentsToOrder($transaction, $message);

                $payment->setCcType($this->_bamboraHelper->calcCardtype($posted['paymenttype']));
                $payment->setCcNumberEnc($posted['cardno']);

                $payment->save();

                if($this->_bamboraHelper->getBamboraEpayConfigData('addfeetoshipping', $order->getStoreId()) && strlen($posted['txnfee']) > 0)
                {
                    $minorUnits = $this->_bamboraHelper->getCurrencyMinorunits($order->getOrderCurrencyCode());
                    $feeAmount = floatval($this->_bamboraHelper->convertPriceFromMinorUnits($posted['txnfee'],$minorUnits));

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

                if (!$order->getEmailSent() && $this->_bamboraHelper->getBamboraEpayConfigData('sendmailorderconfirmation', $order->getStoreId()))
                {
                    $this->_orderSender->send($order);
                    $order->addStatusHistoryComment(__('Order confirmation send to custommer'))
                        ->setIsCustomerNotified(1)
                        ->save();
                }

                $order->save();

                if($this->_bamboraHelper->getBamboraEpayConfigData('instantinvoice', $order->getStoreId())== 1)
                {
                    if($order->canInvoice())
                    {
                         /** @var \Magento\Sales\Model\Order\Invoice */
                        $invoice = $order->prepareInvoice();
                        $invoice->setTransactionId($ePayTransactionId);
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
            $this->setResult(Exception::HTTP_INTERNAL_ERROR, $message,$ePayTransactionId);
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
            $this->_bamboraLogger->addEpayInfo($id,$message);
        }
        else
        {
            $this->_bamboraLogger->addEpayError($id,$message);
        }

        $this->_callbackResult = $result;
    }
}