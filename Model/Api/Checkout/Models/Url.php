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

class Url
{
    /**
     * @var string
     */
    public $accept;
    /**
     * @var \Bambora\Online\Model\Api\Checkout\Models\Callback
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