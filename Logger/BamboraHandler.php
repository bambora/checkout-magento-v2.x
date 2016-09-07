<?php
namespace Bambora\Online\Logger;

use Monolog\Logger;

class BamboraHandler extends \Magento\Framework\Logger\Handler\Base
{
    /**
     * Logging level
     * @var int
     */
    protected $loggerType = Logger::DEBUG;

    /**
     * File name
     * @var string
     */
    protected $fileName = '/var/log/bambora.log';
}