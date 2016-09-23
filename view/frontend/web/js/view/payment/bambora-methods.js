/**
 * Bambora Online 
 * 
 * @category    Online Payment Gatway
 * @package     Bambora_Online_Checkout
 * @author      Bambora
 * @copyright   Bambora (http://bambora.com)
 */
/*browser:true*/
/*global define*/
define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (Component, rendererList) {
        'use strict';
        rendererList.push(
            {
                type: 'bambora_checkout',
                component: 'Bambora_Online/js/view/payment/method-renderer/bambora-checkout-method'
            }
        );
        /** Add view logic here if needed */
        return Component.extend({});
    }
);