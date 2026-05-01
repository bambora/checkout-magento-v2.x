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
    function (ko, $, Component, globalMessageList, $t, fullScreenLoader) {
        'use strict';

        return Component.extend(
            {
                initialize: function () {
                    this._super();
                    this._sdkDeferred = this.loadBamboraCheckoutWebSdk();

                    return this;
                },
                defaults: {
                    template: 'Bambora_Online/payment/checkout-form',
                    paymentLogos: function () {
                        var result = ko.observable();
                        var assetsUrl = window.checkoutConfig.payment.bambora_checkout.assetsUrl;
                        if (assetsUrl) {
                            $.get(assetsUrl, null, result, 'json');
                        }
                        return result;
                    }()
                },
                redirectAfterPlaceOrder: false,
                getBamboraCheckoutTitle: function () {
                    return window.checkoutConfig.payment.bambora_checkout.paymentTitle;
                },
                getBamboraCheckoutIconSrc: function () {
                    return window.checkoutConfig.payment.bambora_checkout.paymentIconSrc;
                },
                afterPlaceOrder: function () {
                    var self = this;

                    fullScreenLoader.startLoader();
                    this.loadBamboraCheckoutWebSdk()
                        .done(function () {
                            self.setCheckoutSession();
                        })
                        .fail(function () {
                            fullScreenLoader.stopLoader();
                            self.showError($t('Could not load payment SDK. Please try again.'));
                        });
                },
                setCheckoutSession: function () {
                    var self = this;
                    var url = window.checkoutConfig.payment.bambora_checkout.checkoutUrl;
                    $.get(url)
                        .done(
                            function (response) {
                                try {
                                    response = typeof response === 'string' ? JSON.parse(response) : response;
                                } catch (e) {
                                    fullScreenLoader.stopLoader();
                                    self.showError($t('Error opening payment window'));
                                    $.mage.redirect(window.checkoutConfig.payment.bambora_checkout.cancelUrl);

                                    return;
                                }

                                if (!response || !response.meta || !response.meta.result) {
                                    fullScreenLoader.stopLoader();
                                    if (!response || !response.meta || !response.meta.message) {
                                        self.showError($t("Error opening payment window"));
                                    } else {
                                        self.showError($t("Error opening payment window") + ': ' + response.meta.message.enduser);
                                    }

                                    $.mage.redirect(window.checkoutConfig.payment.bambora_checkout.cancelUrl);

                                    return;
                                }
                                self.openCheckoutPaymentWindow(response.token);
                            }
                        ).fail(
                        function (error) {
                            fullScreenLoader.stopLoader();
                            self.showError($t("Error opening payment window") + ': ' + error.statusText);
                            $.mage.redirect(window.checkoutConfig.payment.bambora_checkout.cancelUrl);
                        }
                    );
                },
                openCheckoutPaymentWindow: function (checkoutToken) {
                    if (typeof Bambora === 'undefined') {
                        fullScreenLoader.stopLoader();
                        this.showError($t('Could not load payment SDK. Please try again.'));
                        $.mage.redirect(window.checkoutConfig.payment.bambora_checkout.cancelUrl);

                        return;
                    }

                    var windowState = parseInt(window.checkoutConfig.payment.bambora_checkout.windowState, 10);
                    if (windowState === 1) {
                        new Bambora.RedirectCheckout(checkoutToken);
                    } else {
                        var checkout = new Bambora.ModalCheckout(null);
                        checkout.on(
                            Bambora.Event.Cancel, function (payload) {
                                fullScreenLoader.stopLoader();
                                var declineUrl = payload.declineUrl;
                                $.mage.redirect(declineUrl);
                            }
                        );
                        checkout.on(
                            Bambora.Event.Close, function (payload) {
                                fullScreenLoader.stopLoader();
                                $.mage.redirect(payload.acceptUrl);
                            }
                        );
                        checkout.initialize(checkoutToken).then(
                            function () {
                                fullScreenLoader.stopLoader();
                                checkout.show();
                            },
                            function () {
                                fullScreenLoader.stopLoader();
                                $.mage.redirect(window.checkoutConfig.payment.bambora_checkout.cancelUrl);
                            }
                        );
                    }
                },
                loadBamboraCheckoutWebSdk: function () {
                    if (window.Bambora) {
                        return $.Deferred().resolve().promise();
                    }

                    if (!this._sdkDeferred) {
                        this._sdkDeferred = $.getScript(window.checkoutConfig.payment.bambora_checkout.checkoutWebSdkUrl);
                    }

                    return this._sdkDeferred;
                },
                showError: function (errorMessage) {
                    globalMessageList.addErrorMessage(
                        {
                            message: errorMessage
                        }
                    );
                }
            }
        );
    }
);
