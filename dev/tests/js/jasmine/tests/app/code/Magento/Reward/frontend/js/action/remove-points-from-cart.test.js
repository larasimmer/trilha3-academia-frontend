/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/* eslint-disable max-nested-callbacks */
define(['squire', 'jquery'], function (Squire, $) {
    'use strict';

    describe('Magento_Reward/js/action/remove-points-from-cart', function () {
        var injector = new Squire(),
            paymentDeferred = $.Deferred(),
            mocks = {
                'mage/storage': {
                    post: function () {} // jscs:ignore jsDoc
                },
                'Magento_Checkout/js/model/error-processor': {
                    process: jasmine.createSpy()
                },
                'Magento_Checkout/js/action/get-payment-information': jasmine.createSpy().and.returnValue(
                    paymentDeferred.promise()
                ),
                'Magento_Checkout/js/model/totals': {
                    isLoading: jasmine.createSpy()
                },
                'Magento_Customer/js/customer-data': {
                    set: jasmine.createSpy()
                }
            },
            url = '/example/url',
            removeRewardPoints;

        beforeEach(function (done) {
            injector.mock(mocks);
            injector.require(['Magento_Reward/js/action/remove-points-from-cart'], function (instance) {
                removeRewardPoints = instance;
                done();
            });
        });

        afterEach(function () {
            try {
                injector.clean();
                injector.remove();
            } catch (e) {}
        });

        describe('Check Reward Points removing from Cart process', function () {
            it('Check removing without errors', function () {
                var message = 'example message';

                spyOn(mocks['mage/storage'], 'post').and.callFake(function () {
                    var deferred = $.Deferred();

                    deferred.resolve({
                        errors: false,
                        message: message
                    });

                    return deferred.promise();
                });

                expect(removeRewardPoints(url)).toBeUndefined();

                expect(mocks['mage/storage'].post).toHaveBeenCalledWith(url, {});
                expect(mocks['Magento_Checkout/js/model/totals'].isLoading).toHaveBeenCalledWith(true);
                expect(mocks['Magento_Checkout/js/action/get-payment-information']).toHaveBeenCalled();
                expect(mocks['Magento_Checkout/js/model/totals'].isLoading.calls.count()).toEqual(1);
                paymentDeferred.resolve();
                expect(mocks['Magento_Checkout/js/model/totals'].isLoading.calls.count()).toEqual(2);
                expect(mocks['Magento_Checkout/js/model/totals'].isLoading.calls.mostRecent().args).toEqual([false]);
                mocks['Magento_Checkout/js/model/totals'].isLoading.calls.reset();

                expect(mocks['Magento_Customer/js/customer-data'].set.calls.count()).toEqual(2);
                expect(mocks['Magento_Customer/js/customer-data'].set.calls.mostRecent().args[1]).toEqual({
                    messages: [{
                        type: 'success',
                        text: message
                    }]
                });
                mocks['Magento_Customer/js/customer-data'].set.calls.reset();

                expect(mocks['Magento_Checkout/js/model/error-processor'].process).not.toHaveBeenCalled();
            });

            it('Check removing with errors', function () {
                spyOn(mocks['mage/storage'], 'post').and.callFake(function () {
                    var deferred = $.Deferred();

                    deferred.reject();

                    return deferred.promise();
                });

                removeRewardPoints(url);

                expect(mocks['mage/storage'].post).toHaveBeenCalledWith(url, {});
                expect(mocks['Magento_Checkout/js/model/totals'].isLoading.calls.count()).toEqual(1);
                expect(mocks['Magento_Checkout/js/model/totals'].isLoading).toHaveBeenCalledWith(false);
                expect(mocks['Magento_Customer/js/customer-data'].set.calls.count()).toEqual(1);
                expect(mocks['Magento_Checkout/js/model/error-processor'].process).toHaveBeenCalled();
            });
        });
    });
});
