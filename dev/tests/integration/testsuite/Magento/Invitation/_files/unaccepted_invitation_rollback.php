<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Invitation\Model\Invitation;
use Magento\Invitation\Model\InvitationFactory;
use Magento\Invitation\Model\ResourceModel\Invitation as InvitationResource;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

Resolver::getInstance()->requireDataFixture('Magento/Customer/_files/customer_rollback.php');

$objectManager = Bootstrap::getObjectManager();
/** @var InvitationResource $invitationResource */
$invitationResource = $objectManager->get(InvitationResource::class);
/** @var Invitation $invitation */
$invitation = $objectManager->get(InvitationFactory::class)->create();
$invitationResource->load($invitation, 'unaccepted_invitation@example.com', 'email');
if ($invitation->getId()) {
    $invitationResource->delete($invitation);
}
