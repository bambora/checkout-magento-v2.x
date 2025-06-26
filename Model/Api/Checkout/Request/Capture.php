<?php
namespace Bambora\Online\Model\Api\Checkout\Request;

class Capture
{
    /**
     * @var int
     */
    public $amount;
    /**
     * @var string
     */
    public $currency;
    /**
     * @var \Bambora\Online\Model\Api\Checkout\Request\Models\Line[]
     */
    public $invoicelines;
}
