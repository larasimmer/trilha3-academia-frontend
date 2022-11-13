<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftRegistry\Model;

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
use Magento\TestFramework\Helper\Xpath;
use Magento\TestFramework\Mail\Template\TransportBuilderMock;
use PHPUnit\Framework\TestCase;

/**
 * Test for send gift registry email with custom template.
 *
 * @magentoDbIsolation enabled
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class EntityCustomTemplateTest extends TestCase
{
    /** @var ObjectManagerInterface */
    private $objectManager;

    /** @var Manager */
    private $moduleManager;

    /** @var EntityFactory */
    private $entityFactory;

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

    /** @var StoreManagerInterface */
    private $storeManager;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->moduleManager = $this->objectManager->get(Manager::class);
        //This check is needed because Magento_GiftRegistry independent of Magento_Email
        if (!$this->moduleManager->isEnabled('Magento_Email')) {
            $this->markTestSkipped('Magento_Email module disabled.');
        }
        $this->entityFactory = $this->objectManager->get(EntityFactory::class);
        $this->transportBuilder = $this->objectManager->get(TransportBuilderMock::class);
        $this->templateResource = $this->objectManager->get(TemplateResource::class);
        $this->templateFactory = $this->objectManager->get(TemplateFactory::class);
        $this->mutableScopeConfig = $this->objectManager->get(MutableScopeConfigInterface::class);
        $this->templateCollectionFactory = $this->objectManager->get(CollectionFactory::class);
        $this->storeManager = $this->objectManager->get(StoreManagerInterface::class);
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        if ($this->moduleManager->isEnabled('Magento_Email')) {
            $this->mutableScopeConfig->clean();
            $collection = $this->templateCollectionFactory->create();
            $template = $collection->addFieldToFilter('template_code', 'gift_registry_email_template')
                ->getFirstItem();
            if ($template->getId()) {
                $this->templateResource->delete($template);
            }
        }

        parent::tearDown();
    }

    /**
     * @magentoConfigFixture current_store magento_giftregistry/owner_email/identity custom1
     * @magentoDataFixture Magento/Customer/_files/customer.php
     *
     * @return void
     */
    public function testSendNewGiftRegistryEmail(): void
    {
        $this->setEmailTemplateConfig(Entity::XML_PATH_OWNER_EMAIL_TEMPLATE);
        $entity = $this->entityFactory->create();
        $entity->setCustomerId(1);
        $entity->sendNewRegistryEmail();
        $expectedSender = ['name' => 'Custom 1', 'email' => 'custom1@example.com'];
        $this->assertMessage($expectedSender);
    }

    /**
     * @magentoAppArea frontend
     * @magentoDataFixture Magento/GiftRegistry/_files/gift_registry_entity_simple.php
     * @return void
     */
    public function testSendNewGiftRegistryEmailCheckRegistryId(): void
    {
        $registryId = 'gift_regidtry_simple_url';
        $giftRegistry = $this->entityFactory->create();
        $giftRegistry->loadByUrlKey($registryId);
        $giftRegistry->sendNewRegistryEmail();
        $message = $this->transportBuilder->getSentMessage();
        $bodyParts = $message->getBody()->getParts();
        $messageContent = reset($bodyParts)->getRawContent();
        $this->assertNotNull($message);
        $this->assertEquals(
            1,
            Xpath::getElementsCountForXpath(
                sprintf("//p/a[contains(@href, '%s')]", $registryId),
                $messageContent
            ),
            'Expected registry location is incorrect or missing!'
        );
        $this->assertEquals(
            1,
            Xpath::getElementsCountForXpath(
                sprintf("//p/child::text()[contains(., '%s')]", $registryId),
                $messageContent
            ),
            'Expected registry id is incorrect or missing!'
        );
    }

    /**
     * @magentoConfigFixture current_store magento_giftregistry/update_email/identity custom1
     * @magentoDataFixture Magento/Customer/_files/customer.php
     *
     * @return void
     */
    public function testSendUpdateGiftRegistryEmail(): void
    {
        $this->setEmailTemplateConfig(Entity::XML_PATH_UPDATE_EMAIL_TEMPLATE);
        $entity = $this->entityFactory->create();
        $entity->setCustomerId(1);
        $result = $entity->sendUpdateRegistryEmail([]);
        $this->assertTrue($result);
        $expectedSender = ['name' => 'Custom 1', 'email' => 'custom1@example.com'];
        $this->assertMessage($expectedSender);
    }

    /**
     * @magentoConfigFixture current_store magento_giftregistry/sharing_email/identity custom2
     *
     * @return void
     */
    public function testShareGiftRegistryEmail(): void
    {
        $this->setEmailTemplateConfig(Entity::XML_PATH_SHARE_EMAIL_TEMPLATE);
        $storeId = $this->storeManager->getStore('default')->getStoreId();
        $entity = $this->entityFactory->create();
        $result = $entity->sendShareRegistryEmail('test@example.com', $storeId, 'message');
        $this->assertTrue($result);
        $expectedSender = ['name' => 'Custom 2', 'email' => 'custom2@example.com'];
        $this->assertMessage($expectedSender);
    }

    /**
     * Assert message.
     *
     * @param array $expectedSender
     * @return void
     */
    private function assertMessage(array $expectedSender): void
    {
        $message = $this->transportBuilder->getSentMessage();
        $this->assertNotNull($message);
        $this->assertMessageSender($message, $expectedSender);
        $this->assertStringContainsString(
            'Text specially for check in test.',
            $message->getBody()->getParts()[0]->getRawContent(),
            'Expected text wasn\'t found in message.'
        );
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
     * @param string $configPath
     * @return void
     */
    private function setEmailTemplateConfig(string $configPath): void
    {
        $templateText = '{{template config_path="design/email/header_template"}}'
            . '<p>Text specially for check in test.</p>{{template config_path="design/email/footer_template"}}';
        $template = $this->templateFactory->create();
        $template->setTemplateCode('gift_registry_email_template')
            ->setTemplateText($templateText)
            ->setTemplateType(Template::TYPE_HTML);
        $this->templateResource->save($template);
        $this->mutableScopeConfig->setValue($configPath, $template->getId(), ScopeInterface::SCOPE_STORE, 'default');
    }
}
