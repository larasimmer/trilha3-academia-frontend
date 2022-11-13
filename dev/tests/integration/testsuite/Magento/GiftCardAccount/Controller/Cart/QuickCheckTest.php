<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCardAccount\Controller\Cart;

use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Registry;
use Magento\GiftCardAccount\Model\Giftcardaccount;
use Magento\TestFramework\Request;
use Magento\TestFramework\TestCase\AbstractController;

/**
 * Test for quick check controller.
 *
 * @see \Magento\GiftCardAccount\Controller\Cart\QuickCheck
 *
 * @magentoAppArea frontend
 * @magentoDbIsolation enabled
 */
class QuickCheckTest extends AbstractController
{
    /** @var Registry */
    private $registry;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->registry = $this->_objectManager->get(Registry::class);
    }

    /**
     * Check that controller applied only POST requests.
     *
     * @return void
     */
    public function testExecuteWithNonPostRequest(): void
    {
        $this->getRequest()->setParam('isAjax', true);
        $this->getRequest()->getHeaders()->addHeaderLine('X_REQUESTED_WITH', 'XMLHttpRequest');
        $this->getRequest()->setMethod(Request::METHOD_GET);
        $this->dispatch('/giftcard/cart/quickCheck/');
        $this->assert404NotFound();
    }

    /**
     * @return void
     */
    public function testDispatchWithInvalidRequest(): void
    {
        $this->getRequest()->setMethod(Request::METHOD_POST);
        $this->dispatch('/giftcard/cart/quickCheck/');
        $this->assert404NotFound();
    }

    /**
     * @magentoDataFixture Magento/GiftCardAccount/_files/giftcardaccount.php
     *
     * @return void
     */
    public function testQuickCheckGiftCardAccount(): void
    {
        $giftCardCode = 'giftcardaccount_fixture';
        $params = ['giftcard_code' => $giftCardCode, 'isAjax' => true];
        $this->dispatchQuickCheckRequest($params);
        $account = $this->registry->registry('current_giftcardaccount');
        $this->assertNotNull($account);
        $this->assertInstanceOf(Giftcardaccount::class, $account);
        $this->assertEquals($giftCardCode, $account->getCode());
    }

    /**
     * @return void
     */
    public function testQuickCheckNotExistingGiftCardAccount(): void
    {
        $params = ['giftcard_code' => 'not_existing_code', 'isAjax' => true];
        $this->dispatchQuickCheckRequest($params);
        $this->assertNull($this->registry->registry('current_giftcardaccount'));
    }

    /**
     * Dispatch quick check request.
     *
     * @param array $params
     * @return void
     */
    private function dispatchQuickCheckRequest(array $params): void
    {
        $this->getRequest()->setParams($params);
        $this->getRequest()->getHeaders()->addHeaderLine('X_REQUESTED_WITH', 'XMLHttpRequest');
        $this->getRequest()->setMethod(Request::METHOD_POST);
        $this->dispatch('/giftcard/cart/quickCheck/');
    }
}
