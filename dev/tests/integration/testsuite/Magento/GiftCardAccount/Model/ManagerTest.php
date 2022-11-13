<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCardAccount\Model;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\ObjectManagerInterface;
use Magento\GiftCardAccount\Api\Data\GiftCardAccountInterface;
use Magento\GiftCardAccount\Model\Spi\GiftCardAccountManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Test for gift card account manager model.
 *
 * @see \Magento\GiftCardAccount\Model\Manager
 *
 * @magentoDbIsolation enabled
 */
class ManagerTest extends TestCase
{
    /** @var ObjectManagerInterface */
    private $objectManager;

    /** @var GiftCardAccountManagerInterface */
    private $giftCardManager;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->giftCardManager = $this->objectManager->get(GiftCardAccountManagerInterface::class);
    }

    /**
     * @magentoDataFixture Magento/GiftCardAccount/_files/giftcardaccount.php
     *
     * @return void
     */
    public function testGetGiftCardAccount(): void
    {
        $code = 'giftcardaccount_fixture';
        $account = $this->giftCardManager->requestByCode($code);
        $this->assertInstanceOf(GiftCardAccountInterface::class, $account);
        $this->assertEquals($code, $account->getCode());
    }

    /**
     * @return void
     */
    public function testGetNotExistingGiftCardAccount(): void
    {
        $this->expectException(NoSuchEntityException::class);
        $this->giftCardManager->requestByCode('not_existing_code');
    }

    /**
     * @magentoDataFixture Magento/GiftCardAccount/_files/expired_giftcard_account.php
     *
     * @return void
     */
    public function testGetExpiredGiftCardAccount(): void
    {
        $this->expectExceptionObject(new \InvalidArgumentException('Gift Card Account is invalid'));
        $this->giftCardManager->requestByCode('expired_giftcard_account');
    }
}
