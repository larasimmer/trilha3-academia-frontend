<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Logging\Model\Event;

/**
 * Test Enterprise logging processor
 *
 * @magentoAppArea adminhtml
 */
class ChangesTest extends \Magento\TestFramework\TestCase\AbstractController
{
    /** @var Changes */
    private $changes;

    /**
     * Test that Data is properly cleaned
     *
     * @param array $originalData
     * @param array $expectedOriginalData
     * @param array $resultData
     * @param array $expectedResultData
     * @dataProvider cleanupDataDataProvider
     */

    public function testCleanupData(
        array $originalData,
        array $expectedOriginalData,
        array $resultData,
        array $expectedResultData
    ): void {
        $objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
        $this->changes = $objectManager->get(\Magento\Logging\Model\Event\Changes::class);
        $this->changes->setOriginalData($originalData)->setResultData($resultData);
        $this->changes->cleanupData([]);

        $this->assertEquals($expectedOriginalData, $this->changes->getOriginalData());
        $this->assertEquals($expectedResultData, $this->changes->getResultData());
    }

    /**
     * Cleanup data provider
     *
     * @return array
     */
    public function cleanupDataDataProvider(): array
    {
        $originalData = [
            'name' => 'Simple',
            'sku' => 'Simple-sku',
            'stock_status_and_quantity' => [
                'in_stock' => true,
                'qty' => 150,
            ],
            'creation_date' => '07/22/2018',
            'media' => [
                'images' => [
                    'somePath\to\image1.jpg',
                    'somePath\to\image2.jpg',
                ],
                'thumbnails' => [
                    'somePath\to\thumbnail1.jpg',
                    'somePath\to\thumbnail2.jpg',
                ]
            ],
            'related_object' => new \Magento\Framework\DataObject(),
        ];

        $expectedOriginalData = [
            'name' => 'Simple',
            'sku' => 'Simple-sku',
            'stock_status_and_quantity [in_stock]' => true,
            'stock_status_and_quantity [qty]' => 150,
            'creation_date' => '07/22/2018',
        ];

        $resultData = [
            'name' => 'Config',
            'sku' => 'Config-sku',
            'stock_status_and_quantity' => [
                'in_stock' => false,
                'qty' => 0,
            ],
            'modify_date' => '07/22/2018',
        ];

        $expectedResultData = [
            'name' => 'Config',
            'sku' => 'Config-sku',
            'stock_status_and_quantity [in_stock]' => false,
            'stock_status_and_quantity [qty]' => 0,
            'modify_date' => '07/22/2018',
        ];

        return [
            [$originalData, $expectedOriginalData, $resultData, $expectedResultData],
        ];
    }
}
