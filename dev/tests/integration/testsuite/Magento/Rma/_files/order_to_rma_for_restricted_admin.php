<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Authorization\Model\Role;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Model\Customer;
use Magento\Sales\Api\Data\OrderAddressInterfaceFactory;
use Magento\Sales\Api\Data\OrderInterfaceFactory;
use Magento\Sales\Api\Data\OrderItemInterfaceFactory;
use Magento\Sales\Api\Data\OrderPaymentInterfaceFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Address;
use Magento\Store\Api\WebsiteRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Bootstrap;
use Magento\TestFramework\Helper\Bootstrap as BootstrapHelper;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;
use Magento\User\Model\ResourceModel\User as UserResource;
use Magento\User\Model\User;

Resolver::getInstance()->requireDataFixture('Magento/Customer/_files/customer.php');
Resolver::getInstance()
    ->requireDataFixture('Magento/Catalog/_files/category_with_different_price_products_on_two_websites.php');

$objectManager = BootstrapHelper::getObjectManager();

/** @var WebsiteRepositoryInterface $websiteRepository */
$websiteRepository = $objectManager->get(WebsiteRepositoryInterface::class);
$websiteId = $websiteRepository->get('test')->getId();

$role = $objectManager->create(Role::class);
$role->setName('role_has_test_website_access_only')
    ->setGwsIsAll(0)
    ->setRoleType('G')
    ->setGwsWebsites($websiteId)
    ->save();

/**
 * Create users with assigned role
 */
/** @var UserResource $userResource */
$userResource = $objectManager->create(UserResource::class);
/** @var $user User */
$user = $objectManager->create(User::class);
$username = 'johnAdmin' . $role->getId();
$email = 'JohnadminUser' . $role->getId() . '@example.com';
$user->setFirstname("John")
    ->setIsActive(true)
    ->setLastname("Doe")
    ->setUsername($username)
    ->setPassword(Bootstrap::ADMIN_PASSWORD)
    ->setEmail($email)
    ->setRoleType($role->getRoleType())
    ->setResourceId('Magento_Backend::all')
    ->setPrivileges("")
    ->setAssertId(0)
    ->setRoleId($role->getId())
    ->setPermission('allow');
$userResource->save($user);

/** @var OrderAddressInterfaceFactory $addressFactory */
$addressFactory = $objectManager->get(OrderAddressInterfaceFactory::class);
/** @var OrderPaymentInterfaceFactory $paymentFactory */
$paymentFactory = $objectManager->get(OrderPaymentInterfaceFactory::class);
/** @var OrderInterfaceFactory $orderFactory */
$orderFactory = $objectManager->get(OrderInterfaceFactory::class);
/** @var OrderItemInterfaceFactory $orderItemFactory */
$orderItemFactory = $objectManager->get(OrderItemInterfaceFactory::class);
/** @var StoreManagerInterface $storeManager */
$storeManager = $objectManager->get(StoreManagerInterface::class);
/** @var OrderRepositoryInterface $orderRepository */
$orderRepository = $objectManager->get(OrderRepositoryInterface::class);
/** @var ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->create(ProductRepositoryInterface::class);

$payment = $paymentFactory->create();
$payment->setMethod('checkmo')->setAdditionalInformation(
    [
        'last_trans_id' => '11122',
        'metadata' => [
            'type' => 'free',
            'fraudulent' => false,
        ]
    ]
);

$storeId = $storeManager->getStore('fixture_second_store')->getId();
/** @var $customer Customer*/
$customer = $objectManager->create(Customer::class);
$customer->setWebsiteId($websiteId);
$customer->loadByEmail($email);
$order = $orderFactory->create();
$order->setIncrementId('100000001')
    ->setState(Order::STATE_PROCESSING)
    ->setStatus($order->getConfig()->getStateDefaultStatus(Order::STATE_PROCESSING))
    ->setSubtotal(30)
    ->setGrandTotal(30)
    ->setBaseSubtotal(30)
    ->setBaseGrandTotal(30)
    ->setCustomerIsGuest(false)
    ->setCustomerId($customer->getId())
    ->setCustomerEmail($customer->getEmail())
    ->setStoreId($storeId)
    ->setPayment($payment);

$orderItem = $orderItemFactory->create();
$product = $productRepository->get('simple1000');
$orderItem->setProductId($product->getId())
    ->setQtyOrdered(1)
    ->setBasePrice($product->getPrice())
    ->setPrice($product->getPrice())
    ->setRowTotal($product->getPrice())
    ->setProductType($product->getTypeId())
    ->setName($product->getName())
    ->setSku($product->getSku());
$order->addItem($orderItem);

$orderItem2 = $orderItemFactory->create();
$product2 = $productRepository->get('simple1001');
$orderItem2->setProductId($product2->getId())
    ->setQtyOrdered(1)
    ->setBasePrice($product2->getPrice())
    ->setPrice($product2->getPrice())
    ->setRowTotal($product2->getPrice())
    ->setProductType($product2->getTypeId())
    ->setName($product2->getName())
    ->setSku($product2->getSku());
$order->addItem($orderItem2);
$orderRepository->save($order);

$defaultWebsiteId = $websiteRepository->get('base')->getId();
$product = $productRepository->get('simple1000');
$product->setWebsiteIds([$defaultWebsiteId]);
$productRepository->save($product);
