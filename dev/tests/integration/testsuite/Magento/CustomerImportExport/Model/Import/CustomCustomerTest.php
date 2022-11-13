<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerImportExport\Model\Import;

use Magento\Framework\App\Filesystem\DirectoryList;

/**
 * Test for customer import model with custom attributes
 */
class CustomCustomerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Customer entity adapter instance
     *
     * @var Customer
     */
    private $entityAdapter;

    /**
     * @var \Magento\Eav\Api\AttributeRepositoryInterface
     */
    private $attributeManagement;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->attributeManagement = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->get(
            \Magento\Eav\Api\AttributeRepositoryInterface::class
        );
        $this->entityAdapter = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(
            \Magento\CustomerImportExport\Model\Import\Customer::class
        );
    }

    /**
     * @magentoDataFixture Magento/CustomerCustomAttributes/_files/customer_custom_attribute.php
     * @magentoDataFixture Magento/Customer/_files/import_export/customer.php
     */
    public function testImportData()
    {
        $this->entityAdapter->setParameters(
            ['behavior' => \Magento\ImportExport\Model\Import::BEHAVIOR_ADD_UPDATE]
        );

        // set fixture CSV file
        $sourceFile = __DIR__ . '/_files/customer_with_custom_attribute.csv';

        $multiselectAttribute = $this->attributeManagement->get('customer', 'multi_select_attribute_code');
        $selectAttribute = $this->attributeManagement->get('customer', 'test_select_code');
        $multiselectAttrOpts = $this->entityAdapter->getAttributeOptions($multiselectAttribute, ['country_id']);
        $selectAttrOpts = $this->entityAdapter->getAttributeOptions($selectAttribute, ['country_id']);

        $expectedMultiselectOptionsIds = $multiselectAttrOpts[mb_strtolower('Option 2')] . ','
            . $multiselectAttrOpts[mb_strtolower('Option 3')];
        $expectedSelectOptionsIds = $selectAttrOpts[mb_strtolower('Second')];

        /** @var $objectManager \Magento\TestFramework\ObjectManager */
        $objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
        /** @var \Magento\Framework\Filesystem $filesystem */
        $filesystem = $objectManager->create(\Magento\Framework\Filesystem::class);

        $directoryWrite = $filesystem->getDirectoryWrite(DirectoryList::ROOT);

        $validateResult = $this->entityAdapter->setSource(
            \Magento\ImportExport\Model\Import\Adapter::findAdapterFor($sourceFile, $directoryWrite)
        )
            ->validateData()
            ->hasToBeTerminated();

        $this->assertFalse($validateResult);
        $this->assertTrue($this->entityAdapter->importData());

        $requiredAttributes = ['multi_select_attribute_code', 'test_select_code'];

        /** @var \Magento\Customer\Model\ResourceModel\Customer\Collection $customersCollection */
        $customersCollection = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(
            \Magento\Customer\Model\ResourceModel\Customer\Collection::class
        );
        $customersCollection->addAttributeToSelect($requiredAttributes);

        if ($customersCollection->count() !== 1) {
            $this->fail('Expected one item!');
        }

        $customer = $customersCollection->getFirstItem();
        $this->assertEquals($expectedMultiselectOptionsIds, $customer->getData('multi_select_attribute_code'));
        $this->assertEquals($expectedSelectOptionsIds, $customer->getData('test_select_code'));
    }
}
