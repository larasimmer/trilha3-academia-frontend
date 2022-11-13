<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerImportExport\Model\Export;

class CustomAddressTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Address entity adapter instance
     *
     * @var Address
     */
    private $entityAdapter;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->entityAdapter = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(Address::class);
    }

    /**
     * @magentoDataFixture Magento/CustomerCustomAttributes/_files/customer_with_address_custom_attributes.php
     */
    public function testExport()
    {
        $expectedCsv = file_get_contents(__DIR__ . '/_file/expected_custom_adress.csv');
        $this->entityAdapter->setWriter(
            \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(
                \Magento\ImportExport\Model\Export\Adapter\Csv::class
            )
        );
        $this->entityAdapter->setParameters([]);

        $this->assertEquals($expectedCsv, $this->entityAdapter->export());
    }
}
