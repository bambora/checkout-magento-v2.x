/**
 * 888                             888
 * 888                             888
 * 88888b.   8888b.  88888b.d88b.  88888b.   .d88b.  888d888  8888b.
 * 888 "88b     "88b 888 "888 "88b 888 "88b d88""88b 888P"       "88b
 * 888  888 .d888888 888  888  888 888  888 888  888 888     .d888888
 * 888 d88P 888  888 888  888  888 888 d88P Y88..88P 888     888  888
 * 88888P"  "Y888888 888  888  888 88888P"   "Y88P"  888     "Y888888
 *
 * @category    Online Payment Gatway
 * @package     Bambora_Online
 * @author      Bambora Online
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
                        var cancelUrl = window.checkoutConfig.payment["bambora_checkout"].cancelUrl + "?magentoerror=1";
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

