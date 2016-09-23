<?php
/**
 * Bambora Online
 *
 * @category    Online Payment Gatway
 * @package     Bambora_Online_Checkout
 * @author      Bambora
 * @copyright   Bambora (http://bambora.com)
 */
namespace Bambora\Online\Model\Api\Checkout;

use Bambora\Online\Model\Api\Checkout\ApiEndpoints;

class Transaction extends Base
{
    /**
     * Capture an amount for a given transaction
     * @param string $transactionid
     * @param int|long $amount
     * @param string $currency
     * @param string $apikey
     * @param \Bambora\Online\Model\Api\Checkout\Models\Orderline[]
     * @return mixed
     */
    public function capture($transactionid, $amount, $currency, $invoicelines, $apikey)
    {
        $serviceUrl = $this->_getEndpoint(ApiEndpoints::ENDPOINT_TRANSACTION) .'/transactions/'.  sprintf('%.0F',$transactionid) . '/capture';

        $data = array();
        $data["amount"] = $amount;
        $data["currency"] = $currency;
        $data["invoicelines"] = $invoicelines;

        $jsonData = json_encode($data);

        $result = $this->_callRestService($serviceUrl, $jsonData, "POST",$apikey);
        return json_decode($result,true);

    }

    /**
     * Credit an amount for a given transaction
     * @param string $transactionid
     * @param int|long $amount
     * @param string $currency
     * @param string $apikey
     * @param \Bambora\Online\Model\Api\Checkout\Models\Orderline[]
     * @return mixed
     */
    public function credit($transactionid, $amount, $currency, $invoicelines, $apikey )
    {
        $serviceUrl = $this->_getEndpoint(ApiEndpoints::ENDPOINT_TRANSACTION).'/transactions/'.  sprintf('%.0F',$transactionid) . '/credit';

        $data = array();
        $data["amount"] = $amount;
        $data["currency"] = $currency;
        $data["invoicelines"] = $invoicelines;

        $jsonData = json_encode($data);

        $result = $this->_callRestService($serviceUrl, $jsonData, "POST", $apikey);
        return json_decode($result,true);
    }

    /**
     * Detete a transaction
     * @param string $transactionid
     * @param string $apikey
     * @return mixed
     */
    public function delete($transactionid, $apikey)
    {
        $serviceUrl = $this->_getEndpoint(ApiEndpoints::ENDPOINT_TRANSACTION).'/transactions/'.  sprintf('%.0F',$transactionid) . '/delete';
        $result = $this->_callRestService($serviceUrl, null, "POST", $apikey);

        return json_decode($result,true);
    }
}