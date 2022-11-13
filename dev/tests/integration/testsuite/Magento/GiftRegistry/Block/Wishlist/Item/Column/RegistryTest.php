<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftRegistry\Block\Wishlist\Item\Column;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\View\LayoutInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Test add to gift registry link in wishlist block.
 *
 * @see \Magento\GiftRegistry\Block\Wishlist\Item\Column\Registry
 * @magentoAppArea frontend
 */
class RegistryTest extends TestCase
{
    /** @var ObjectManagerInterface */
    private $objectManager;

    /** @var Registry */
    private $block;

    /** @var Session */
    private $session;

    /** @var CustomerRepositoryInterface */
    private $customerRepository;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->block = $this->objectManager->get(LayoutInterface::class)->createBlock(Registry::class);
        $this->session = $this->objectManager->get(Session::class);
        $this->customerRepository = $this->objectManager->get(CustomerRepositoryInterface::class);
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        $this->session->setCustomerId(null);

        parent::tearDown();
    }

    /**
     * @magentoConfigFixture current_store magento_giftregistry/general/enabled 1
     * @magentoDataFixture Magento/GiftRegistry/_files/gift_registry_entity_simple.php
     *
     * @return void
     */
    public function testGiftRegistryEnabled(): void
    {
        $customer = $this->customerRepository->get('john.doe@magento.com');
        $this->session->setCustomerId($customer->getId());
        $this->assertTrue($this->block->isEnabled());
    }

    /**
     * @magentoConfigFixture current_store magento_giftregistry/general/enabled 0
     * @magentoDataFixture Magento/GiftRegistry/_files/gift_registry_entity_simple.php
     *
     * @return void
     */
    public function testGiftRegistryDisabled(): void
    {
        $customer = $this->customerRepository->get('john.doe@magento.com');
        $this->session->setCustomerId($customer->getId());
        $this->assertFalse($this->block->isEnabled());
    }
}
