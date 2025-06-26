<?php
namespace Bambora\Online\Model\Api\Checkout\Response\Models;

class Total
{
    /**
     * @var int
     */
    public $authorized;
    /**
     * @var int
     */
    public $balance;
    /**
     * @var int
     */
    public $captured;
    /**
     * @var int
     */
    public $credited;
    /**
     * @var int
     */
    public $declined;
    /**
     * @var int
     */
    public $feeamount;
}
