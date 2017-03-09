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
namespace Bambora\Online\Model\Api\Epay\Request;

class Payment
{
    /**
     * @var string
     */
    public $encoding;

    /**
     * @var string
     */
    public $cms;

    /**
     * @var string
     */
    public $windowState;

    /**
     * @var string
     */
    public $mobile;

    /**
     * @var string
     */
    public $merchantNumber;

    /**
     * @var string
     */
    public $windowId;

    /**
     * @var string
     */
    public $amount;

    /**
     * @var string
     */
    public $currency;

    /**
     * @var string
     */
    public $orderId;

    /**
     * @var string
     */
    public $acceptUrl;

    /**
     * @var string
     */
    public $cancelUrl;

    /**
     * @var string
     */
    public $callbackUrl;

    /**
     * @var string
     */
    public $instantCapture;

    /**
     * @var string
     */
    public $group;

    /**
     * @var string
     */
    public $language;

    /**
     * @var string
     */
    public $ownReceipt;

    /**
     * @var string
     */
    public $timeout;

    /**
     * @var string
     */
    public $invoice;

    /**
     * @var string
     */
    public $hash;
}
