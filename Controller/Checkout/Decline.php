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

use \Magento\Sales\Model\Order;

class Decline extends AbstractCheckout
{
    /**
     * Decline Action
     */
    public function execute()
    {
        $this->cancelCurrentOrder();
        $this->restoreQuote();
        $this->_redirect('checkout', ['_fragment' => 'payment']);
    }

    /**
     * Cancel last placed order with specified comment message
     * @return bool
     */
    public function cancelCurrentOrder()
    {
        $order = $this->_getOrder();
        if ($order->getId() && $order->getState() != Order::STATE_CANCELED) {
            $comment =  __("The order was canceled");
            $this->_bamboraLogger->addCheckoutInfo($order->getIncrementId(),$comment);
            $order->registerCancellation($comment)->save();

            return true;
        }

        return false;
    }

    /**
     * Restores quote
     * @return bool
     */
    public function restoreQuote()
    {
        return $this->_checkoutSession->restoreQuote();
    }
}