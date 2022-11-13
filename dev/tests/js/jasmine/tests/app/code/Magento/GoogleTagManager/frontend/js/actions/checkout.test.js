/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
/* eslint-disable max-nested-callbacks */
/*jscs:disable jsDoc*/

define([
    'squire',
    'ko'
], function (Squire, ko) {
    'use strict';

    var injector = new Squire(),
        Payment = function () {},
        mocks = {
            'Magento_Checkout/js/view/payment': Payment
        },
        checkout;

    beforeEach(function (done) {
        Payment.prototype.isVisible = ko.observable();
        injector.mock(mocks);
        injector.require(['Magento_GoogleTagManager/js/actions/checkout'], function (action) {
            checkout = action;
            done();
        });
    });

    afterEach(function () {
        try {
            injector.clean();
            injector.remove();
        } catch (e) {}
    });

    describe('Magento_GoogleTagManager/js/actions/checkout', function () {

        it('test when google tag manager is not initiated and payment is visible', function () {
            checkout({
                cart: {}
            });
            expect(function () {
                Payment.prototype.isVisible(1);
            }).not.toThrow();
        });

        it('test when google tag manager is initiated', function () {
            window.dataLayer = window.dataLayer || [];
            window.dataLayer.push = jasmine.createSpy();
            checkout({
                cart: {}
            });
            expect(window.dataLayer.push.calls.count()).toEqual(1);
        });

        it('test when google tag manager is initiated and payment is visible', function () {
            window.dataLayer = window.dataLayer || [];
            window.dataLayer.push = jasmine.createSpy();
            checkout({
                cart: {}
            });
            Payment.prototype.isVisible(1);
            expect(window.dataLayer.push.calls.count()).toEqual(2);
        });
    });
});
