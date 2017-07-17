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
namespace Bambora\Online\Model\Api\Epay;

use Bambora\Online\Model\Api\Epay\ApiEndpoints;
use Bambora\Online\Model\Api\EpayApiModels;

class Action extends Base
{
    /**
     * Get Payment window url
     *
     * @param \Bambora\Online\Model\Api\Epay\Request\Payment $paymentRequest
     * @return \Bambora\Online\Model\Api\Epay\Request\Models\Url
     */
    public function getPaymentWindowUrl($paymentRequest)
    {
        $baseUrl = $this->_getEndpoint(ApiEndpoints::ENDPOINT_EPAY_INTEGRATION). '/ewindow/Default.aspx';

        /** @var \Bambora\Online\Model\Api\Epay\Request\Models\Url */
        $url = $this->_bamboraHelper->getEpayApiModel(EpayApiModels::REQUEST_MODEL_URL);

        $paramString = "?encoding=".urlencode($paymentRequest->encoding).
                       "&cms=".urlencode($paymentRequest->cms).
                       "&windowstate=".urlencode($paymentRequest->windowstate).
                       "&mobile=".urlencode($paymentRequest->mobile).
                       "&merchantnumber=".urlencode($paymentRequest->merchantnumber).
                       "&windowid=".urlencode($paymentRequest->windowid).
                       "&amount=".urlencode($paymentRequest->amount).
                       "&currency=".urlencode($paymentRequest->currency).
                       "&orderid=".urlencode($paymentRequest->orderid).
                       "&accepturl=".urlencode($paymentRequest->accepturl).
                       "&cancelurl=".urlencode($paymentRequest->cancelurl).
                       "&callbackurl=".urlencode($paymentRequest->callbackurl).
                       "&instantcapture=".urlencode($paymentRequest->instantcapture).
                       "&language=".urlencode($paymentRequest->language).
                       "&ownreceipt=".urlencode($paymentRequest->ownreceipt).
                       "&timeout=".urlencode($paymentRequest->timeout).
                       "&invoice=".urlencode($paymentRequest->invoice).
                       "&hash=".urlencode($paymentRequest->hash);

        $url->url = $baseUrl . $paramString;

        return $url;
    }

    ///**
    // * Get ePay payment window js url
    // *
    // * @return \Bambora\Online\Model\Api\Epay\Request\Models\Url
    // */
    //public function getPaymentWindowJSUrl()
    //{
    //    $result = $this->_getEndpoint(ApiEndpoints::ENDPOINT_EPAY_INTEGRATION).'/ewindow/paymentwindow.js';

    //    /** @var \Bambora\Online\Model\Api\Epay\Request\Models\Url */
    //    $url = $this->_bamboraHelper->getEpayApiModel(EpayApiModels::REQUEST_MODEL_URL);
    //    $url->url = $result;

    //    return $url;
    //}
    /**
     * Get ePay payment window js url
     *
     * @return string
     */
    public function getPaymentWindowJSUrl()
    {
        $url = $this->_getEndpoint(ApiEndpoints::ENDPOINT_EPAY_INTEGRATION).'/ewindow/paymentwindow.js';
        return $url;
    }

    /**
     * Get ePay payment logo url
     *
     * @param string $merchantNumber
     * @return string
     */
    public function getPaymentLogoUrl($merchantNumber)
    {
        $url = $this->_getEndpoint(ApiEndpoints::ENDPOINT_EPAY_INTEGRATION)."/paymentlogos/PaymentLogos.aspx?merchantnumber={$merchantNumber}&direction=2&padding=1&rows=1&logo=0&showdivs=0&iframe=1";
        return $url;
    }
    /**
     * Get ePay logo url
     *
     * @return string
     */
    public function getEpayLogoUrl()
    {
        $url = $this->_getEndpoint(ApiEndpoints::ENDPOINT_EPAY_ASSETS)."/ePay-logo.png";
        return $url;
    }

    /**
     * Capture transaction
     *
     * @param int|long $amount
     * @param string $transactionId
     * @param \Bambora\Online\Model\Api\Epay\Request\Models\Auth $auth
     * @return \Bambora\Online\Model\Api\Epay\Response\Capture
     */
    public function capture($amount, $transactionId, $auth)
    {
        try {
            $param = array(
                'merchantnumber' => $auth->merchantNumber,
                'transactionid' => $transactionId,
                'amount' => (string)$amount,
                'group' => '',
                'pbsResponse' => -1,
                'epayresponse' => -1,
                'pwd' => $auth->pwd
            );

            $url = $this->_getEndpoint(ApiEndpoints::ENDPOINT_REMOTE).'/payment.asmx?WSDL';
            $client = $this->_initSoapClient($url);

            $result = $client->capture($param);

            /** @var \Bambora\Online\Model\Api\Epay\Response\Capture */
            $captureResponse = $this->_bamboraHelper->getEpayApiModel(EpayApiModels::RESPONSE_CAPTURE);
            $captureResponse->result = $result->captureResult;
            $captureResponse->epayResponse = $result->epayresponse;
            $captureResponse->pbsResponse = $result->pbsResponse;

            return $captureResponse;
        } catch (\Exception $ex) {
            $this->_bamboraLogger->addEpayError("-1", $ex->getMessage());
            return null;
        }
    }

