<?php
namespace Bambora\Online\Model\Api\Checkout;

use Bambora\Online\Model\Api\Checkout\ApiEndpoints;

class Assets extends Base
{
    /**
     * Get Bambora Checkout payment window js url
     *
     * @return string
     */
    public function getCheckoutWebSdkUrl()
    {
        $serviceEndpoint = $this->_getEndpoint(ApiEndpoints::ENDPOINT_CHECKOUT_CDN);
        return "{$serviceEndpoint}/checkout-sdk-web/latest/checkout-sdk-web.min.js";
    }

    /**
     * Get Checkout payment window js url
     *
     * @return string
     */
    public function getCheckoutIconUrl()
    {
        $serviceEndpoint = $this->_getEndpoint(ApiEndpoints::ENDPOINT_GLOBAL_ASSETS);
        return "{$serviceEndpoint}/worldline_icon_64x64.png";
    }
}
