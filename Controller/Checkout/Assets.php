<?php
/**
 * 888                             888
 * 888                             888
 * 88888b.   8888b.  88888b.d88b.  88888b.   .d88b.  888d888  8888b.
 * 888 "88b     "88b 888 "888 "88b 888 "88b d88""88b 888P"       "88b
 * 888  888 .d888888 888  888  888 888  888 888  888 888     .d888888
 * 888 d88P 888  888 888  888  888 888 d88P Y88..88P 888     888  888
 * 88888P"  "Y888888 888  888  888 88888P"   "Y88P"  888     "Y888888
 *
 * @category    Online Payment Gatway
 * @package     Bambora_Online
 * @author      Bambora Online
 * @copyright   Bambora (http://bambora.com)
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
        try {
            /** @var \Bambora\Online\Model\Method\Checkout\Payment */
            $checkoutMethod =  $this->_getPaymentMethodInstance(CheckoutPayment::METHOD_CODE);
            $paymentCardIds = array();

            if ($checkoutMethod) {
                $paymentCardIds = $checkoutMethod->getPaymentCardIds();
            }

            return $paymentCardIds;
        } catch (\Exception $ex) {
            $this->messageManager->addError(__("The allowed payment types could not be loaded"));
            $this->_bamboraLogger->addCheckoutError(-1, $ex->getMessage());
            return null;
        }
    }
}
