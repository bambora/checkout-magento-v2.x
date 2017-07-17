/*browser:true*/
/*global define*/
define(
    [
        'ko',
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Bambora_Online/js/action/set-payment-method',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Ui/js/model/messageList',
        'mage/translate'
    ],
    function(ko, $, Component, setPaymentMethodAction, additionalValidators, globalMessageList, $t) {
        'use strict';

        return Component.extend({
            initialize: function() {
                this._super().initChildren();
                this.loadEPayPaymentWindowJs();
            },
            defaults: {
                template: 'Bambora_Online/payment/epay-form'
            },
            getBamboraEpayTitle: function () {
                return window.checkoutConfig.payment.bambora_epay.paymentTitle;
            },
            getBamboraEpayLogo: function () {
                return window.checkoutConfig.payment.bambora_epay.paymentLogoSrc;
            },
            getBamboraEpayPaymentLogoSrc: function () {
                return window.checkoutConfig.payment.bambora_epay.paymentTypeLogoSrc;
            },
            continueToBamboraEpay: function () {
                var self = this;
                if (additionalValidators.validate()) {
                    self.selectPaymentMethod();
                    setPaymentMethodAction().then(function() {
                        self.getPaymentWindow();
                    });
                } else {
                    return false;
                } 
            },
            getPaymentWindow: function () {
                 var self = this;
                 var url = window.checkoutConfig.payment.bambora_epay.checkoutUrl;                   
                    $.get(url)
                        .done(function (response) {
                            response = JSON.parse(response);
                            if(!response) {
                                self.showError($t("Error opening payment window"));
                                $.mage.redirect(window.checkoutConfig.payment.bambora_epay.cancelUrl);
                            }
                            self.openPaymentWindow(response);                             
                        }).fail(function(error) {
                            self.showError($t("Error opening payment window"));
                            $.mage.redirect(window.checkoutConfig.payment.bambora_epay.cancelUrl);
                        });
            },
            openPaymentWindow: function(requestString) {
                var onclose = function() {
                     var cancelUrl = window.checkoutConfig.payment.bambora_epay.cancelUrl;
                      $.mage.redirect(cancelUrl);
                }
                var paymentwindow = new PaymentWindow(requestString);
                if(window.checkoutConfig.payment.bambora_epay.windowState === "1") {
                    paymentwindow.on("close", onclose);
                }
                paymentwindow.open();
            },
            loadEPayPaymentWindowJs: function() {
                $.getScript(window.checkoutConfig.payment.bambora_epay.paymentWindowJsUrl);
            },
            showError: function (errorMessage) {
                globalMessageList.addErrorMessage({
                    message: errorMessage
                });
            }
        });
    }
);