<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\GraphQl\Rma;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\Uid;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\GraphQl\GetCustomerAuthenticationHeader;
use Magento\Rma\Api\Data\RmaInterface;
use Magento\Rma\Api\RmaRepositoryInterface;
use Magento\Rma\Model\Rma;
use Magento\Rma\Model\Rma\Source\Status;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderInterfaceFactory;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * Test for request return mutation
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RequestReturnTest extends GraphQlAbstract
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var GetCustomerAuthenticationHeader
     */
    private $getCustomerAuthenticationHeader;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var RmaRepositoryInterface
     */
    private $rmaRepository;

    /**
     * @var OrderInterfaceFactory
     */
    private $orderFactory;

    /**
     * @var Uid
     */
    private $idEncoder;

    /**
     * @var AttributeRepositoryInterface
     */
    private $attributeRepository;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var AdapterInterface
     */
    private $connection;

    /**
     * Setup
     */
    public function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->getCustomerAuthenticationHeader = $this->objectManager->get(GetCustomerAuthenticationHeader::class);
        $this->customerRepository = $this->objectManager->get(CustomerRepositoryInterface::class);
        $this->searchCriteriaBuilder = $this->objectManager->get(SearchCriteriaBuilder::class);
        $this->rmaRepository = $this->objectManager->get(RmaRepositoryInterface::class);
        $this->orderFactory = $this->objectManager->get(OrderInterfaceFactory::class);
        $this->idEncoder = $this->objectManager->get(Uid::class);
        $this->attributeRepository = $this->objectManager->get(AttributeRepositoryInterface::class);
        $this->serializer = $this->objectManager->get(SerializerInterface::class);
        $this->connection = $this->objectManager->get(ResourceConnection::class)->getConnection();
    }

    /**
     * Test request return with unauthorized customer
     *
     * @magentoConfigFixture default_store sales/magento_rma/enabled 1
     * @magentoApiDataFixture Magento/Rma/_files/order_and_rma_item_attributes.php
     */
    public function testUnauthorized()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The current customer isn\'t authorized.');

        $order = $this->orderFactory->create()->loadByIncrementId('100000555');
        $orderUid = $this->idEncoder->encode((string)$order->getEntityId());

        $items = $this->prepareItems($order);

        $mutation = <<<MUTATION
