<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCard\Observer;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\MailException;
use Magento\Framework\Registry;
use Magento\GiftCard\Model\Giftcard;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Item;
use Magento\Store\Model\ScopeInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\GiftCard\Model\Catalog\Product\Type\Giftcard as ProductGiftCard;
use Magento\Config\Model\Config;
use Magento\Sales\Model\EmailSenderHandler;
use Magento\TestFramework\Mail\Template\TransportBuilderMock;
use Magento\TestFramework\Mail\TransportInterfaceMock;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Config\MutableScopeConfigInterface;
use Magento\GiftCardAccount\Model\Pool;
use Magento\GiftCardAccount\Model\ResourceModel\Pool\Collection as PoolCollection;
use Magento\GiftCard\Model\Catalog\Product\Type\Giftcard as TypeGiftcard;
use Magento\GiftCardAccount\Model\Pool\AbstractPool;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @magentoDbIsolation disabled
 */
class GenerateGiftCardAccountsTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var OrderInterface[]
     */
    private $orders = [];

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->registry = $this->objectManager->get(Registry::class);
    }

    /**
     * @inheritDoc
     */
    protected function tearDown(): void
    {
        $this->deleteOrder();
        $this->objectManager->removeSharedInstance(TransportBuilderMock::class);
        parent::tearDown();
    }

    /**
     * Tests the controller for declines
     *
     * @magentoDataFixture Magento/GiftCard/_files/giftcard_on_ordered_setting.php
     * @magentoDataFixture Magento/GiftCard/_files/gift_card.php
     * @magentoDataFixture Magento/GiftCard/_files/order_with_gift_card.php
     */
    public function testGiftcardGeneratorOnOrderAfterSaveSetting()
    {
        $order = $this->getOrder();
        /** @var ScopeConfigInterface $config */
        $config = $this->objectManager->get(ScopeConfigInterface::class);
        $giftcardSetting = $config->getValue(
            Giftcard::XML_PATH_ORDER_ITEM_STATUS,
            ScopeInterface::SCOPE_STORE,
            $order->getStore()
        );
        $this->assertEquals(Item::STATUS_PENDING, $giftcardSetting);
        /** @var Item $orderItem */
        $orderItem = $this->getGiftcardItem($order);
        $productOptions = $orderItem->getProductOptions();

        $this->assertArrayHasKey('email_sent', $productOptions);
        $this->assertArrayHasKey('giftcard_created_codes', $productOptions);
        $this->assertEquals('1', $productOptions['email_sent']);
        $this->assertEquals(
            2,
            count($productOptions['giftcard_created_codes'])
        );
    }

    /**
     * Test for generation girtcard code after saving order with MailException
     *
     * @magentoDataFixture Magento/GiftCard/_files/giftcard_on_ordered_setting.php
     * @magentoDataFixture Magento/GiftCard/_files/gift_card.php
     * @magentoDataFixture Magento/Catalog/_files/product_simple.php
     *
     * @return void
     */
    public function testGiftcardGeneratorOnOrderAfterSaveWithMailException(): void
    {
        $this->mockTransportBuilder();
        $order = $this->createOrder();
        /** @var ScopeConfigInterface $config */
        $config = $this->objectManager->get(ScopeConfigInterface::class);
        $giftcardSetting = $config->getValue(
            Giftcard::XML_PATH_ORDER_ITEM_STATUS,
            ScopeInterface::SCOPE_STORE,
            $order->getStore()
        );
        $this->assertEquals(Item::STATUS_PENDING, $giftcardSetting);
        $poolCollection = $this->objectManager->get(PoolCollection::class);
        $items = $poolCollection->getItems();
        $item = array_pop($items);

        $this->assertEquals(AbstractPool::STATUS_USED, $item->getStatus());
    }

    /**
     * Create order with giftcard
     *
     * @return OrderInterface
     */
    private function createOrder(): OrderInterface
    {
        $this->objectManager->get(MutableScopeConfigInterface::class)
            ->setValue(Pool::XML_CONFIG_POOL_SIZE, 2, 'website', 'base');
        /** @var $pool Pool */
        $pool = Bootstrap::getObjectManager()->create(Pool::class);
        $pool->setWebsiteId(1)
            ->generatePool();
        $productRepository = $this->objectManager->get(ProductRepositoryInterface::class);
        $product = $productRepository->get('simple');
        /** @var $billingAddress \Magento\Sales\Model\Order\Address */
        $billingAddress = Bootstrap::getObjectManager()->create(
            \Magento\Sales\Model\Order\Address::class,
            [
                'data' => [
                    'firstname' => 'guest',
                    'lastname' => 'guest',
                    'email' => 'customer@example.com',
                    'street' => 'street',
                    'city' => 'Los Angeles',
                    'region' => 'CA',
                    'postcode' => '1',
                    'country_id' => 'US',
                    'telephone' => '1',
                ]
            ]
        );
        $billingAddress->setAddressType('billing');
        $shippingAddress = clone $billingAddress;
        $shippingAddress->setId(null)->setAddressType('shipping');
        /** @var $payment \Magento\Sales\Model\Order\Payment */
        $payment = Bootstrap::getObjectManager()->create(
            \Magento\Sales\Model\Order\Payment::class
        );
        $payment->setMethod('checkmo');
        /** @var $orderGiftCardItem Item */
        $orderGiftCardItem = Bootstrap::getObjectManager()->create(
            Item::class
        );
        $orderGiftCardItem->setProductId(1)
            ->setProductType(TypeGiftcard::TYPE_GIFTCARD)
            ->setBasePrice(100)
            ->setQtyOrdered(2)
            ->setStoreId(1)
            ->setProductOptions(
                [
                    'giftcard_amount' => 'custom',
                    'custom_giftcard_amount' => 100,
                    'giftcard_sender_name' => 'Gift Card Sender Name',
                    'giftcard_sender_email' => 'sender@example.com',
                    'giftcard_recipient_name' => 'Gift Card Recipient Name',
                    'giftcard_recipient_email' => 'recipient@example.com',
                    'giftcard_message' => 'Gift Card Message',
                    'giftcard_email_template' => 'giftcard_email_template',
                ]
            );
        /** @var Item $orderItemSimple */
        $orderItemSimple = $this->objectManager->get(Item::class);
        $orderItemSimple->setProductId($product->getId())
            ->setQtyOrdered(1)
            ->setBasePrice($product->getPrice())
            ->setPrice($product->getPrice())
            ->setRowTotal($product->getPrice())
            ->setProductType('simple');
        /** @var $order Order */
        $order = $this->objectManager->get(Order::class);
        $order->setCustomerEmail('mail@to.co')
            ->addItem($orderItemSimple)
            ->addItem($orderGiftCardItem)
            ->setCustomerEmail('someone@example.com')
            ->setIncrementId('100000001')
            ->setCustomerIsGuest(true)
            ->setStoreId(1)
            ->setEmailSent(1)
            ->setBillingAddress($billingAddress)
            ->setShippingAddress($shippingAddress)
            ->setPayment($payment);

        return $this->orders[] = $this->objectManager->get(OrderRepositoryInterface::class)
            ->save($order);
    }

    /**
     * Delete order
     *
     * @return void
     */
    private function deleteOrder(): void
    {
        $this->registry->unregister('isSecureArea');
        $this->registry->register('isSecureArea', true);
        $orderRepository = $this->objectManager->get(OrderRepositoryInterface::class);
        foreach ($this->orders as $order) {
            $orderRepository->delete($order);
        }
        $this->registry->unregister('isSecureArea');
        $this->registry->register('isSecureArea', false);
    }

    /**
     * Replace testing framework transport builder.
     *
     * @return void
     */
    private function mockTransportBuilder(): void
    {
        $transportBuilder = $this->getMockBuilder(TransportBuilderMock::class)
            ->disableOriginalConstructor()
            ->getMock();
        $transport = $this->getMockBuilder(TransportInterfaceMock::class)
            ->onlyMethods(['sendMessage'])
            ->getMock();
        $transport->method('sendMessage')
            ->willThrowException(new MailException(__('Unable to send mail')));
        $this->objectManager->addSharedInstance($transportBuilder, TransportBuilderMock::class);
    }

    /**
     * Tests the controller for declines
     *
     * @magentoDataFixture Magento/GiftCardAccount/_files/codes_pool.php
     * @magentoDataFixture Magento/GiftCard/_files/giftcard_on_invoiced_setting.php
     * @magentoDataFixture Magento/GiftCard/_files/gift_card.php
     * @magentoDataFixture Magento/GiftCard/_files/order_with_gift_card.php
     */
    public function testGiftcardGeneratorOnInvoiceAfterSaveSettingNoGenerate()
    {
        $order = $this->getOrder();
        /** @var ScopeConfigInterface $config */
        $config = $this->objectManager->get(ScopeConfigInterface::class);
        $giftcardSetting = $config->getValue(
            Giftcard::XML_PATH_ORDER_ITEM_STATUS,
            ScopeInterface::SCOPE_STORE,
            $order->getStore()
        );
        $this->assertEquals(Item::STATUS_INVOICED, $giftcardSetting);
        /** @var Item $orderItem */
        $orderItem = $this->getGiftcardItem($order);
        $productOptions = $orderItem->getProductOptions();

        $this->assertArrayNotHasKey('email_sent', $productOptions);
        $this->assertArrayNotHasKey('giftcard_created_codes', $productOptions);
    }

    /**
     * Tests that giftcard account codes are generated after invoice creation.
     *
     * @magentoDataFixture Magento/GiftCardAccount/_files/codes_pool.php
     * @magentoDataFixture Magento/GiftCard/_files/gift_card.php
     * @magentoDataFixture Magento/GiftCard/_files/invoice_with_gift_card.php
     */
    public function testGiftcardGeneratorOnInvoiceAfterSaveSettingGenerate()
    {
        $order = $this->getOrder();
        /** @var ScopeConfigInterface $config */
        $config = $this->objectManager->get(ScopeConfigInterface::class);
        $giftcardSetting = $config->getValue(
            Giftcard::XML_PATH_ORDER_ITEM_STATUS,
            ScopeInterface::SCOPE_STORE,
            $order->getStore()
        );
        $this->assertEquals(Item::STATUS_INVOICED, $giftcardSetting);
        /** @var Item $orderItem */
        $orderItem = $this->getGiftcardItem($order);
        $productOptions = $orderItem->getProductOptions();

        $this->assertArrayHasKey('email_sent', $productOptions);
        $this->assertArrayHasKey('giftcard_created_codes', $productOptions);
        $this->assertEquals('1', $productOptions['email_sent']);
        $this->assertCount(2, $productOptions['giftcard_created_codes']);
    }

    /**
     * Tests that giftcard account codes are generated after invoice creation and email sending
     *
     * @magentoDataFixture Magento/GiftCardAccount/_files/codes_pool.php
     * @magentoDataFixture Magento/GiftCard/_files/gift_card.php
     * @magentoDataFixture Magento/GiftCard/_files/invoice_with_gift_card.php
     */
    public function testGiftcardGeneratorOnInvoiceAfterAsyncEmailSending()
    {
        /** @var Config $defConfig */
        $defConfig = $this->objectManager->create(Config::class);
        $defConfig->setScope(ScopeConfigInterface::SCOPE_TYPE_DEFAULT);
        $defConfig->setDataByPath('sales_email/general/async_sending', 1);
        $defConfig->save();

        $this->getEmailSender()->sendEmails();

        $order = $this->getOrder();
        /** @var ScopeConfigInterface $config */
        $config = $this->objectManager->get(ScopeConfigInterface::class);
        $giftcardSetting = $config->getValue(
            Giftcard::XML_PATH_ORDER_ITEM_STATUS,
            ScopeInterface::SCOPE_STORE,
            $order->getStore()
        );
        $this->assertEquals(Item::STATUS_INVOICED, $giftcardSetting);
        /** @var Item $orderItem */
        $orderItem = $this->getGiftcardItem($order);
        $productOptions = $orderItem->getProductOptions();

        $this->assertArrayHasKey('email_sent', $productOptions);
        $this->assertArrayHasKey('giftcard_created_codes', $productOptions);
        $this->assertEquals('1', $productOptions['email_sent']);
        $this->assertCount(2, $productOptions['giftcard_created_codes']);
    }

    /**
     * Create email sender
     *
     * @return EmailSenderHandler
     */
    private function getEmailSender(): EmailSenderHandler
    {
        $invoiceIdentity = $this->objectManager->create(
            \Magento\Sales\Model\Order\Email\Container\InvoiceIdentity::class
        );
        $invoiceSender = $this->objectManager
            ->create(
                \Magento\Sales\Model\Order\Email\Sender\InvoiceSender::class,
                [
                    'identityContainer' => $invoiceIdentity,
                ]
            );
        $entityResource = $this->objectManager->create(
            \Magento\Sales\Model\ResourceModel\Order\Invoice::class
        );
        $entityCollection = $this->objectManager->create(
            \Magento\Sales\Model\ResourceModel\Order\Invoice\Collection::class
        );
        return $this->objectManager->create(
            EmailSenderHandler::class,
            [
                'emailSender' => $invoiceSender,
                'entityResource' => $entityResource,
                'entityCollection' => $entityCollection,
                'identityContainer' => $invoiceIdentity,
            ]
        );
    }

    /**
     * Tests that giftcard account codes are generated if payments action is "Sale".
     *
     * @magentoDataFixture Magento/GiftCardAccount/_files/codes_pool.php
     * @magentoConfigFixture current_store payment/payflowpro/active 1
     * @magentoConfigFixture current_store payment/payflowpro_cc_vault/active 1
     * @magentoConfigFixture current_store payment/payflowpro/payment_action Sale
     * @magentoDataFixture Magento/GiftCard/Fixtures/order_invoice_payflowpro_with_gift_card.php
     */
    public function testGiftcardGeneratorForSale()
    {
        $order = $this->getOrder('100000002');
        /** @var ScopeConfigInterface $config */
        $config = $this->objectManager->get(ScopeConfigInterface::class);
        $giftcardSetting = $config->getValue(
            Giftcard::XML_PATH_ORDER_ITEM_STATUS,
            ScopeInterface::SCOPE_STORE,
            $order->getStore()
        );
        $this->assertEquals(Item::STATUS_INVOICED, $giftcardSetting);
        /** @var Item $orderItem */
        $orderItem = $this->getGiftcardItem($order);
        $productOptions = $orderItem->getProductOptions();

        $this->assertArrayHasKey('email_sent', $productOptions);
        $this->assertArrayHasKey('giftcard_created_codes', $productOptions);
        $this->assertEquals('1', $productOptions['email_sent']);
        $this->assertCount(2, $productOptions['giftcard_created_codes']);
    }

    /**
     * Returns giftcard item from order.
     *
     * @param Order $order
     * @return OrderItemInterface|null
     */
    private function getGiftcardItem(Order $order)
    {
        foreach ($order->getItems() as $item) {
            if ($item->getProductType() === ProductGiftCard::TYPE_GIFTCARD) {
                return $item;
            }
        }

        return null;
    }

    /**
     * Get stored order
     *
     * @param string $incrementId
     *
     * @return \Magento\Sales\Model\Order
     */
    private function getOrder(string $incrementId = '100000001')
    {
        /** @var FilterBuilder $filterBuilder */
        $filterBuilder = $this->objectManager->get(FilterBuilder::class);
        $filters = [
            $filterBuilder->setField(OrderInterface::INCREMENT_ID)
                ->setValue($incrementId)
                ->create()
        ];

        /** @var SearchCriteriaBuilder $searchCriteriaBuilder */
        $searchCriteriaBuilder = $this->objectManager->get(SearchCriteriaBuilder::class);
        $searchCriteria = $searchCriteriaBuilder->addFilters($filters)
            ->create();

        $orderRepository = $this->objectManager->get(OrderRepositoryInterface::class);
        $orders = $orderRepository->getList($searchCriteria)
            ->getItems();

        /** @var OrderInterface $order */
        return array_pop($orders);
    }
}
