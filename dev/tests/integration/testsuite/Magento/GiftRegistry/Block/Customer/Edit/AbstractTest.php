<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

/**
 * Test class for \Magento\GiftRegistry\Block\Customer\Edit\AbstractEdit
 */
namespace Magento\GiftRegistry\Block\Customer\Edit;

use Magento\Framework\View\LayoutInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class AbstractTest extends TestCase
{
    /**
     * Stub class name
     */
    const STUB_CLASS = 'Magento_GiftRegistry_Block_Customer_Edit_AbstractEdit_Stub';

    /**
     * Verify format and value calendar HTML
     *
     * @magentoAppArea frontend
     * @param string $date
     * @param string $dateFormat
     * @param int $formatType
     * @param string $dateExpect
     * @dataProvider dataProviderGetCalendarDateHtml
     * @return void
     */
    public function testGetCalendarDateHtml(
        string $date,
        string $dateFormat,
        int $formatType,
        string $dateExpect
    ) {
        $this->getMockForAbstractClass(
            AbstractEdit::class,
            [],
            self::STUB_CLASS,
            false
        );
         /** @var AbstractEdit $block */
        $block = Bootstrap::getObjectManager()
            ->get(LayoutInterface::class)
            ->createBlock(self::STUB_CLASS);
        $html = $block->getCalendarDateHtml('date_name', 'date_id', $date, $formatType);
        $this->assertStringContainsString('dateFormat: "' . $dateFormat . '",', $html);
        $this->assertStringContainsString('value="' . $dateExpect . '"', $html);
    }

    /**
     * @return array
     */
    public function dataProviderGetCalendarDateHtml(): array
    {
        return [
            [
                'date' => '2021-02-01',
                'dateFormat' => 'M/d/yy',
                'formatType' => \IntlDateFormatter::SHORT,
                'dateExpect' => '2/1/21',
            ],
        ];
    }
}
