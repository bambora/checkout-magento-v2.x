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

use \Bambora\Online\Model\Method\Checkout\Payment as CheckoutPayment;

class Assets extends \Bambora\Online\Controller\AbstractActionController
{
    /**
     * Assets Action
     */
    public function execute()
    {
        $result = $this->getPaymentcardIds();
        return $this->_resultJsonFactory->create()->setData($result);
    }

    /**
     * Get an array of paymentcardids the order
     * @return array
     */
    public function getPaymentcardIds()
    {
        $paymentCardIds = array();
        try {
            /** @var \Bambora\Online\Model\Method\Checkout\Payment */
            $checkoutMethod =  $this->_getPaymentMethodInstance(CheckoutPayment::METHOD_CODE);


            if ($checkoutMethod) {
                $paymentCardIds = $checkoutMethod->getPaymentCardIds();
            }
        } catch (\Exception $ex) {
            $this->_bamboraLogger->addCheckoutError(-1, $ex->getMessage());
            return null;
        }

        return $paymentCardIds;
    }
}
