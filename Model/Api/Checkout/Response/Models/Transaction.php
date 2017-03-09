<?php
/**
 * Copyright (c) 2017. All rights reserved Bambora Online.
 *
 * This program is free software. You are allowed to use the software but NOT allowed to modify the software.
 * It is also not legal to do any changes to the software and distribute it in your own name / brand.
 *
 * All use of the payment modules happens at your own risk. We offer a free test account that you can use to test the module.
 *
 * @author    Bambora Online
 * @copyright Bambora Online (http://bambora.com)
 * @license   Bambora Online
 *
 */
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
