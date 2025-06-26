<?php
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