mutation {
  requestReturn(
    input: {
      order_uid: "{$orderUid}"
      contact_email: "returnemail@magento.com"
      items: [{$items}],
      comment_text: "Return comment"
    }
  ) {
    return {
      uid
      items {
        uid
        quantity
        request_quantity
        order_item {
          product_sku
          product_name
        }
        custom_attributes {
          uid
          label
          value
        }
      }
      comments {
        created_at
        author_name
        text
      }
    }
  }
}
MUTATION;

        $this->graphQlMutation($mutation);
    }

    /**
     * Test request return
     *
     * @magentoConfigFixture sales/magento_rma/enabled 1
     * @magentoConfigFixture sales/magento_rma/use_store_address 0
     * @magentoConfigFixture sales/magento_rma/store_name test
     * @magentoConfigFixture sales/magento_rma/address street
     * @magentoConfigFixture sales/magento_rma/address1 1
     * @magentoConfigFixture sales/magento_rma/region_id wrong region
     * @magentoConfigFixture sales/magento_rma/city Montgomery
     * @magentoConfigFixture sales/magento_rma/zip 12345
     * @magentoConfigFixture sales/magento_rma/country_id US
     * @magentoApiDataFixture Magento/Rma/_files/order_and_rma_item_attributes.php
     */
    public function testRequestReturn()
    {
        $order = $this->orderFactory->create()->loadByIncrementId('100000555');
        $orderUid = $this->idEncoder->encode((string)$order->getEntityId());
        $contactEmail = 'returnemail@magento.com';

        $items = $this->prepareItems($order);

        $mutation = <<<MUTATION
mutation {
  requestReturn(
    input: {
      order_uid: "{$orderUid}"
      contact_email: "{$contactEmail}"
      items: [{$items}],
      comment_text: "Return comment"
    }
  ) {
    return {
      uid
      status
      customer{email}
      items {
        uid
        quantity
        request_quantity
        order_item {
          product_sku
          product_name
        }
        custom_attributes {
          uid
          label
          value
        }
        status
      }
      comments {
        created_at
        author_name
        text
      }
    }
  }
}
MUTATION;

        $customerEmail = 'customer_uk_address@test.com';

        $response = $this->graphQlMutation(
            $mutation,
            [],
            '',
            $this->getCustomerAuthenticationHeader->execute($customerEmail, 'password')
        );

        $rma = $this->getCustomerReturn($customerEmail);

        self::assertEquals(
            $this->idEncoder->encode((string)$rma->getEntityId()),
            $response['requestReturn']['return']['uid']
        );
        self::assertEqualsIgnoringCase(Status::STATE_PENDING, $response['requestReturn']['return']['status']);
        $this->assertRmaItems($response['requestReturn']['return']['items']);
        $this->assertRmaComments($response['requestReturn']['return']['comments']);
        self::assertEquals($contactEmail, $response['requestReturn']['return']['customer']['email']);
    }

    /**
     * Test request return with wrong order id
     *
     * @magentoConfigFixture sales/magento_rma/enabled 1
     * @magentoConfigFixture sales/magento_rma/use_store_address 0
     * @magentoConfigFixture sales/magento_rma/store_name test
     * @magentoConfigFixture sales/magento_rma/address street
     * @magentoConfigFixture sales/magento_rma/address1 1
     * @magentoConfigFixture sales/magento_rma/region_id wrong region
     * @magentoConfigFixture sales/magento_rma/city Montgomery
     * @magentoConfigFixture sales/magento_rma/zip 12345
     * @magentoConfigFixture sales/magento_rma/country_id US
     * @magentoApiDataFixture Magento/Rma/_files/order_and_rma_item_attributes.php
     */
    public function testWithWrongOrderId()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The entity that was requested doesn\'t exist. Verify the entity and try again.');

        $order = $this->orderFactory->create()->loadByIncrementId('100000555');
        $orderId = $order->getEntityId() + 10;
        $orderUid = $this->idEncoder->encode((string)$orderId);

        $items = $this->prepareItems($order);

        $mutation = <<<MUTATION
mutation {
  requestReturn(
    input: {
      order_uid: "{$orderUid}"
      contact_email: "returnemail@magento.com"
      items: [{$items}],
      comment_text: "Return comment"
    }
  ) {
    return {
      uid
      status
      items {
        uid
        quantity
        request_quantity
        order_item {
          product_sku
          product_name
        }
        custom_attributes {
          uid
          label
          value
        }
        status
      }
      comments {
        created_at
        author_name
        text
      }
    }
  }
}
MUTATION;

        $this->graphQlMutation(
            $mutation,
            [],
            '',
            $this->getCustomerAuthenticationHeader->execute('customer_uk_address@test.com', 'password')
        );
    }

    /**
     * Test request return with not encoded order id
     *
     * @magentoConfigFixture sales/magento_rma/enabled 1
     * @magentoConfigFixture sales/magento_rma/use_store_address 0
     * @magentoConfigFixture sales/magento_rma/store_name test
     * @magentoConfigFixture sales/magento_rma/address street
     * @magentoConfigFixture sales/magento_rma/address1 1
     * @magentoConfigFixture sales/magento_rma/region_id wrong region
     * @magentoConfigFixture sales/magento_rma/city Montgomery
     * @magentoConfigFixture sales/magento_rma/zip 12345
     * @magentoConfigFixture sales/magento_rma/country_id US
     * @magentoApiDataFixture Magento/Rma/_files/order_and_rma_item_attributes.php
     */
    public function testWithNotEncodedOrderId()
    {
        $order = $this->orderFactory->create()->loadByIncrementId('100000555');
        $orderId = $order->getEntityId();
        $items = $this->prepareItems($order);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Value of uid \"{$orderId}\" is incorrect.");

        $mutation = <<<MUTATION
