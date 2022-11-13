<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerCustomAttributes\SalesController\Adminhtml\Order\Create;

use Magento\Backend\Model\Session\Quote;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Message\MessageInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\OrderRepository;
use Magento\TestFramework\TestCase\AbstractBackendController;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use Laminas\Stdlib\Parameters;
use Magento\Sales\Api\OrderAddressRepositoryInterface;

/**
 * Test for backend order save.
 *
 * @magentoAppArea adminhtml
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SaveTest extends AbstractBackendController
{
    /**
     * @var FormKey
     */
    private $formKey;

    /**
     * @var string
     */
    protected $resource = 'Magento_Sales::create';

    /**
     * @var string
     */
    protected $uri = 'backend/sales/order_create/save';

    /**
     * @var OrderAddressRepositoryInterface
     */
    private $orderAddressRepository;

    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var FileSystem
     */
    private $fileSystem;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->formKey = $this->_objectManager->get(FormKey::class);
        $this->orderAddressRepository = $this->_objectManager->get(OrderAddressRepositoryInterface::class);
        $this->fileSystem = $this->_objectManager->get(Filesystem::class);
        $this->searchCriteriaBuilder = $this->_objectManager->get(SearchCriteriaBuilder::class);
        $this->cartRepository = $this->_objectManager->get(CartRepositoryInterface::class);
    }

    /**
     * @magentoDbIsolation disabled
     * @magentoDataFixture Magento/CustomerCustomAttributes/_files/address_custom_file_attribute.php
     * @magentoDataFixture Magento/Sales/_files/guest_quote_with_addresses.php
     */
    public function testSaveWithFileTypeAttribute(): void
    {
        $mediaDirectory = $this->fileSystem->getDirectoryWrite(DirectoryList::MEDIA);
        $mediaDirectory->delete('customer_address');
        $mediaDirectory->create($mediaDirectory->getRelativePath('customer_address/tmp/'));
        $fixtureFile = realpath(INTEGRATION_TESTS_DIR . '/testsuite/Magento/Customer/_files/image/magento.jpg');

        $tmpFilePath = $mediaDirectory->getAbsolutePath('customer_address/tmp/magento.jpg');
        $mediaDirectory->getDriver()->filePutContents($tmpFilePath, file_get_contents($fixtureFile));

        $fileData = [
            'name' => 'magento.jpg',
            'type' => 'image/jpeg',
            'tmp_name' => $tmpFilePath,
            'error' => 0,
            'size' => 139416,
        ];

        foreach ($fileData as $field => $value) {
            $_FILES['order'][$field]['billing_address']['document'] = $value;
        }

        $this->prepareRequest([
            'send_confirmation' => true,
            'billing_address' => [
                'firstname' => 'JohnCustomFileAttributeTest',
                'lastname' => 'Doe',
                'street' => ['Baker', ''],
                'country_id' => 'UA',
                'region' => 'Please select a region, state or province.',
                'city' => 'Zhashkiv',
                'postcode' => '19200',
                'telephone' => '+380676767677',
                'document' => [
                    'value' => '',
                ],
            ],
        ]);

        $fileParameters = new Parameters();
        $files = [
            'billing_address' => [
                'document' => $fileData,
            ],
        ];
        $fileParameters->set('order', $files);
        $this->getRequest()
            ->setFiles($fileParameters);

        $this->getRequest()
            ->setPostValue('shipping_same_as_billing', 'on');

        $this->dispatch($this->uri);

        // Assert Order was created and there were no errors
        $this->assertSessionMessages(
            $this->equalTo([(string)__('You created the order.')]),
            MessageInterface::TYPE_SUCCESS
        );
        $this->assertSessionMessages($this->isEmpty(), MessageInterface::TYPE_ERROR);

        $this->assertRedirect($this->stringContains('sales/order/view/'));

        $orderId = $this->getOrderId();
        if ($orderId === false) {
            $this->fail('Order is not created.');
        }
        $order = $this->getOrder((int)$orderId);

        $addressesList = $order->getAddresses();

        foreach ($addressesList as $address) {
            // Assert custom attribute was saved
            $this->assertStringContainsString('/m/a/magento', $address->getDocument());
        }
    }

    /**
     * @inheritDoc
     * @magentoDataFixture Magento/Sales/_files/guest_quote_with_addresses.php
     */
    public function testAclHasAccess()
    {
        $this->prepareRequest();

        parent::testAclHasAccess();
    }

    /**
     * @inheritDoc
     * @magentoDataFixture Magento/Sales/_files/guest_quote_with_addresses.php
     */
    public function testAclNoAccess()
    {
        $this->prepareRequest();

        parent::testAclNoAccess();
    }

    /**
     * Gets quote by reserved order id.
     *
     * @param string $reservedOrderId
     * @return CartInterface
     */
    private function getQuote(string $reservedOrderId): CartInterface
    {
        $searchCriteria = $this->searchCriteriaBuilder->addFilter('reserved_order_id', $reservedOrderId)
            ->create();

        $items = $this->cartRepository->getList($searchCriteria)->getItems();

        return array_pop($items);
    }

    /**
     * @param array $params
     * @return void
     */
    private function prepareRequest(array $params = []): void
    {
        $quote = $this->getQuote('guest_quote');
        $session = $this->_objectManager->get(Quote::class);
        $session->setQuoteId($quote->getId());
        $session->setCustomerId(0);

        $email = 'john.doe001@test.com';
        $data = [
            'account' => [
                'email' => $email,
            ],
        ];

        $data = array_replace_recursive($data, $params);

        $this->getRequest()
            ->setMethod('POST')
            ->setParams(['form_key' => $this->formKey->getFormKey()])
            ->setPostValue(['order' => $data]);
    }

    /**
     * @return string|bool
     */
    private function getOrderId()
    {
        $currentUrl = $this->getResponse()->getHeader('Location');
        if ($currentUrl) {
            $currentUrl = $currentUrl->getUri();
        }
        $orderId = false;

        if (preg_match('/order_id\/(?<order_id>\d+)/', $currentUrl, $matches)) {
            $orderId = $matches['order_id'] ?? '';
        }

        return $orderId;
    }

    /**
     * @param int $orderId
     * @return OrderInterface
     */
    private function getOrder(int $orderId): OrderInterface
    {
        return $this->_objectManager->get(OrderRepository::class)->get($orderId);
    }
}
