<?php
namespace Bambora\Online\Logger;

use Monolog\Logger;

class BamboraHandler extends \Magento\Framework\Logger\Handler\Base
{
    /**
     * @var int
     */
    protected $loggerType = Logger::DEBUG;

    /**
     * @var string
     */
    protected $fileName = '/var/log/bambora.log';
}
