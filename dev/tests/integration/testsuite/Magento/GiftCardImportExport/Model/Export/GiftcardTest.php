<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\GiftCardImportExport\Model\Export;

/**
 * Test class for \Magento\GiftCardImportExport\Model\Export\Product\Type\GiftCard.
 *
 * @magentoDataFixtureBeforeTransaction Magento/Catalog/_files/enable_reindex_schedule.php
 * @magentoAppIsolation enabled
 * @magentoDbIsolation enabled
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class GiftcardTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\CatalogImportExport\Model\Export\Product
     */
    private $model;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var \Magento\Framework\Filesystem
     */
    private $fileSystem;

    /**
     * Expected values.
     */
    private $expectedValues = [
        'Simple Gift Card',
        'gift-card-with-open-amount',
        'Gift Card Description',
        'Gift Card Meta Title',
        'Gift Card Meta Keyword',
        'Gift Card Meta Description',
        '110.0000,120.0000',
        'open_amount_min=100.0000',
        'open_amount_max=1500.0000',
        'giftcard_type=Virtual',
        'allow_open_amount=Yes',
        'use_config_email_template=0',
        'email_template=Default',
        'use_config_lifetime=0',
        'lifetime=20',
        'use_config_is_redeemable',
        'use_config_allow_message',
    ];

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
        $this->fileSystem = $this->objectManager->get(\Magento\Framework\Filesystem::class);
        $this->model = $this->objectManager->create(
            \Magento\CatalogImportExport\Model\Export\Product::class
        );
    }

    /**
     * Export GiftCard product and check all unique values.
     *
     * @magentoDataFixture Magento/GiftCard/_files/gift_card_with_open_amount.php
     * @return void
     */
    public function testExport(): void
    {
        $this->model->setWriter(
            $this->objectManager->create(
                \Magento\ImportExport\Model\Export\Adapter\Csv::class
            )
        );
        $exportData = $this->model->export();
        foreach ($this->expectedValues as $value) {
            $this->assertStringContainsString($value, $exportData);
        }
    }
}
