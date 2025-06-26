<?php
namespace Bambora\Online\Controller\Checkout;

class Checkout extends \Bambora\Online\Controller\AbstractActionController
{
    /**
     * Checkout Action
     */
    public function execute()
    {
        $order = $this->_getOrder();
        $this->setOrderDetails($order);
        $result = $this->getPaymentWindow($order);
        $resultJson = json_encode($result);

        return $this->_resultJsonFactory->create()->setData($resultJson);
    }

    /**
     * Get the Bambora Checkout Response
     *
     * @param \Magento\Sales\Model\Order $order
     * @return \Bambora\Online\Model\Api\Checkout\Response\Checkout|null
     */
    public function getPaymentWindow($order)
    {
        try {
            $checkoutMethod = $this->_getPaymentMethodInstance(
                $order->getPayment()->getMethod()
            );
            $paymentWindowResponse = $checkoutMethod->getPaymentWindow($order);

            return $paymentWindowResponse;
        } catch (\Exception $ex) {
            $this->_bamboraLogger->addCheckoutError(
                $order->getId(),
                $ex->getMessage()
            );
            return null;
        }
    }
}
