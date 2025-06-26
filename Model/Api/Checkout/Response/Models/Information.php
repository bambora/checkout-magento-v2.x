<?php
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
