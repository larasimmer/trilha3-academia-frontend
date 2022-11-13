<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\VisualMerchandiser\Controller\Adminhtml\Position;

use Magento\Framework\App\Request\Http as HttpRequest;

/**
 * Get controller test.
 *
 * @magentoAppArea adminhtml
 */
class GetTest extends \Magento\TestFramework\TestCase\AbstractBackendController
{
    /**
     * Checks get position response.
     *
     * @magentoDataFixture Magento/Catalog/_files/category.php
     */
    public function testGetActionSuccess()
    {
        $this->getRequest()
            ->setPostValue(['position_cache_key' => 'cache-key'])
            ->setMethod(HttpRequest::METHOD_POST);
        $this->dispatch('backend/merchandiser/position/get');
        $response = $this->getResponse();
        $this->assertEquals("Content-Type: application/json", $response->getHeader('Content-type')->toString());
        $this->assertEquals(200, $response->getStatusCode(), 'Invalid response code');
    }
}
