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
namespace Bambora\Online\Logger;

use Monolog\Logger;

class BamboraLogger extends Logger
{
    /**
     * Add Checkout error to log
     *
     * @param mixed $id
     * @param mixed $reason
     * @return void
     */
    public function addCheckoutError($id, $reason)
    {
        $errorMessage = 'Bambora Checkout Error - ID: ' .$id . ' - ' . $reason;
        $this->addError($errorMessage);
    }

    /**
     * Add Checkout info to log
     *
     * @param mixed $id
     * @param mixed $reason
     * @return void
     */
    public function addCheckoutInfo($id, $reason)
    {
        $errorMessage = 'Bambora Checkout Info - ID: ' .$id . ' - ' . $reason;
        $this->addInfo($errorMessage);
    }

    /**
     * Add ePay error to log
     *
     * @param mixed $id
     * @param mixed $reason
     * @return void
     */
    public function addEpayError($id, $reason)
    {
        $errorMessage = 'Bambora ePay Error - ID: ' .$id . ' - ' . $reason;
        $this->addError($errorMessage);
    }

    /**
     * Add ePay info to log
     *
     * @param mixed $id
     * @param mixed $reason
     * @return void
     */
    public function addEpayInfo($id, $reason)
    {
        $errorMessage = 'Bambora ePay Info - ID: ' .$id . ' - ' . $reason;
        $this->addInfo($errorMessage);
    }
}
