<?php
/**
 * 888                             888
 * 888                             888
 * 88888b.   8888b.  88888b.d88b.  88888b.   .d88b.  888d888  8888b.
 * 888 "88b     "88b 888 "888 "88b 888 "88b d88""88b 888P"       "88b
 * 888  888 .d888888 888  888  888 888  888 888  888 888     .d888888
 * 888 d88P 888  888 888  888  888 888 d88P Y88..88P 888     888  888
 * 88888P"  "Y888888 888  888  888 88888P"   "Y88P"  888     "Y888888
 *
 * @category    Online Payment Gatway
 * @package     Bambora_Online
 * @author      Bambora Online
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