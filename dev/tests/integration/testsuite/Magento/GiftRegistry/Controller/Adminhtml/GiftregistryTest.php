<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftRegistry\Controller\Adminhtml;

use Magento\Framework\Escaper;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\AbstractBackendController;

/**
 * Testing registry controllers.
 *
 * @magentoAppArea adminhtml
 */
class GiftregistryTest extends AbstractBackendController
{
    /**
     * @var Escaper
     */
    private $escaper;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->escaper = Bootstrap::getObjectManager()->get(Escaper::class);
    }

    /**
     * Rendering new registry page.
     */
    public function testNewAction()
    {
        $this->dispatch('backend/admin/giftregistry/new');
        $body = preg_replace('/\s\s+/', ' ', str_replace(PHP_EOL, '', $this->getResponse()->getBody()));
        $this->assertMatchesRegularExpression(
            '/<h1 class\="page-title">\s*New Gift Registry Type\s*<\/h1>/',
            $body
        );
        $this->assertStringContainsString(
            '<a href="#magento_giftregistry_tabs_general_section_content"' .
            ' id="magento_giftregistry_tabs_general_section" name="general_section"' .
            ' title="' .$this->escaper->escapeHtmlAttr('General Information') .'"',
            $body
        );
        $this->assertStringContainsString(
            '<a href="#magento_giftregistry_tabs_registry_attributes_content"' .
            ' id="magento_giftregistry_tabs_registry_attributes"' .
            ' name="registry_attributes" title="Attributes"',
            $body
        );
    }

    /**
     * Testing creating a new registry via the save controller.
     *
     * @magentoDbIsolation enabled
     */
    public function testSaveAction()
    {
        $this->getRequest()->setPostValue(
            'type',
            ['code' => 'test_registry', 'label' => 'Test', 'sort_order' => 10, 'is_listed' => 1]
        );
        $this->dispatch('backend/admin/giftregistry/save/store/0');
        /** @var $type \Magento\GiftRegistry\Model\Type */
        $type = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(
            \Magento\GiftRegistry\Model\Type::class
        );
        $type->setStoreId(0);

        $type = $type->load('test_registry', 'code');

        $this->assertInstanceOf(\Magento\GiftRegistry\Model\Type::class, $type);
        $this->assertNotEmpty($type->getId());
    }
}
