<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogEvent\Controller\Adminhtml\Catalog;

use DateTime;
use DateTimeZone;
use Exception;
use IntlDateFormatter;
use Magento\CatalogEvent\Model\Event as CatalogEventModel;
use Magento\Framework\Message\MessageInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\TestFramework\Helper\Xpath;
use Magento\TestFramework\TestCase\AbstractBackendController;

/**
 * @magentoAppArea adminhtml
 */
class EventTest extends AbstractBackendController
{
    public function testEditActionSingleStore()
    {
        $this->dispatch('backend/admin/catalog_event/new');
        $body = $this->getResponse()->getBody();
        $this->assertStringNotContainsString('name="store_switcher"', $body);
    }

    /**
     * @magentoDataFixture Magento/Store/_files/core_fixturestore.php
     * @magentoDataFixture Magento/CatalogEvent/_files/events.php
     * @magentoConfigFixture current_store catalog/frontend/flat_catalog_product 1
     */
    public function testEditActionMultipleStore()
    {
        /** @var $event CatalogEventModel */
        $event = $this->_objectManager->create(
            CatalogEventModel::class
        );
        $event->load(CatalogEventModel::DISPLAY_CATEGORY_PAGE, 'display_state');
        $this->dispatch('backend/admin/catalog_event/edit/id/' . $event->getId());
        $body = $this->getResponse()->getBody();

        $this->assertStringContainsString('name="store_switcher"', $body);
        $event->delete();
        unset($event);
    }

    /**
     * @magentoDataFixture Magento/Catalog/_files/categories.php
     * @magentoConfigFixture default/general/locale/timezone America/Chicago
     */
    public function testDatesShouldBeSavedInUTCTimezone()
    {
        $utcDateStart = (new DateTime('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:00');
        $utcDateEnd = (new DateTime('+1 day', new DateTimeZone('UTC')))->format('Y-m-d H:i:00');
        $formData = [
            'catalogevent' => [
                'display_state' => 1,
                'date_start' => $this->formatDateToLocalString($utcDateStart, 'America/Chicago'),
                'date_end' => $this->formatDateToLocalString($utcDateEnd, 'America/Chicago'),
                'sort_order' => 1,
            ],
        ];

        $this->getRequest()
            ->setParams(
                [
                    'category_id' => 3,
                ]
            )
            ->setMethod('POST')
            ->setPostValue($formData);
        $this->dispatch('backend/admin/catalog_event/save');
        $this->assertRedirect($this->stringContains('backend/admin/catalog_event/index'));
        $this->assertSessionMessages(
            $this->equalTo(['You saved the event.']),
            MessageInterface::TYPE_SUCCESS
        );
        /** @var $event CatalogEventModel */
        $event = $this->_objectManager->create(CatalogEventModel::class);
        $event->load(3, 'category_id');
        $this->assertNotNull($event->getId());
        $this->assertEquals($utcDateStart, $event->getDateStart());
        $this->assertEquals($utcDateEnd, $event->getDateEnd());
        $event->delete();
    }

    /**
     * @magentoDataFixture Magento/Catalog/_files/categories.php
     * @magentoDataFixture Magento/CatalogEvent/_files/category_event.php
     * @magentoConfigFixture default/general/locale/timezone America/Chicago
     */
    public function testDatesShouldBeDisplayedInLocalTimezone()
    {
        /** @var $event CatalogEventModel */
        $event = $this->_objectManager->create(CatalogEventModel::class);
        $event->load(3, 'category_id');
        $this->assertNotNull($event->getId());
        $expectedDateStart = $this->formatDateToLocalString($event->getDateStart(), 'America/Chicago');
        $expectedDateEnd = $this->formatDateToLocalString($event->getDateEnd(), 'America/Chicago');
        $this->dispatch('backend/admin/catalog_event/edit/id/' . $event->getId());
        $body = $this->getResponse()->getBody();
        $this->assertHtmlInputValue($expectedDateStart, 'event_edit_date_start', $body);
        $this->assertHtmlInputValue($expectedDateEnd, 'event_edit_date_end', $body);
    }

    /**
     * @param string $utcDate
     * @param string $localTimezone
     * @return string
     * @throws Exception
     */
    private function formatDateToLocalString(string $utcDate, string $localTimezone): string
    {
        $locale = $this->_objectManager->create(TimezoneInterface::class);
        $localDate = new DateTime($utcDate, new DateTimeZone('UTC'));
        $localDate->setTimezone(new DateTimeZone($localTimezone));
        return $locale->formatDateTime(
            $localDate,
            null,
            null,
            null,
            $localDate->getTimezone(),
            $locale->getDateTimeFormat(IntlDateFormatter::SHORT)
        );
    }

    /**
     * @param string $expected
     * @param string $id
     * @param string $html
     */
    private function assertHtmlInputValue(string $expected, string $id, string $html): void
    {
        $xpath = '//input[@id="' . $id . '"]';
        $elements = Xpath::getElementsForXpath($xpath, $html);
        $this->assertGreaterThan(0, $elements->count(), "Cannot find element '$xpath' in the HTML:\n $html");
        $this->assertEquals($expected, $elements->item(0)->getAttribute('value'));
    }
}
