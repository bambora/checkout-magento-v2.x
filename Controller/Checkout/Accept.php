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
     * Accept Action
     */
    public function execute()
    {
        $posted = $this->getRequest()->getParams();
        $order = $this->_getOrderByIncrementId($posted['orderid']);

        $this->_checkoutSession->setLastOrderId($order->getId());
        $this->_checkoutSession->setLastRealOrderId($order->getIncrementId());
        $this->_checkoutSession->setLastQuoteId($order->getQuoteId());
        $this->_checkoutSession->setLastSuccessQuoteId($order->getQuoteId());

        $this->_redirect('checkout/onepage/success');
    }
}