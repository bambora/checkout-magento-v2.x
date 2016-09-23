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
                        return $.get(window.checkoutConfig.payment["bambora_checkout"].assetsUrl);
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

