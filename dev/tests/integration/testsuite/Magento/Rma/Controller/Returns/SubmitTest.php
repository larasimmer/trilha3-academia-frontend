<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Rma\Controller\Returns;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Exception\MailException;
use Magento\Framework\Message\MessageInterface;
use Magento\Rma\Model\Item;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;
use Magento\TestFramework\Mail\Template\TransportBuilderMock;
use Magento\TestFramework\Mail\TransportInterfaceMock;
use Magento\TestFramework\TestCase\AbstractController;

/**
 * Customer submit new RMA from storefront.
 *
 * @magentoAppArea frontend
 * @magentoDbIsolation enabled
 */
class SubmitTest extends AbstractController
{
    /**
     * @var OrderFactory
     */
    private $orderFactory;

    /**
     * @var EavConfig
     */
    private $eavConfig;

    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @inhertidoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->orderFactory = $this->_objectManager->create(OrderFactory::class);
        $this->eavConfig = $this->_objectManager->get(EavConfig::class);
        $this->customerSession = $this->_objectManager->get(CustomerSession::class);
        $this->customerRepository = $this->_objectManager->create(CustomerRepositoryInterface::class);
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        $this->customerSession->logout();
        $this->_objectManager->removeSharedInstance(TransportBuilderMock::class);
        parent::tearDown();
    }

    /**
     * @magentoConfigFixture current_store sales/magento_rma/enabled 1
     * @magentoDataFixture Magento/Sales/_files/shipment_for_order_with_customer.php
     * @return void
     */
    public function testSubmitNewRmaWithEmailSendingError(): void
    {
        $customer = $this->customerRepository->get('customer@example.com');
        $this->customerSession->loginById($customer->getId());

        $order = $this->getOrderByIncrementId('100000001');

        $this->buildRequestParams($order);
        $this->mockTransportBuilder();

        $this->dispatch('rma/returns/submit');
        $this->assertSessionMessages($this->isEmpty(), MessageInterface::TYPE_ERROR);
        $this->assertSessionMessages(
            $this->callback(
                function ($messages) {
                    $result = false;
                    foreach ($messages as $message) {
                        if (mb_strrpos($message, (string)__('You submitted Return')) !== false) {
                            $result = true;
                            break;
                        }
                    }
                    return $result;
                }
            ),
            MessageInterface::TYPE_SUCCESS
        );
        $this->assertRedirect($this->stringContains('rma/returns/history'));
    }

    /**
     * @param string $incrementId
     * @return Order
     */
    private function getOrderByIncrementId(string $incrementId): Order
    {
        return $this->orderFactory->create()->loadByIncrementId($incrementId);
    }

    /**
     * @param Order $order
     * @return void
     */
    private function buildRequestParams(Order $order): void
    {
        $orderItems = $order->getItems();
        $orderItem = reset($orderItems);
        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);
        $this->getRequest()->setParam('order_id', $order->getEntityId());
        $this->getRequest()->setPostValue(
            [
                'items' => [
                    [
                        'order_item_id' => $orderItem->getItemId(),
                        'qty_requested' => $orderItem->getQtyOrdered(),
                        'resolution' => $this->getAttributeOptionIdByLabel('resolution', 'Refund'),
                        'condition' => $this->getAttributeOptionIdByLabel('condition', 'Opened'),
                        'reason' => $this->getAttributeOptionIdByLabel('reason', 'Wrong Color'),
                        'reason_other' => '',
                    ]
                ],
                'customer_custom_email' => '',
            ]
        );
    }

    /**
     * @param string $attributeCode
     * @param string $label
     * @return string|null
     */
    private function getAttributeOptionIdByLabel(string $attributeCode, string $label)
    {
        $attribute = $this->eavConfig->getAttribute(Item::ENTITY, $attributeCode);

        return $attribute->getSource()->getOptionId($label);
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
            ->setMethods(['sendMessage'])
            ->getMock();
        $transport->expects($this->any())->method('sendMessage')
            ->willThrowException(new MailException(__('Unable to send mail')));

        $transportBuilder->expects($this->any())->method('setTemplateIdentifier')->willReturnSelf();
        $transportBuilder->expects($this->any())->method('setTemplateOptions')->willReturnSelf();
        $transportBuilder->expects($this->any())->method('setTemplateVars')->willReturnSelf();
        $transportBuilder->expects($this->any())->method('setFromByScope')->willReturnSelf();
        $transportBuilder->expects($this->any())->method('addTo')->willReturnSelf();
        $transportBuilder->expects($this->any())->method('addBcc')->willReturnSelf();
        $transportBuilder->expects($this->any())->method('getTransport')->willReturn($transport);

        $this->_objectManager->addSharedInstance($transportBuilder, TransportBuilderMock::class);
    }
}
