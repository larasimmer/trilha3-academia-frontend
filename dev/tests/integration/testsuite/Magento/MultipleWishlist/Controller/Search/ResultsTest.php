<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MultipleWishlist\Controller\Search;

use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Message\MessageInterface;
use Magento\TestFramework\TestCase\AbstractController;

/**
 * Tests for search wish list.
 *
 * @magentoDbIsolation enabled
 * @magentoAppArea frontend
 */
class ResultsTest extends AbstractController
{
    /**
     * @return void
     */
    public function testSearchWithInvalidEmail(): void
    {
        $this->performSearchWishListRequest(['email' => 'test@exa mple.com', 'search' => 'email']);
        $this->assertSessionMessages(
            $this->equalTo([(string)__('Please enter a valid email address.')]),
            MessageInterface::TYPE_NOTICE
        );
    }

    /**
     * @dataProvider searchWithIncorrectParamsDataProvider
     *
     * @param array $params
     * @return void
     */
    public function testSearchWithIncorrectParams(array $params): void
    {
        $this->performSearchWishListRequest($params);
        $this->assertSessionMessages(
            $this->equalTo([(string)__('Please reenter your search options.')]),
            MessageInterface::TYPE_ERROR
        );
    }

    /**
     * @return array
     */
    public function searchWithIncorrectParamsDataProvider(): array
    {
        return [
            [
                'params' => [],
            ],
            [
                'params' => ['email' => 'test@example.com'],
            ],
            [
                'params' => ['email' => 'test@example.com', 'search' => 'test'],
            ],
        ];
    }

    /**
     * Perform search wish list request.
     *
     * @param array $params
     * @return void
     */
    private function performSearchWishListRequest(array $params): void
    {
        $this->getRequest()->setParam('params', $params);
        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);
        $this->dispatch('wishlist/search/results');
    }
}
