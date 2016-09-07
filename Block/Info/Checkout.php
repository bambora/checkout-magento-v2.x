<?php
/**
 * Bambora Online
 *
 * @category    Online Payment Gatway
 * @package     Bambora_Online_Checkout
 * @author      Bambora
 * @copyright   Bambora (http://bambora.com)
 */
namespace Bambora\Online\Block\Info;

class Checkout extends \Magento\Payment\Block\Info
{
    /**
     * @var string
     */
    protected $_template = 'Bambora_Online::info/bambora_checkout.phtml';

    /**
     * @desc Returns the name defined in the module config
     * @return string
     */
    public function getMethodTitle()
    {
        $methodTitle = $this->getInfo()->getTitle();
        $checkoutMethod = $this->getInfo()->getMethodInstance();

        if($checkoutMethod->getCode() == \Bambora\Online\Model\Method\Checkout::METHOD_CODE)
        {
            $customTitle = $checkoutMethod->getCheckoutConfig('checkout_title');
            if(isset($customTitle))
            {
                $methodTitle = $customTitle;
            }
        }

        return $methodTitle;
    }

    /**
     * @desc Returns the last transaction id
     * @return string
     */
    public function getTransactionId()
    {
        $result = "";
        $transactionId = $this->getInfo()->getLastTransId();

        if($transactionId)
        {
            $result =  __("Transaction Id:") . " " . $transactionId;
        }

        return $result;
    }

    /**
     * @desc Returns the payment type
     * @return string
     */
    public function getPaymentType()
    {
        $result = "";
        try
        {
            $payment = $this->getInfo()->getOrder()->getPayment()->getCcType();
            if($payment)
            {
                $result = $payment;
            }
        }
        catch(Exception $e)
        {
            //Do nothing
        }

        return $result;
    }

}