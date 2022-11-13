<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\GiftCardAccount\Model\UsageAttempt;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\GiftCardAccount\Api\Exception\TooManyAttemptsException;
use Magento\GiftCardAccount\Model\Spi\UsageAttemptFactoryInterface;
use Magento\GiftCardAccount\Model\Spi\UsageAttemptsManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Request\Http as HttpRequest;

class UsageAttemptsManagerTest extends TestCase
{
    /**
     * @var UsageAttemptsManagerInterface
     */
    private $manager;

    /**
     * @var UsageAttemptFactoryInterface
     */
    private $factory;

    /**
     * @var CustomerSession
     */
    private $loggedInManager;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        /** @var ObjectManager $objectManager */
        $objectManager = Bootstrap::getObjectManager();
        /** @var HttpRequest $request */
        $request = $objectManager->get(RequestInterface::class);
        $request->getServer()->set('REMOTE_ADDR', '127.0.0.1');
        $objectManager->removeSharedInstance(RemoteAddress::class);

        $this->manager = $objectManager->get(UsageAttemptsManagerInterface::class);
        if (!($this->manager instanceof UsageAttemptsManager)) {
            $this->markTestSkipped(
                'The test is for ' .UsageAttemptsManager::class
                .' implementation'
            );
        }
        $this->factory = $objectManager->get(
            UsageAttemptFactoryInterface::class
        );
        $this->loggedInManager = $objectManager->get(CustomerSession::class);
        $this->customerRepository = $objectManager->get(
            CustomerRepositoryInterface::class
        );
    }

    /**
     * @inheritDoc
     */
    protected function tearDown(): void
    {
        $this->loggedInManager->logout();
        $this->loggedInManager->clearStorage();
    }

    /**
     * @param int $id
     * @return void
     */
    private function loginCustomer(int $id): void
    {
        $success = $this->loggedInManager->loginById($id);
        if (!$success) {
            throw new \RuntimeException('Failed to login customer');
        }
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoDataFixture Magento/Customer/_files/customer.php
     *
     * @magentoConfigFixture default_store customer/captcha/enable 1
     */
    public function testCounterDisabled()
    {
        $this->manager->attempt($this->factory->create('fakeCode'));
        $this->loginCustomer(1);
        $this->manager->attempt($attempt = $this->factory->create('fakeCode'));
        //Checking that factory actually provides attempts
        //with current customer ID.
        $this->assertEquals(1, $attempt->getCustomerId());
    }

    /**
     * @magentoDbIsolation enabled
     *
     * @magentoConfigFixture default_store customer/captcha/enable 1
     * @magentoConfigFixture default_store customer/captcha/forms user_forgotpassword,user_login,gift_code_request
     * @magentoConfigFixture default_store customer/captcha/failed_attempts_login 3
     * @magentoConfigFixture default_store customer/captcha/failed_attempts_ip 5
     *
     * @magentoDataFixture Magento/Customer/_files/customer.php
     */
    public function testUnderLimit()
    {
        $this->manager->attempt($this->factory->create('fakeCode'));
        $this->manager->attempt($this->factory->create('fakeCode'));

        $this->loginCustomer(1);
        $this->manager->attempt($this->factory->create('fakeCode'));
        $this->manager->attempt($this->factory->create('fakeCode'));
    }

    /**
     * @magentoDbIsolation enabled
     *
     * @magentoConfigFixture default_store customer/captcha/enable 1
     * @magentoConfigFixture default_store customer/captcha/forms user_forgotpassword,user_login,gift_code_request
     * @magentoConfigFixture default_store customer/captcha/failed_attempts_login 10
     * @magentoConfigFixture default_store customer/captcha/failed_attempts_ip 2
     *
     */
    public function testAboveLimitNotLoggedIn()
    {
        $this->expectException(\Magento\GiftCardAccount\Api\Exception\TooManyAttemptsException::class);

        try {
            $this->manager->attempt($this->factory->create('fakeCode'));
            $this->manager->attempt($this->factory->create('fakeCode'));
        } catch (TooManyAttemptsException $exception) {
            $this->fail('Attempt denied before reaching the limit');
        }
        $this->manager->attempt($this->factory->create('fakeCode'));
    }

    /**
     * @magentoDbIsolation enabled
     *
     * @magentoConfigFixture default_store customer/captcha/enable 1
     * @magentoConfigFixture default_store customer/captcha/forms user_forgotpassword,user_login,gift_code_request
     * @magentoConfigFixture default_store customer/captcha/failed_attempts_login 2
     * @magentoConfigFixture default_store customer/captcha/failed_attempts_ip 10
     *
     * @magentoDataFixture Magento/Customer/_files/customer.php
     *
     */
    public function testAboveLimitLoggedIn()
    {
        $this->expectException(\Magento\GiftCardAccount\Api\Exception\TooManyAttemptsException::class);

        try {
            $this->loginCustomer(1);
            $this->manager->attempt($this->factory->create('fakeCode'));
            $this->manager->attempt($this->factory->create('fakeCode'));
        } catch (TooManyAttemptsException $exception) {
            $this->fail('Attempt denied before reaching the limit');
        }
        $this->manager->attempt($this->factory->create('fakeCode'));
    }

    /**
     * @magentoDbIsolation enabled
     *
     * @magentoConfigFixture default_store customer/captcha/enable 1
     * @magentoConfigFixture default_store customer/captcha/forms user_forgotpassword,user_login,gift_code_request
     * @magentoConfigFixture default_store customer/captcha/failed_attempts_login 10
     * @magentoConfigFixture default_store customer/captcha/failed_attempts_ip 10
     * @magentoConfigFixture default_store customer/captcha/mode always
     *
     * @magentoDataFixture Magento/Customer/_files/customer.php
     *
     */
    public function testCustomerNotAllowedWithoutCode()
    {
        $this->expectException(\Magento\GiftCardAccount\Api\Exception\TooManyAttemptsException::class);

        $this->loginCustomer(1);
        $this->manager->attempt($this->factory->create('fakeCode'));
    }

    /**
     * @magentoDbIsolation enabled
     *
     * @magentoConfigFixture default_store customer/captcha/enable 1
     * @magentoConfigFixture default_store customer/captcha/forms user_forgotpassword,user_login,gift_code_request
     * @magentoConfigFixture default_store customer/captcha/failed_attempts_login 10
     * @magentoConfigFixture default_store customer/captcha/failed_attempts_ip 10
     * @magentoConfigFixture default_store customer/captcha/mode always
     *
     */
    public function testGuestNotAllowedWithoutCode()
    {
        $this->expectException(\Magento\GiftCardAccount\Api\Exception\TooManyAttemptsException::class);

        $this->manager->attempt($this->factory->create('fakeCode'));
    }

    /**
     * @magentoDbIsolation enabled
     *
     * @magentoConfigFixture default_store customer/captcha/enable 1
     * @magentoConfigFixture default_store customer/captcha/forms user_forgotpassword,user_login,gift_code_request
     * @magentoConfigFixture default_store customer/captcha/failed_attempts_login 2
     * @magentoConfigFixture default_store customer/captcha/failed_attempts_ip 10
     *
     * @magentoDataFixture Magento/GiftCardAccount/_files/giftcardaccount.php
     * @magentoDataFixture Magento/Customer/_files/customer.php
     *
     */
    public function testLoggingOnlyInvalidCodes()
    {
        $this->expectException(\Magento\GiftCardAccount\Api\Exception\TooManyAttemptsException::class);

        try {
            $this->loginCustomer(1);
            $this->manager->attempt(
                $this->factory->create('giftcardaccount_fixture')
            );
            $this->manager->attempt(
                $this->factory->create('giftcardaccount_fixture')
            );
            $this->manager->attempt($this->factory->create('fakeCode'));
            $this->manager->attempt($this->factory->create('fakeCode'));
        } catch (TooManyAttemptsException $exception) {
            $this->fail('Attempts are logged for existing codes');
        }
        $this->manager->attempt($this->factory->create('fakeCode'));
    }
}
