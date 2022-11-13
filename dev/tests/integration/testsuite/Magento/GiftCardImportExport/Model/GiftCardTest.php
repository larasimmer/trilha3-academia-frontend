<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCardImportExport\Model;

use Magento\CatalogImportExport\Model\AbstractProductExportImportTestCase;

class GiftCardTest extends AbstractProductExportImportTestCase
{
    /**
     * @return array
     */
    public function exportImportDataProvider(): array
    {
        return [
            'gift-card' => [
                [
                    'Magento/GiftCard/_files/gift_card.php'
                ],
                [
                    'gift-card',
                ],
            ],
            'gift-card-with-message' => [
                [
                    'Magento/GiftCard/_files/gift_card_with_available_message.php'
                ],
                [
                    'gift-card-with-allowed-messages',
                ]
            ]
        ];
    }

    /**
     * @inheritdoc
     */
    protected function modifyData(array $skus): void
    {
        $this->objectManager->get(\Magento\CatalogImportExport\Model\Version::class)->create($skus, $this);
    }

    /**
     * Run import/export tests.
     *
     * @magentoAppArea adminhtml
     * @magentoDbIsolation disabled
     * @magentoAppIsolation enabled
     *
     * @param array $fixtures
     * @param string[] $skus
     * @param string[] $skippedAttributes
     * @return void
     * @dataProvider exportImportDataProvider
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function testImportExport(array $fixtures, array $skus, array $skippedAttributes = []): void
    {
        $this->markTestSkipped('Uncomment after MAGETWO-38240 resolved');
    }

    /**
     * Run import/export test with pagination.
     *
     * @magentoAppArea adminhtml
     * @magentoDbIsolation disabled
     * @magentoAppIsolation enabled
     *
     * @param array $fixtures
     * @param string[] $skus
     * @param string[] $skippedAttributes
     * @dataProvider exportImportDataProvider
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function testImportExportWithPagination(array $fixtures, array $skus, array $skippedAttributes = [])
    {
        $this->markTestSkipped('Uncomment after MAGETWO-38240 resolved');
    }

    /**
     * @inheritdoc
     */
    protected function assertEqualsSpecificAttributes(
        \Magento\Catalog\Model\Product $expectedProduct,
        \Magento\Catalog\Model\Product $actualProduct
    ): void {
        foreach ($this->getFieldsToCompare() as $fieldKey => $fieldValue) {
            if (is_array($fieldValue)) {
                if (count($expectedProduct->getData($fieldKey)) > 0) {
                    foreach ($fieldValue as $field) {
                        $valueMatchFound = false;
                        foreach ($expectedProduct->getData($fieldKey) as $expectedData) {
                            $this->assertArrayHasKey($field, $expectedData);
                            foreach ($actualProduct->getData($fieldKey) as $actualData) {
                                $this->assertArrayHasKey($field, $actualData);
                                if ($expectedData[$field] == $actualData[$field]) {
                                    $valueMatchFound = true;
                                    break 2;
                                }
                            }
                        }
                        $this->assertTrue($valueMatchFound, $fieldKey . ' not found');
                    }
                }
            } else {
                $this->assertEquals($expectedProduct->getData($fieldKey), $actualProduct->getData($fieldKey));
            }
        }
    }

    /**
     * Get array of GiftCard Product field mapping to compare
     *
     * @return array
     */
    private function getFieldsToCompare(): array
    {
        return [
            'sku' => false,
            'giftcard_type' => false,
            'is_redeemable' => false,
            'lifetime' => false,
            'allow_message' => false,
            'giftcard_amounts' => ['value'],
         ];
    }
}
