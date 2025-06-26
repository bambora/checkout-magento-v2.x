<?php
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
     * @var string
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
     * @var string
     */
    public $parenttransactionoperationid;
    /**
     * @var \Bambora\Online\Model\Api\Checkout\Response\Models\TransactionOperation[]
     */
    public $transactionoperations;
    /**
     * @var \Bambora\Online\Model\Api\Checkout\Response\Models\PaymentType[]
     */
    public $paymenttypes;
    /**
     * @var \Bambora\Online\Model\Api\Checkout\Response\Models\Eci
     */
    public $eci;
    /**
     * @var \Bambora\Online\Model\Api\Checkout\Response\Models\Eci[]
     */
    public $ecis;
    /**
     * @var \Bambora\Online\Model\Api\Checkout\Response\Models\Exemption[]
     */
    public $exemptions;
    /**
     * @var \Bambora\Online\Model\Api\Checkout\Response\Models\PrimaryAccountnumber[]
     */
    public $primaryAccountnumbers;
}
