<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\Sales;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\GraphQl\GetCustomerAuthenticationHeader;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\CreditmemoFactory;
use Magento\Sales\Model\Service\CreditmemoService;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;
use Magento\Sales\Model\Order\Shipment;
use Magento\Sales\Model\Order\ShipmentFactory;
use Magento\Framework\DB\Transaction;
use Magento\Framework\Exception\LocalizedException;

/**
 * Tests gift card product fields in Orders, Invoices, CreditMemo and Shipments
 */
class RetrieveOrderWithGiftCardProductTest extends GraphQlAbstract
{
    /** @var OrderRepositoryInterface */
    private $orderRepository;

    /** @var SearchCriteriaBuilder */
    private $searchCriteriaBuilder;

    /** @var GetCustomerAuthenticationHeader */
    private $customerAuthenticationHeader;

    /** @var ProductRepositoryInterface */
    private $productRepository;

    /** @var Order */
    private $order;

    /** @var CreditmemoFactory */
    private $creditMemoFactory;

    /** @var CreditmemoService */
    private $creditMemoService;

    /** @var Transaction */
    private $transaction;

    /** @var ShipmentFactory */
    private $shipmentFactory;

    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->customerAuthenticationHeader = $objectManager->get(GetCustomerAuthenticationHeader::class);
        $this->orderRepository = $objectManager->get(OrderRepositoryInterface::class);
        $this->searchCriteriaBuilder = $objectManager->get(SearchCriteriaBuilder::class);
        $this->productRepository = $objectManager->get(ProductRepositoryInterface::class);
        $this->creditMemoFactory = $objectManager->get(CreditmemoFactory::class);
        $this->order = $objectManager->create(Order::class);
        $this->creditMemoService = $objectManager->get(CreditmemoService::class);
        $this->transaction = $objectManager->create(Transaction::class);
        $this->shipmentFactory = $objectManager->get(ShipmentFactory::class);
    }

    /**
     * @magentoApiDataFixture Magento/GiftCard/_files/customer_invoice_with_gift_card.php
     */
    public function testGetCustomerOrdersGiftCardProduct()
    {
        $orderNumber = '100000001';
        $this->createShipmentsAndCreditMemos($orderNumber);
        $customerOrders = $this->getCustomerOrdersQuery($orderNumber);
        //assert order item for gift-card
        $this->assertGiftCard($customerOrders[0]['items']);
        //assert invoice item for gift-card
        $this->assertGiftCard($customerOrders[0]['invoices'][0]['items']);
        $this->assertGiftCard($customerOrders[0]['credit_memos'][0]['items']);
        $this->assertGiftCard($customerOrders[0]['shipments'][0]['items']);
    }

    /**
     * Create shipments and credit memo from an invoiced order
     *
     * @param string $orderNumber
     * @throws LocalizedException
     */
    private function createShipmentsAndCreditMemos(string $orderNumber): void
    {
        $order = $this->order->loadByIncrementId($orderNumber);

        $items = [];
        //arbitrary id
        $shipmentIds = ['0000000098'];
        $i = 0;
        foreach ($order->getItems() as $orderItem) {
            $items[$orderItem->getId()] = $orderItem->getQtyOrdered();
            /** @var Shipment $shipment */
            $shipment = $this->shipmentFactory->create($order, $items);
            $shipment->setIncrementId($shipmentIds[$i]);
            $shipment->register();

            $this->transaction->addObject($shipment)->addObject($order)->save();
            $i++;
        }

        $orderItem = current($order->getAllItems());
        $orderItem->setQtyRefunded(1);
        $order->addItem($orderItem);
        $order->save();
        // Create a credit memo
        $creditMemo = $this->creditMemoFactory->createByOrder($order, $order->getData());
        $creditMemo->setOrder($order);
        $creditMemo->setState(1);
        $creditMemo->setSubtotal(10);
        $creditMemo->setBaseSubTotal(10);
        $creditMemo->setShippingAmount(10);
        $creditMemo->setBaseGrandTotal(20);
        $creditMemo->setGrandTotal(20);
        $creditMemo->setAdjustment(0.00);
        $creditMemo->addComment("Test comment for partial refund", false, true);
        $creditMemo->save();

        $this->creditMemoService->refund($creditMemo, true);
    }

    /**
     * @param array $customerItemsInResponse
     */
    private function assertGiftCard(array $customerItemsInResponse)
    {
        $this->assertNotEmpty($customerItemsInResponse);
        $giftCardItemInTheOrder = $customerItemsInResponse[0];
        $this->assertEquals(
            'gift-card-with-fixed-amount-10',
            $giftCardItemInTheOrder['product_sku']
        );
        $priceOfGiftCardItemInOrder = $giftCardItemInTheOrder['product_sale_price']['value'];
        $this->assertEquals(10, $priceOfGiftCardItemInOrder);
        $this->assertArrayHasKey('gift_card', $giftCardItemInTheOrder);
        $giftCardFromResponse = $giftCardItemInTheOrder['gift_card'];
        $this->assertNotEmpty($giftCardFromResponse);
        $expectedGiftCardData = [
            'sender_name' => 'Gift Card Sender Name',
            'sender_email' => 'sender@example.com',
            'recipient_name' => 'Gift Card Recipient Name',
            'recipient_email' => 'recipient@example.com',
            'message' => 'Gift Card Message'
        ];
        $this->assertResponseFields($expectedGiftCardData, $giftCardFromResponse);
    }

    /**
     * Get customer order query with invoices, creditmemos and shipments
     *
     * @param string $orderNumber
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    private function getCustomerOrdersQuery(string $orderNumber): array
    {
        $query =
            <<<QUERY
{
     customer {
       orders(filter:{number:{eq:"{$orderNumber}"}}) {
         total_count
         items {
           id
           number
           order_date
           status
           items{
            __typename
            product_sku
            product_name
            product_url_key
            product_sale_price{value}
            quantity_ordered
            discounts{amount{value} label}
            ... on GiftCardOrderItem {
                  gift_card {
                    sender_name
                    sender_email
                    recipient_name
                    recipient_email
                    message
                  }
              entered_options{value label}
              product_sku
              product_name
              quantity_ordered
            }
           }
           total {
             base_grand_total{value currency}
             grand_total{value currency}
             subtotal {value currency }
             total_tax{value currency}
             taxes {amount{value currency} title rate}
             total_shipping{value currency}
             shipping_handling
             {
               amount_including_tax{value}
               amount_excluding_tax{value}
               total_amount{value}
               discounts{amount{value}}
               taxes {amount{value} title rate}
             }
             discounts {amount{value currency} label}
           }
           invoices {
              number
              items {
              	product_name
                product_sku
                product_sale_price{value currency}
                quantity_invoiced
                ... on GiftCardInvoiceItem {
                  gift_card{
                    sender_name
                    recipient_name
                    message
                  }
                }
              }
          }
          credit_memos {
            items {
                product_name
                product_sku
                product_sale_price {
                value
                }
                quantity_refunded
                ... on GiftCardCreditMemoItem {
                  gift_card {
                    sender_name
                    recipient_name
                    message
                  }
                }
            }
          }
          shipments {
          number
            tracking {
              carrier
              title
              number
            }
          items {
            product_name
            product_sku
            product_sale_price{value currency}
            quantity_shipped
             ... on GiftCardShipmentItem {
                  gift_card {
                    sender_name
                    recipient_name
                    message
                  }
                }
            }
      	  }
         }
       }
     }
   }
QUERY;
        $currentEmail = 'customer@example.com';
        $currentPassword = 'password';
        $response = $this->graphQlQuery(
            $query,
            [],
            '',
            $this->customerAuthenticationHeader->execute($currentEmail, $currentPassword)
        );

        $this->assertArrayHasKey('orders', $response['customer']);
        $this->assertArrayHasKey('items', $response['customer']['orders']);
        $this->assertNotEmpty($response['customer']['orders']['items']);
        return $response['customer']['orders']['items'];
    }
}
