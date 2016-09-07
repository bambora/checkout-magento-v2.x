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

class Assets extends Base
{
    /**
     * Get Bambora Checkout payment window js url
     *
     * return string
     */
    public function getCheckoutPaymentWindowJSUrl()
    {
        $url = $this->_getEndpoint(ApiEndpoints::ENDPOINT_ASSETS).'/paymentwindow-v1.min.js';

        return $url;
    }

    /**
     * Get Checkout payment window js url
     *
     * return string
     */
    public function getCheckoutIconUrl()
    {
        $url = 'https://d3r1pwhfz7unl9.cloudfront.net/bambora/bambora_icon_64x64.png';

        return $url;
    }
}