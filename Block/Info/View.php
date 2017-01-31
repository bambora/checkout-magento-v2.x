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
namespace Bambora\Online\Block\Info;

use \Bambora\Online\Model\Method\Checkout\Payment as CheckoutPayment;
use \Bambora\Online\Model\Method\Epay\Payment as EpayPayment;

class View extends \Magento\Payment\Block\Info
{
    /**
     * {@inheritdoc}
     */
    protected function _prepareSpecificInformation($transport = null)
    {
        if (null !== $this->_paymentSpecificInformation) {
            return $this->_paymentSpecificInformation;
        }
        $transport = parent::_prepareSpecificInformation($transport);
        $data = [];

        if ($this->getInfo()->getLastTransId()) {
            $ccType = $this->getInfo()->getOrder()->getPayment()->getCcType();
            if ($ccType) {
                $data[(string)__("Payment type")] = $ccType;
            }
            $ccNumber = $this->getInfo()->getOrder()->getPayment()->getCcNumberEnc();
            if ($ccNumber) {
                $data[(string)__("Card number")] = $ccNumber;
            }

            $txnId = "";
            $payment = $this->getInfo()->getOrder()->getPayment();
            if ($payment->getMethod() === CheckoutPayment::METHOD_CODE) {
                $txnId = $payment->getAdditionalInformation(CheckoutPayment::METHOD_REFERENCE);
            } elseif ($payment->getMethod() === EpayPayment::METHOD_CODE) {
                $txnId = $payment->getAdditionalInformation(EpayPayment::METHOD_REFERENCE);
            }

            if ($txnId) {
                $data[(string)__("Transaction Id")] = $txnId;
            }
        }

        return $transport->setData(array_merge($data, $transport->getData()));
    }
}
