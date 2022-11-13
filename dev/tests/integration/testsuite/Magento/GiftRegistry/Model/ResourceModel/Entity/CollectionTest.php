<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftRegistry\Model\ResourceModel\Entity;

use Magento\Framework\ObjectManagerInterface;
use Magento\GiftRegistry\Model\EntityFactory;
use Magento\GiftRegistry\Model\Search\Results\FilterInputs;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Checks the behavior of the gift registry collection
 */
class CollectionTest extends TestCase
{
    /** @var ObjectManagerInterface */
    private $objectManager;

    /** @var EntityFactory */
    private $entityFactory;

    /** @var CollectionFactory */
    private $giftRegistryCollectionFactory;

    /** @var Collection */
    private $giftRegistryCollection;

    /** @var FilterInputs */
    private $filterInputs;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->entityFactory = $this->objectManager->get(EntityFactory::class);
        $this->giftRegistryCollectionFactory = $this->objectManager->get(CollectionFactory::class);
        $this->giftRegistryCollection = $this->giftRegistryCollectionFactory->create();
        $this->filterInputs = $this->objectManager->get(FilterInputs::class);
    }

    /**
     * @magentoDataFixture Magento/GiftRegistry/_files/gift_registry_entity_simple.php
     * @magentoDataFixture Magento/GiftRegistry/_files/gift_registry_entity_birthday_type.php
     * @return void
     */
    public function testApplySearchFiltersWithRegistryId(): void
    {
        $giftRegistry = $this->entityFactory->create();
        $giftRegistry->loadByUrlKey('gift_registry_birthday_type_url');
        $params = [
            'id' => $giftRegistry->getUrlKey(),
            'search' => 'id',
        ];
        $this->giftRegistryCollection->applySearchFilters($this->filterInputs->filterInputParams($params));
        $this->assertCount(
            1,
            $this->giftRegistryCollection,
            'Incorrect amount of gift registries.'
        );
        $this->assertEquals(
            $giftRegistry->getId(),
            $this->giftRegistryCollection->getFirstItem()->getId(),
            'Expected gift registry does not match the received.'
        );
    }
}
