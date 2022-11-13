<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Invitation\Model;

use Magento\Customer\Model\CustomerRegistry;
use Magento\Email\Model\ResourceModel\Template\CollectionFactory as TemplateCollectionFactory;
use Magento\Framework\App\Config\MutableScopeConfigInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Invitation\Model\ResourceModel\Invitation as InvitationResource;
use Magento\Store\Model\ScopeInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Mail\Template\TransportBuilderMock;
use PHPUnit\Framework\TestCase;

/**
 * Tests for invitation emails sending.
 *
 * @magentoDbIsolation disabled
 */
class InvitationTest extends TestCase
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var TransportBuilderMock
     */
    private $transportBuilder;

    /**
     * @var MutableScopeConfigInterface
     */
    private $mutableScopeConfig;

    /**
     * @var TemplateCollectionFactory
     */
    private $templateCollectionFactory;

    /**
     * @var CustomerRegistry
     */
    private $customerRegistry;

    /**
     * @var InvitationFactory
     */
    private $invitationFactory;

    /**
     * @var InvitationResource
     */
    private $invitationResource;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->transportBuilder = $this->objectManager->get(TransportBuilderMock::class);
        $this->mutableScopeConfig = $this->objectManager->get(MutableScopeConfigInterface::class);
        $this->templateCollectionFactory = $this->objectManager->get(TemplateCollectionFactory::class);
        $this->customerRegistry = $this->objectManager->get(CustomerRegistry::class);
        $this->invitationFactory = $this->objectManager->get(InvitationFactory::class);
        $this->invitationResource = $this->objectManager->get(InvitationResource::class);
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        $this->mutableScopeConfig->clean();
        $invitation = $this->invitationFactory->create();
        $this->invitationResource->load($invitation, 'invitation@example.com', 'email');
        if ($invitation->getId()) {
            $this->invitationResource->delete($invitation);
        }

        parent::tearDown();
    }

    /**
     * @magentoDataFixture Magento/Customer/_files/customer.php
     * @dataProvider invitationDataProvider
     * @param array $configs
     * @param array $result
     * @return void
     */
    public function testSendInvitationEmail(array $configs, array $result): void
    {
        $this->setConfig($configs);
        $invitation = $this->getInvitation($result['message']);
        $invitation->sendInvitationEmail();
        $this->assertEmailData($result);
    }

    /**
     * @magentoDataFixture Magento/Customer/_files/customer.php
     * @magentoDataFixture Magento/Invitation/_files/custom_invitation_template.php
     * @return void
     */
    public function testSendInvitationEmailWithCustomTemplate(): void
    {
        $message = 'Custom template message';
        $expectedData = ['name' => 'Owner', 'email' => 'owner@example.com', 'message' => $message];
        $this->setConfig([Invitation::XML_PATH_EMAIL_TEMPLATE => $this->getCustomTemplateId()]);
        $invitation = $this->getInvitation($message);
        $invitation->sendInvitationEmail();
        $this->assertEmailData($expectedData);
    }

    /**
     * @magentoDataFixture Magento/Customer/_files/customer.php
     * @magentoDataFixture Magento/Invitation/_files/custom_invitation_template.php
     * @return void
     */
    public function testCanBeSent(): void
    {
        $message = 'Custom template message';
        $this->setConfig([Invitation::XML_PATH_EMAIL_TEMPLATE => $this->getCustomTemplateId()]);
        $invitation = $this->getInvitation($message);
        $this->assertIsBool($invitation->canBeSent());
    }

    /**
     * @return array
     */
    public function invitationDataProvider(): array
    {
        return [
            'default_email_identity_with_empty_message' => [
                'config' => [],
                'result' => ['name' => 'Owner', 'email' => 'owner@example.com', 'message' => ''],
            ],
            'custom_email_identity' => [
                'config' => [Invitation::XML_PATH_EMAIL_IDENTITY => 'custom1'],
                'result' => ['name' => 'Custom 1', 'email' => 'custom1@example.com', 'message' => ''],
            ],
            'custom_email_identity_with_message' => [
                'config' => [Invitation::XML_PATH_EMAIL_IDENTITY => 'custom2'],
                'result' => ['name' => 'Custom 2', 'email' => 'custom2@example.com', 'message' => 'Test Message'],
            ],
        ];
    }

    /**
     * Returns invitation.
     *
     * @param string $message
     * @return Invitation
     */
    private function getInvitation(string $message): Invitation
    {
        $customer = $this->customerRegistry->retrieve(1);
        $invitation = $this->invitationFactory->create();
        $invitation->setData(
            ['email' => 'invitation@example.com', 'customer' => $customer, 'message' => $message]
        );
        $this->invitationResource->save($invitation);

        return $invitation;
    }

    /**
     * Sets config data.
     *
     * @param array $configs
     * @return void
     */
    private function setConfig(array $configs): void
    {
        foreach ($configs as $path => $value) {
            $this->mutableScopeConfig->setValue($path, $value, ScopeInterface::SCOPE_STORE, 'default');
        }
    }

    /**
     * Assert email data.
     *
     * @param array $expectedSender
     * @return void
     */
    private function assertEmailData(array $expectedSender): void
    {
        $message = $this->transportBuilder->getSentMessage();
        $this->assertNotNull($message);
        $messageFrom = $message->getFrom();
        $this->assertNotNull($messageFrom);
        $messageFrom = reset($messageFrom);
        $this->assertEquals($expectedSender['name'], $messageFrom->getName());
        $this->assertEquals($expectedSender['email'], $messageFrom->getEmail());

        if ($expectedSender['message']) {
            $this->assertStringContainsString(
                $expectedSender['message'],
                $message->getBody()->getParts()[0]->getRawContent(),
                'Expected message wasn\'t found in email content.'
            );
        }
    }

    /**
     * Returns template id for custom invitation email.
     *
     * @return int
     */
    private function getCustomTemplateId(): int
    {
        return (int)$this->templateCollectionFactory->create()
            ->addFieldToFilter('template_code', 'custom_invitation_template')
            ->getFirstItem()
            ->getId();
    }
}
