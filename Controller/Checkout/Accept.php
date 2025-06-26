<?php
namespace Bambora\Online\Controller\Checkout;

class Accept extends \Bambora\Online\Controller\AbstractActionController
{
    /**
     * Accept Action
     */
    public function execute()
    {
        $this->acceptOrder();
    }
}
