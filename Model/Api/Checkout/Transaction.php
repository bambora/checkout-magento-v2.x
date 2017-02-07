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
