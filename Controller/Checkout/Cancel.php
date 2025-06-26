<?php
namespace Bambora\Online\Controller\Checkout;

class Cancel extends \Bambora\Online\Controller\AbstractActionController
{
    /**
     * Cancel Action
     */
    public function execute()
    {
        $this->cancelOrder();
    }
}
