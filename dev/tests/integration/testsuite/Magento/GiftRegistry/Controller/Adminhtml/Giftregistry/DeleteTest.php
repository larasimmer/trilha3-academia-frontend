<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftRegistry\Controller\Adminhtml\Giftregistry;

use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Message\MessageInterface;
use Magento\TestFramework\TestCase\AbstractBackendController;

/**
 * Test for class \Magento\GiftRegistry\Controller\Adminhtml\Giftregistry\Delete
 *
 * @magentoAppArea adminhtml
 */
class DeleteTest extends AbstractBackendController
{

    /** Test Delete Registry
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     * @magentoDataFixture Magento/GiftRegistry/_files/gift_registry_entity_simple.php
     * @return void
     */
    public function testDeleteGiftRegistry(): void
    {
        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);
        $this->getRequest()->setPostValue(['id' => 1]);
        $this->dispatch('backend/admin/giftregistry/delete');
        $this->assertSessionMessages($this->equalTo([(string)__('You deleted the gift registry type.')]));
    }

    /** Test Delete Incorrect Gift Registry ID
     * @return void
     */
    public function testDeleteMissingGiftRegistry(): void
    {
        $incorrectId = 8;
        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);
        $this->getRequest()->setPostValue(['id' => $incorrectId]);
        $this->dispatch('backend/admin/giftregistry/delete');
        $this->assertSessionMessages(
            $this->equalTo([(string)__('The gift registry ID is incorrect. Verify the ID and try again.')]),
            MessageInterface::TYPE_ERROR
        );
    }
}
