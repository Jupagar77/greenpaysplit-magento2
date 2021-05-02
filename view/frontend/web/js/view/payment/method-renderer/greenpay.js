/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*browser:true*/
/*global define*/
define(
    [
        'jquery',
        'Magento_Payment/js/view/payment/cc-form',
        'aes-js',
        'Magento_Checkout/js/model/quote',
        'Magento_Ui/js/model/messageList',
        'Magento_Payment/js/model/credit-card-validation/credit-card-number-validator',
        'Magento_Payment/js/model/credit-card-validation/expiration-date-validator',
        'mage/translate',
        'jsencrypt',
    ],
    function ($, Component, aesjs, quote, globalMessageList, creditCardNumberValidator, expirationDateValidator) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Bananacode_GreenPay/payment/form'
            },

            context: function () {
                return this;
            },

            getCode: function () {
                return 'greenpay';
            },

            isActive: function () {
                return true;
            },

            /**
             * Get data
             * @returns {Object}
             */
            getData: function () {
                return {
                    'method': this.getCode(),
                    'additional_data': {
                        'payment_method_nonce': this.paymentMethodNonce
                    }
                };
            },

            /**
             * Set payment nonce
             * @param paymentMethodNonce
             */
            setPaymentMethodNonce: function (paymentMethodNonce) {
                this.paymentMethodNonce = JSON.stringify(paymentMethodNonce);
            },

            /**
             * AES Pairs
             * @returns {{s: *, k: Array}}
             */
            generateAESPairs: function () {
                let key = [],
                    iv;

                for (let k = 0; k < 16; k++) {
                    key.push(Math.floor(Math.random() * 255));
                }
                for (let k = 0; k < 16; k++) {
                    iv = Math.floor(Math.random() * 255);
                }

                return {
                    k: key,
                    s: iv
                };
            },

            /**
             * Pack ld, lk keys
             * @param obj
             * @param pair_
             * @returns {Array}
             */
            pack: function (obj, pair_) {
                /**
                 * ld generation
                 */
                let pair = (pair_ !== undefined) ? pair_ : this.generateAESPairs(),
                    textBytes = aesjs.utils.utf8.toBytes(JSON.stringify(obj)),
                    aesCtr = new aesjs.ModeOfOperation.ctr(pair.k, new aesjs.Counter(pair.s)),
                    encryptedBytes = aesCtr.encrypt(textBytes),
                    encryptedHex = aesjs.utils.hex.fromBytes(encryptedBytes);

                /**
                 * lk generation
                 */
                let config = window.checkoutConfig.payment[this.getCode()],
                    encrypt = new JSEncrypt(),
                    pairEncrypted,
                    pack = [];

                encrypt.setPublicKey(config.publicKey);
                pairEncrypted = encrypt.encrypt(JSON.stringify(pair));
                pack.push({
                    ld: encryptedHex,
                    lk: pairEncrypted,
                    type: 'master'
                });

                config.partnerKeys.map(partner => {
                    let encryptPartner = new JSEncrypt(),
                        pairEncryptedPartner;

                    encryptPartner.setPublicKey(partner.key);
                    pairEncryptedPartner = encryptPartner.encrypt(JSON.stringify(pair));
                    pack.push({
                        ld: encryptedHex,
                        lk: pairEncryptedPartner,
                        ref: partner.reference,
                        type: partner.type,
                        id: partner.id
                    });
                });

                return pack;
            },

            /**
             * Generate random string
             * @param length
             * @returns {string}
             */
            makeid: function (length) {
                let result = '';
                let characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
                let charactersLength = characters.length;
                for (let i = 0; i < length; i++) {
                    result += characters.charAt(Math.floor(Math.random() * charactersLength));
                }
                return result;
            },

            /**
             * Place order, generate payment nonce before.
             * @param data
             * @param event
             */
            placeOrder: function (data, event) {
                /**
                 * Validate cc number
                 */
                if (!creditCardNumberValidator(this.creditCardNumber()).isValid) {
                    this._showErrors($.mage.__('Invalid credit card number.'));
                    return false;
                } else {
                    const cardInfo = creditCardNumberValidator(this.creditCardNumber()).card;
                    const allowedTypes = Object.values(window.checkoutConfig.payment['ccform']['availableTypes']['greenpay']);
                    let allow = false;

                    for (let i = 0, l = allowedTypes.length; i < l; i++) {
                        if (cardInfo.title === allowedTypes[i]) {
                            allow = true
                        }
                    }

                    if (!allow) {
                        this._showErrors($.mage.__('Invalid credit card type.'));
                        return false;
                    }
                }

                /**
                 * Validate expiration date
                 */
                if (!expirationDateValidator(this.creditCardExpMonth() + '/' + this.creditCardExpYear()).isValid) {
                    this._showErrors($.mage.__('Invalid expiration date.'));
                    return false;
                }

                /**
                 * Validate expiration date
                 */
                if(!Number.isInteger(parseInt(this.creditCardVerificationNumber()))) {
                    this._showErrors($.mage.__('Invalid verification number.'));
                    return false;
                }

                let cardData = {
                    "card": {
                        "cardHolder": quote.billingAddress().firstname + ' ' + quote.billingAddress().lastname,
                        "expirationDate": {
                            "month": parseInt(this.creditCardExpMonth()),
                            "year": (this.creditCardExpYear())
                        },
                        "cardNumber": this.creditCardNumber(),
                        "cvc": this.creditCardVerificationNumber(),
                        "nickname": this.creditCardType() + this.makeid(7)
                    }
                };
                this.setPaymentMethodNonce(this.pack(cardData));
                this._super(data, event);
            },

            /**
             * Show error messages
             * @param msg
             * @private
             */
            _showErrors: function (msg) {
                $(window).scrollTop(0);
                globalMessageList.addErrorMessage({
                    message: msg
                });
            }
        });
    }
);
