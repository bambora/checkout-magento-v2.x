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
    public $windowstate;

    /**
     * @var string
     */
    public $mobile;

    /**
     * @var string
     */
    public $merchantnumber;

    /**
     * @var string
     */
    public $windowid;

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
    public $orderid;

    /**
     * @var string
     */
    public $accepturl;

    /**
     * @var string
     */
    public $cancelurl;

    /**
     * @var string
     */
    public $callbackurl;

    /**
     * @var string
     */
    public $instantcapture;

    /**
     * @var string
     */
    public $language;

    /**
     * @var string
     */
    public $ownreceipt;

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
