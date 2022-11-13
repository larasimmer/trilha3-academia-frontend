<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Catalog\Model\Product\Type;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Store\Model\Store;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;
use Magento\Theme\Model\ResourceModel\Theme\CollectionFactory as ThemeCollectionFactory;
use Magento\VersionsCms\Api\Data\HierarchyNodeInterfaceFactory;
use Magento\VersionsCms\Model\Hierarchy\Node;
use Magento\VersionsCms\Model\ResourceModel\Hierarchy\Node as NodeResource;
use Magento\Widget\Model\ResourceModel\Widget\Instance as InstanceResource;
use Magento\Widget\Model\Widget\InstanceFactory;

Resolver::getInstance()->requireDataFixture('Magento/VersionsCms/_files/hierarchy_node_with_default_store.php');

$objectManager = Bootstrap::getObjectManager();
/** @var StoreRepositoryInterface $storeRepository */
$storeRepository = $objectManager->get(StoreRepositoryInterface::class);
$storeId = $storeRepository->get('default')->getId();
/** @var ThemeCollectionFactory $themeCollectionFactory */
$themeCollectionFactory = $objectManager->get(ThemeCollectionFactory::class);
$themeCollection = $themeCollectionFactory->create();
/** @var NodeResource $nodeResource */
$nodeResource = $objectManager->get(NodeResource::class);
/** @var HierarchyNodeInterfaceFactory $nodeFactory */
$nodeFactory = $objectManager->get(HierarchyNodeInterfaceFactory::class);
$node = $nodeFactory->create();
$nodeResource->load($node, 'simple_node', Node::IDENTIFIER);
/** @var InstanceResource $resourceModel */
$resourceModel = $objectManager->get(InstanceResource::class);
/** @var InstanceFactory $model */
$widgetFactory = $objectManager->get(InstanceFactory::class);
$widget = $widgetFactory->create();
$widget->setData(
    [
        'title' => 'Test Widget with Hierarchy node for default store',
        'store_ids' => [Store::DEFAULT_STORE_ID],
        'sort_order' => 1,
        'code' => 'cms_hierarchy_node',
        'instance_type' => \Magento\VersionsCms\Block\Widget\Node::class,
        'theme_id' => $themeCollection->getThemeByFullPath('frontend/Magento/luma')->getThemeId(),
        'page_groups' => [
            [
                'page_group' => 'simple_products',
                'simple_products' => [
                    'page_id' => '0',
                    'layout_handle' => 'catalog_product_view_type_simple',
                    'for' => 'all',
                    'block' => 'content',
                    'template' => 'hierarchy/widget/link/link_block.phtml',
                    'product_type_id' => Type::TYPE_SIMPLE,
                ],
            ],
        ],
        'widget_parameters' => [
            'node_id_' . $storeId => $node->getId(),
            'anchor_text_' . $storeId => 'Default store node text',
            'title_' . $storeId => 'Title',
            'radio' => $storeId
        ],
    ]
);

$resourceModel->save($widget);
