<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\GiftCardAccount\Model\Spi;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Test the manager.
 */
class GiftCardAccountManagerInterfaceTest extends TestCase
{
    /**
     * @var GiftCardAccountManagerInterface
     */
    private $manager;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->manager = Bootstrap::getObjectManager()->get(GiftCardAccountManagerInterface::class);
    }

    /**
     * Test validations requestByCode executes.
     *
     * @magentoDataFixture Magento/GiftCardAccount/_files/giftcardaccount.php
     */
    public function testRequestByCode()
    {
        //Positive scenario.
        $account = $this->manager->requestByCode('giftcardaccount_fixture');
        $this->assertNull($account->getGiftCards());

        //Using fake code.
        try {
            $this->manager->requestByCode('fake_code');
            $this->fail('Returned gift card account for non-existing code');
        } catch (NoSuchEntityException $exception) {
            //Not found.
        }

        //Filtering
        try {
            $this->manager->requestByCode('giftcardaccount_fixture', 3, 100.0, false, true);
            $this->fail('Failed to validate gift card account.');
        } catch (\InvalidArgumentException $exception) {
            //All good
        }
    }
}
