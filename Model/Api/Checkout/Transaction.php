<?php
namespace Bambora\Online\Model\Api\Checkout;

use Bambora\Online\Model\Api\Checkout\ApiEndpoints;
use Bambora\Online\Model\Api\CheckoutApiModels;

class Transaction extends Base
{
    /**
     * Capture an amount for a given transaction
     *
     * @param string $transactionId
     * @param \Bambora\Online\Model\Api\Checkout\Request\Capture $captureRequest
     * @param string $apikey
     * @return \Bambora\Online\Model\Api\Checkout\Response\Capture | null
     */
    public function capture($transactionId, $captureRequest, $apikey)
    {
        try {
            $serviceEndpoint = $this->_getEndpoint(
                ApiEndpoints::ENDPOINT_TRANSACTION
            );
            $serviceUrl = "{$serviceEndpoint}/transactions/{$transactionId}/capture";
            $captureRequestJson = json_encode($captureRequest);
            $resultJson = $this->_callRestService(
                $serviceUrl,
                $captureRequestJson,
                Base::POST,
                $apikey
            );
            $result = json_decode($resultJson, true);
            $captureResponse = $this->_bamboraHelper->getCheckoutModel(
                CheckoutApiModels::RESPONSE_CAPTURE
            );
            $captureResponse->meta = $this->_mapMeta($result);
            if ($captureResponse->meta->result) {
                $captureResponse->transactionOperations = [];
                foreach ($result['transactionoperations'] as $operation) {
                    $transactionOperation = $this->_bamboraHelper->getCheckoutModel(
                        CheckoutApiModels::RESPONSE_MODEL_TRANSACTIONOPERATION
                    );
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
     *
     * @param string $transactionId
     * @param \Bambora\Online\Model\Api\Checkout\Request\Credit $creditRequest
     * @param string $apikey
     * @return \Bambora\Online\Model\Api\Checkout\Response\Credit | null
     */
    public function credit($transactionId, $creditRequest, $apikey)
    {
        try {
            $serviceEndpoint = $this->_getEndpoint(
                ApiEndpoints::ENDPOINT_TRANSACTION
            );
            $serviceUrl = "{$serviceEndpoint}/transactions/{$transactionId}/credit";
            $creditRequestJson = json_encode($creditRequest);

            $resultJson = $this->_callRestService(
                $serviceUrl,
                $creditRequestJson,
                Base::POST,
                $apikey
            );
            $result = json_decode($resultJson, true);
            $creditResponse = $this->_bamboraHelper->getCheckoutModel(
                CheckoutApiModels::RESPONSE_CREDIT
            );
            $creditResponse->meta = $this->_mapMeta($result);
            if ($creditResponse->meta->result) {
                $creditResponse->transactionOperations = [];
                foreach ($result['transactionoperations'] as $operation) {
                    $transactionOperation = $this->_bamboraHelper->getCheckoutModel(
                        CheckoutApiModels::RESPONSE_MODEL_TRANSACTIONOPERATION
                    );
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
     * Delete a transaction
     *
     * @param string $transactionId
     * @param string $apikey
     * @return \Bambora\Online\Model\Api\Checkout\Response\Delete | null
     */
    public function delete($transactionId, $apikey)
    {
        try {
            $serviceEndpoint = $this->_getEndpoint(
                ApiEndpoints::ENDPOINT_TRANSACTION
            );
            $serviceUrl = "{$serviceEndpoint}/transactions/{$transactionId}/delete";
            $resultJson = $this->_callRestService(
                $serviceUrl,
                null,
                Base::POST,
                $apikey
            );
            $result = json_decode($resultJson, true);
            $deleteResponse = $this->_bamboraHelper->getCheckoutModel(
                CheckoutApiModels::RESPONSE_DELETE
            );
            $deleteResponse->meta = $this->_mapMeta($result);
            if ($deleteResponse->meta->result) {
                $deleteResponse->transactionOperations = [];
                foreach ($result['transactionoperations'] as $operation) {
                    $transactionOperation = $this->_bamboraHelper->getCheckoutModel(
                        CheckoutApiModels::RESPONSE_MODEL_TRANSACTIONOPERATION
                    );
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
