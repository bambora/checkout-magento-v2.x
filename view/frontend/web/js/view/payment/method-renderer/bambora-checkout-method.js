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
                template: 'Bambora_Online/payment/checkout-form',
                paymentLogos: function () {
                    var result = ko.observable();
                    var evaluator = function () {
                        var cancelUrl = window.checkoutConfig.payment["bambora_checkout"].cancelUrl;
                        var answer = $.get(window.checkoutConfig.payment["bambora_checkout"].assetsUrl);
                        if (!answer) {
                            $.mage.redirect(cancelUrl);
                        }
                        return answer;
                    }
                    ko.computed(function () {
                        evaluator.call(this).done(result);
                    });
                    return result;               
                }()
            },
            getBamboraCheckoutTitle: function () {
                return window.checkoutConfig.payment["bambora_checkout"].paymentTitle;
            },
            getBamboraCheckoutIconSrc: function () {
                return window.checkoutConfig.payment["bambora_checkout"].paymentIconSrc;
            },
            /** Redirect to Bambora */
            continueToBamboraCheckout: function () {
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

