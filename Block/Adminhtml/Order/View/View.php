<?php
/**
 * Bambora Online
 *
 * @category    Online Payment Gatway
 * @package     Bambora_Online_Checkout
 * @author      Bambora
 * @copyright   Bambora (http://bambora.com)
 */
namespace Bambora\Online\Block\Adminhtml\Order\View;

use \Bambora\Online\Model\Method\Checkout\Payment as CheckoutPayment;

class View extends \Magento\Backend\Block\Template
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $_registry;

    /**
     * @var \Bambora\Online\Helper\Data
     */
    protected $_bamboraHelper;

    /**
     * View constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Bambora\Online\Helper\Data $bamboraHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Bambora\Online\Helper\Data $bamboraHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_registry = $registry;
        $this->_bamboraHelper = $bamboraHelper;
    }

    /**
     * @return \Magento\Sales\Model\Order\Payment
     */
    public function getPayment()
    {
        $order = $this->_registry->registry('current_order');
        return $order->getPayment();
    }
    /**
     * @return string
     */
    protected function _toHtml()
    {
        return ($this->getPayment()->getMethod() === CheckoutPayment::METHOD_CODE) ? parent::_toHtml() : '';
    }
    public function getTransactionData()
    {
        $result = null;
        $payment = $this->getPayment();
        $paymentMethod = $payment->getMethod();
        if($paymentMethod === CheckoutPayment::METHOD_CODE)
        {
            $checkoutMethod = $payment->getMethodInstance();
            if(isset($checkoutMethod))
            {
                $parantTxnId = $payment->getAuthorizationTransaction()->getParentTxnId();

                $transactionId = isset($parantTxnId) ? $parantTxnId : $payment->getAuthorizationTransaction()->getTxnId();
                $transactionResponse = $checkoutMethod->getTransaction($transactionId);

                if(isset($transactionResponse))
                {
                    $result = $this->getFromCheckout($transactionResponse);
                }
            }
        }
        return $result;
    }

    private function getFromCheckout($transaction)
    {
        $transactionInfo = $transaction['transaction'];

        $result = array('transactionId'=>$transactionInfo['id'],
                    'totalAuthorized'=>$this->formatCurrency($this->_bamboraHelper->convertPriceFromMinorUnits($transactionInfo['total']['authorized'],$transactionInfo['currency']['minorunits'])),
                    'createdDate'=>$this->formatDate($transactionInfo['createddate'],\IntlDateFormatter::SHORT,true),
                    'paymentTypeName'=>$transactionInfo["information"]["paymenttypes"][0]["displayname"],
                    'paymentTypeId'=>$transactionInfo["information"]["paymenttypes"][0]["groupid"],
                    'primaryAccountNumbers'=>$transactionInfo["information"]["primaryaccountnumbers"][0]["number"],
                    'totalFeeAmount'=>$this->formatCurrency($this->_bamboraHelper->convertPriceFromMinorUnits($transactionInfo['total']['feeamount'],$transactionInfo['currency']['minorunits'])),
                    'totalCaptured'=>$this->formatCurrency($this->_bamboraHelper->convertPriceFromMinorUnits($transactionInfo['total']['captured'],$transactionInfo['currency']['minorunits'])),
                    'totalCredited'=>$this->formatCurrency($this->_bamboraHelper->convertPriceFromMinorUnits($transactionInfo['total']['credited'],$transactionInfo['currency']['minorunits'])),
                    );

        return $result;
    }

    public function formatCurrency($amount)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $priceHelper = $objectManager->create('Magento\Framework\Pricing\Helper\Data');

        return $priceHelper->currency($amount, true, false);
    }

    public function getPaymentLogoUrl($paymentId)
    {
        return '<img class="bambora_paymentcard" src="https://d3r1pwhfz7unl9.cloudfront.net/paymentlogos/'.$paymentId . '.png"';
    }
}