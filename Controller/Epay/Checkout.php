<?php
/**
 * Copyright (c) 2019. All rights reserved Bambora Online.
 *
 * This program is free software. You are allowed to use the software but NOT allowed to modify the software.
 * It is also not legal to do any changes to the software and distribute it in your own name / brand.
 *
 * All use of the payment modules happens at your own risk. We offer a free test account that you can use to test the module.
 *
 * @author    Bambora Online
 * @copyright Bambora Online (https://bambora.com)
 * @license   Bambora Online
 */

namespace Bambora\Online\Controller\Epay;

class Checkout extends \Bambora\Online\Controller\AbstractActionController
{
    /**
     * Checkout Action
     */
    public function execute()
    {
        $order = $this->_getOrder();
        $this->setOrderDetails($order);
        $result = $this->getEPayPaymentWindowRequest($order);
        $resultJson = json_encode($result);

        return $this->_resultJsonFactory->create()->setData($resultJson);
    }

    /**
     * Get the Epay Payment window url
     *
     * @param \Magento\Sales\Model\Order
     * @return string|null
     */
    public function getEPayPaymentWindowRequest($order)
    {
        try {
            $epayMethod = $this->_getPaymentMethodInstance(
                $order->getPayment()->getMethod()
            );
            $response = $epayMethod->getPaymentWindow($order);
            return $response;
        } catch (\Exception $ex) {
            $this->_bamboraLogger->addEpayError($order->getId(), $ex->getMessage());
            return null;
        }
    }
}
