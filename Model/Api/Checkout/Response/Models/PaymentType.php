<?php
namespace Bambora\Online\Model\Api\Checkout\Response\Models;

class PaymentType
{
    /**
     * @var string
     */
    public $displayName;
    /**
     * @var int
     */
    public $groupid;
    /**
     * @var int
     */
    public $id;
    /**
     * @var int
     */
    public $name;
    /**
     * @var \Bambora\Online\Model\Api\Checkout\Response\Models\Fee
     */
    public $fee;
}
