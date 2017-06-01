<?php
/**
 * Copyright (c) 2017. All rights reserved Bambora Online.
 *
 * This program is free software. You are allowed to use the software but NOT allowed to modify the software.
 * It is also not legal to do any changes to the software and distribute it in your own name / brand.
 *
 * All use of the payment modules happens at your own risk. We offer a free test account that you can use to test the module.
 *
 * @author    Bambora Online
 * @copyright Bambora Online (http://bambora.com)
 * @license   Bambora Online
 *
 */
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
     * @param \Magento\Sales\Model\Order
     * @return \Bambora\Online\Model\Api\Checkout\Response\Checkout|null
     */
    public function getPaymentWindow($order)
    {
        try {
            /** @var \Bambora\Online\Model\Method\Checkout\Payment */
            $checkoutMethod = $this->_getPaymentMethodInstance($order->getPayment()->getMethod());
            $paymentWindowResponse = $checkoutMethod->getPaymentWindow($order);

            return $paymentWindowResponse;
        } catch (\Exception $ex) {
            $this->_bamboraLogger->addCheckoutError($order->getId(), $ex->getMessage());
            return null;
        }
    }
}
