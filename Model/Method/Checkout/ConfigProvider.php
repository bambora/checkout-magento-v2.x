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
     * @var Object
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
    )
    {
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
                    'windowState' => 1,
                    'checkoutUrl'=> $this->_checkoutMethod->getCheckoutUrl(),
                    'assetsUrl'=> $this->_checkoutMethod->getAssetsUrl(),
                    'cancelUrl'=> $this->_checkoutMethod->getCancelUrl()
                ]
            ]
        ];

        return $config;
    }
}