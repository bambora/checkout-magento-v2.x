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
namespace Bambora\Online\Controller\Checkout;

use \Magento\Framework\Webapi\Exception;
use \Magento\Framework\Webapi\Response;
use \Bambora\Online\Model\Api\CheckoutApi;
use \Bambora\Online\Model\Method\Checkout\Payment as CheckoutPayment;
use \Bambora\Online\Model\Api\CheckoutApiModels;
use \Bambora\Online\Helper\BamboraConstants;

class Callback extends \Bambora\Online\Controller\AbstractActionController
{
    /**
     * @var \Magento\Framework\Controller\Result\Json
     */
    protected $_callbackResult;

    /**
     * Callback Action
     */
    public function execute()
    {
        $posted = $this->getRequest()->getParams();

        /** @var \Magento\Sales\Model\Order */
        $order = null;
        $transactionResponse = null;
        $message = "Callback Failed: ";
        $responseCode = Exception::HTTP_BAD_REQUEST;
        if ($this->validateCallback($posted, $transactionResponse, $order, $message)) {
            $message = $this->processCallback($transactionResponse, $order, $responseCode);
        }

        $id = isset($order) ? $order->getIncrementId() : 0;
        $callBackResult = $this->_createCallbackResult($responseCode, $message, $id);
        if ($responseCode !== Response::HTTP_OK) {
            $this->_logError(CheckoutPayment::METHOD_CODE, $id, $message);
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
     * @param \Bambora\Online\Model\Api\Checkout\Response\Transaction $transactionResponse
     * @param \Magento\Sales\Model\Order $order
     * @param string $message
     * @return bool
     */
    protected function validateCallback($posted, &$transactionResponse, &$order, &$message)
    {
        //Validate response
        if (!isset($posted) || !$posted['txnid']) {
            $message .= isset($posted) ? "TransactionId is missing" : "Response is null";
            return false;
        }

        //Validate Order
        $order = $this->_getOrderByIncrementId($posted['orderid']);
        if (!isset($order)) {
            $message .= "The Order could be found or created";
            return false;
        }

        $payment = $order->getPayment();
        //Validate Payment
        if (!isset($payment)) {
            $message .= "The Payment object is null";
            return false;
        }

        //Validate MD5
        $shopMd5 = $this->_bamboraHelper->getBamboraCheckoutConfigData(BamboraConstants::MD5_KEY, $order->getStoreId());
        $var = "";
        if (strlen($shopMd5) > 0) {
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

        //Validate Transaction
        $transactionId = $posted['txnid'];
        $apiKey = $this->_bamboraHelper->generateCheckoutApiKey($order->getStoreId());

        /** @var \Bambora\Online\Model\Api\Checkout\Merchant */
        $merchantApi = $this->_bamboraHelper->getCheckoutApi(CheckoutApi::API_MERCHANT);

        $transactionResponse = $merchantApi->getTransaction($transactionId, $apiKey);

        //Validate transaction
        if (!$this->_bamboraHelper->validateCheckoutApiResult($transactionResponse, $order->getIncrementId(), true, $message)) {
            return false;
        }

        //Validate orderId
        if ($order->getIncrementId() != $transactionResponse->transaction->orderid) {
            $message .= "The posted ordernumber does not match the transaction";
            return false;
        }

        return true;
    }

    /**
     * Process the callback from Bambora
     *
     * @param \Bambora\Online\Model\Api\Checkout\Response\Transaction $transactionResponse
     * @param \Magento\Sales\Model\Order $order
     * @param int $responseCode
     * @return void
     */
    protected function processCallback($transactionResponse, $order, &$responseCode)
    {
        $bamboraTransactionId = $transactionResponse->transaction->id;
        $payment = $order->getPayment();

        try {
            $pspReference = $payment->getAdditionalInformation(CheckoutPayment::METHOD_REFERENCE);
            if (empty($pspReference)) {
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
                    $this->_bamboraHelper->getBamboraCheckoutConfigData(BamboraConstants::ORDER_STATUS),
                    $payment
                );

                $message = "Callback Success - Order created";
            } else {
                $message = "Callback Success - Order already created";
            }
            $responseCode = Response::HTTP_OK;
        } catch (\Exception $ex) {
            $order->setState(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT);
            $order->setStatus(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT);
            $payment->setAdditionalInformation(array(CheckoutPayment::METHOD_REFERENCE => ""));
            $payment->save();
            $order->save();

            $message = "Callback Failed - " .$ex->getMessage();
            $responseCode = Exception::HTTP_INTERNAL_ERROR;
        }

        return $message;
    }
}
