<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftWrapping\Controller\Adminhtml\Giftwrapping;

use Magento\GiftWrapping\Model\Wrapping;
use Magento\GiftWrapping\Model\ResourceModel\Wrapping\Collection;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Message\MessageInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\AbstractBackendController;

/**
 * Test for class \Magento\GiftWrapping\Controller\Adminhtml\Giftwrapping\Delete
 *
 * @magentoAppArea adminhtml
 */
class DeleteTest extends AbstractBackendController
{
    /** Test Delete GiftWrapping
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     * @magentoDataFixture Magento/GiftWrapping/_files/wrapping.php
     * @return void
     */
    public function testDeleteGiftWrapping(): void
    {
        $wrapping=$this->getTestFixture();
        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);
        $this->getRequest()->setPostValue(['id' => $wrapping->getId()]);
        $this->dispatch('backend/admin/giftwrapping/delete');
        $this->assertSessionMessages($this->equalTo([(string)__('You deleted the gift wrapping.')]));
    }

    /** Test Delete Incorrect GiftWrapping Id
     * @return void
     */
    public function testDeleteMissingGiftWrapping(): void
    {
        $incorrectId = 8;
        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);
        $this->getRequest()->setPostValue(['id' => $incorrectId]);
        $this->dispatch('backend/admin/giftwrapping/delete');
        $this->assertSessionMessages(
            $this->equalTo([(string)__('Requested gift wrapping does not exist.')]),
            MessageInterface::TYPE_ERROR
        );
    }

    /**
     * Gets Wrapping Fixture.
     *
     * @return Wrapping
     */
    private function getTestFixture(): Wrapping
    {
        /** @var Collection $wrappingCollection */
        $wrappingCollection = Bootstrap::getObjectManager()->create(Collection::class);
        return $wrappingCollection->getLastItem();
    }
}
