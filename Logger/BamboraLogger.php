<?php
/**
 * Bambora Online
 *
 * @category    Online Payment Gatway
 * @package     Bambora_Online_Checkout
 * @author      Bambora
 * @copyright   Bambora (http://bambora.com)
 */
namespace Bambora\Online\Logger;

use Monolog\Logger;

class BamboraLogger extends Logger
{
    /**
     * @desc Add Checkout error to log
     * @param $id
     * @param $reason
     * @return void
     */
    public function addCheckoutError($id, $reason)
    {
        $errorMessage = 'ID: ' .$id . ' - ' . $reason;
        $this->addError($errorMessage);
    }

    /**
     * @desc Add Checkout info to log
     * @param $id
     * @param $reason
     * @return void
     */
    public function addCheckoutInfo($id, $reason)
    {
        $errorMessage = 'ID: ' .$id . ' - ' . $reason;
        $this->addInfo($errorMessage);
    }



}