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

namespace Bambora\Online\Controller\Epay;

use Bambora\Online\Model\Method\Epay\Payment as EpayPayment;
use Bambora\Online\Helper\BamboraConstants;
use Magento\Framework\Webapi\Exception;
use Magento\Framework\Webapi\Response;

class Callback extends \Bambora\Online\Controller\AbstractActionController
{
    /**
     * Callback Action
     */
    public function execute()
    {
        $posted = $this->getRequest()->getParams();
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
    public function validateCallback($posted, &$order, &$message)
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
        $shopMd5 = $this->_bamboraHelper->getBamboraEpayConfigData(
            BamboraConstants::MD5_KEY,
            $order->getStoreId()
        );
        if (!empty($shopMd5)) {
            $var = "";

            foreach ($posted as $key => $value) {
                if ($key !== "hash") {
                    $var .= $value;
                }
            }

            $genstamp = $this->_bamboraHelper->getHashFromString($var . $shopMd5);
            if ($genstamp != $posted["hash"]) {
                $message .= "Bambora MD5 check failed";
                return false;
            }
        }

        return true;
    }

    /**
     * Process the callback from Bambora
     *
     * @param mixed $posted
     * @param \Magento\Sales\Model\Order $order
     * @param int $responseCode
     * @return void
     */
    public function processCallback($posted, $order, &$responseCode)
    {
        $ePayTransactionId = $posted['txnid'];
        $payment = $order->getPayment();

        try {
            $pspReference = $payment->getAdditionalInformation(
                EpayPayment::METHOD_REFERENCE
            );
            if (empty($pspReference)) {
                $paymentMethod = $this->_getPaymentMethodInstance(
                    $order->getPayment()->getMethod()
                );
                $currency = $this->_bamboraHelper->convertIsoCode(
                    $posted['currency'],
                    false
                );
                $minorUnits = $this->_bamboraHelper->getCurrencyMinorunits(
                    $currency
                );
                $isInstantCapture = intval(
                    $this->_bamboraHelper->getBamboraEpayConfigData(
                        BamboraConstants::INSTANT_CAPTURE,
                        $order->getStoreId()
                    )
                ) === 1 ? true : false;
                $paymentType = array_key_exists(
                    'paymenttype',
                    $posted
                ) ? $posted['paymenttype'] : "";
                $cardNumber = array_key_exists(
                    'cardno',
                    $posted
                ) ? $posted['cardno'] : "";
                $txnFee = array_key_exists(
                    'txnfee',
                    $posted
                ) ? $posted['txnfee'] : 0;
                $fraud = array_key_exists('fraud', $posted) ? $posted['fraud'] : 0;

                $this->_processCallbackData(
                    $order,
                    $paymentMethod,
                    $ePayTransactionId,
                    EpayPayment::METHOD_REFERENCE,
                    $paymentType,
                    $cardNumber,
                    $txnFee,
                    $minorUnits,
                    $this->_bamboraHelper->getBamboraEpayConfigData(
                        BamboraConstants::ORDER_STATUS
                    ),
                    $isInstantCapture,
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
            $payment->setAdditionalInformation([EpayPayment::METHOD_REFERENCE => ""]
            );
            $payment->save();
            $order->save();

            $message = "Callback Failed - " . $ex->getMessage();
            $responseCode = Exception::HTTP_INTERNAL_ERROR;
        }

        return $message;
    }
}