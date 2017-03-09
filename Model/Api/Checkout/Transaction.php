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
namespace Bambora\Online\Model\Api\Checkout;

use \Bambora\Online\Model\Api\Checkout\ApiEndpoints;
use \Bambora\Online\Model\Api\CheckoutApiModels;

class Transaction extends Base
{
    /**
     * Capture an amount for a given transaction
     * @param string $transactionId
     * @param \Bambora\Online\Model\Api\Checkout\Request\Capture $captureRequest
     * @param string $apikey
     * @return \Bambora\Online\Model\Api\Checkout\Response\Capture
     */
    public function capture($transactionId, $captureRequest, $apikey)
    {
        try {
            $serviceUrl = $this->_getEndpoint(ApiEndpoints::ENDPOINT_TRANSACTION) .'/transactions/'.  sprintf('%.0F', $transactionId) . '/capture';
            $captureRequestJson = json_encode($captureRequest);

            $resultJson = $this->_callRestService($serviceUrl, $captureRequestJson, Base::POST, $apikey);
            $result = json_decode($resultJson, true);

            /** @var \Bambora\Online\Model\Api\Checkout\Response\Capture */
            $captureResponse = $this->_bamboraHelper->getCheckoutApiModel(CheckoutApiModels::RESPONSE_CAPTURE);
            $captureResponse->meta = $this->_mapMeta($result);

            if ($captureResponse->meta->result) {
                $captureResponse->transactionOperations = array();
                foreach ($result['transactionoperations'] as $operation) {
                    /** @var \Bambora\Online\Model\Api\Checkout\Response\Models\TransactionOperation */
                    $transactionOperation = $this->_bamboraHelper->getCheckoutApiModel(CheckoutApiModels::RESPONSE_MODEL_TRANSACTIONOPERATION);
                    $transactionOperation->id = $operation['id'];
                    $captureResponse->transactionOperations[] = $transactionOperation;
                }
            }

            return $captureResponse;
        } catch (\Exception $ex) {
            $this->_bamboraLogger->addCheckoutError("-1", $ex->getMessage());
            return null;
        }
    }

    /**
     * Credit an amount for a given transaction
     * @param string $transactionId
     * @param \Bambora\Online\Model\Api\Checkout\Request\Credit $creditRequest
     * @param string $apikey
     * @return \Bambora\Online\Model\Api\Checkout\Response\Credit
     */
    public function credit($transactionId, $creditRequest, $apikey)
    {
        try {
            $serviceUrl = $this->_getEndpoint(ApiEndpoints::ENDPOINT_TRANSACTION).'/transactions/'.  sprintf('%.0F', $transactionId) . '/credit';
            $creditRequestJson = json_encode($creditRequest);

            $resultJson = $this->_callRestService($serviceUrl, $creditRequestJson, Base::POST, $apikey);
            $result = json_decode($resultJson, true);

            /** @var \Bambora\Online\Model\Api\Checkout\Response\Credit */
            $creditResponse = $this->_bamboraHelper->getCheckoutApiModel(CheckoutApiModels::RESPONSE_CREDIT);
            $creditResponse->meta = $this->_mapMeta($result);

            if ($creditResponse->meta->result) {
                $creditResponse->transactionOperations = array();
                foreach ($result['transactionoperations'] as $operation) {
                    /** @var \Bambora\Online\Model\Api\Checkout\Response\Models\TransactionOperation */
                    $transactionOperation = $this->_bamboraHelper->getCheckoutApiModel(CheckoutApiModels::RESPONSE_MODEL_TRANSACTIONOPERATION);
                    $transactionOperation->id = $operation['id'];
                    $creditResponse->transactionOperations[] = $transactionOperation;
                }
            }

            return $creditResponse;
        } catch (\Exception $ex) {
            $this->_bamboraLogger->addCheckoutError("-1", $ex->getMessage());
            return null;
        }
    }

    /**
     * Detete a transaction
     *
     * @param string $transactionId
     * @param string $apikey
     * @return \Bambora\Online\Model\Api\Checkout\Response\Delete
     */
    public function delete($transactionId, $apikey)
    {
        try {
            $serviceUrl = $this->_getEndpoint(ApiEndpoints::ENDPOINT_TRANSACTION).'/transactions/'.  sprintf('%.0F', $transactionId) . '/delete';
            $resultJson = $this->_callRestService($serviceUrl, null, Base::POST, $apikey);
            $result = json_decode($resultJson, true);

            /** @var \Bambora\Online\Model\Api\Checkout\Response\Delete */
            $deleteResponse = $this->_bamboraHelper->getCheckoutApiModel(CheckoutApiModels::RESPONSE_DELETE);
            $deleteResponse->meta = $this->_mapMeta($result);

            if ($deleteResponse->meta->result) {
                $deleteResponse->transactionOperations = array();
                foreach ($result['transactionoperations'] as $operation) {
                    /** @var \Bambora\Online\Model\Api\Checkout\Response\Models\TransactionOperation */
                    $transactionOperation = $this->_bamboraHelper->getCheckoutApiModel(CheckoutApiModels::RESPONSE_MODEL_TRANSACTIONOPERATION);
                    $transactionOperation->id = $operation['id'];
                    $deleteResponse->transactionOperations[] = $transactionOperation;
                }
            }

            return $deleteResponse;
        } catch (\Exception $ex) {
            $this->_bamboraLogger->addCheckoutError("-1", $ex->getMessage());
            return null;
        }
    }
}
