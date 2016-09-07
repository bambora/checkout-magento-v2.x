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

class Merchant extends Base
{
    /**
     * Get the allowed payment types
     * 
     * @param string $currency 
     * @param int|long $amount 
     * @param string $apiKey 
     * @return mixed
     */
    public function getPaymentTypes($currency, $amount, $apiKey)
    {
        $serviceUrl = $this->_getEndpoint(ApiEndpoints::ENDPOINT_MERCHANT) . '/paymenttypes?currency='. $currency . '&amount=' . $amount;

        $result = $this->_callRestService($serviceUrl, null, "GET", $apiKey);

        return json_decode($result, true);
    }

    /**
     * Returns a transaction based on the id
     * 
     * @param int|long $transactionid 
     * @param string $apiKey 
     * @return mixed
     */
    public function getTransaction($transactionid, $apiKey)
	{
        $serviceUrl = $this->_getEndpoint(ApiEndpoints::ENDPOINT_MERCHANT) . '/transactions/' . sprintf('%.0F', $transactionid);

        $result = $this->_callRestService($serviceUrl, null, "GET", $apiKey);

        return json_decode($result, true);
	}
}