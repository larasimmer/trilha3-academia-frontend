<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Rma\Helper;

use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Class to test RMA Helper
 */
class DataTest extends TestCase
{
    /**
     * @var Data
     */
    private $helper;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->helper = $objectManager->create(Data::class);
    }

    /**
     * Test to retrieve allowed carriers for RMA
     *
     * @magentoConfigFixture current_store carriers/dhl/active_rma 0
     * @magentoConfigFixture current_store carriers/ups/active_rma 0
     * @magentoConfigFixture current_store carriers/usps/active_rma 1
     * @magentoConfigFixture current_store carriers/fedex/active_rma 1
     */
    public function testGetAllowedShippingCarriers()
    {
        $allowedCarriers = $this->helper->getAllowedShippingCarriers();

        $this->assertArrayNotHasKey(
            'dhl',
            $allowedCarriers,
            'The DHL carrier must be absent in RMA carrier list'
        );
        $this->assertArrayNotHasKey(
            'ups',
            $allowedCarriers,
            'The UPS carrier must be absent in RMA carrier list'
        );
        $this->assertArrayHasKey(
            'usps',
            $allowedCarriers,
            'The USPS carrier must be presented in RMA carrier list'
        );
        $this->assertArrayHasKey(
            'fedex',
            $allowedCarriers,
            'The FedEx carrier must be presented in RMA carrier list'
        );
    }
}
