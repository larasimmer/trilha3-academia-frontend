<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdvancedCheckout\Helper;

use Magento\Customer\Model\Session;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Class checks advanced checkout helper behaviour
 *
 * @see \Magento\AdvancedCheckout\Helper\Data
 */
class DataTest extends TestCase
{
    /** @var ObjectManagerInterface */
    private $objectManager;

    /** @var Data */
    private $helper;

    /** @var Session */
    private $session;

    /** @var DataObjectFactory */
    private $dataObjectFactory;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->helper = $this->objectManager->get(Data::class);
        $this->session = $this->objectManager->get(Session::class);
        $this->dataObjectFactory = $this->objectManager->get(DataObjectFactory::class);
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        $this->session->logout();

        parent::tearDown();
    }

    /**
     * @magentoConfigFixture current_store sales/product_sku/my_account_enable 2
     * @magentoConfigFixture current_store sales/product_sku/allowed_groups 101
     *
     * @magentoDataFixture Magento/Customer/_files/customer.php
     *
     * @return void
     */
    public function testGetSkuEmptyDataMessageText(): void
    {
        $this->session->loginById(1);
        $this->assertEquals(__('You have not entered a product SKU.'), $this->helper->getSkuEmptyDataMessageText());
    }

    /**
     * @dataProvider itemDataProvider
     *
     * @param array $itemData
     * @param string $expectedMessage
     * @return void
     */
    public function testGetMessageByItem(array $itemData, string $expectedMessage): void
    {
        $item = $this->dataObjectFactory->create(['data' => $itemData]);
        $this->assertEquals($expectedMessage, (string)$this->helper->getMessageByItem($item));
    }

    /**
     * @return array
     */
    public function itemDataProvider(): array
    {
        return [
            'failed_sku' => [
                'data' => [
                    'code' => Data::ADD_ITEM_STATUS_FAILED_SKU,
                ],
                'expectedMessage' => (string)__('The SKU was not found in the catalog.'),
            ],
            'failed_out_of_stock' => [
                'data' => [
                    'code' => Data::ADD_ITEM_STATUS_FAILED_OUT_OF_STOCK,
                ],
                'expectedMessage' => (string)__('Availability: Out of stock.'),
            ],
            'failed_allowed_qty' => [
                'data' => [
                    'code' => Data::ADD_ITEM_STATUS_FAILED_QTY_ALLOWED,
                ],
                'expectedMessage' => (string)__('We don\'t have as many of these as you want.'),
            ],
            'failed_allowed_in_cart' => [
                'data' => [
                    'code' => Data::ADD_ITEM_STATUS_FAILED_QTY_ALLOWED_IN_CART,
                ],
                'expectedMessage' => (string)__('You can\'t add this many to your cart.'),
            ],
            'failed_configure' => [
                'data' => [
                    'code' => Data::ADD_ITEM_STATUS_FAILED_CONFIGURE,
                ],
                'expectedMessage' => (string)__('You need to choose options for your item.'),
            ],
            'failed_permission' => [
                'data' => [
                    'code' => Data::ADD_ITEM_STATUS_FAILED_PERMISSIONS,
                ],
                'expectedMessage' => (string)__('We can\'t add the item to your cart.'),
            ],
            'failed_qty_non_positive' => [
                'data' => [
                    'code' => Data::ADD_ITEM_STATUS_FAILED_QTY_INVALID_NON_POSITIVE,
                ],
                'expectedMessage' => (string)__('Please enter an actual number in the "Qty" field.'),
            ],
            'failed_qty_invalid_number' => [
                'data' => [
                    'code' => Data::ADD_ITEM_STATUS_FAILED_QTY_INVALID_NUMBER,
                ],
                'expectedMessage' => (string)__('Please enter an actual number in the "Qty" field.'),
            ],

            'failed_website' => [
                'data' => [
                    'code' => Data::ADD_ITEM_STATUS_FAILED_WEBSITE,
                ],
                'expectedMessage' => (string)__('This product is assigned to another website.'),
            ],
            'failed_disabled' => [
                'data' => [
                    'code' => Data::ADD_ITEM_STATUS_FAILED_DISABLED,
                ],
                'expectedMessage' => (string)__('You can add only enabled products.'),
            ],
            'failed_another_error' => [
                'data' => [
                    'code' => '',
                    'error' => 'test error',
                ],
                'expectedMessage' => 'test error',
            ],
        ];
    }
}
