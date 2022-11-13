<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftRegistry\Block\Customer\Edit;

use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\LayoutInterface;
use Magento\GiftRegistry\Model\Entity;
use Magento\GiftRegistry\Model\EntityFactory;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Helper\Xpath;
use PHPUnit\Framework\TestCase;

/**
 * Test for gift registry link.
 *
 * @see \Magento\GiftRegistry\Block\Customer\Edit\Registrants
 * @magentoAppArea frontend
 */
class RegistrantsTest extends TestCase
{
    /** @var ObjectManagerInterface */
    private $objectManager;

    /** @var Registrants */
    private $block;

    /** @var Registry */
    private $registry;

    /** @var EntityFactory */
    private $entityFactory;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->block = $this->objectManager->get(LayoutInterface::class)->createBlock(Registrants::class)
            ->setTemplate('Magento_GiftRegistry::edit/registrants.phtml');
        $this->registry = $this->objectManager->get(Registry::class);
        $this->entityFactory = $this->objectManager->get(EntityFactory::class);
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        $this->registry->unregister('magento_giftregistry_entity');

        parent::tearDown();
    }

    /**
     * @magentoConfigFixture current_store magento_giftregistry/general/max_registrant 3
     *
     * @return void
     */
    public function testRegistrantsForm(): void
    {
        $giftRegistry = $this->entityFactory->create();
        $this->registerGiftRegistry($giftRegistry);
        $blockHtml = $this->block->toHtml();
        $this->assertEquals(
            1,
            Xpath::getElementsCountForXpath(
                sprintf("//button[@id='add-registrant-button']/span[contains(text(), '%s')]", __('Add Registrant')),
                $blockHtml
            ),
            sprintf('%s button wasn\'t found.', __('Add Registrant'))
        );
        $this->assertEquals(
            1,
            Xpath::getElementsCountForXpath(
                sprintf(
                    "//div[@id='max-registrant-message']/span[contains(text(), '%s')]",
                    __('Maximum %1 registrants.', 3)
                ),
                $blockHtml
            ),
            sprintf('Max registrant message wasn\'t found or not equals to %s.', 3)
        );
    }

    /**
     * @magentoConfigFixture current_store magento_giftregistry/general/max_registrant 1
     *
     * @return void
     */
    public function testAddRegistrantButtonWithLimit(): void
    {
        $giftRegistry = $this->entityFactory->create();
        $this->registerGiftRegistry($giftRegistry);
        $blockHtml = $this->block->toHtml();
        $this->assertEquals(
            0,
            Xpath::getElementsCountForXpath(
                sprintf("//button[@id='add-registrant-button']/span[contains(text(), '%s')]", __('Add Registrant')),
                $blockHtml
            ),
            sprintf('%s button was found, but not expected.', __('Add Registrant'))
        );
        $this->assertEquals(
            1,
            Xpath::getElementsCountForXpath(
                sprintf(
                    "//div[@id='max-registrant-message']/span[contains(text(), '%s')]",
                    __('Maximum %1 registrants.', 1)
                ),
                $blockHtml
            ),
            sprintf('Max registrant message wasn\'t found or not equals to %s.', 1)
        );
    }

    /**
     * Register gift registry.
     *
     * @param Entity $giftRegistry
     * @return void
     */
    private function registerGiftRegistry(Entity $giftRegistry): void
    {
        $this->registry->unregister('magento_giftregistry_entity');
        $this->registry->register('magento_giftregistry_entity', $giftRegistry);
    }
}
