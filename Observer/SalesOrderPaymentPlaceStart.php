<?php
/**
 * Bambora Online
 *
 * @category    Online Payment Gatway
 * @package     Bambora_Online_Checkout
 * @author      Bambora
 * @copyright   Bambora (http://bambora.com)
 */
namespace Bambora\Online\Observer;

use \Magento\Framework\Event\Observer;
use \Bambora\Online\Model\Method\Checkout\Payment as CheckoutPayment;

class SalesOrderPaymentPlaceStart implements \Magento\Framework\Event\ObserverInterface
{
    public function execute(Observer $observer)
    {
        $payment = $observer['payment'];
        if ($payment->getMethod() == CheckoutPayment::METHOD_CODE) {
            $order = $payment->getOrder();
            $order->setCanSendNewEmailFlag(false);
            $order->setIsCustomerNotified(false);
            $order->save();
        }
    }
}