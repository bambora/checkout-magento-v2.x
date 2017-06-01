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
namespace Bambora\Online\Model\Method\Checkout;

use Bambora\Online\Model\Method\Checkout\Payment as CheckoutPayment;
use \Bambora\Online\Helper\BamboraConstants;

class ConfigProvider implements \Magento\Checkout\Model\ConfigProviderInterface
{
    /**
     * @var string
     */
    protected $methodCode = CheckoutPayment::METHOD_CODE;

    /**
     * @var \Bambora\Online\Model\Method\Checkout\Payment
     */
    protected $_checkoutMethod;

    /**
     * @var \Magento\Payment\Helper\Data
     */
    protected $_paymentHelper;

    /**
     * Config Provider
     *
     * @param \Magento\Payment\Helper\Data $paymentHelper
     */
    public function __construct(
        \Magento\Payment\Helper\Data $paymentHelper
    ) {
        $this->_paymentHelper = $paymentHelper;
        $this->_checkoutMethod = $this->_paymentHelper->getMethodInstance($this->methodCode);
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        $config = [
            'payment' => [
                $this->methodCode => [
                    'paymentTitle' => $this->_checkoutMethod->getConfigData(BamboraConstants::TITLE),
                    'paymentIconSrc' => $this->_checkoutMethod->getCheckoutIconUrl(),
                    'paymentWindowJsUrl' => $this->_checkoutMethod->getCheckoutPaymentWindowJsUrl(),
                    'windowState' => $this->_checkoutMethod->getConfigData(BamboraConstants::WINDOW_STATE),
                    'checkoutUrl'=> $this->_checkoutMethod->getCheckoutUrl(),
                    'assetsUrl'=> $this->_checkoutMethod->getAssetsUrl(),
                    'cancelUrl'=> $this->_checkoutMethod->getCancelUrl()
                ]
            ]
        ];

        return $config;
    }
}
