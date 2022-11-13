<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Test positions of the CustomerBalance total collectors as compared to other collectors
 */
namespace Magento\CustomerBalance\Model;

class CollectorPositionsTest extends \Magento\Sales\Model\AbstractCollectorPositionsTest
{
    /**
     * @return array
     */
    public function collectorPositionDataProvider()
    {
        return [
            'invoice collectors' => [
                'customerbalance',
                'invoice',
                [],
                ['weee'],
            ]
        ];
    }
}