mutation {
  requestReturn(
    input: {
      order_uid: "{$orderId}"
      contact_email: "returnemail@magento.com"
      items: [{$items}],
      comment_text: "Return comment"
    }
  ) {
    return {
      uid
      status
      items {
        uid
        quantity
        request_quantity
        order_item {
          product_sku
          product_name
        }
        custom_attributes {
          uid
          label
          value
        }
        status
      }
      comments {
        created_at
        author_name
        text
      }
    }
  }
}
MUTATION;

        $this->graphQlMutation(
            $mutation,
            [],
            '',
            $this->getCustomerAuthenticationHeader->execute('customer_uk_address@test.com', 'password')
        );
    }

    /**
     * Test request return without required fields
     *
     * @magentoConfigFixture sales/magento_rma/enabled 1
     * @magentoConfigFixture sales/magento_rma/use_store_address 0
     * @magentoConfigFixture sales/magento_rma/store_name test
     * @magentoConfigFixture sales/magento_rma/address street
     * @magentoConfigFixture sales/magento_rma/address1 1
     * @magentoConfigFixture sales/magento_rma/region_id wrong region
     * @magentoConfigFixture sales/magento_rma/city Montgomery
     * @magentoConfigFixture sales/magento_rma/zip 12345
     * @magentoConfigFixture sales/magento_rma/country_id US
     * @magentoApiDataFixture Magento/Rma/_files/order_and_rma_item_attributes.php
     */
    public function testWithoutRequiredFields()
    {
        $order = $this->orderFactory->create()->loadByIncrementId('100000555');
        $orderUid = $this->idEncoder->encode((string)$order->getEntityId());

        $items = $this->prepareItems($order);

        $mutation = <<<MUTATION
mutation {
  requestReturn(
    input: {
      order_uid: "{$orderUid}"
      items: [{$items}]
    }
  ) {
    return {
      uid
      status
      customer{email}
      items {
        uid
        quantity
        request_quantity
        order_item {
          product_sku
          product_name
        }
        custom_attributes {
          uid
          label
          value
        }
        status
      }
      comments {
        created_at
        author_name
        text
      }
    }
  }
}
MUTATION;

        $customerEmail = 'customer_uk_address@test.com';

        $response = $this->graphQlMutation(
            $mutation,
            [],
            '',
            $this->getCustomerAuthenticationHeader->execute($customerEmail, 'password')
        );

        $rma = $this->getCustomerReturn($customerEmail);

        self::assertEquals(
            $this->idEncoder->encode((string)$rma->getEntityId()),
            $response['requestReturn']['return']['uid']
        );
        self::assertEqualsIgnoringCase(Status::STATE_PENDING, $response['requestReturn']['return']['status']);
        $this->assertRmaItems($response['requestReturn']['return']['items']);
        self::assertEmpty($response['requestReturn']['return']['comments']);
        self::assertEquals($customerEmail, $response['requestReturn']['return']['customer']['email']);
    }

    /**
     * Test request return with unauthorized customer
     *
     * @magentoConfigFixture sales/magento_rma/enabled 1
     * @magentoConfigFixture sales/magento_rma/use_store_address 0
     * @magentoConfigFixture sales/magento_rma/store_name test
     * @magentoConfigFixture sales/magento_rma/address street
     * @magentoConfigFixture sales/magento_rma/address1 1
     * @magentoConfigFixture sales/magento_rma/region_id wrong region
     * @magentoConfigFixture sales/magento_rma/city Montgomery
     * @magentoConfigFixture sales/magento_rma/zip 12345
     * @magentoConfigFixture sales/magento_rma/country_id US
     * @magentoApiDataFixture Magento/Rma/_files/order_and_rma_item_attributes.php
     */
    public function testWithWrongOrderItemId()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('You cannot return');

        $order = $this->orderFactory->create()->loadByIncrementId('100000555');
        $orderUid = $this->idEncoder->encode((string)$order->getEntityId());

        $items = $this->prepareItems($order, true);

        $mutation = <<<MUTATION
