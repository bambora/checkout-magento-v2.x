<?php
namespace Bambora\Online\Model\Api\Checkout\Request\Models;

class Line
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
     * @var int
     */
    public $totalprice;
    /**
     * @var int
     */
    public $totalpriceinclvat;
    /**
     * @var int
     */
    public $totalpricevatamount;
    /**
     * @var int
     */
    public $unitprice;
    /**
     * @var int
     */
    public $unitpriceinclvat;
    /**
     * @var int
     */
    public $unitpricevatamount;
    /**
     * @var string
     */
    public $unit;
    /**
     * @var float
     */
    public $vat;
}
