<?php
/**
 * 888                             888
 * 888                             888
 * 88888b.   8888b.  88888b.d88b.  88888b.   .d88b.  888d888  8888b.
 * 888 "88b     "88b 888 "888 "88b 888 "88b d88""88b 888P"       "88b
 * 888  888 .d888888 888  888  888 888  888 888  888 888     .d888888
 * 888 d88P 888  888 888  888  888 888 d88P Y88..88P 888     888  888
 * 88888P"  "Y888888 888  888  888 88888P"   "Y88P"  888     "Y888888
 *
 * @category    Online Payment Gatway
 * @package     Bambora_Online
 * @author      Bambora Online
 * @copyright   Bambora (http://bambora.com)
 */
namespace Bambora\Online\Model\Api\Epay\Response\Models;

class TransactionInformationType
{
    /**
     * @var string
     */
    public $group;

    /**
     * @var int
     */
    public $authamount;
    /**
     * @var int
     */
    public $currency;
    /**
     * @var int
     */
    public $cardTypeid;

    /**
     * @var int
     */
    public $capturedamount;

    /**
     * @var int
     */
    public $creditedamount;

    /**
     * @var string
     */
    public $orderid;

    /**
     * @var string
     */
    public $description;

    /**
     * @var \dateTime
     */
    public $authdate;

    /**
     * @var \dateTime
     */
    public $captureddate;

    /**
     * @var \dateTime
     */
    public $deleteddate;

    /**
     * @var \dateTime
     */
    public $crediteddate;

    /**
     * @var string
     */
    public $status;

    /**
     * @var \Bambora\Online\Model\Api\Epay\Response\Models\TransactionHistoryInfo
     */
    public $history;

    /**
     * @var long
     */
    public $transactionid;

    /**
     * @var string
     */
    public $cardholder;

    /**
     * @var string
     */
    public $mode;

    /**
     * @var bool
     */
    public $msc;

    /**
     * @var int
     */
    public $fraudStatus;

    /**
     * @var string
     */
    public $FraudMessage;

    /**
     * @var string
     */
    public $payerCountryCode;

    /**
     * @var string
     */
    public $issuedCountryCode;

    /**
     * @var int
     */
    public $fee;

    /**
     * @var bool
     */
    public $splitpayment;

    /**
     * @var string
     */
    public $acquirer;

    /**
     * @var string
     */
    public $truncatedcardnumber;

    /**
     * @var int
     */
    public $expmonth;

    /**
     * @var int
     */
    public $expyear;
}