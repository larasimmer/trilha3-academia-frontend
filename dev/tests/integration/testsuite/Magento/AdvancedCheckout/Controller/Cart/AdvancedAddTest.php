<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdvancedCheckout\Controller\Cart;

use Magento\AdvancedCheckout\Helper\Data;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\AbstractController;

/**
 * Class checks add products by sku controller
 *
 * @see \Magento\AdvancedCheckout\Controller\Cart\AdvancedAdd
 *
 * @magentoAppArea frontend
 */
class AdvancedAddTest extends AbstractController
{
    /** @var SerializerInterface */
    private $json;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->json = $this->_objectManager->get(SerializerInterface::class);
    }

    /**
     * @dataProvider postDataProvider
     *
     * @magentoDataFixture Magento/Catalog/_files/second_product_simple.php
     *
     * @param array $postData
     * @param string $message
     * @return void
     */
    public function testAdvancedAddWithInvalidPost(array $postData, string $message): void
    {
        $this->dispatchWithData($postData);
        $this->assertNull($this->getRequest()->getCookie('add_to_cart'));
        $this->assertSessionMessages($this->containsEqual($message));
    }

    /**
     * @return array
     */
    public function postDataProvider(): array
    {
        return [
            'empty_items' => [
                [
                    'items' => [],
                ],
                'expected_message' => (string)__(
                    'You have not entered a product SKU. Please <a href="%1">click here</a> to add product(s) by SKU.',
                    $this->getAccountSkuUrl()
                ),
            ],
            'without_sku' => [
                [
                    'items' => [
                        ['qty' => 1],
                    ],
                ],
                'expected_message' => (string)__(
                    'You have not entered a product SKU. Please <a href="%1">click here</a> to add product(s) by SKU.',
                    $this->getAccountSkuUrl()
                ),
            ],
            'without_qty' => [
                [
                    'items' => [
                        ['sku' => 'simple2'],
                    ],
                ],
                'expected_message' => (string)__('1 product requires your attention.'),
            ],
        ];
    }

    /**
     * @magentoDataFixture Magento/Catalog/_files/second_product_simple.php
     *
     * @return void
     */
    public function testAdvancedAddProduct(): void
    {
        $postData = [
            'items' => [
                ['sku' => 'simple2', 'qty' => 1],
            ],
        ];
        $expectedCookie = [
            [
                'sku' => 'simple2',
                'name' => 'Simple Product2',
                'price' => 10,
                'qty' => 1,
            ]
        ];
        $this->dispatchWithData($postData);
        $this->assertCookie($expectedCookie);
        $this->assertSessionMessages($this->containsEqual((string)__('You added 1 product to your shopping cart.')));
    }

    /**
     * Assert that cookies was set
     *
     * @param array $expected
     * @return void
     */
    private function assertCookie(array $expected): void
    {
        $cookie = $this->getRequest()->getCookie('add_to_cart');
        $this->assertNotNull($cookie);
        $cookie = $this->json->unserialize(rawurldecode($cookie));
        $this->assertEquals($expected, $cookie);
    }

    /**
     * Dispatch request with data
     *
     * @param array $postData
     * @return void
     */
    private function dispatchWithData(array $postData): void
    {
        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);
        $this->getRequest()->setPostValue($postData);
        $this->dispatch('customer_order/cart/advancedAdd/');
    }

    /**
     * Get account sku url
     *
     * @return string
     */
    private function getAccountSkuUrl(): string
    {
        return Bootstrap::getObjectManager()->get(Data::class)->getAccountSkuUrl();
    }
}