mutation {
  requestReturn(
    input: {
      order_uid: "{$orderUid}"
      contact_email: "returnemail@magento.com"
      items: [{$items}],
      comment_text: "Return comment"
    }
  ) {
    return {
      uid
      status
      customer{email}
      items {
        uid
        quantity
        request_quantity
        order_item {
          product_sku
          product_name
        }
        custom_attributes {
          uid
          label
          value
        }
        status
      }
      comments {
        created_at
        author_name
        text
      }
    }
  }
}
MUTATION;

        $this->graphQlMutation(
            $mutation,
            [],
            '',
            $this->getCustomerAuthenticationHeader->execute('customer_uk_address@test.com', 'password')
        );
    }

    /**
     * Test request return with not encoded order item id
     *
     * @magentoConfigFixture sales/magento_rma/enabled 1
     * @magentoConfigFixture sales/magento_rma/use_store_address 0
     * @magentoConfigFixture sales/magento_rma/store_name test
     * @magentoConfigFixture sales/magento_rma/address street
     * @magentoConfigFixture sales/magento_rma/address1 1
     * @magentoConfigFixture sales/magento_rma/region_id wrong region
     * @magentoConfigFixture sales/magento_rma/city Montgomery
     * @magentoConfigFixture sales/magento_rma/zip 12345
     * @magentoConfigFixture sales/magento_rma/country_id US
     * @magentoApiDataFixture Magento/Rma/_files/order_and_rma_item_attributes.php
     */
    public function testWithNotEncodedOrderItemId()
    {
        $order = $this->orderFactory->create()->loadByIncrementId('100000555');
        $orderUid = $this->idEncoder->encode((string)$order->getEntityId());

        $items = $this->prepareItems($order, false, false);
        $item = current($order->getItems());
        $itemId = $item->getItemId();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Value of uid \"{$itemId}\" is incorrect.");

        $mutation = <<<MUTATION
