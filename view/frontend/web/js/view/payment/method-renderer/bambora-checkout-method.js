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
                this.loadBamboraCheckoutPaymentWindowJs();
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
                            self.openCheckoutPaymentWindow(response.url);                             
                        }).fail(function(error) {
                            self.showError($t("Error opening payment window") + ': ' + error.statusText);
                            $.mage.redirect(window.checkoutConfig.payment.bambora_checkout.cancelUrl);
                        });
            },
            openCheckoutPaymentWindow: function(sessionUrl) {
                var onclose = function() {
                     var cancelUrl = window.checkoutConfig.payment.bambora_checkout.cancelUrl;
                      $.mage.redirect(cancelUrl);
                }
                var options = {
                    windowstate: parseInt(window.checkoutConfig.payment.bambora_checkout.windowState),
                    onClose: onclose
                }
                window.bam("open", sessionUrl, options);
            },
            loadBamboraCheckoutPaymentWindowJs: function() {
                (function(n, t, i, r, u, f, e) {
                    n[u] = n[u] ||
                        function() {
                            (n[u].q = n[u].q || []).push(arguments);
                        };
                    f = t.createElement(i);
                    e = t.getElementsByTagName(i)[0];
                    f.async = 1;
                    f.src = r;
                    e.parentNode.insertBefore(f, e);
                })(window,
                    document,
                    "script",
                    window.checkoutConfig.payment.bambora_checkout.paymentWindowJsUrl,
                    "bam");
                },
            showError: function (errorMessage) {
                globalMessageList.addErrorMessage({
                    message: errorMessage
                });
            }
        });
    }
);