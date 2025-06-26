<?php
namespace Bambora\Online\Model\Api\Checkout\Response\Models;

class PaymentCollection
{
    /**
     * @var string
     */
    public $displayName;
    /**
     * @var int
     */
    public $id;
    /**
     * @var string
     */
    public $name;
    /**
     * @var \Bambora\Online\Model\Api\Checkout\Response\Models\PaymentGroup[]
     */
    public $paymentGroups;
}
