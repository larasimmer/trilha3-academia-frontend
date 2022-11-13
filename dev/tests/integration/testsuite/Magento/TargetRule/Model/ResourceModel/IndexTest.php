<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\TargetRule\Model\ResourceModel;

use Magento\TargetRule\Model\ResourceModel\Index as TargetRuleIndex;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Test for \Magento\TargetRule\Model\ResourceModel\Index
 */
class IndexTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var TargetRuleIndex
     */
    private $model;

    protected function setUp(): void
    {
        $this->model = Bootstrap::getObjectManager()->create(TargetRuleIndex::class);
    }

    /**
     * @return array
     */
    public function getOperatorConditionDataProvider(): array
    {
        return [
            ['category_id', '==', 1, '`category_id`=1'],
            ['category_id', '>=', 1, '`category_id`>=1'],
            ['category_id', '()', [2, 4], '`category_id` IN(2, 4)'],
            ['category_id', '!()', [2, 4], '`category_id` NOT IN(2, 4)'],
            ['category_id', '{}', 8, '`category_id` LIKE \'%8%\''],
            ['category_id', '!{}', 8, '`category_id` NOT LIKE \'%8%\''],
            ['value', '{}', [9, 10], '`value` IN (9, 10)'],
            ['value', '!{}', [9, 10], '`value` NOT IN (9, 10)'],
            ['value', '{}', 5, '`value` LIKE \'%5%\''],
            ['value', '!{}', 5, '`value` NOT LIKE \'%5%\''],
        ];
    }

    /**
     * @param string $field
     * @param string $operator
     * @param mixed $value
     * @param string $expected
     *
     * @dataProvider getOperatorConditionDataProvider
     *
     * @return void
     */
    public function testGetOperatorCondition(string $field, string $operator, $value, string $expected): void
    {
        $result = $this->model->getOperatorCondition($field, $operator, $value);

        $this->assertEquals($expected, $result);
    }
}
