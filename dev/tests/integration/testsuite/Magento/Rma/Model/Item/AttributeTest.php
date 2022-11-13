<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Rma\Model\Item;

use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Eav\Model\Config;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Entity;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Test for \Magento\Rma\Model\Item\Attribute.
 */
class AttributeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var Attribute
     */
    private $model;

    /**
     * @var AttributeRepositoryInterface
     */
    private $attributeRepository;

    /**
     * @var int|string
     */
    private $rmaItemEntityType;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->model = $this->objectManager->get(Attribute::class);
        $this->attributeRepository = $this->objectManager->get(AttributeRepositoryInterface::class);
        $this->rmaItemEntityType = $this->objectManager->get(Config::class)
            ->getEntityType('rma_item')
            ->getId();
    }

    /**
     * Test Create -> Read -> Update -> Delete attribute operations.
     *
     * @return void
     */
    public function testCRUD(): void
    {
        $this->model->setAttributeCode('test')
            ->setEntityTypeId($this->rmaItemEntityType)
            ->setFrontendLabel('test')
            ->setIsUserDefined(1);
        $crud = new Entity($this->model, [AttributeInterface::FRONTEND_LABEL => uniqid()]);
        $crud->testCrud();
    }

    /**
     * @magentoDataFixture Magento/Rma/_files/rma_item_attribute.php
     *
     * @return void
     */
    public function testAttributeSaveWithChangedEntityType(): void
    {
        $this->expectException(
            \Magento\Framework\Exception\LocalizedException::class
        );
        $this->expectExceptionMessage('Do not change entity type.');

        $attribute = $this->attributeRepository->get($this->rmaItemEntityType, 'rma_item_attribute');
        $attribute->setEntityTypeId(5);
        $attribute->save();
    }

    /**
     * @magentoDataFixture Magento/Rma/_files/rma_item_attribute.php
     *
     * @return void
     */
    public function testAttributeSaveWithoutChangedEntityType(): void
    {
        $attribute = $this->attributeRepository->get($this->rmaItemEntityType, 'rma_item_attribute');
        $attribute->setSortOrder(1250);
        $attribute->save();
    }
}
