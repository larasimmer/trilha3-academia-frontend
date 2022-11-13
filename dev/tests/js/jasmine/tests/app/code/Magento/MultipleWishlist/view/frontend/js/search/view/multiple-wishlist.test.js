/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'squire',
    'jquery'
], function (Squire, $) {
    'use strict';

    var injector = new Squire(),
        form,
        button,
        formId = 'form-id',
        mocks = {
            'Magento_Ui/js/modal/alert': jasmine.createSpy()
        };

    beforeEach(function (done) {
        form = $(
            '<form id="' + formId + '"><input type="checkbox"/><button type="submit"/></form>'
        );
        $('body').append(form);
        button = form.find('button[type="submit"]');
        $(form).on('submit', function (event) {
            event.preventDefault();
        });
        injector.mock(mocks);
        injector.require(
            ['Magento_MultipleWishlist/js/search/view/multiple-wishlist'], function (Wishlist) {
                Wishlist({
                    'id': formId
                });
                done();
            });
    });

    afterEach(function () {
        try {
            form.remove();
            injector.clean();
            injector.remove();
        } catch (e) {
        }
    });

    describe('Magento_MultipleWishlist/js/search/view/multiple-wishlist', function () {
        it('Click on button when checkbox is checked', function () {
            form.find('input[type="checkbox"]').attr('checked', true);
            button.trigger('click');
            expect(mocks['Magento_Ui/js/modal/alert']).not.toHaveBeenCalled();
        });

        it('Click on button when checkbox is not checked', function () {
            button.trigger('click');
            expect(mocks['Magento_Ui/js/modal/alert']).toHaveBeenCalled();
        });
    });
});
