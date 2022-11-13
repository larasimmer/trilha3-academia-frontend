<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCardAccount\Api;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\GiftCardAccount\Model\Giftcardaccount;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Class to test Gift Card Account Repository
 */
class GiftCardAccountRepositoryInterfaceTest extends TestCase
{
    /**
     * @var GiftCardAccountRepositoryInterface
     */
    private $repository;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->repository = Bootstrap::getObjectManager()->create(GiftCardAccountRepositoryInterface::class);
    }

    /**
     * @magentoDataFixture Magento/GiftCardAccount/_files/giftcardaccounts_for_search.php
     */
    public function testGetList()
    {
        /** @var FilterBuilder $filterBuilder */
        $filterBuilder = Bootstrap::getObjectManager()->create(FilterBuilder::class);

        $filter1 = $filterBuilder->setField('code')
            ->setValue('gift_card_account_2')
            ->create();
        $filter2 = $filterBuilder->setField('code')
            ->setValue('gift_card_account_3')
            ->create();
        $filter3 = $filterBuilder->setField('code')
            ->setValue('gift_card_account_4')
            ->create();
        $filter4 = $filterBuilder->setField('code')
            ->setValue('gift_card_account_5')
            ->create();
        $filter5 = $filterBuilder->setField('balance')
            ->setValue(45)
            ->setConditionType('lt')
            ->create();

        /**@var SortOrderBuilder $sortOrderBuilder */
        $sortOrderBuilder = Bootstrap::getObjectManager()->create(SortOrderBuilder::class);

        /** @var SortOrder $sortOrder */
        $sortOrder = $sortOrderBuilder->setField('balance')->setDirection(SortOrder::SORT_DESC)->create();

        /** @var SearchCriteriaBuilder $searchCriteriaBuilder */
        $searchCriteriaBuilder =  Bootstrap::getObjectManager()->create(SearchCriteriaBuilder::class);

        $searchCriteriaBuilder->addFilters([$filter1, $filter2, $filter3, $filter4]);
        $searchCriteriaBuilder->addFilters([$filter5]);
        $searchCriteriaBuilder->setSortOrders([$sortOrder]);

        $searchCriteriaBuilder->setPageSize(2);
        $searchCriteriaBuilder->setCurrentPage(2);

        $searchCriteria = $searchCriteriaBuilder->create();

        $searchResult = $this->repository->getList($searchCriteria);

        $items = array_values($searchResult->getItems());
        $this->assertCount(1, $items);
        $this->assertEquals('gift_card_account_2', $items[0]['code']);
    }

    /**
     * @magentoDataFixture Magento/GiftCardAccount/_files/expired_giftcard_account.php
     */
    public function testSaveExpiredWithFutureDate()
    {
        $model = $this->getGiftCardAccount('expired_giftcard_account');
        $this->assertTrue($model->isExpired());
        $this->assertEquals(Giftcardaccount::STATE_EXPIRED, $model->getState());
        $model->setDateExpires(date('Y-m-d', strtotime('+2 day')));
        $this->repository->save($model);
        $model = $this->getGiftCardAccount('expired_giftcard_account');
        $this->assertFalse($model->isExpired());
        $this->assertEquals(Giftcardaccount::STATE_AVAILABLE, $model->getState());
    }

    /**
     * Get gift card account by code
     *
     * @param string $code
     * @return Giftcardaccount
     */
    private function getGiftCardAccount(string $code): Giftcardaccount
    {
        $objectManager = Bootstrap::getObjectManager();
        /** * @var Giftcardaccount $model */
        $model = $objectManager->create(Giftcardaccount::class);
        $model->loadByCode($code);
        return $model;
    }
}
