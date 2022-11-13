<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Logging\Model;

use Magento\TestFramework\Helper\Bootstrap;

/**
 * @magentoAppArea adminhtml
 */
class EventTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Event
     */
    private $object;

    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->object = $objectManager->create(Event::class);
    }

    /**
     * @dataProvider ipDataProvider
     */
    public function testBeforeSaveIpCanBeReturnedAsLongFormat($testIPAddress, $expectedValueBeforeSave)
    {
        $this->object->setId(1);
        $this->object->setData([
            'ip' => $testIPAddress,
            'x_forwarded_ip' => $testIPAddress
        ]);

        $this->object->beforeSave();

        $this->assertEquals($expectedValueBeforeSave, $this->object->getIp());
        $this->assertEquals($expectedValueBeforeSave, $this->object->getXForwardedIp());
    }

    /**
     * @return string[]
     */
    public function ipDataProvider(): array
    {
        return [
            ['127.0.0.1', ip2long('127.0.0.1')],
            ['2001:0db8:85a3:0000:0000:8a2e:0370:7334', 0],
            [ip2long('127.0.0.1'), ip2long('127.0.0.1')]
        ];
    }

    /**
     * @dataProvider testBeforeSaveIPv6TextRepresentationInAdditionalInfoDataProvider
     * @param $eventData
     * @param $expectedInfo
     */
    public function testBeforeSaveIPv6TextRepresentationInAdditionalInfo($eventData, $expectedInfo)
    {
        $expectedInfo = json_encode($expectedInfo);

        $this->object->setId(1);
        $this->object->setData($eventData);
        $this->object->setInfo(['key' => 'value']);

        $this->object->beforeSave();

        $this->assertEquals($expectedInfo, $this->object->getInfo());
    }

    /**
     * @return string[]
     */
    public function testBeforeSaveIPv6TextRepresentationInAdditionalInfoDataProvider(): array
    {
        return [
            [['ip' => '127.0.0.1'], ['general' => ['key' => 'value']]],
            [['ip' => '2001:0db8:85a3:0000:0000:8a2e:0370:7334'], [
                'general' => ['key' => 'value'],
                'additional' => [
                    'ip_v6' => '2001:0db8:85a3:0000:0000:8a2e:0370:7334'
                ]
            ]],
            [['x_forwarded_ip' => '2001:0db8:85a3:0000:0000:8a2e:0370:7334'], [
                'general' => ['key' => 'value'],
                'additional' => [
                    'x_forwarded_ip_v6' => '2001:0db8:85a3:0000:0000:8a2e:0370:7334'
                ]
            ]],
            [
                [
                    'ip' => '2001:0db8:85a3:0000:0000:8a2e:0370:7334',
                    'x_forwarded_ip' => '2001:0db8:85a3:0000:0000:8a2e:0370:7334'
                ],
                [
                    'general' => ['key' => 'value'],
                    'additional' => [
                        'ip_v6' => '2001:0db8:85a3:0000:0000:8a2e:0370:7334',
                        'x_forwarded_ip_v6' => '2001:0db8:85a3:0000:0000:8a2e:0370:7334'
                    ]
                ]
            ],
            [['ip' => ip2long('127.0.0.1')], ['general' => ['key' => 'value']]]
        ];
    }

    public function testAfterLoadIPv4ConvertedToTextFormat()
    {
        $this->object->setId(1);
        $this->object->setData([
            'ip' => '127.0.0.1',
            'x_forwarded_ip' => '127.0.0.1'
        ]);

        $this->object->afterLoad();

        $this->assertEquals('127.0.0.1', $this->object->getIp());
        $this->assertEquals('127.0.0.1', $this->object->getXForwardedIp());
    }

    public function testAfterLoadIPv6MovedFromAdditionalInfo()
    {
        $this->object->setId(1);
        $this->object->setData([
            'ip' => '2001:0db8:85a3:0000:0000:8a2e:0370:7334',
            'x_forwarded_ip' => '2001:0db8:85a3:0000:0000:8a2e:0370:7334'
        ]);

        $this->assertEquals('2001:0db8:85a3:0000:0000:8a2e:0370:7334', $this->object->getIp());
        $this->assertEquals('2001:0db8:85a3:0000:0000:8a2e:0370:7334', $this->object->getXForwardedIp());

        $this->object->beforeSave();
        $this->assertEquals(0, $this->object->getIp());
        $this->assertEquals(0, $this->object->getXForwardedIp());

        $this->object->afterLoad();
        $this->assertEquals('2001:0db8:85a3:0000:0000:8a2e:0370:7334', $this->object->getIp());
        $this->assertEquals('2001:0db8:85a3:0000:0000:8a2e:0370:7334', $this->object->getXForwardedIp());
        $this->assertFalse(
            isset($this->object->getInfo()['general']['additional']['ip_v6'])
        );
        $this->assertFalse(
            isset($this->object->getInfo()['general']['additional']['x_forwarded_ip_v6'])
        );
    }
}
