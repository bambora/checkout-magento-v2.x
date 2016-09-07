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

class Order
{
    /**
     * @var \Bambora\Online\Model\Api\Checkout\Models\Address
     */
    public $billingaddress;
    /**
     * @var string
     */
    public $currency;
    /**
     * @var \Bambora\Online\Model\Api\Checkout\Models\Orderline
     */
    public $lines;
    /**
     * @var string
     */
    public $ordernumber;
    /**
     * @var \Bambora\Online\Model\Api\Checkout\Models\Address
     */
    public $shippingaddress;
    /**
     * @var long
     */
    public $total;
    /**
     * @var long
     */
    public $vatamount;
}