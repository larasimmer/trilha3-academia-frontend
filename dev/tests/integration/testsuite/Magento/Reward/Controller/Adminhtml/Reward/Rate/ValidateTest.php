<?php
/***
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Reward\Controller\Adminhtml\Reward\Rate;

use Magento\Customer\Model\GroupManagement;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Reward\Model\Reward\Rate;
use Magento\TestFramework\TestCase\AbstractBackendController;

/**
 * Rate validation tests
 *
 * @magentoAppArea adminhtml
 *
 * @see \Magento\Reward\Controller\Adminhtml\Reward\Rate\Validate
 */
class ValidateTest extends AbstractBackendController
{
    /** @var SerializerInterface */
    private $json;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->json = $this->_objectManager->get(SerializerInterface::class);
    }

    /**
     * @dataProvider missedParameterProvider
     *
     * @param $postData
     * @return void
     */
    public function testMissedRateParams(array $postData): void
    {
        $this->dispatchWithData($postData);
        $content = $this->json->unserialize($this->getResponse()->getContent());
        $this->assertNotNull($content['html_message']);
        $this->assertStringContainsString(
            (string)__('Please enter all rate information.'),
            strip_tags($content['html_message'])
        );
    }

    /**
     * @return array
     */
    public function missedParameterProvider(): array
    {
        return [
            'missed_customer_group_id' => [
                'rate' => [
                    'website_id' => '0',
                    'direction' => Rate::RATE_EXCHANGE_DIRECTION_TO_CURRENCY,
                    'value' => '1',
                    'equal_value' => '1',
                ],
            ],
            'missed_website_id' => [
                'rate' => [
                    'customer_group_id' => GroupManagement::NOT_LOGGED_IN_ID,
                    'direction' => Rate::RATE_EXCHANGE_DIRECTION_TO_CURRENCY,
                    'value' => '1',
                    'equal_value' => '1',
                ],
            ],
            'missed_direction' => [
                'rate' => [
                    'website_id' => '0',
                    'customer_group_id' => GroupManagement::NOT_LOGGED_IN_ID,
                    'value' => '1',
                    'equal_value' => '1',
                ],
            ],
            'missed_value' => [
                'rate' => [
                    'website_id' => '0',
                    'customer_group_id' => GroupManagement::NOT_LOGGED_IN_ID,
                    'direction' => Rate::RATE_EXCHANGE_DIRECTION_TO_CURRENCY,
                    'equal_value' => '1',
                ],
            ],
            'missed_equal_value' => [
                'rate' => [
                    'website_id' => '0',
                    'customer_group_id' => GroupManagement::NOT_LOGGED_IN_ID,
                    'direction' => Rate::RATE_EXCHANGE_DIRECTION_TO_CURRENCY,
                    'value' => '1',
                ],
            ],
        ];
    }

    /**
     * @magentoDataFixture Magento/Reward/_files/rate.php
     *
     * @return void
     */
    public function testNotRequiredRate(): void
    {
        $postData = [
            'rate' => [
                'website_id' => '0',
                'customer_group_id' => GroupManagement::NOT_LOGGED_IN_ID,
                'direction' => Rate::RATE_EXCHANGE_DIRECTION_TO_CURRENCY,
                'value' => 100,
                'equal_value' => 1,
            ],
        ];
        $this->dispatchWithData($postData);
        $expectedMessage = (string)__(
            'Sorry, but a rate for the same website, '
            . 'customer group and direction or covering rate already exists.'
        );
        $content = $this->json->unserialize($this->getResponse()->getContent());
        $this->assertNotNull($content['html_message']);
        $this->assertEquals($expectedMessage, strip_tags($content['html_message']));
    }

    /**
     * @dataProvider incorrectParamsProvider
     *
     * @param array $postData
     * @param string $expectedMessage
     * @return void
     */
    public function testIncorrectParams(array $postData, string $expectedMessage): void
    {
        $this->dispatchWithData($postData);
        $content = $this->json->unserialize($this->getResponse()->getContent());
        $this->assertNotNull($content['html_message']);
        $this->assertEquals($expectedMessage, strip_tags($content['html_message']));
    }

    /**
     * @return array
     */
    public function incorrectParamsProvider(): array
    {
        return [
            'to_currency_negative_value' => [
                'post' => [
                    'rate' => [
                        'website_id' => '0',
                        'customer_group_id' => GroupManagement::NOT_LOGGED_IN_ID,
                        'direction' => Rate::RATE_EXCHANGE_DIRECTION_TO_CURRENCY,
                        'value' => -1,
                        'equal_value' => 1,
                    ],
                ],
                'error_message' => (string)__('Please enter a positive integer number in the left rate field.'),
            ],
            'to_currency_negative_equal_value' => [
                'post' => [
                    'rate' => [
                        'website_id' => '0',
                        'customer_group_id' => GroupManagement::NOT_LOGGED_IN_ID,
                        'direction' => Rate::RATE_EXCHANGE_DIRECTION_TO_CURRENCY,
                        'value' => 1,
                        'equal_value' => -1,
                    ],
                ],
                'error_message' => (string)__('Please enter a positive number in the right-side rate field.'),
            ],
            'to_points_neagtive_value' => [
                'post' => [
                    'rate' => [
                        'website_id' => '0',
                        'customer_group_id' => GroupManagement::NOT_LOGGED_IN_ID,
                        'direction' => Rate::RATE_EXCHANGE_DIRECTION_TO_POINTS,
                        'value' => -1,
                        'equal_value' => 1,
                    ],
                ],
                'error_message' => (string)__('Please enter a positive number in the left-side rate field.'),
            ],
            'to_points_negative_equal_value' => [
                'post' => [
                    'rate' => [
                        'website_id' => '0',
                        'customer_group_id' => GroupManagement::NOT_LOGGED_IN_ID,
                        'direction' => Rate::RATE_EXCHANGE_DIRECTION_TO_POINTS,
                        'value' => 1,
                        'equal_value' => -1,
                    ],
                ],
                'error_message' => (string)__('Please enter a positive integer number in the right-side rate field.'),
            ],
        ];
    }

    /**
     * Dispatch controller with data
     *
     * @param array $postData
     * @return void
     */
    private function dispatchWithData(array $postData): void
    {
        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);
        $this->getRequest()->setPostValue($postData);
        $this->dispatch('backend/admin/reward_rate/validate');
    }
}
