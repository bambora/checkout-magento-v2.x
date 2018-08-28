/*browser:true*/
/*global define*/
define(
    [
        'ko',
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Ui/js/model/messageList',
        'mage/translate',
        'Magento_Checkout/js/model/full-screen-loader'
    ],
    function(ko, $, Component, globalMessageList, $t, fullScreenLoader) {
        'use strict';

        return Component.extend({
            initialize: function() {
                this._super().initChildren();
                this.loadBamboraCheckoutWebSdk();
            },
            defaults: {
                template: 'Bambora_Online/payment/checkout-form',
                paymentLogos: function() {
                    var result = ko.observable();
                    var evaluator = function() {
                        var response = $.get(window.checkoutConfig.payment.bambora_checkout.assetsUrl);
                        if (!response) {
                            response = new [];
                        }
                        return response;
                    }
                    ko.computed(function() {
                        evaluator.call(this).done(result);
                    });
                    return result;
                }()                
            },
            redirectAfterPlaceOrder: false,
            getBamboraCheckoutTitle: function() {
                return window.checkoutConfig.payment.bambora_checkout.paymentTitle;
            },
            getBamboraCheckoutIconSrc: function() {
                return window.checkoutConfig.payment.bambora_checkout.paymentIconSrc;
            },
            afterPlaceOrder: function() {
                fullScreenLoader.startLoader();
                this.setCheckoutSession();                
            },
            setCheckoutSession: function () {
                 var self = this;
                 var url = window.checkoutConfig.payment.bambora_checkout.checkoutUrl;                   
                    $.get(url)
                        .done(function (response) {
                            response = JSON.parse(response);
                            if(!response || !response.meta.result) {
                                if(!response) {
                                    self.showError($t("Error opening payment window"));
                                } else {
                                    self.showError($t("Error opening payment window") + ': ' + response.meta.message.enduser);
                                }
                                
                                $.mage.redirect(window.checkoutConfig.payment.bambora_checkout.cancelUrl);
                            }
                            self.openCheckoutPaymentWindow(response.token);                             
                        }).fail(function(error) {
                            self.showError($t("Error opening payment window") + ': ' + error.statusText);
                            $.mage.redirect(window.checkoutConfig.payment.bambora_checkout.cancelUrl);
                        });
            },
            openCheckoutPaymentWindow: function(checkoutToken) {
                var windowState = parseInt(window.checkoutConfig.payment.bambora_checkout.windowState);
                if(windowState === 1) {
                    new Bambora.RedirectCheckout(checkoutToken);    
                } else {
                    var checkout = new Bambora.ModalCheckout(null);
                    checkout.on(Bambora.Event.Cancel, function(payload) {
                        var declineUrl = payload.declineUrl;
                        $.mage.redirect(declineUrl);
                    });
                    checkout.on(Bambora.Event.Close, function (payload){
                        $.mage.redirect(payload.acceptUrl);
                    });
                    checkout.initialize(checkoutToken).then(function() {
                        checkout.show();
                    });
                }
            },
            loadBamboraCheckoutWebSdk: function() {
                $.getScript(window.checkoutConfig.payment.bambora_checkout.checkoutWebSdkUrl);
            },
            showError: function (errorMessage) {
                globalMessageList.addErrorMessage({
                    message: errorMessage
                });
            }
        });
    }
);