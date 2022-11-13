<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogStaging\Ui\DataProvider\Product\Form\Modifier;

use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Test Magento\CatalogStaging\Ui\DataProvider\Product\Form\Modifier\ScheduleDesignUpdate class
 */
class ScheduleDesignUpdateTest extends TestCase
{
    /**
     * @var ScheduleDesignUpdate
     */
    private $model;

    /**
     * @inheritDoc
     */
    public function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->model = $objectManager->get(ScheduleDesignUpdate::class);
    }

    /**
     * @dataProvider dataTestModifyMetaWithoutCustomDesignComponent
     * @param array $meta
     * @param array $expectMeta
     * @return void
     */
    public function testModifyMetaWithoutCustomDesignComponent(
        array $meta,
        array $expectMeta
    ): void {
        $resultMeta = $this->model->modifyMeta($meta);
        $this->assertEquals($expectMeta, $resultMeta);
    }

    /**
     * @return array
     */
    public function dataTestModifyMetaWithoutCustomDesignComponent(): array
    {
        return [
            [
                'meta' => [
                    'product-details' => [
                        'children' => [
                            'container_status' => [
                                'arguments' => [
                                    'data' => [
                                        'config' => [
                                            'formElement' => 'container',
                                            'componentType' => 'container',
                                            'breakLine' => false,
                                            'label' => 'Enable Product',
                                            'required' => '0',
                                            'sortOrder' => 0,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'expectMeta' => [
                    'product-details' => [
                        'children' => [
                            'container_status' => [
                                'arguments' => [
                                    'data' => [
                                        'config' => [
                                            'formElement' => 'container',
                                            'componentType' => 'container',
                                            'breakLine' => false,
                                            'label' => 'Enable Product',
                                            'required' => '0',
                                            'sortOrder' => 0,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
