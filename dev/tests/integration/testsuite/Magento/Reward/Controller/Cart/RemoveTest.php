<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Reward\Controller\Cart;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\TestFramework\TestCase\AbstractController;
use Magento\Checkout\Model\Session;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface as CustomerData;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\LocalizedException;

/**
 * Test for class \Magento\Reward\Controller\Cart\Remove
 *
 * @magentoAppIsolation enabled
 * @magentoDbIsolation enabled
 * @magentoAppArea frontend
 * @magentoDataFixture Magento/Reward/_files/quote_with_reward_points.php
 */
class RemoveTest extends AbstractController
{
    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var Json
     */
    private $jsonSerializer;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $customerData = $this->getTestFixture();
        /** @var Session $customerSession */
        $this->customerSession = $this->_objectManager->get(CustomerSession::class);
        $this->customerSession->setCustomerDataAsLoggedIn($customerData);
        $this->jsonSerializer = $this->_objectManager->get(Json::class);
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        $this->customerSession->logout();
        parent::tearDown();
    }

    /**
     * Test Remove Reward points
     *
     * @magentoConfigFixture current_store magento_reward/general/is_enabled  1
     * @return void
     */
    public function testExecute(): void
    {
        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);
        $this->dispatch('reward/cart/remove');
        $this->assertSessionMessages($this->equalTo([(string)__('You removed the reward points from this order.')]));
    }

    /**
     * Test GET request returns 404
     */
    public function test404NotFound(): void
    {
        $this->getRequest()->setMethod(HttpRequest::METHOD_GET);
        $this->dispatch('reward/cart/remove');
        $this->assert404NotFound();
    }

    /**
     * Test with reward points disabled
     *
     * @return void
     */
    public function testExecuteWithRewardPointsDisabled(): void
    {
        $quote = $this->_objectManager->get(Session::class)->getQuote();
        $quote->setUseRewardPoints(false);
        $quote->save();
        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);
        $this->dispatch('reward/cart/remove');
        $this->assertSessionMessages($this->equalTo([(string)__('Reward points will not be used in this order.')]));
    }

    /**
     * Test remove Reward Points with Ajax enabled.
     *
     * @dataProvider ajaxExecuteDataProvider
     * @param bool $useRewardPoints
     * @param bool $errors
     * @param string $message
     * @return void
     */
    public function testAjaxExecute(bool $useRewardPoints, bool $errors, string $message): void
    {
        $expected = [
            'errors' => $errors,
            'message' => $message,
        ];

        $quote = $this->_objectManager->get(Session::class)->getQuote();
        $quote->setUseRewardPoints($useRewardPoints)->save();
        $this->getRequest()->setMethod(HttpRequest::METHOD_POST)->setParam('isAjax', true);
        $this->dispatch('reward/cart/remove');
        $this->assertSessionMessages($this->isEmpty());
        $responseBody = $this->jsonSerializer->unserialize($this->getResponse()->getBody());
        $this->assertEquals($expected, $responseBody);
    }

    /**
     * DataProvider for testAjaxExecute()
     *
     * @return array
     */
    public function ajaxExecuteDataProvider(): array
    {
        return [
            'execute_with_enabled_reward_points' => [true, false, 'You removed the reward points from this order.'],
            'execute_with_disabled_reward_points' => [false, true, 'Reward points will not be used in this order.'],
        ];
    }

    /**
     * Gets Test Fixture.
     *
     * @throws NoSuchEntityException If customer with the specified email does not exist.
     * @throws LocalizedException
     * @return CustomerData
     */
    private function getTestFixture(): CustomerData
    {
        /** @var CustomerRepositoryInterface $customerRepository */
        $customerRepository = $this->_objectManager->get(CustomerRepositoryInterface::class);
        return $customerRepository->get('john_smith@company.com');
    }
}
