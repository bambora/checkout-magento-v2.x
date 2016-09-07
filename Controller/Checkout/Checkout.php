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

class Checkout extends AbstractCheckout
{
    /**
     * @desc Checkout Action
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
        $order->setState(Order::STATE_PENDING_PAYMENT);
        $message = __("Order placed and is now awaiting payment authorization");
        $order->addStatusHistoryComment($message, Order::STATE_PENDING_PAYMENT);
        $order->setIsNotified(false);
        $order->save();
    }

    /**
     * @desc Get the Bambora Checkout Response
     * @param $order
     * @return array
     */
    public function getCheckoutResponse($order)
    {
        $checkoutMethod = $this->_getPaymentMethodInstance();
        $setCheckoutRequest = $checkoutMethod->createBamboraCheckoutRequest($order);
        $setCheckoutResponse = $checkoutMethod->setCheckout($setCheckoutRequest);

        if(!$this->_bamboraHelper->validateCheckoutApiResult($setCheckoutResponse, $order->getIncrementId()))
        {
            $this->messageManager->addError(__("An error occured while fetching the payment windows."));
        }

        return $setCheckoutResponse;
    }

}