mutation {
  requestReturn(
    input: {
      order_uid: "{$orderUid}"
      contact_email: "returnemail@magento.com"
      items: [{$items}],
      comment_text: "Return comment"
    }
  ) {
    return {
      uid
      status
      customer{email}
      items {
        uid
        quantity
        request_quantity
        order_item {
          product_sku
          product_name
        }
        custom_attributes {
          uid
          label
          value
        }
        status
      }
      comments {
        created_at
        author_name
        text
      }
    }
  }
}
MUTATION;

        $this->graphQlMutation(
            $mutation,
            [],
            '',
            $this->getCustomerAuthenticationHeader->execute('customer_uk_address@test.com', 'password')
        );
    }

    /**
     * Test request return with bigger item's quantity than ordered
     *
     * @magentoConfigFixture sales/magento_rma/enabled 1
     * @magentoConfigFixture sales/magento_rma/use_store_address 0
     * @magentoConfigFixture sales/magento_rma/store_name test
     * @magentoConfigFixture sales/magento_rma/address street
     * @magentoConfigFixture sales/magento_rma/address1 1
     * @magentoConfigFixture sales/magento_rma/region_id wrong region
     * @magentoConfigFixture sales/magento_rma/city Montgomery
     * @magentoConfigFixture sales/magento_rma/zip 12345
     * @magentoConfigFixture sales/magento_rma/country_id US
     * @magentoApiDataFixture Magento/Rma/_files/order_and_rma_item_attributes.php
     */
    public function testWithBiggerQuantityToReturn()
    {
        $order = $this->orderFactory->create()->loadByIncrementId('100000555');
        $orderUid = $this->idEncoder->encode((string)$order->getEntityId());
        $items = $this->prepareItems($order, false, true, 1000);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("A quantity of Simple Product is greater than you can return.");

        $mutation = <<<MUTATION
mutation {
  requestReturn(
    input: {
      order_uid: "{$orderUid}"
      contact_email: "returnemail@magento.com"
      items: [{$items}],
      comment_text: "Return comment"
    }
  ) {
    return {
      uid
      status
      customer{email}
      items {
        uid
        quantity
        request_quantity
        order_item {
          product_sku
          product_name
        }
        custom_attributes {
          uid
          label
          value
        }
        status
      }
      comments {
        created_at
        author_name
        text
      }
    }
  }
}
MUTATION;

        $this->graphQlMutation(
            $mutation,
            [],
            '',
            $this->getCustomerAuthenticationHeader->execute('customer_uk_address@test.com', 'password')
        );
    }

    /**
     * Test request return with less than 1 quantity
     *
     * @magentoConfigFixture sales/magento_rma/enabled 1
     * @magentoConfigFixture sales/magento_rma/use_store_address 0
     * @magentoConfigFixture sales/magento_rma/store_name test
     * @magentoConfigFixture sales/magento_rma/address street
     * @magentoConfigFixture sales/magento_rma/address1 1
     * @magentoConfigFixture sales/magento_rma/region_id wrong region
     * @magentoConfigFixture sales/magento_rma/city Montgomery
     * @magentoConfigFixture sales/magento_rma/zip 12345
     * @magentoConfigFixture sales/magento_rma/country_id US
     * @magentoApiDataFixture Magento/Rma/_files/order_and_rma_item_attributes.php
     */
    public function testWithLessQuantityToReturn()
    {
        $order = $this->orderFactory->create()->loadByIncrementId('100000555');
        $orderUid = $this->idEncoder->encode((string)$order->getEntityId());
        $items = $this->prepareItems($order, false, true, 0);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("You cannot return less than 1 product.");

        $mutation = <<<MUTATION
mutation {
  requestReturn(
    input: {
      order_uid: "{$orderUid}"
      contact_email: "returnemail@magento.com"
      items: [{$items}],
      comment_text: "Return comment"
    }
  ) {
    return {
      uid
      status
      customer{email}
      items {
        uid
        quantity
        request_quantity
        order_item {
          product_sku
          product_name
        }
        custom_attributes {
          uid
          label
          value
        }
        status
      }
      comments {
        created_at
        author_name
        text
      }
    }
  }
}
MUTATION;

        $this->graphQlMutation(
            $mutation,
            [],
            '',
            $this->getCustomerAuthenticationHeader->execute('customer_uk_address@test.com', 'password')
        );
    }

    /**
     * Test request return with disabled RMA
     *
     * @magentoConfigFixture sales/magento_rma/enabled 0
     * @magentoApiDataFixture Magento/Rma/_files/order_and_rma_item_attributes.php
     */
    public function testWithDisabledRma()
    {
        $order = $this->orderFactory->create()->loadByIncrementId('100000555');
        $orderUid = $this->idEncoder->encode((string)$order->getEntityId());
        $items = $this->prepareItems($order);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("RMA is disabled.");

        $mutation = <<<MUTATION
mutation {
  requestReturn(
    input: {
      order_uid: "{$orderUid}"
      contact_email: "returnemail@magento.com"
      items: [{$items}],
      comment_text: "Return comment"
    }
  ) {
    return {
      uid
      status
      customer{email}
      items {
        uid
        quantity
        request_quantity
        order_item {
          product_sku
          product_name
        }
        custom_attributes {
          uid
          label
          value
        }
        status
      }
      comments {
        created_at
        author_name
        text
      }
    }
  }
}
MUTATION;

        $this->graphQlMutation(
            $mutation,
            [],
            '',
            $this->getCustomerAuthenticationHeader->execute('customer_uk_address@test.com', 'password')
        );
    }

    /**
     * Assert RMA items
     *
     * @param array $actualItems
     * @throws GraphQlInputException
     */
    private function assertRmaItems(array $actualItems): void
    {
        $expectedItems = [
            [
                'quantity' => 0,
                'request_quantity' => 1,
                'order_item' => [
                    'product_sku' => 'simple-1',
                    'product_name' => 'Simple Product'
                ],
                'status' => Status::STATE_PENDING,
                'custom_attributes' => [
                    [
                        'value' => "Exchange",
                        'label' => 'Resolution',
                    ],
                    [
                        'value' => "Opened",
                        'label' => 'Item Condition',
                    ],
                    [
                        'value' => 'Wrong Color',
                        'label' => 'Reason to Return',
                    ],
                    [
                        'label' => 'selected_rma_item_attribute',
                        'value' => 'second',
                    ],
                    [
                        'label' => 'entered_item_attribute',
                        'value' => 'Custom attribute value'
                    ]
                ]
            ],
            [
                'quantity' => 0,
                'request_quantity' => 1,
                'order_item' => [
                    'product_sku' => 'simple',
                    'product_name' => 'New Product'
                ],
                'status' => Status::STATE_PENDING,
                'custom_attributes' => [
                    [
                        'value' => 'Exchange',
                        'label' => 'Resolution',
                    ],
                    [
                        'value' => 'Opened',
                        'label' => 'Item Condition',
                    ],
                    [
                        'value' => 'Wrong Color',
                        'label' => 'Reason to Return',
                    ],
                    [
                        'label' => 'selected_rma_item_attribute',
                        'value' => 'second',
                    ],
                    [
                        'label' => 'entered_item_attribute',
                        'value' => 'Custom attribute value',
                    ]
                ]
            ]
        ];

        foreach ($expectedItems as $key => $expectedItem) {
            $this->assertItem($expectedItem, $actualItems[$key]);
        }
    }

    /**
     * Assert RMA item
     *
     * @param array $expectedItem
     * @param array $actualItem
     * @throws GraphQlInputException
     */
    private function assertItem(array $expectedItem, array $actualItem): void
    {
        self::assertIsNumeric($this->idEncoder->decode($actualItem['uid']));
        self::assertEquals($expectedItem['quantity'], $actualItem['quantity']);
        self::assertEquals($expectedItem['request_quantity'], $actualItem['request_quantity']);
        self::assertEqualsCanonicalizing($expectedItem['order_item'], $actualItem['order_item']);
        self::assertEqualsIgnoringCase(Status::STATE_PENDING, $actualItem['status']);

        foreach ($expectedItem['custom_attributes'] as $key => $customAttribute) {
            $this->assertCustomAttribute($customAttribute, $actualItem['custom_attributes'][$key]);
        }
    }

    /**
     * Assert RMA item custom attribute
     *
     * @param array $expectedAttribute
     * @param array $actualAttribute
     * @throws GraphQlInputException
     */
    private function assertCustomAttribute(array $expectedAttribute, array $actualAttribute): void
    {
        self::assertIsNumeric($this->idEncoder->decode($actualAttribute['uid']));
        self::assertEquals($expectedAttribute['label'], $actualAttribute['label']);
        self::assertEquals($this->serializer->serialize($expectedAttribute['value']), $actualAttribute['value']);
    }

    /**
     * Assert RMA comments
     *
     * @param array $actualComments
     */
    private function assertRmaComments(array $actualComments): void
    {
        $expectedComments = [
            [
                'created_at' => date('Y-m-d H:i:s'),
                'author_name' => 'Customer Service',
                'text' => 'We placed your Return request.'
            ],
            [
                'created_at' => date('Y-m-d H:i:s'),
                'author_name' => 'John Smith',
                'text' => 'Return comment'
            ]
        ];

        foreach ($expectedComments as $key => $expectedComment) {
            $this->assertComment($expectedComment, $actualComments[$key]);
        }
    }

    /**
     * Assert RMA comment
     *
     * @param array $expectedComment
     * @param array $actualComment
     */
    private function assertComment(array $expectedComment, array $actualComment): void
    {
        self::assertEqualsWithDelta(
            strtotime($expectedComment['created_at']),
            strtotime($actualComment['created_at']),
            1
        );
        self::assertEquals($expectedComment['author_name'], $actualComment['author_name']);
        self::assertEquals($expectedComment['text'], $actualComment['text']);
    }

    /**
     * Get customer return
     *
     * @param string $customerEmail
     * @return RmaInterface
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function getCustomerReturn(string $customerEmail): RmaInterface
    {
        $customer = $this->customerRepository->get($customerEmail);
        $this->searchCriteriaBuilder->addFilter(Rma::CUSTOMER_ID, $customer->getId());
        $searchResults = $this->rmaRepository->getList($this->searchCriteriaBuilder->create());

        return $searchResults->getFirstItem();
    }

    /**
     * Prepare items for mutation
     *
     * @param OrderInterface $order
     * @param bool $isWrong
     * @param bool $isEncoded
     * @param int $qty
     * @return string
     */
    private function prepareItems(
        OrderInterface $order,
        bool $isWrong = false,
        bool $isEncoded = true,
        int $qty = 1
    ): string {
        $selectedValue = 'second';
        $selectedAttribute = 'selected_rma_item_attribute';
        $encodedSelectedValueId = $this->idEncoder->encode(
            $this->getOptionValueIdByValue(
                $selectedValue,
                $selectedAttribute
            )
        );

        $encodedResolutionValueId = $this->idEncoder->encode('4');
        $encodedConditionValueId = $this->idEncoder->encode('8');
        $encodedReasonValueId = $this->idEncoder->encode('10');

        $items = '';
        foreach ($order->getItems() as $item) {
            $itemId = $item->getItemId();
            if ($isWrong) {
                $itemId += 10;
            }
            if ($isEncoded) {
                $itemId = $this->idEncoder->encode((string)$itemId);
            }

            $items .= <<<ITEM
{
          order_item_uid: "{$itemId}",
          quantity_to_return: {$qty},
          selected_custom_attributes: [
          {attribute_code: "{$selectedAttribute}", value: "{$encodedSelectedValueId}"}
          {attribute_code: "resolution", value: "{$encodedResolutionValueId}"}
          {attribute_code: "condition", value: "{$encodedConditionValueId}"}
          {attribute_code: "reason", value: "{$encodedReasonValueId}"}
          ],
          entered_custom_attributes: [{attribute_code: "entered_item_attribute", value: "Custom attribute value"}]
        }
ITEM;
        }

        return $items;
    }

    /**
     * Get option value id by value
     *
     * @param string $value
     * @param string $attributeCode
     * @return string
     */
    private function getOptionValueIdByValue(string $value, string $attributeCode): string
    {
        $select = $this->connection->select()
            ->from(
                ['eaov' => $this->connection->getTableName('eav_attribute_option_value')],
                'eaov.value_id'
            )
            ->joinInner(
                ['eao' =>$this->connection->getTableName('eav_attribute_option')],
                'eao.option_id = eaov.option_id'
            )
            ->joinInner(
                ['ea' =>$this->connection->getTableName('eav_attribute')],
                'ea.attribute_id = eao.attribute_id'
            )
            ->where('eaov.value = ?', $value)
            ->where('ea.attribute_code = ?', $attributeCode)
            ->where('ea.entity_type_id = ?', 9);

        return $this->connection->fetchOne($select);
    }
}
