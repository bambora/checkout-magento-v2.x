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