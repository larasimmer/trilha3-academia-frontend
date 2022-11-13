<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdvancedCheckout\Controller\Cart;

use Magento\TestFramework\Request;
use Magento\TestFramework\TestCase\AbstractController;

/**
 * Test for \Magento\AdvancedCheckout\Controller\Cart\RemoveAllFailed class.
 */
class RemoveAllFailedTest extends AbstractController
{
    /**
     * Check that controller applied only POST requests.
     */
    public function testExecuteWithNonPostRequest()
    {
        $this->getRequest()->setMethod(Request::METHOD_GET);
        $this->dispatch('checkout/cart/removeAllFailed/');

        $this->assert404NotFound();
    }
}
