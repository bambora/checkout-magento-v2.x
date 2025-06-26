<?php
namespace Bambora\Online\Controller\Checkout;

use Bambora\Online\Model\Method\Checkout\Payment as CheckoutPayment;

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
     *
     * @return array
     */
    public function getPaymentcardIds()
    {
        $paymentCardIds = [];
        try {
            $checkoutMethod = $this->_getPaymentMethodInstance(
                CheckoutPayment::METHOD_CODE
            );
            if ($checkoutMethod) {
                $paymentCardIds = $checkoutMethod->getPaymentCardIds();
            }
        } catch (\Exception $ex) {
            $this->_bamboraLogger->addCheckoutError(-1, $ex->getMessage());
            return [];
        }

        return $paymentCardIds;
    }
}
