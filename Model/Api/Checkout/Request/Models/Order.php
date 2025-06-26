<?php
namespace Bambora\Online\Model\Api\Checkout\Request\Models;

class Order
{
    /**
     * @var \Bambora\Online\Model\Api\Checkout\Request\Models\Address
     */
    public $billingaddress;
    /**
     * @var string
     */
    public $currency;
    /**
     * @var \Bambora\Online\Model\Api\Checkout\Request\Models\Line[]
     */
    public $lines;
    /**
     * @var string
     */
    public $ordernumber;
    /**
     * @var \Bambora\Online\Model\Api\Checkout\Request\Models\Address
     */
    public $shippingaddress;
    /**
     * @var int
     */
    public $total;
    /**
     * @var int
     */
    public $vatamount;
}
