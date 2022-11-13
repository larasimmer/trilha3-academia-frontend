/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/* eslint-disable max-nested-callbacks */
define(['squire', 'jquery'], function (Squire, $) {
    'use strict';

    describe('Magento_Reward/js/action/remove-points-from-summary', function () {
        var injector = new Squire(),
            paymentDeferred = $.Deferred(),
            mocks = {
                'mage/storage': {
                    post: function () {} // jscs:ignore jsDoc
                },
                'Magento_Checkout/js/model/error-processor': {
                    process: jasmine.createSpy()
                },
                'Magento_Ui/js/model/messageList': {
                    clear: jasmine.createSpy(),
                    addErrorMessage: jasmine.createSpy(),
                    addSuccessMessage: jasmine.createSpy()
                },
                'Magento_Checkout/js/model/full-screen-loader': {
                    startLoader: jasmine.createSpy(),
                    stopLoader: jasmine.createSpy()
                },
                'Magento_Checkout/js/action/get-payment-information': jasmine.createSpy().and.returnValue(
                    paymentDeferred.promise()
                ),
                'Magento_Checkout/js/model/totals': {
                    isLoading: jasmine.createSpy()
                }
            },
            url = '/example/url',
            removeRewardPoints;

        beforeEach(function (done) {
            injector.mock(mocks);
            injector.require(['Magento_Reward/js/action/remove-points-from-summary'], function (instance) {
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

        describe('Check Reward Points removing from summary process', function () {
            it('Check removing without errors', function () {
                spyOn(mocks['mage/storage'], 'post').and.callFake(function () {
                    var deferred = $.Deferred();

                    deferred.resolve({
                        errors: false,
                        message: 'example message'
                    });

                    return deferred.promise();
                });

                expect(removeRewardPoints(url)).toBeUndefined();

                expect(mocks['Magento_Ui/js/model/messageList'].clear).toHaveBeenCalled();
                expect(mocks['Magento_Checkout/js/model/full-screen-loader'].startLoader).toHaveBeenCalled();
                expect(mocks['mage/storage'].post).toHaveBeenCalledWith(url, {});

                expect(mocks['Magento_Checkout/js/model/totals'].isLoading).toHaveBeenCalledWith(true);
                expect(mocks['Magento_Checkout/js/action/get-payment-information']).toHaveBeenCalled();
                expect(mocks['Magento_Checkout/js/model/totals'].isLoading.calls.count()).toEqual(1);
                paymentDeferred.resolve();
                expect(mocks['Magento_Checkout/js/model/totals'].isLoading.calls.count()).toEqual(2);
                expect(mocks['Magento_Checkout/js/model/totals'].isLoading.calls.mostRecent().args).toEqual([false]);
                mocks['Magento_Checkout/js/model/totals'].isLoading.calls.reset();

                expect(mocks['Magento_Ui/js/model/messageList'].addSuccessMessage).toHaveBeenCalled();
                expect(mocks['Magento_Ui/js/model/messageList'].addErrorMessage).not.toHaveBeenCalled();

                expect(mocks['Magento_Checkout/js/model/full-screen-loader'].stopLoader).toHaveBeenCalled();
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
                expect(mocks['Magento_Checkout/js/model/error-processor'].process).toHaveBeenCalled();

                expect(mocks['Magento_Checkout/js/model/full-screen-loader'].stopLoader).toHaveBeenCalled();
            });
        });
    });
});
