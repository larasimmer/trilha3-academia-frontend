/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define(['squire', 'jquery'], function (Squire, $) {
    'use strict';

    var injector = new Squire(),
        dataPost = {
            postData: jasmine.createSpy()
        },
        mocks = {
            /** Stub */
            'mage/dataPost': function () {
                return dataPost;
            }
        },
        link,
        linkId = 'giftcard_remove',
        linkUrl = 'example.com',
        removeGiftCard;

    beforeEach(function (done) {
        link = $('<a href="' + linkUrl + '" id="' + linkId + '"/>');
        $(document.body).append(link);

        injector.mock(mocks);
        injector.require(['Magento_GiftCardAccount/js/action/remove-gift-card-from-totals'], function (instance) {
            removeGiftCard = instance;
            done();
        });
    });

    afterEach(function () {
        try {
            injector.clean();
            injector.remove();
        } catch (e) {}

        link.remove();
    });

    describe('Magento_GiftCardAccount/js/action/remove-gift-card-from-totals', function () {
        it('Check actions after clicking on remove link', function () {
            var config = {
                code: 'example_code'
            };

            removeGiftCard(config, '#' + linkId);
            expect(dataPost.postData).not.toHaveBeenCalled();

            document.getElementById(linkId).click();
            expect(dataPost.postData).toHaveBeenCalledWith({
                action: linkUrl,
                data: config
            });
        });
    });
});
