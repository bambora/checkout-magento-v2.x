<?php
namespace Bambora\Online\Model\Config\Source;

use Bambora\Online\Helper\BamboraConstants as BamboraConstants;

class Roundingmode implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Module rounding mode
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => BamboraConstants::ROUND_DEFAULT, 'label' => __('Default')],
            ['value' => BamboraConstants::ROUND_UP, 'label' => __('Always Up')],
            ['value' => BamboraConstants::ROUND_DOWN, 'label' => __('Always Down')],
        ];
    }
}
