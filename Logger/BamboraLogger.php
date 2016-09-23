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
     * Add Checkout error to log
     * 
     * @param mixed $id
     * @param mixed $reason
     * @return void
     */
    public function addCheckoutError($id, $reason)
    {
        $errorMessage = 'ID: ' .$id . ' - ' . $reason;
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
        $errorMessage = 'ID: ' .$id . ' - ' . $reason;
        $this->addInfo($errorMessage);
    }
}