<?php
namespace Bambora\Online\Model\Api\Checkout\Response;

class ListPaymentTypes extends Base
{
    /**
     * @var \Bambora\Online\Model\Api\Checkout\Response\Models\PaymentCollection[]
     */
    public $paymentCollections;
}
