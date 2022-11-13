<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Controller\Tracking;

use Magento\Framework\App\Request\Http;
use Magento\Framework\Url\EncoderInterface;
use Magento\Rma\Model\Rma;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\AbstractController;

/**
 * Class to test load Rma Packages
 */
class PackageTest extends AbstractController
{
    /**
     * @var EncoderInterface
     */
    private $urlEncoder;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->urlEncoder = Bootstrap::getObjectManager()->get(EncoderInterface::class);
    }

    /**
     * Test success response code
     *
     * @magentoConfigFixture current_store sales/magento_rma/enabled 1
     * @magentoDataFixture Magento/Rma/_files/rma_with_package.php
     */
    public function testResponseCode()
    {
        $this->getRequest()->setMethod(Http::METHOD_GET);

        /** @var $rma Rma */
        $rma = Bootstrap::getObjectManager()->create(Rma::class);
        $rma->load(1, 'increment_id');
        $url = $this->getPackagePopupUrl((int)$rma->getId(), $rma->getProtectCode());
        $this->dispatch($url);

        $this->assertEquals(200, $this->getResponse()->getStatusCode(), 'Invalid response code');
    }

    /**
     * Tests to get response code 404
     *
     * @magentoConfigFixture current_store sales/magento_rma/enabled 1
     * @magentoDataFixture Magento/Rma/_files/rma_with_package.php
     */
    public function test404NotFound()
    {
        $this->getRequest()->setMethod(Http::METHOD_GET);

        /** @var $rma Rma */
        $rma = Bootstrap::getObjectManager()->create(Rma::class);
        $rma->load(1, 'increment_id');
        $url = $this->getPackagePopupUrl((int)$rma->getId(), 'invalid_protected_code');
        $this->dispatch($url);

        $this->assert404NotFound();
    }

    /**
     * Get shipping package popup url
     *
     * @param int $rmaId
     * @param string $protectCode
     * @return string
     */
    private function getPackagePopupUrl(int $rmaId, string $protectCode): string
    {
        $hash = $this->urlEncoder->encode("rma_id:{$rmaId}:{$protectCode}");

        return 'rma/tracking/package?hash=' . $hash;
    }
}
