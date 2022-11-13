<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Webapi\Controller;

/**
 * Test for Magento\Webapi\Controller\Soap class.
 */
class SoapEnterpriseTest extends SoapTest
{
    /**
     * Check wsdl available methods.
     *
     * @param array $decodedWsdl
     *
     * @return void
     */
    protected function assertWsdlServices(array $decodedWsdl): void
    {
        parent::assertWsdlServices($decodedWsdl);
        $this->assertArrayHasKey('giftCardAccountGuestGiftCardAccountManagementV1', $decodedWsdl);
    }
}
