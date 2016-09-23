<?php
/**
 * Bambora Online
 *
 * @category    Online Payment Gatway
 * @package     Bambora_Online_Checkout
 * @author      Bambora
 * @copyright   Bambora (http://bambora.com)
 */
namespace Bambora\Online\Model\Method;

use Bambora\Online\Model\Method\Checkout\Payment as CheckoutPayment;

class ConfigProvider implements \Magento\Checkout\Model\ConfigProviderInterface
{
    /**
     * @var \Magento\Payment\Helper\Data
     */
    protected $_paymentHelper;

    /**
     * @param \Magento\Payment\Helper\Data $paymentHelper
     */
    public function __construct(
        \Magento\Payment\Helper\Data $paymentHelper
    )
    {
        $this->_paymentHelper = $paymentHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        $checkoutMethod = $this->_paymentHelper->getMethodInstance(CheckoutPayment::METHOD_CODE);

        $config = [
            'payment' => [
                CheckoutPayment::METHOD_CODE => [
                    'paymentTitle' => $checkoutMethod->getCheckoutConfig('title'),
                    'paymentIconSrc' => $checkoutMethod->getCheckoutIconUrl(),
                    'windowState' => 1, //$checkoutMethod->getBamboraConfig('window_state'), TODO implement overlay
                    'checkoutUrl'=> $checkoutMethod->getCheckoutUrl(),
                    'assetsUrl'=> $checkoutMethod->getAssetsUrl(),
                    'declineUrl'=> $checkoutMethod->getDeclineUrl()
                ]
            ]
        ];

        return $config;
    }
}