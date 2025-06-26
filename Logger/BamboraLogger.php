<?php
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
        $errorMessage = "Worldline Checkout Error - ID: {$id} - {$reason}";
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
        $errorMessage = "Worldline Checkout Info - ID: {$id} - {$reason}";
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
        $errorMessage = "Worldline Error - ID: {$id} - {$reason}";
        $this->addRecord(self::ERROR, $errorMessage);
    }
}
