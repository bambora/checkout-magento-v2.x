<?php
namespace Bambora\Online\Model\Config\Source;

class CheckoutWindowstate implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Checkout Window state
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 1, 'label' => __('Full screen')],
            ['value' => 2, 'label' => __('Overlay')],
        ];
    }
}
