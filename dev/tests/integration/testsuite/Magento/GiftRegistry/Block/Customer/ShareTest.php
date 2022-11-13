<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftRegistry\Block\Customer;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\View\LayoutInterface;
use Magento\GiftRegistry\Model\EntityFactory;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Helper\Xpath;
use PHPUnit\Framework\TestCase;

/**
 * Test for share gift registry block.
 *
 * @see \Magento\GiftRegistry\Block\Customer\Share
 * @magentoAppArea frontend
 */
class ShareTest extends TestCase
{
    /** @var ObjectManagerInterface */
    private $objectManager;

    /** @var Share */
    private $block;

    /** @var EntityFactory */
    private $entityFactory;

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
        $this->block = $this->objectManager->get(LayoutInterface::class)->createBlock(Share::class)
            ->setTemplate('Magento_GiftRegistry::customer/share.phtml');
        $this->entityFactory = $this->objectManager->get(EntityFactory::class);
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
     * @magentoConfigFixture current_store magento_giftregistry/sharing_email/send_limit 2
     * @magentoDataFixture Magento/GiftRegistry/_files/gift_registry_entity_simple.php
     *
     * @return void
     */
    public function testShareGiftRegistryForm(): void
    {
        $customer = $this->customerRepository->get('john.doe@magento.com');
        $this->session->setCustomerId($customer->getId());
        $giftRegistry = $this->entityFactory->create()->loadByUrlKey('gift_regidtry_simple_url');
        $blockHtml = $this->block->setEntity($giftRegistry)->toHtml();
        $this->assertEquals(
            1,
            Xpath::getElementsCountForXpath(
                sprintf(
                    "//h2[contains(@class, 'subtitle') and contains(text(), \"%s\")]",
                    __("Share '%1' Gift Registry", $giftRegistry->getTitle())
                ),
                $blockHtml
            ),
            'Title for gift registry wasn\'t found.'
        );
        $this->assertEquals(
            1,
            Xpath::getElementsCountForXpath(
                sprintf(
                    "//div[contains(@class, 'name')]"
                    . "//input[contains(@name, 'sender_name') and contains(@value, '%s')]",
                    $this->block->getCustomerName()
                ),
                $blockHtml
            ),
            sprintf('Input sender name wasn\'t found or not equals to %s.', $this->block->getCustomerName())
        );
        $this->assertEquals(
            1,
            Xpath::getElementsCountForXpath(
                "//div[contains(@class, 'message')]//textarea[contains(@name, 'sender_message')]",
                $blockHtml
            ),
            'Input for message wasn\'t found.'
        );
        $this->assertEquals(
            1,
            Xpath::getElementsCountForXpath(
                sprintf(
                    "//button[@id='add-recipient-button']/span[contains(text(), '%s')]",
                    __('Add Invitee')
                ),
                $blockHtml
            ),
            sprintf('%s button wasn\'t found.', __('Add Invitee'))
        );
        $this->assertEquals(
            1,
            Xpath::getElementsCountForXpath(
                sprintf(
                    "//div[@id='max-recipient-message']/span[contains(text(), '%s')]",
                    __('Maximum %1 email addresses.', 2)
                ),
                $blockHtml
            ),
            sprintf('Max recipient email addresses wasn\'t found or not equals to %s.', 2)
        );
        $this->assertEquals(
            1,
            Xpath::getElementsCountForXpath(
                sprintf(
                    "//button[contains(@class, 'share')]/span[contains(text(), '%s')]",
                    __('Share Gift Registry')
                ),
                $blockHtml
            ),
            sprintf('%s button wasn\'t found.', __('Share Gift Registry'))
        );
    }
}
