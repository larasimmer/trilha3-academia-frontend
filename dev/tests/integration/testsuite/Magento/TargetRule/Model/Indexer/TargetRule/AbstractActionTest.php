<?php
/**
 * @category    Magento
 * @package     Magento_TargetRule
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\TargetRule\Model\Indexer\TargetRule;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Indexer\TestCase;
use Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule as IndexerTargetProductRule;

/**
 * Test for index target rule
 */
class AbstractActionTest extends TestCase
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
    }

    /**
     * Test for index rule with removed product
     *
     * @return void
     *
     * @magentoAppArea adminhtml
     * @magentoDbIsolation disabled
     * @magentoDataFixture Magento/TargetRule/_files/related_conditions_attribute.php
     */
    public function testIndexRuleWithRemovedProduct(): void
    {
        $indexer = $this->objectManager->get(IndexerTargetProductRule::class);

        /** @var ProductRepositoryInterface $productRepository */
        $productRepository = $this->objectManager->get(ProductRepositoryInterface::class);
        try {
            $product = $productRepository->get('simple1');
            $productRepository->delete($product);
            // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock
        } catch (NoSuchEntityException $exception) {
        }

        try {
            $indexer->executeList(
                [
                    $product->getId(),
                ]
            );
        } catch (LocalizedException $exp) {
            $this->fail($exp->getMessage());
        }
    }
}
