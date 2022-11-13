<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerImportExport\Model\Import;

use Magento\Framework\App\Filesystem\DirectoryList;

/**
 * Class CustomAddressTest
 */
class CustomAddressTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Address entity adapter instance
     *
     * @var Address
     */
    private $entityAdapter;

    /**
     * @var \Magento\Eav\Api\AttributeRepositoryInterface
     */
    private $attributeManagement;

    /**
     * Init new instance of address entity adapter
     */
    protected function setUp(): void
    {
        $this->attributeManagement = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->get(
            \Magento\Eav\Api\AttributeRepositoryInterface::class
        );
        $this->entityAdapter = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(
            \Magento\CustomerImportExport\Model\Import\Address::class
        );
    }

    /**
     * Test import data validation for address with custom attribute
     *
     * @magentoDataFixture Magento/CustomerCustomAttributes/_files/address_custom_attribute.php
     * @magentoDataFixture Magento/Customer/_files/import_export/customers_for_address_import.php
     */
    public function testImportData()
    {
        // set behaviour
        $this->entityAdapter->setParameters(
            ['behavior' => \Magento\ImportExport\Model\Import::BEHAVIOR_ADD_UPDATE]
        );

        // set fixture CSV file
        $sourceFile = __DIR__ . '/_files/address_with_custom_attribute.csv';
        $tempFile = __DIR__ . '/_files/temp_address_csv.csv';

        $multiselectAttribute = $this->attributeManagement->get('customer_address', 'multi_select_attribute_code');
        $selectAttribute = $this->attributeManagement->get('customer_address', 'test_select_code');
        $multiselectAttrOpts = $this->entityAdapter->getAttributeOptions($multiselectAttribute, ['country_id']);
        $selectAttrOpts = $this->entityAdapter->getAttributeOptions($selectAttribute, ['country_id']);

        $multiselectOptions = [
            1 => 'Option 2,Option 3',
            2 => 'Option 1'
        ];
        $expectedMultiselectOptionsIds = [
            1 => $multiselectAttrOpts[mb_strtolower('Option 2')] . ','
                . $multiselectAttrOpts[mb_strtolower('Option 3')],
            2 => $multiselectAttrOpts[mb_strtolower('Option 1')],
        ];
        $selectOptions = [
            1 => 'Second',
            2 => 'First'
        ];
        $expectedSelectOptionsIds = [
            1 => $selectAttrOpts[mb_strtolower('Second')],
            2 => $selectAttrOpts[mb_strtolower('First')],
        ];

        /** @var $objectManager \Magento\TestFramework\ObjectManager */
        $objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
        /** @var \Magento\Framework\Filesystem $filesystem */
        $filesystem = $objectManager->create(\Magento\Framework\Filesystem::class);
        $sourceFileContents = $filesystem->getDirectoryRead(DirectoryList::ROOT)->readFile($sourceFile);

        foreach ($multiselectOptions as $key => $option) {
            $sourceFileContents = str_replace('{multiselect_' . $key . '}', $option, $sourceFileContents);
        }
        foreach ($selectOptions as $key => $option) {
            $sourceFileContents = str_replace('{select_' . $key . '}', $option, $sourceFileContents);
        }

        $directoryWrite = $filesystem->getDirectoryWrite(DirectoryList::ROOT);

        try {
            $directoryWrite->writeFile($tempFile, $sourceFileContents);
            $validateResult = $this->entityAdapter->setSource(
                \Magento\ImportExport\Model\Import\Adapter::findAdapterFor($tempFile, $directoryWrite)
            )
                ->validateData()
                ->hasToBeTerminated();

            $this->assertFalse($validateResult);
            $this->assertTrue($this->entityAdapter->importData());

            $requiredAttributes = ['multi_select_attribute_code', 'test_select_code'];
            // get addresses
            $addressCollection = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(
                \Magento\Customer\Model\ResourceModel\Address\Collection::class
            );
            $addressCollection->addAttributeToSelect($requiredAttributes);

            /** @var \Magento\Customer\Model\Address $address */
            foreach ($addressCollection as $address) {
                $this->assertEquals(
                    $expectedMultiselectOptionsIds[$address->getEntityId()],
                    $address->getData('multi_select_attribute_code')
                );
                $this->assertEquals(
                    $expectedSelectOptionsIds[$address->getEntityId()],
                    $address->getData('test_select_code')
                );
            }
        } finally {
            $directoryWrite->delete($tempFile);
        }
    }
}
