<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftRegistry\Controller\Index;

use Magento\TestFramework\Request;
use Magento\TestFramework\TestCase\AbstractController;

/**
 * Test for \Magento\GiftRegistry\Controller\Index\Cart class.
 */
class CartTest extends AbstractController
{
    /**
     * Check that controller applied only POST requests.
     */
    public function testExecuteWithNonPostRequest()
    {
        $this->getRequest()->setMethod(Request::METHOD_GET);
        $this->dispatch('giftregistry/index/cart/');

        $this->assert404NotFound();
    }
}
