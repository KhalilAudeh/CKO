/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*browser:true*/
/*global define*/
define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/url-builder',
        'mage/url',
        'Magento_Checkout/js/model/quote',
        'Magento_Customer/js/customer-data',
        'Magento_Checkout/js/model/error-processor',
        'Magento_Checkout/js/model/full-screen-loader',
        'Bookey_BookeyPaymentGateway/js/form/form-builder'
    ],
    function (
        $,
        Component, 
        urlBuilder,
        url,
        quote,
        customerData, 
        errorProcessor, 
        fullScreenLoader, 
        formBuilder) {
        'use strict';

        var self;

        return Component.extend({
            redirectAfterPlaceOrder: false, //This is important, so the customer isn't redirected to success.phtml by default

            defaults: {
                template: 'Bookey_BookeyPaymentGateway/payment/form'
            },

            getMailingAddress: function () {
                return window.checkoutConfig.payment.checkmo.mailingAddress;
            },

            initialize: function() {
                this._super();
                self = this;
            },

            getCode: function() {
                return 'bookey_gateway';
            },

             getData: function() {
                return {
                    'method': this.item.method,
                    'additional_data': {
                        'paymentoptions': $('input:radio.paymentoptions:checked').val()
                    }
                };
            },

            afterPlaceOrder: function () {
            //    var cardtypr =  $('input:radio.paymentoptions:checked').val();
            //    window.location.replace(url.build('bookey/checkout/index?type='+cardtypr));

                var custom_controller_url = url.build('{frontname/path/action}'); // custom controller url
                $.post(custom_controller_url, 'json')
                .done(function (response) {
                    customerData.invalidate(['cart']);
                    formBuilder(response).submit(); // this function builds and submits the form
                })
                .fail(function (response) {
                    errorProcessor.process(response, this.messageContainer);
                })
                .always(function () {
                    fullScreenLoader.stopLoader();
                });
            },

            /*
             * This same validation is done server-side in InitializationRequest.validateQuote()
             */
            validate: function() {
                var billingAddress = quote.billingAddress();
                var shippingAddress = quote.shippingAddress();
                var totals = quote.totals();

                self.messageContainer.clear();

                if (!billingAddress) {
                    self.messageContainer.addErrorMessage({'message': 'Please enter your billing address'});
                    return false;
                }

                if (!billingAddress.firstname || 
                    !billingAddress.lastname ||
                    !billingAddress.street ||
                    !billingAddress.city ||
                    !billingAddress.postcode ||
                    billingAddress.firstname.length == 0 ||
                    billingAddress.lastname.length == 0 ||
                    billingAddress.street.length == 0 ||
                    billingAddress.city.length == 0 ||
                    billingAddress.postcode.length == 0) {
                    self.messageContainer.addErrorMessage({'message': 'Please enter your billing address details'});
                    return false;
                }

                if (totals.grand_total < 1) {
                    self.messageContainer.addErrorMessage({'message': 'Bookey doesn\'t support purchases less than 1.'});
                    return false;
                }

                return true;
            },

            getTitle: function() {
                return window.checkoutConfig.payment.bookey_gateway.title;
            },

            getDescription: function() {
                return window.checkoutConfig.payment.bookey_gateway.description;
            },
            
            getBookeyLogo:function(){
                var logo = window.checkoutConfig.payment.bookey_gateway.logo;

                return logo;
            },

        });
    }
);