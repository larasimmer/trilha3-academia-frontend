<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Invitation\Controller\Customer\Account;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\GroupRegistry;
use Magento\Framework\Message\MessageInterface;
use Magento\Framework\Url\EncoderInterface;
use Magento\TestFramework\TestCase\AbstractController;
use Magento\Invitation\Model\Invitation;
use Magento\Invitation\Model\InvitationFactory;
use Magento\Invitation\Model\ResourceModel\Invitation as InvitationResource;

/**
 * Tests for customer creation via invitation email link.
 *
 * @magentoAppArea frontend
 * @magentoDbIsolation enabled
 * @magentoAppIsolation enabled
 */
class CreatePostTest extends AbstractController
{
    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var InvitationResource
     */
    private $invitationResource;

    /**
     * @var InvitationFactory
     */
    private $invitationFactory;

    /**
     * @var EncoderInterface
     */
    private $urlEncoder;

    /**
     * @var GroupRegistry
     */
    private $groupRegistry;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->customerRepository = $this->_objectManager->get(CustomerRepositoryInterface::class);
        $this->invitationResource = $this->_objectManager->get(InvitationResource::class);
        $this->invitationFactory = $this->_objectManager->get(InvitationFactory::class);
        $this->urlEncoder = $this->_objectManager->get(EncoderInterface::class);
        $this->groupRegistry = $this->_objectManager->get(GroupRegistry::class);
    }

    /**
     * @magentoDataFixture Magento/Customer/_files/customer_confirmation_config_disable.php
     * @magentoDataFixture Magento/Invitation/_files/unaccepted_invitation.php
     * @magentoConfigFixture current_store magento_invitation/general/registration_use_inviter_group 1
     * @return void
     */
    public function testCreatePostAction(): void
    {
        $this->fillRequest();
        $createUrl = sprintf(
            'customer/account/createPost/invitation/%s',
            $this->urlEncoder->encode($this->getInvitation()->getInvitationCode())
        );
        $this->dispatch($createUrl);
        $this->assertResponse();
        $invitedCustomer = $this->customerRepository->get('unaccepted_invitation@example.com');
        $inviter = $this->customerRepository->get('customer@example.com');
        $this->assertEquals($invitedCustomer->getGroupId(), $inviter->getGroupId());
    }

    /**
     * @magentoDataFixture Magento/Customer/_files/customer_confirmation_config_disable.php
     * @magentoDataFixture Magento/Invitation/_files/unaccepted_invitation.php
     * @magentoConfigFixture current_store magento_invitation/general/registration_use_inviter_group 0
     * @magentoConfigFixture current_store customer/create_account/default_group 2
     * @return void
     */
    public function testCreatePostActionWithDefaultGroup(): void
    {
        $this->fillRequest();
        $createUrl = sprintf(
            'customer/account/createPost/invitation/%s',
            $this->urlEncoder->encode($this->getInvitation()->getInvitationCode())
        );
        $this->dispatch($createUrl);
        $this->assertResponse();
        $inviterGroup = $this->groupRegistry->retrieve(
            $this->customerRepository->get('customer@example.com')->getGroupId()
        );
        $invitedCustomerGroup = $this->groupRegistry->retrieve(
            $this->customerRepository->get('unaccepted_invitation@example.com')->getGroupId()
        );
        $this->assertEquals('General', $inviterGroup->getCode());
        $this->assertEquals('Wholesale', $invitedCustomerGroup->getCode());
    }

    /**
     * Sets customer data to request.
     *
     * @return void
     */
    private function fillRequest(): void
    {
        $this->getRequest()
            ->setMethod('POST')
            ->setParam(CustomerInterface::FIRSTNAME, 'firstname1')
            ->setParam(CustomerInterface::LASTNAME, 'lastname1')
            ->setParam(CustomerInterface::EMAIL, 'unaccepted_invitation@example.com')
            ->setParam('password', '_Password1')
            ->setParam('password_confirmation', '_Password1');
    }

    /**
     * Returns invitation.
     *
     * @return Invitation
     */
    private function getInvitation(): Invitation
    {
        $invitation =  $this->invitationFactory->create();
        $this->invitationResource->load($invitation, 'unaccepted_invitation@example.com', 'email');

        return $invitation;
    }

    /**
     * Asserts response.
     *
     * @return void
     */
    private function assertResponse(): void
    {
        $this->assertRedirect($this->stringEndsWith('customer/account/'));
        $this->assertSessionMessages(
            $this->equalTo([__('Thank you for registering with Main Website Store.')]),
            MessageInterface::TYPE_SUCCESS
        );
    }
}
