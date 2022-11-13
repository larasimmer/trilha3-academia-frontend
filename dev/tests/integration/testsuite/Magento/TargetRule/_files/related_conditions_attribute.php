<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\TestFramework\Helper\Bootstrap;
use Magento\TargetRule\Model\Rule;
use Magento\TargetRule\Model\Actions\Condition\Combine;
use Magento\TargetRule\Model\Rule\Condition\Product\Attributes;
use Magento\TargetRule\Model\ResourceModel\Rule as TargetRuleResourceModel;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

Resolver::getInstance()->requireDataFixture('Magento/TargetRule/_files/products_with_attributes.php');

$objectManager = Bootstrap::getObjectManager();
/** @var TargetRuleResourceModel $targetRuleResourceModel */
$targetRuleResourceModel = $objectManager->get(TargetRuleResourceModel::class);
/** @var Rule $model */
$model = $objectManager->get(Rule::class);
$model->setName('Test rule');
$model->setSortOrder(0);
$model->setIsActive(1);
$model->setApplyTo(Rule::RELATED_PRODUCTS);
$conditions = [
    'type' => Combine::class,
    'aggregator' => 'all',
    'value' => 1,
    'new_child' => '',
    'conditions' => [],
];
$conditions['conditions'][1] = [
    'type' => Attributes::class,
    'attribute' => 'promo_attribute',
    'operator' => '==',
    'value' => 'RELATED_PRODUCT',
];
$model->getConditions()->setConditions([])->loadArray($conditions);
$targetRuleResourceModel->save($model);
