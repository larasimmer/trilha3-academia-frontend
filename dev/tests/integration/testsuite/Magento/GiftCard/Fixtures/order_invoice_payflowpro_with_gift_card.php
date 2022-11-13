<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Address;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Sales\Model\Order\Item;
use Magento\GiftCard\Model\Catalog\Product\Type\Giftcard;
use Magento\Framework\App\Config\MutableScopeConfigInterface;
use Magento\GiftCardAccount\Model\Pool;
use Magento\Sales\Api\InvoiceManagementInterface;
use Magento\Sales\Model\Order\Invoice;
use Magento\Framework\DB\Transaction;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterfaceFactory;
use Magento\Sales\Model\Order\Payment;
use Magento\Vault\Model\PaymentToken;
use Magento\Paypal\Model\Payflow\Transparent;

Resolver::getInstance()->requireDataFixture('Magento/Vault/_files/token.php');

$objectManager = Bootstrap::getObjectManager();
/** @var PaymentToken $token */
$token = $objectManager->create(PaymentToken::class);
$token->load('vault_payment', 'payment_method_code');
$token->setPaymentMethodCode(Transparent::CC_VAULT_CODE);
/** @var OrderPaymentExtensionInterfaceFactory $paymentExtensionFactory */
$paymentExtensionFactory = $objectManager->get(OrderPaymentExtensionInterfaceFactory::class);
$extensionAttributes = $paymentExtensionFactory->create();
$extensionAttributes->setVaultPaymentToken($token);

/** @var Payment $payment */
$payment = $objectManager->create(Payment::class);
$payment->setMethod(Transparent::CC_VAULT_CODE);
$payment->setExtensionAttributes($extensionAttributes);
$payment->setAuthorizationTransaction(true);

$addressData = include INTEGRATION_TESTS_DIR . '/testsuite/Magento/Sales/_files/address_data.php';

$storeId = $objectManager->get(StoreManagerInterface::class)
    ->getStore()
    ->getId();
$websiteId = $objectManager->get(StoreManagerInterface::class)
    ->getWebsite()
    ->getId();

$objectManager->get(MutableScopeConfigInterface::class)
    ->setValue(Pool::XML_CONFIG_POOL_SIZE, 2, 'website', 'base');
/** @var $pool Pool */
$pool = $objectManager->create(Pool::class);
$pool->setWebsiteId($websiteId)
    ->generatePool();

$billingAddress = $objectManager->create(Address::class, ['data' => $addressData]);
$billingAddress->setAddressType('billing');

$shippingAddress = clone $billingAddress;
$shippingAddress->setId(null)
    ->setAddressType('shipping');

/** @var Item $orderGiftCardItem Item */
$orderGiftCardItem = $objectManager->create(Item::class);
$orderGiftCardItem->setProductId(1)
    ->setProductType(Giftcard::TYPE_GIFTCARD)
    ->setBasePrice(100)
    ->setQtyOrdered(2)
    ->setStoreId($storeId)
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
/** @var Order $order */
$order = $objectManager->create(Order::class);
$order->setIncrementId('100000002')
    ->addItem($orderGiftCardItem)
    ->setCustomerEmail('someone@example.com')
    ->setCustomerIsGuest(true)
    ->setBillingAddress($billingAddress)
    ->setShippingAddress($shippingAddress)
    ->setStoreId($storeId)
    ->setPayment($payment);

$orderService = $objectManager::getInstance()->create(InvoiceManagementInterface::class);
/** @var Invoice $invoice */
$invoice = $orderService->prepareInvoice($order);
$invoice->register();
$order = $invoice->getOrder();
$order->setIsInProcess(true);
$transactionSave = $objectManager->create(Transaction::class);
$transactionSave->addObject($order)->addObject($invoice)->save();
