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

class Checkout extends Base
{
    /**
     * Sends the checkout request
     * 
     * @param \Bambora\Online\Model\Api\Checkout\Models\CheckoutRequest $setcheckoutrequest 
     * @param string $apiKey 
     * @return mixed
     */
    public function setCheckout($setcheckoutrequest, $apiKey)
    {
        $serviceUrl = $this->_getEndpoint(ApiEndpoints::ENDPOINT_CHECKOUT) . '/checkout';
        $jsonData = json_encode($setcheckoutrequest);
        $checkoutresponse = $this->_callRestService($serviceUrl, $jsonData, "POST", $apiKey);

        return json_decode($checkoutresponse, true);
    }
}