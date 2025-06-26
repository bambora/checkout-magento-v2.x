<?php
namespace Bambora\Online\Model\Method;

class BamboraAdvanced extends \Magento\Payment\Model\Method\AbstractMethod
{
    public const string METHOD_CODE = 'bambora_advanced';

    /**
     * @var string
     */
    protected $_code = self::METHOD_CODE;

    /**
     * @var bool
     */
    protected $_isGateway = false;

    /**
     * @var bool
     */
    protected $_canAuthorize = false;

    /**
     * @var bool
     */
    protected $_isInitializeNeeded = false;
}
