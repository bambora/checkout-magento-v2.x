/*browser:true*/
/*global define*/
define(
    [
        'ko',
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Bambora_Online/js/action/set-payment-method',
        'Magento_Checkout/js/model/payment/additional-validators'
    ],
    function (ko, $, Component, setPaymentMethodAction, additionalValidators) {
        'use strict';

        return Component.extend({
            self: this,
            defaults: {
                template: 'Bambora_Online/payment/epay-form'
            },
            getBamboraEpayTitle: function () {
                return window.checkoutConfig.payment["bambora_epay"].paymentTitle;
            },
            getBamboraEpayLogo: function () {
                return window.checkoutConfig.payment["bambora_epay"].paymentLogoSrc;
            },
            getBamboraEpayPaymentLogoSrc: function () {
                return window.checkoutConfig.payment["bambora_epay"].paymentTypeLogoSrc;
            },
            /** Redirect to Bambora */
            continueToBamboraEpay: function () {
                if (additionalValidators.validate()) {
                    //update payment method information if additional data was changed
                    this.selectPaymentMethod();
                    setPaymentMethodAction();
                    return false;
                }
            }
        });
    }
);