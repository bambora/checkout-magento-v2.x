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

class Information
{
    /**
     * @var \Bambora\Online\Model\Api\Checkout\Response\Models\Acquirer[]
     */
    public $acquirers;
    /**
     * @var \Bambora\Online\Model\Api\Checkout\Response\Models\AcquirerReference[]
     */
    public $acquirerReferences;
    /**
     * @var \Bambora\Online\Model\Api\Checkout\Response\Models\PaymentType[]
     */
    public $paymenttypes;
    /**
     * @var \Bambora\Online\Model\Api\Checkout\Response\Models\PrimaryAccountnumber[]
     */
    public $primaryAccountnumbers;
    /**
     * @var \Bambora\Online\Model\Api\Checkout\Response\Models\Eci[]
     */
    public $ecis;
    /**
     * @var \Bambora\Online\Model\Api\Checkout\Response\Models\Exemption[]
     */
    public $exemptions;

    /**
     * @var \Bambora\Online\Model\Api\Checkout\Response\Models\Wallet[]
     */
    public $wallets;

}
