<?php
/**
 * Bambora Online
 *
 * @category    Online Payment Gatway
 * @package     Bambora_Online_Checkout
 * @author      Bambora
 * @copyright   Bambora (http://bambora.com)
 */
namespace Bambora\Online\Model\Config\Source;

class Windowstate implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @desc Checkout Window state
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
