<?php
namespace Bambora\Online\Observer;

use Bambora\Online\Model\Method\Checkout\Payment as CheckoutPayment;

class SalesOrderPaymentPlaceStart implements
    \Magento\Framework\Event\ObserverInterface
{
    /**
     * Sales Order Payment Place Start Observer
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $payment = $observer->getEvent()->getPayment();
        if (isset($payment) && $payment->getMethod() === CheckoutPayment::METHOD_CODE) {
            $order = $payment->getOrder();
            $order->setCanSendNewEmailFlag(false);
            $order->setIsCustomerNotified(false);
            $order->save();
        }
    }
}
