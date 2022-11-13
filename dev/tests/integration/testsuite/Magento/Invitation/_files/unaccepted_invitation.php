<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Invitation\Model\Invitation;
use Magento\Invitation\Model\InvitationFactory;
use Magento\Invitation\Model\ResourceModel\Invitation as InvitationResource;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

Resolver::getInstance()->requireDataFixture('Magento/Customer/_files/customer.php');

$objectManager = Bootstrap::getObjectManager();
/** @var CustomerRepositoryInterface $customerRepository */
$customerRepository = $objectManager->create(CustomerRepositoryInterface::class);
$customer = $customerRepository->get('customer@example.com', 1);
/** @var InvitationResource $invitationResource */
$invitationResource = $objectManager->get(InvitationResource::class);
/** @var Invitation $invitation */
$invitation = $objectManager->get(InvitationFactory::class)->create();
$invitation->setInvitationDate('2015-08-03 09:13:11');
$invitation->isObjectNew(true);
$invitation->setCustomerId($customer->getId())
    ->setEmail('unaccepted_invitation@example.com')
    ->setGroupId($customer->getGroupId());
$invitationResource->save($invitation);
