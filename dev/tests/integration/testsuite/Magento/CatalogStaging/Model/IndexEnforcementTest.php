<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogStaging\Model;

use Magento\CatalogSearch\Model\ResourceModel\Search\Collection;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Tests Update Repository functionality
 *
 * @magentoDbIsolation enabled
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class IndexEnforcementTest extends TestCase
{
    /** @var Collection */
    private $collection;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->collection = $objectManager->create(Collection::class);
    }

    /**
     * Checks if force index hints are present in the select
     */
    public function testIndexEnforcementsArePresent()
    {
        $this->collection->addSearchFilter('test');
        $select = $this->collection->getSelect();
        self::assertStringContainsString('FORCE INDEX', (string) $select);
    }
}
