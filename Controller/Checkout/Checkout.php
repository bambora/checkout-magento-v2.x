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

use \Magento\Sales\Model\Order;
use \Bambora\Online\Model\Method\Checkout\Payment as CheckoutPayment;
use \Bambora\Online\Helper\BamboraConstants;

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
            $this->messageManager->addError(__("The payment window could not be retrived"));
            $this->_bamboraLogger->addCheckoutError($order->getId(), $ex->getMessage());
            return null;
        }
    }
}
