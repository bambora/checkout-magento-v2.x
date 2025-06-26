<?php
namespace Bambora\Online\Model\Config\Source;

use Bambora\Online\Helper\BamboraConstants as BamboraConstants;

class Surchargemode implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Module rounding mode
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => BamboraConstants::SURCHARGE_ORDER_LINE,
                'label' => __('Create order line')
            ],
            [
                'value' => BamboraConstants::SURCHARGE_SHIPMENT,
                'label' => __('Add to shipment & handling')
            ],
        ];
    }
}
