<?php
namespace Bambora\Online\Model\Api\Checkout\Response\Models;

class Transaction
{
    /**
     * @var \Bambora\Online\Model\Api\Checkout\Response\Models\Available
     */
    public $available;
    /**
     * @var bool
     */
    public $canDelete;
    /**
     * @var string
     */
    public $createdDate;
    /**
     * @var \Bambora\Online\Model\Api\Checkout\Response\Models\Currency
     */
    public $currency;
    /**
     * @var string
     */
    public $id;
    /**
     * @var \Bambora\Online\Model\Api\Checkout\Response\Models\Information
     */
    public $information;
    /**
     * @var \Bambora\Online\Model\Api\Checkout\Response\Models\Links
     */
    public $links;
    /**
     * @var string
     */
    public $merchantnumber;
    /**
     * @var string
     */
    public $orderid;
    /**
     * @var string
     */
    public $reference;
    /**
     * @var string
     */
    public $status;
    /**
     * @var \Bambora\Online\Model\Api\Checkout\Response\Models\Subscription
     */
    public $subscription;
    /**
     * @var \Bambora\Online\Model\Api\Checkout\Response\Models\Total
     */
    public $total;
}
