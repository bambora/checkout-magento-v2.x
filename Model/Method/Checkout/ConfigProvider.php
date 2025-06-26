<?php
namespace Bambora\Online\Model\Method\Checkout;

use Bambora\Online\Model\Method\Checkout\Payment as CheckoutPayment;
use Bambora\Online\Helper\BamboraConstants;

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
        $this->_checkoutMethod = $this->_paymentHelper->getMethodInstance(
            $this->methodCode
        );
    }

    /**
     * @inheritdoc
     */
    public function getConfig()
    {
        return [
            'payment' => [
                $this->methodCode => [
                    'paymentTitle' => $this->_checkoutMethod->getConfigData(
                        BamboraConstants::TITLE
                    ),
                    'paymentIconSrc' => $this->_checkoutMethod->getCheckoutIconUrl(),
                    'checkoutWebSdkUrl' => $this->_checkoutMethod->getCheckoutWebSdkUrl(
                    ),
                    'windowState' => $this->_checkoutMethod->getConfigData(
                        BamboraConstants::WINDOW_STATE
                    ),
                    'checkoutUrl' => $this->_checkoutMethod->getCheckoutUrl(),
                    'assetsUrl' => $this->_checkoutMethod->getAssetsUrl(),
                    'cancelUrl' => $this->_checkoutMethod->getCancelUrl()
                ]
            ]
        ];
    }
}
