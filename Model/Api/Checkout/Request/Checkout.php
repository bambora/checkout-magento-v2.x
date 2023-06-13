<?php
/**
 * Copyright (c) 2019. All rights reserved Bambora Online.
 *
 * This program is free software. You are allowed to use the software but NOT allowed to modify the software.
 * It is also not legal to do any changes to the software and distribute it in your own name / brand.
 *
 * All use of the payment modules happens at your own risk. We offer a free test account that you can use to test the module.
 *
 * @author    Bambora Online
 * @copyright Bambora Online (https://bambora.com)
 * @license   Bambora Online
 */

namespace Bambora\Online\Model\Api\Checkout\Request;

class Checkout
{
    /**
     * @var \Bambora\Online\Model\Api\Checkout\Request\Models\Customer
     */
    public $customer;
    /**
     * @var long
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
