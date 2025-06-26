<?php
namespace Bambora\Online\Block\Info;

use Bambora\Online\Model\Method\Checkout\Payment as CheckoutPayment;

class View extends \Magento\Payment\Block\Info
{
    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('sales/order/view/info.phtml');
    }

    /**
     * @inheritdoc
     */
    protected function _prepareSpecificInformation($transport = null)
    {
        if ($this->_paymentSpecificInformation !== null) {
            return $this->_paymentSpecificInformation;
        }

        $transport = parent::_prepareSpecificInformation($transport);
        $data = [];

        if ($this->getInfo()->getLastTransId()) {
            $ccType = $this->getInfo()->getOrder()->getPayment()->getCcType();
            if (!empty($ccType)) {
                $data[(string)__('Payment type')] = $ccType;
            }
            $ccNumber = $this->getInfo()->getOrder()->getPayment()->getCcNumberEnc();
            if (!empty($ccNumber)) {
                $data[(string)__('Card number')] = $ccNumber;
            }

            $txnId = "";
            $payment = $this->getInfo()->getOrder()->getPayment();
            if ($payment->getMethod() === CheckoutPayment::METHOD_CODE) {
                $txnId = $payment->getAdditionalInformation(
                    CheckoutPayment::METHOD_REFERENCE
                );
            }
            if (!empty($txnId)) {
                $data[(string)__('Transaction Id')] = $txnId;
            }
        }

        return $transport->setData(array_merge($data, $transport->getData()));
    }

    /**
     * Get translated payment information title
     *
     * @return string
     */
    public function getPaymentInformationTitle()
    {
        return __('Payment Information');
    }
}
