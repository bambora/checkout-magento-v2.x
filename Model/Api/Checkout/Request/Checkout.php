<?php
namespace Bambora\Online\Model\Api\Checkout\Request;

class Checkout
{
    /**
     * @var \Bambora\Online\Model\Api\Checkout\Request\Models\Customer
     */
    public $customer;
    /**
     * @var int
     */
    public $instantcaptureamount;
    /**
     * @var string
     */
    public $language;
    /**
     * @var \Bambora\Online\Model\Api\Checkout\Request\Models\Order
     */
    public $order;
    /**
     * @var \Bambora\Online\Model\Api\Checkout\Request\Models\Url
     */
    public $url;
    /**
     * @var int
     */
    public $paymentwindowid;
    /**
     * @var string
     */
    public $securityexemption;
    /**
     * @var string
     */
    public $securitylevel;
}
