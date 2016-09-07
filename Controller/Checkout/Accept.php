<?php
/**
 * Bambora Online
 *
 * @category    Online Payment Gatway
 * @package     Bambora_Online_Checkout
 * @author      Bambora
 * @copyright   Bambora (http://bambora.com)
 */
namespace Bambora\Online\Controller\Checkout;

class Accept extends AbstractCheckout
{
    /**
     * @desc Accept Action
     */
    public function execute()
    {
        $this->_redirect('checkout/onepage/success');
    }
}