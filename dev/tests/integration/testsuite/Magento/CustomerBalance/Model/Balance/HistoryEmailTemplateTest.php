<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerBalance\Model\Balance;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\CustomerBalance\Model\BalanceFactory;
use Magento\Email\Model\ResourceModel\Template as TemplateResource;
use Magento\Email\Model\ResourceModel\Template\CollectionFactory;
use Magento\Email\Model\Template;
use Magento\Email\Model\TemplateFactory;
use Magento\Framework\App\Config\MutableScopeConfigInterface;
use Magento\Framework\Mail\MessageInterface;
use Magento\Framework\Module\Manager;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Mail\Template\TransportBuilderMock;
use PHPUnit\Framework\TestCase;

/**
 * Test for history email template.
 *
 * @magentoDbIsolation enabled
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class HistoryEmailTemplateTest extends TestCase
{
    /** @var ObjectManagerInterface */
    private $objectManager;

    /** @var Manager */
    private $moduleManager;

    /** @var BalanceFactory */
    private $balanceFactory;

    /** @var CustomerRepositoryInterface */
    private $customerRepository;

    /** @var HistoryFactory */
    private $historyFactory;

    /** @var StoreManagerInterface */
    private $storeManager;

    /** @var TransportBuilderMock */
    private $transportBuilder;

    /** @var TemplateResource */
    private $templateResource;

    /** @var TemplateFactory */
    private $templateFactory;

    /** @var MutableScopeConfigInterface */
    private $mutableScopeConfig;

    /** @var CollectionFactory */
    private $templateCollectionFactory;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->moduleManager = $this->objectManager->get(Manager::class);
        //This check is needed because Magento_CustomerBalance independent of Magento_Email
        if (!$this->moduleManager->isEnabled('Magento_Email')) {
            $this->markTestSkipped('Magento_Email module disabled.');
        }
        $this->customerRepository = $this->objectManager->get(CustomerRepositoryInterface::class);
        $this->balanceFactory = $this->objectManager->get(BalanceFactory::class);
        $this->historyFactory = $this->objectManager->get(HistoryFactory::class);
        $this->storeManager = $this->objectManager->get(StoreManagerInterface::class);
        $this->transportBuilder = $this->objectManager->get(TransportBuilderMock::class);
        $this->templateResource = $this->objectManager->get(TemplateResource::class);
        $this->templateFactory = $this->objectManager->get(TemplateFactory::class);
        $this->mutableScopeConfig = $this->objectManager->get(MutableScopeConfigInterface::class);
        $this->templateCollectionFactory = $this->objectManager->get(CollectionFactory::class);
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        if ($this->moduleManager->isEnabled('Magento_Email')) {
            $this->mutableScopeConfig->clean();
            $collection = $this->templateCollectionFactory->create();
            $template = $collection->addFieldToFilter('template_code', 'store_credit_email_template')
                ->getFirstItem();
            if ($template->getId()) {
                $this->templateResource->delete($template);
            }
        }

        parent::tearDown();
    }

    /**
     * @magentoDataFixture Magento/Customer/_files/customer.php
     * @magentoConfigFixture current_store customer/magento_customerbalance/email_identity support
     *
     * @return void
     */
    public function testCustomCustomerBalanceEmailTemplate(): void
    {
        $this->setEmailTemplateConfig();
        $store = $this->storeManager->getStore('default');
        $customer = $this->customerRepository->get('customer@example.com');
        $balance = $this->balanceFactory->create();
        $balance->setCustomer($customer);
        $balance->setNotifyByEmail(true, $store->getId());
        $history = $this->historyFactory->create();
        $history->setBalanceModel($balance);
        $history->afterSave();
        $this->assertTrue($history->getIsCustomerNotified());
        $expectedData = [
            'sender' => ['name' => 'CustomerSupport', 'email' => 'support@example.com'],
            'text' => 'Text specially for check in test.',
        ];
        $this->assertMessage($expectedData);
    }

    /**
     * @magentoDataFixture Magento/Customer/_files/customer_for_second_website.php
     *
     * @return void
     */
    public function testCustomerBalanceEmailSenderForSecondWebsite(): void
    {
        $storeCode = 'fixture_third_store';
        $supportEmail = 'support2@example.com';
        $this->setEmailTemplateConfig($storeCode);
        $this->mutableScopeConfig->setValue(
            'customer/magento_customerbalance/email_identity',
            'support',
            ScopeInterface::SCOPE_STORE,
            $storeCode
        );
        $this->mutableScopeConfig->setValue(
            'trans_email/ident_support/email',
            $supportEmail,
            ScopeInterface::SCOPE_STORE,
            $storeCode
        );
        $store = $this->storeManager->getStore($storeCode);
        $customer = $this->customerRepository->get('customer@example.com', $store->getWebsiteId());
        $balance = $this->balanceFactory->create();
        $balance->setCustomer($customer);
        $balance->setStoreId($store->getId());
        $balance->setNotifyByEmail(true, $store->getId());
        $history = $this->historyFactory->create();
        $history->setBalanceModel($balance);
        $history->afterSave();
        $this->assertTrue($history->getIsCustomerNotified());
        $expectedData = [
            'sender' => ['name' => 'CustomerSupport', 'email' => $supportEmail]
        ];
        $this->assertMessage($expectedData);
    }

    /**
     * Assert message.
     *
     * @param array $expectedData
     * @return void
     */
    private function assertMessage(array $expectedData): void
    {
        $message = $this->transportBuilder->getSentMessage();
        $this->assertNotNull($message);
        $this->assertMessageSender($message, $expectedData['sender']);
        if (isset($expectedData['text'])) {
            $this->assertStringContainsString(
                $expectedData['text'],
                $message->getBody()->getParts()[0]->getRawContent()
            );
        }
    }

    /**
     * Assert message sender.
     *
     * @param MessageInterface $message
     * @param array $expectedSender
     * @return void
     */
    private function assertMessageSender(MessageInterface $message, array $expectedSender): void
    {
        $messageFrom = $message->getFrom();
        $this->assertNotNull($messageFrom);
        $messageFrom = current($messageFrom);
        $this->assertEquals($expectedSender['name'], $messageFrom->getName());
        $this->assertEquals($expectedSender['email'], $messageFrom->getEmail());
    }

    /**
     * Set email template config.
     *
     * @param null|string $storeCode
     */
    private function setEmailTemplateConfig($storeCode = null): void
    {
        $templateText = '{{template config_path="design/email/header_template"}}'
            . '<p>Text specially for check in test.</p>{{template config_path="design/email/footer_template"}}';
        $template = $this->templateFactory->create();
        $template->setTemplateCode('store_credit_email_template')
            ->setTemplateText($templateText)
            ->setTemplateType(Template::TYPE_HTML);
        $this->templateResource->save($template);
        $this->mutableScopeConfig->setValue(
            'customer/magento_customerbalance/email_template',
            $template->getId(),
            ScopeInterface::SCOPE_STORE,
            $storeCode ?? 'default'
        );
    }
}
