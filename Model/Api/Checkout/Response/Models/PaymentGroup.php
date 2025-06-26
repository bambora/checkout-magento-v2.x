<?php
namespace Bambora\Online\Model\Api\Checkout\Response\Models;

class PaymentGroup
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
     * @var \Bambora\Online\Model\Api\Checkout\Response\Models\PaymentType[]
     */
    public $paymenttypes;
}
