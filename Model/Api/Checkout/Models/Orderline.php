<?php
/**
 * Bambora Online
 *
 * @category    Online Payment Gatway
 * @package     Bambora_Online_Checkout
 * @author      Bambora
 * @copyright   Bambora (http://bambora.com)
 */
namespace Bambora\Online\Model\Api\Checkout\Models;

class Orderline
{
    /**
     * @var string
     */
    public $description;
    /**
     * @var string
     */
    public $id;
    /**
     * @var string
     */
    public $linenumber;
    /**
     * @var float
     */
    public $quantity;
    /**
     * @var string
     */
    public $text;
    /**
     * @var int|long
     */
    public $totalprice;
    /**
     * @var int|long
     */
    public $totalpriceinclvat;
    /**
     * @var int|long
     */
    public $totalpricevatamount;
    /**
     * @var string
     */
    public $unit;
    /**
     * @var float
     */
    public $vat;
}