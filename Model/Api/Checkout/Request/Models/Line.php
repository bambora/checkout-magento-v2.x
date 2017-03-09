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
