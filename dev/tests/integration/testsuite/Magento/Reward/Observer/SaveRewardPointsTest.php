<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Reward\Observer;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Magento\Reward\Model\Reward;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Request;

class SaveRewardPointsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
    }

    /**
     * @magentoDataFixture Magento/Customer/_files/import_export/customer.php
     * @dataProvider saveRewardPointsDataProvider
     *
     * @param string $pointsDelta
     * @param int|null $expectedBalance
     * @return void
     */
    public function testSaveRewardPoints(string $pointsDelta, $expectedBalance): void
    {
        $initialBalance = 500;

        $customer = $this->getCustomer();
        $this->setInitialBalance($customer, $initialBalance);

        $this->saveRewardPoints($customer, $pointsDelta);

        /** @var $reward Reward */
        $reward = $this->objectManager->create(Reward::class);
        $reward->setCustomer($customer)->loadByCustomer();

        $this->assertEquals($expectedBalance, $reward->getPointsBalance());
    }

    /**
     * @return array
     */
    public function saveRewardPointsDataProvider(): array
    {
        return [
            'points delta is not set' => ['$pointsDelta' => '', '$expectedBalance' => 500],
            'points delta is positive' => ['$pointsDelta' => '100', '$expectedBalance' => 600],
            'points delta is negative' => ['$pointsDelta' => '-100', '$expectedBalance' => 400]
        ];
    }

    /**
     * @magentoDataFixture Magento/Customer/_files/import_export/customer.php
     * @dataProvider invalidRewardPointsDataProvider
     *
     * @param string $pointsDelta
     * @return void
     */
    public function testInvalidRewardPoints(string $pointsDelta): void
    {
        $customer = $this->getCustomer();
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Reward points should be a valid integer number.');

        $this->saveRewardPoints($customer, $pointsDelta);
    }

    /**
     * @return array
     */
    public function invalidRewardPointsDataProvider()
    {
        return [
            'points delta is float' => ['$pointsDelta' => '100.5'],
            'points delta with invalid minus symbol' => ['$pointsDelta' => '–100'],
            'points delta is not a number' => ['$pointsDelta' => 'not a number'],
        ];
    }

    /**
     * @param CustomerInterface $customer
     * @param mixed $pointsDelta
     * @return void
     */
    private function saveRewardPoints(CustomerInterface $customer, $pointsDelta = ''): void
    {
        $reward = ['points_delta' => (string)$pointsDelta];

        /** @var $request Request */
        $request = $this->objectManager->get(Request::class);
        $request->setPostValue(['reward' => $reward]);

        $event = new Event(['request' => $request, 'customer' => $customer]);
        $eventObserver = new Observer(['event' => $event]);

        $rewardObserver = $this->objectManager->create(SaveRewardPoints::class);
        $rewardObserver->execute($eventObserver);
    }

    /**
     * @return CustomerInterface
     */
    private function getCustomer(): CustomerInterface
    {
        /** @var \Magento\Customer\Model\Customer $customer */
        $customer = $this->objectManager->get(Registry::class)
            ->registry('_fixture/Magento_ImportExport_Customer');

        /** @var CustomerRepositoryInterface $customerRepository */
        $customerRepository = $this->objectManager->get(CustomerRepositoryInterface::class);
        $customer = $customerRepository->getById($customer->getId());

        return $customer;
    }

    /**
     * @param CustomerInterface $customer
     * @param int $balance
     * @return void
     */
    private function setInitialBalance(CustomerInterface $customer, int $balance): void
    {
        $this->saveRewardPoints($customer, $balance);
    }
}
