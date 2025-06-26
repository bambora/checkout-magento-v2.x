<?php
namespace Bambora\Online\Model\Api\Checkout;

use Bambora\Online\Model\Api\Checkout\ApiEndpoints;
use Bambora\Online\Model\Api\CheckoutApiModels;

class Checkout extends Base
{
    /**
     * Create the checkout request
     *
     * @param \Bambora\Online\Model\Api\Checkout\Request\Checkout $setcheckoutrequest
     * @param string $apiKey
     * @return \Bambora\Online\Model\Api\Checkout\Response\Checkout | null
     */
    public function setCheckout($setcheckoutrequest, $apiKey)
    {
        try {
            $serviceEndpoint = $this->_getEndpoint(ApiEndpoints::ENDPOINT_CHECKOUT);
            $serviceUrl = "{$serviceEndpoint}/checkout";
            $jsonData = json_encode($setcheckoutrequest);
            $checkoutResponseJson = $this->_callRestService(
                $serviceUrl,
                $jsonData,
                Base::POST,
                $apiKey
            );
            $checkoutResponseArray = json_decode($checkoutResponseJson, true);
            $checkoutResponse = $this->_bamboraHelper->getCheckoutModel(
                CheckoutApiModels::RESPONSE_CHECKOUT
            );
            $checkoutResponse->meta = $this->_mapMeta($checkoutResponseArray);
            if ($checkoutResponse->meta->result) {
                $checkoutResponse->token = $checkoutResponseArray['token'];
                $checkoutResponse->url = $checkoutResponseArray['url'];
            }

            return $checkoutResponse;
        } catch (\Exception $ex) {
            $this->_bamboraLogger->addCheckoutError("-1", $ex->getMessage());
            return null;
        }
    }
}
