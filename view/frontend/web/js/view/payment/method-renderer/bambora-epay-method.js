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
                    this.loadEPayPaymentWindowJs();
                },
                defaults: {
                    template: 'Bambora_Online/payment/epay-form'
                },
                redirectAfterPlaceOrder: false,
                getBamboraEpayTitle: function () {
                    return window.checkoutConfig.payment.bambora_epay.paymentTitle;
                },
                getBamboraEpayLogo: function () {
                    return window.checkoutConfig.payment.bambora_epay.paymentLogoSrc;
                },
                getBamboraEpayPaymentLogoSrc: function () {
                    return window.checkoutConfig.payment.bambora_epay.paymentTypeLogoSrc;
                },
                afterPlaceOrder: function () {
                    fullScreenLoader.startLoader();
                    this.getPaymentWindow();
                },
                getPaymentWindow: function () {
                     var self = this;
                     var url = window.checkoutConfig.payment.bambora_epay.checkoutUrl;
                    $.get(url)
                        .done(
                            function (response) {
                                response = JSON.parse(response);
                                if (!response) {
                                    self.showError($t("Error opening payment window"));
                                    $.mage.redirect(window.checkoutConfig.payment.bambora_epay.cancelUrl);
                                }
                                self.openPaymentWindow(response);
                            }
                        ).fail(
                            function (error) {
                                    self.showError($t("Error opening payment window") + ': ' + error.statusText);
                                    $.mage.redirect(window.checkoutConfig.payment.bambora_epay.cancelUrl);
                            }
                        );
                },
                openPaymentWindow: function (request) {
                    var paymentwindow = new PaymentWindow(request);
                    paymentwindow.open();
                },
                loadEPayPaymentWindowJs: function () {
                    $.getScript(window.checkoutConfig.payment.bambora_epay.paymentWindowJsUrl);
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