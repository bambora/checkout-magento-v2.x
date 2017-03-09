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

class Url
{
    /**
     * @var string
     */
    public $accept;
    /**
     * @var \Bambora\Online\Model\Api\Checkout\Request\Models\Callback[]
     */
    public $callbacks;
    /**
     * @var string
     */
    public $decline;
    /**
     * @var int
     */
    public $immediateredirecttoaccept;
}
