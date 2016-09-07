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

class Assets extends AbstractCheckout
{
    /**
     * @desc Assets Action
     */
    public function execute()
    {
        $result = $this->getPaymentcardIds();
        return $this->_resultJsonFactory->create()->setData($result);
    }

    /**
     * @desc Get an array of paymentcardids the order
     * @return array
     */
    public function getPaymentcardIds()
    {
        $checkoutMethod =  $this->_getPaymentMethodInstance();
        $paymentCardIds = array();

        if($checkoutMethod)
        {
            $paymentCardIds = $checkoutMethod->getPaymentCardIds();
        }

        return $paymentCardIds;
    }
}