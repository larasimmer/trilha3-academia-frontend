<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Staging\Block\Adminhtml\Update;

use Magento\Store\Model\Store;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class PreviewTest extends TestCase
{
    /**
     * @var Preview
     */
    private $block;

    /**
     * @var Store
     */
    private $store;

    protected function setUp(): void
    {
        $this->block = Bootstrap::getObjectManager()->create(Preview::class);
        $this->store = Bootstrap::getObjectManager()->create(Store::class);
    }

    /**
     * Data should not contain deleted or disabled Store.
     *
     * @magentoAppArea adminhtml
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento/Store/_files/second_website_with_two_stores.php
     */
    public function testGetStoreSelectorOptionsForDisabledStore()
    {
        /** @var Store $disabledStore */
        $disabledStore = $this->store->load('fixture_third_store', 'code');
        $disabledStore->setIsActive(0);
        $disabledStore->save();

        $deletedStore = $this->store->load('fixture_second_store', 'code');
        $deletedStore->delete();

        $this->assertCount(1, json_decode($this->block->getStoreSelectorOptions()), 'Data contains extra values.');
    }
}
