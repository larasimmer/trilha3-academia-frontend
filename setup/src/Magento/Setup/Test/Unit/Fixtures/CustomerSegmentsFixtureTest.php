<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Setup\Test\Unit\Fixtures;

use Magento\Catalog\Model\Category;
use Magento\CustomerSegment\Model\Segment;
use Magento\CustomerSegment\Model\Segment\Condition\Combine\Root;
use Magento\CustomerSegment\Model\Segment\Condition\Customer\Attributes;
use Magento\CustomerSegment\Model\SegmentFactory;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Framework\ObjectManager\ObjectManager;
use Magento\SalesRule\Model\Rule;
use Magento\SalesRule\Model\Rule\Condition\Address;
use Magento\SalesRule\Model\Rule\Condition\Combine;
use Magento\SalesRule\Model\Rule\Condition\Product;
use Magento\SalesRule\Model\Rule\Condition\Product\Found;
use Magento\SalesRule\Model\RuleFactory;
use \Magento\Setup\Fixtures\CustomerSegmentsFixture;
use Magento\Setup\Fixtures\FixtureModel;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManager;
use Magento\Store\Model\Website;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CustomerSegmentsFixtureTest extends TestCase
{
    /**
     * @var MockObject|FixtureModel
     */
    private $fixtureModelMock;

    /**
     * @var \Magento\Setup\Fixtures\CartPriceRulesFixture
     */
    private $model;

    /**
     * @var RuleFactory|MockObject
     */
    private $ruleFactoryMock;

    /**
     * @var SegmentFactory|MockObject
     */
    private $segmentFactoryMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->fixtureModelMock = $this->createMock(FixtureModel::class);
        $this->ruleFactoryMock = $this->createPartialMock(RuleFactory::class, ['create']);
        $this->segmentFactoryMock = $this->createPartialMock(
            SegmentFactory::class,
            ['create']
        );
        $this->model = new CustomerSegmentsFixture(
            $this->fixtureModelMock,
            $this->ruleFactoryMock,
            $this->segmentFactoryMock
        );
    }

    /**
     * @return void
     */
    public function testExecute(): void
    {
        $contextMock = $this->createMock(Context::class);
        $abstractDbMock = $this->getMockForAbstractClass(
            AbstractDb::class,
            [$contextMock],
            '',
            true,
            true,
            true,
            ['getAllChildren']
        );
        $abstractDbMock->expects($this->once())
            ->method('getAllChildren')
            ->willReturn([1]);

        $storeMock = $this->createMock(Store::class);
        $storeMock->expects($this->once())
            ->method('getRootCategoryId')
            ->willReturn(2);

        $websiteMock = $this->createMock(Website::class);
        $websiteMock->expects($this->once())
            ->method('getGroups')
            ->willReturn([$storeMock]);
        $websiteMock->expects($this->once())
            ->method('getId')
            ->willReturn('website_id');

        $storeManagerMock = $this->createMock(StoreManager::class);
        $storeManagerMock->expects($this->once())
            ->method('getWebsites')
            ->willReturn([$websiteMock]);

        $categoryMock = $this->createMock(Category::class);
        $categoryMock->expects($this->once())
            ->method('getResource')
            ->willReturn($abstractDbMock);
        $categoryMock->expects($this->once())
            ->method('getPath')
            ->willReturn('path/to/file');
        $categoryMock->expects($this->once())
            ->method('getId')
            ->willReturn('category_id');

        $ruleModelMock = $this->createMock(Rule::class);
        $this->ruleFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($ruleModelMock);

        $segmentModelMock = $this->createMock(Segment::class);
        $this->segmentFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($segmentModelMock);

        $objectValueMap = [[Category::class, $categoryMock]];

        $objectManagerMock = $this->createMock(ObjectManager::class);
        $objectManagerMock->expects($this->once())
            ->method('create')
            ->willReturn($storeManagerMock);
        $objectManagerMock->expects($this->any())
            ->method('get')
            ->willReturnMap($objectValueMap);

        $valueMap = [
            ['customer_segment_rules', 0, 1],
            ['cart_price_rules', 0, 1],
            ['customer_segments', 1, 1]
        ];

        $this->fixtureModelMock
            ->expects($this->any())
            ->method('getValue')
            ->willReturnMap($valueMap);
        $this->fixtureModelMock
            ->expects($this->any())
            ->method('getObjectManager')
            ->willReturn($objectManagerMock);

        $this->model->execute();
    }

    /**
     * @return void
     */
    public function testNoFixtureConfigValue(): void
    {
        $ruleMock = $this->createMock(Rule::class);
        $ruleMock->expects($this->never())->method('save');

        $objectManagerMock = $this->createMock(ObjectManager::class);
        $objectManagerMock->expects($this->never())
            ->method('get')
            ->with($this->equalTo(Rule::class))
            ->willReturn($ruleMock);

        $this->fixtureModelMock->expects($this->never())
            ->method('getObjectManager')
            ->willReturn($objectManagerMock);
        $this->fixtureModelMock->expects($this->once())
            ->method('getValue')
            ->willReturn(0);

        $this->model->execute();
    }

    /**
     * @return void
     */
    public function testGenerateCustomerSegments(): void
    {
        $segmentModelMock = $this->getMockBuilder(Segment::class)
            ->disableOriginalConstructor()
            ->addMethods(['getSegmentId', 'getApplyTo'])
            ->onlyMethods(['save', 'matchCustomers', 'loadPost'])
            ->getMock();

        $data1 = [
            'name'          => 'Customer Segment 0',
            'website_ids'   => [1],
            'is_active'     => '1',
            'apply_to'      => 0
        ];
        $segmentModelMock->expects($this->once())
            ->method('getSegmentId')
            ->willReturn(1);
        $data2 = [
            'name'          => 'Customer Segment 0',
            'segment_id'    => 1,
            'website_ids'   => [1],
            'is_active'     => '1',
            'conditions'    => [
                1 => [
                    'type' => Root::class,
                    'aggregator' => 'any',
                    'value' => '1',
                    'new_child' => ''
                ],
                '1--1' => [
                    'type' => Attributes::class,
                    'attribute' => 'email',
                    'operator' => '==',
                    'value' => 'user_1@example.com'
                ]
            ]
        ];
        $segmentModelMock->method('loadPost')
            ->withConsecutive([$data1], [$data2]);
        $segmentModelMock->expects($this->exactly(2))
            ->method('save');
        $this->segmentFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($segmentModelMock);

        $valueMap = [
            ['customers', 0, 1]
        ];

        $this->fixtureModelMock
            ->expects($this->any())
            ->method('getValue')
            ->willReturnMap($valueMap);

        $reflection = new \ReflectionClass($this->model);
        $reflectionProperty = $reflection->getProperty('customerSegmentsCount');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($this->model, 1);

        $this->model->generateCustomerSegments();
    }

    /**
     * @return void
     */
    public function testGenerateSegmentCondition(): void
    {
        $firstCondition = [
            'type'      => Product::class,
            'attribute' => 'category_ids',
            'operator'  => '==',
            'value'     => 0
        ];

        $secondCondition = [
            'type'      => Address::class,
            'attribute' => 'base_subtotal',
            'operator'  => '>=',
            'value'     => 0
        ];

        $thirdCondition = [
            'type'      => Segment\Condition\Segment::class,
            'operator'  => '==',
            'value'     => 1
        ];
        $expected = [
            'conditions' => [
                1 => [
                    'type' => Combine::class,
                    'aggregator' => 'all',
                    'value' => '1',
                    'new_child' => ''
                ],
                '1--1'=> [
                    'type' => Found::class,
                    'aggregator' => 'all',
                    'value' => '1',
                    'new_child' => ''
                ],
                '1--1--1' => $firstCondition,
                '1--2' => $secondCondition,
                '1--3' => $thirdCondition
            ],
            'actions' => [
                1 => [
                    'type' => Product\Combine::class,
                    'aggregator' => 'all',
                    'value' => '1',
                    'new_child' => ''
                ]
            ]
        ];

        $reflection = new \ReflectionClass($this->model);
        $reflectionProperty = $reflection->getProperty('customerSegmentsCount');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($this->model, 1);
        $result = $this->model->generateSegmentCondition(0, [[0]]);
        $this->assertSame($expected, $result);
    }

    /**
     * @return void
     */
    public function testGetActionTitle(): void
    {
        $this->assertSame('Generating customer segments and rules', $this->model->getActionTitle());
    }

    /**
     * @return void
     */
    public function testIntroduceParamLabels(): void
    {
        $this->assertSame([
            'customer_segment_rules' => 'Customer Segments and Rules'
        ], $this->model->introduceParamLabels());
    }
}
