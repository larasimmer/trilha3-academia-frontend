<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\VisualMerchandiser\Model\Position;

use Magento\TestFramework\Helper\Bootstrap;

/**
 * @magentoAppArea adminhtml
 */
class CacheTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @return void
     */
    public function testSaveData()
    {
        $key = 'position_cache_key';

        $positions = array_combine(range(1, 3), range(0, 2));
        $sortOrder = 0;
        $positionCache = Bootstrap::getObjectManager()->create(Cache::class);
        $positionCache->saveData($key, $positions, $sortOrder);
        $positionCache = Bootstrap::getObjectManager()->create(Cache::class);
        $this->assertEquals($positions, $positionCache->getPositions($key));
        $this->assertEquals($sortOrder, $positionCache->getSortOrder($key));

        $newPositions = array_combine(range(1, 3), range(3, 5));
        $newSortOrder = 1;
        $positionCache = Bootstrap::getObjectManager()->create(Cache::class);
        $positionCache->saveData($key, $newPositions, $newSortOrder);
        $positionCache = Bootstrap::getObjectManager()->create(Cache::class);
        $this->assertEquals($newPositions, $positionCache->getPositions($key));
        $this->assertEquals($newSortOrder, $positionCache->getSortOrder($key));
    }
}