    /**
     * Credit transaction
     *
     * @param int|long $amount
     * @param string $transactionId
     * @param \Bambora\Online\Model\Api\Epay\Request\Models\Auth $auth
     * @return \Bambora\Online\Model\Api\Epay\Response\Credit
     */
    public function credit($amount, $transactionId, $auth)
    {
        /** @var \Bambora\Online\Model\Api\Epay\Response\Credit */
        $creditResponse = $this->_bamboraHelper->getEpayApiModel(EpayApiModels::RESPONSE_CREDIT);

        try {
            $param = array(
                'merchantnumber' => $auth->merchantNumber,
                'transactionid' => $transactionId,
                'amount' => (string)$amount,
                'group' => '',
                'pbsresponse' => -1,
                'epayresponse' => -1,
                'pwd' => $auth->pwd
            );
            $url = $this->_getEndpoint(ApiEndpoints::ENDPOINT_REMOTE).'/payment.asmx?WSDL';
            $client = $this->_initSoapClient($url);
            $result = $client->credit($param);

            $creditResponse->result = $result->creditResult;
            $creditResponse->epayResponse = $result->epayresponse;
            $creditResponse->pbsResponse = $result->pbsresponse;

            return $creditResponse;
        } catch (\Exception $ex) {
            $this->_bamboraLogger->addEpayError("-1", $ex->getMessage());
            return null;
        }
    }

    /**
     * Delete transaction
     *
     * @param string $transactionId
     * @param \Bambora\Online\Model\Api\Epay\Request\Models\Auth $auth
     * @return \Bambora\Online\Model\Api\Epay\Response\Delete
     */
    public function delete($transactionId, $auth)
    {
        try {
            $param = array(
                'merchantnumber' => $auth->merchantNumber,
                'transactionid' => $transactionId,
                'group' => '',
                'epayresponse' => -1,
                'pwd' => $auth->pwd
            );
            $url = $this->_getEndpoint(ApiEndpoints::ENDPOINT_REMOTE).'/payment.asmx?WSDL';
            $client = $this->_initSoapClient($url);
            $result = $client->delete($param);

            /** @var \Bambora\Online\Model\Api\Epay\Response\Delete */
            $deleteResponse = $this->_bamboraHelper->getEpayApiModel(EpayApiModels::RESPONSE_DELETE);
            $deleteResponse->result = $result->deleteResult;
            $deleteResponse->epayResponse = $result->epayresponse;

            return $deleteResponse;
        } catch (\Exception $ex) {
            $this->_bamboraLogger->addEpayError("-1", $ex->getMessage());
            return null;
        }
    }

    /**
     * Get Transaction
     *
     * @param string $transactionId
     * @param \Bambora\Online\Model\Api\Epay\Request\Models\Auth $auth
     * @return \Bambora\Online\Model\Api\Epay\Response\Transaction
     */
    public function getTransaction($transactionId, $auth)
    {
        try {
            $param = array(
                'merchantnumber' => $auth->merchantNumber,
                'transactionid' => $transactionId,
                'epayresponse' => -1,
                'pwd' => $auth->pwd
            );

            $url = $this->_getEndpoint(ApiEndpoints::ENDPOINT_REMOTE).'/payment.asmx?WSDL';
            $client = $this->_initSoapClient($url);

            $result = $client->gettransaction($param);

            /** @var \Bambora\Online\Model\Api\Epay\Response\Transaction */
            $getTransactionResponse = $this->_bamboraHelper->getEpayApiModel(EpayApiModels::RESPONSE_TRANSACTION);
            $getTransactionResponse->result = $result->gettransactionResult;
            $getTransactionResponse->epayResponse = $result->epayresponse;
            $getTransactionResponse->transactionInformation = $result->transactionInformation;

            return $getTransactionResponse;
        } catch (\Exception $ex) {
            $this->_bamboraLogger->addEpayError("-1", $ex->getMessage());
            return null;
        }
    }
}
