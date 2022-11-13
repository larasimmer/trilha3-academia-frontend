<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerImportExport\Model\Export;

class CustomCustomerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Address entity adapter instance
     *
     * @var Customer
     */
    private $entityAdapter;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->entityAdapter = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(Customer::class);
    }

    /**
     * @magentoDataFixture Magento/CustomerCustomAttributes/_files/customer_with_custom_attribute.php
     */
    public function testExport()
    {
        $this->entityAdapter->setWriter(
            \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(
                \Magento\ImportExport\Model\Export\Adapter\Csv::class
            )
        );

        $this->entityAdapter->setParameters([]);
        $exportedData = $this->_csvToArray($this->entityAdapter->export());

        $this->assertEquals('Option 1,Option 2', $exportedData['data'][0]['multi_select_attribute_code']);
        $this->assertEquals('Second', $exportedData['data'][0]['test_select_code']);
    }

    /**
     * Export CSV string to array
     *
     * @param string $content
     * @param mixed $entityId
     * @return array
     */
    private function _csvToArray($content, $entityId = null)
    {
        $data = ['header' => [], 'data' => []];

        $lines = str_getcsv($content, "\n");
        foreach ($lines as $index => $line) {
            if ($index == 0) {
                $data['header'] = str_getcsv($line);
            } else {
                $row = array_combine($data['header'], str_getcsv($line));
                if ($entityId !== null && !empty($row[$entityId])) {
                    $data['data'][$row[$entityId]] = $row;
                } else {
                    $data['data'][] = $row;
                }
            }
        }
        return $data;
    }
}
