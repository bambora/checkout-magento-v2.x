<?php
/**
 * Bambora Online
 *
 * @category    Online Payment Gatway
 * @package     Bambora_Online_Checkout
 * @author      Bambora
 * @copyright   Bambora (http://bambora.com)
 */
namespace Bambora\Online\Model\Api\Checkout\Models;

class CheckoutRequest
{
    /**
     * @var \Bambora\Online\Model\Api\Checkout\Models\Customer
     */
    public $customer;
    /**
     * @var long
     */
    public $instantcaptureamount;
    /**
     * @var string
     */
    public $language;
    /**
     * @var \Bambora\Online\Model\Api\Checkout\Models\Order
     */
    public $order;
    /**
     * @var \Bambora\Online\Model\Api\Checkout\Models\Url
     */
    public $url;
    /**
     * @var int
     */
    public $paymentwindowid;
}