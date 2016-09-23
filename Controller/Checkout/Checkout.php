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
use \Bambora\Online\Model\Method\Checkout\Payment as CheckoutPayment;

class Checkout extends AbstractCheckout
{
    /**
     * Checkout Action
     */
    public function execute()
    {
        $order = $this->_getOrder();

        $this->setOrderDetails($order);
        $result = $this->getCheckoutResponse($order);
        $resultJson = json_encode($result);

        return $this->_resultJsonFactory->create()->setData($resultJson);
    }

    public function setOrderDetails($order)
    {
        $order->setPaymentMethod(CheckoutPayment::METHOD_CODE);
        $order->setState(Order::STATE_PROCESSING);
        $status = $this->_bamboraHelper->getBamboraCheckoutConfigData('order_status_pending',$this->_getOrder()->getStoreId());
        $order->setStatus($status);
        $message = __("Order placed and is now awaiting payment authorization");
        $order->addStatusHistoryComment($message,$status);
        $order->setIsNotified(false);
        $order->save();
    }

    /**
     * Get the Bambora Checkout Response
     *
     * @param \Magento\Sales\Model\Order
     * @return array
     */
    public function getCheckoutResponse($order)
    {
        $checkoutMethod = $this->_getPaymentMethodInstance();
        $setCheckoutResponse = $checkoutMethod->setCheckout($order);

        $message = "";
        if(!$this->_bamboraHelper->validateCheckoutApiResult($setCheckoutResponse, $order->getIncrementId(),false, $message))
        {
            $this->messageManager->addError($message);
            throw new \Magento\Framework\Exception\LocalizedException(__('The payment window could not be retrived'));
        }

        return $setCheckoutResponse;
    }
}