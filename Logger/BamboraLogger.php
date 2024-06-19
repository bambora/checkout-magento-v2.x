<?php
/**
 * Copyright (c) 2019. All rights reserved Bambora Online.
 *
 * This program is free software. You are allowed to use the software but NOT allowed to modify the software.
 * It is also not legal to do any changes to the software and distribute it in your own name / brand.
 *
 * All use of the payment modules happens at your own risk. We offer a free test account that you can use to test the module.
 *
 * @author    Bambora Online
 * @copyright Bambora Online (https://bambora.com)
 * @license   Bambora Online
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
        $errorMessage = 'Worldline Checkout Error - ID: ' . $id . ' - ' . $reason;
        $this->addRecord(self::ERROR, $errorMessage);
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
        $errorMessage = 'Worldline Checkout Info - ID: ' . $id . ' - ' . $reason;
        $this->addRecord(self::INFO, $errorMessage);
    }

    /**
     * Add Common error to log
     *
     * @param mixed $id
     * @param mixed $reason
     * @return void
     */
    public function addCommonError($id, $reason)
    {
        $errorMessage = 'Bambora Error - ID: ' . $id . ' - ' . $reason;
        $this->addRecord(self::ERROR, $errorMessage);
    }
}
