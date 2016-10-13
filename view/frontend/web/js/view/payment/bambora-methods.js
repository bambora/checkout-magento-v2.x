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
            },
             {
                 type: 'bambora_epay',
                 component: 'Bambora_Online/js/view/payment/method-renderer/bambora-epay-method'
             }
        );
        /** Add view logic here if needed */
        return Component.extend({});
    }
);