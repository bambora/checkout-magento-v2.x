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
namespace Bambora\Online\Model\Api\Checkout\Response\Models;

class TransactionOperation
{
    /**
     * @var string
     */
    public $id;

    /**
     * @var integer
     */
    public $amount;
    /**
     * @var string
     */
    public $createddate;
    /**
     * @var \Bambora\Online\Model\Api\Checkout\Response\Models\Currency
     */
    public $currency;
    /**
     * @var string
     */
    public $orderid;
    /**
     * @var string
     */
    public $status;
    /**
     * @var string
     */
    public $acquirername;
    /**
     * @string
     */
    public $currentbalance;
    /**
     * @var integer
     */
    public $action;
    /**
     * @var string
     */
    public $actioncode;
    /**
     * @var string
     */
    public $actionsource;
    /**
     * @var string
     */
    public $subaction;
    /**
     * @var \Bambora\Online\Model\Api\Checkout\Response\Models\TransactionOperation[]
     */
    public $transactionoperations;
    /**
     * @var \Bambora\Online\Model\Api\Checkout\Response\Models\PaymentType[]
     */
    public $paymenttypes;
    /**
     * @var \Bambora\Online\Model\Api\Checkout\Response\Models\Eci[]
     */
    public $eci;
    /**
     * @var \Bambora\Online\Model\Api\Checkout\Response\Models\Exemption[]
     */
    public $exemptions;
    /**
     * @var \Bambora\Online\Model\Api\Checkout\Response\Models\PrimaryAccountnumber[]
     */
    public $primaryAccountnumbers;

}
