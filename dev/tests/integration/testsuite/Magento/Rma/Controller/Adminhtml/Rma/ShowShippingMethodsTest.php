<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Rma\Controller\Adminhtml\Rma;

use Magento\TestFramework\TestCase\AbstractBackendController;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Rma\Api\RmaRepositoryInterface;
use Magento\Rma\Model\Rma;

/**
 * Class ShowShippingMethods
 *
 * @magentoAppArea adminhtml
 */
class ShowShippingMethodsTest extends AbstractBackendController
{
    /**
     * Tests for ShowShippingMethods action
     *
     * @return void
     * @magentoDataFixture Magento/Rma/_files/rma_for_ship_label.php
     */
    public function testShowShippingMethods(): void
    {
        /** @var \Magento\Catalog\Api\ProductRepositoryInterface $productRepository */
        $productRepository = $this->_objectManager->get(\Magento\Catalog\Api\ProductRepositoryInterface::class);
        $productSku = 'simple';
        $productName = 'Simple Product';
        $product = $productRepository->get($productSku);
        $productRepository->delete($product);

        $params = $this->getRequestShippingData();
        $request = $this->getRequest();
        $this->getRequest()->setParam('isAjax', true);
        $this->getRequest()->setMethod('POST');
        $request->setPostValue($params);

        $this->dispatch('backend/admin/rma/showShippingMethods');

        $this->assertEquals(
            '{"error":true,"message":"The label cannot be created for \''.
            $productName.'\' because the product does not exist in the system."}',
            $this->getResponse()->getBody()
        );
    }

    /**
     * Gets request params.
     *
     * @return array
     */
    private function getRequestShippingData(): array
    {
        $rmaId = $this->getRma('103')
            ->getId();

        return [
            'id' => $rmaId,
            'form_key' => $this->_objectManager->get(FormKey::class)->getFormKey(),
        ];
    }

    /**
     * Loads RMA entity by increment ID.
     *
     * @param string $incrementId
     * @return Rma
     */
    private function getRma(string $incrementId): Rma
    {
        /** @var SearchCriteriaBuilder $searchCriteriaBuilder */
        $searchCriteriaBuilder = $this->_objectManager->get(SearchCriteriaBuilder::class);
        $searchCriteria = $searchCriteriaBuilder->addFilter('increment_id', $incrementId)
            ->create();

        /** @var RmaRepositoryInterface $repository */
        $repository = $this->_objectManager->get(RmaRepositoryInterface::class);
        $items = $repository->getList($searchCriteria)
            ->getItems();

        return array_pop($items);
    }
}
