<?php
/**
 * Copyright (c) 2017. All rights reserved Bambora Online.
 *
 * This program is free software. You are allowed to use the software but NOT allowed to modify the software.
 * It is also not legal to do any changes to the software and distribute it in your own name / brand.
 *
 * All use of the payment modules happens at your own risk. We offer a free test account that you can use to test the module.
 *
 * @author    Bambora Online
 * @copyright Bambora Online (http://bambora.com)
 * @license   Bambora Online
 *
 */
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
            ['value' => BamboraConstants::ROUND_DEFAULT, 'label' => "Default"],
            ['value' => BamboraConstants::ROUND_UP, 'label' => "Always Up"],
            ['value' => BamboraConstants::ROUND_DOWN, 'label' => "Always Down"],
        ];
    }
}
