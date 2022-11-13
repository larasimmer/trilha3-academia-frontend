<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Logging\Model;

use Magento\TestFramework\Helper\Bootstrap;
use Magento\UrlRewrite\Model\UrlFinderInterface;
use Magento\UrlRewrite\Service\V1\Data\UrlRewrite;

/**
 * Test Enterprise logging processor
 *
 * @magentoAppArea adminhtml
 */
class ProcessorTest extends \Magento\TestFramework\TestCase\AbstractController
{
    /**
     * Test that configured admin actions are properly logged
     *
     * @param string $url
     * @param string $action
     * @param array $post
     * @param string $method
     */
    public function loggingProcessorLogsAction($url, $action, array $post = [], $method = 'POST')
    {
        Bootstrap::getInstance()->loadArea(\Magento\Backend\App\Area\FrontNameResolver::AREA_CODE);
        $collection = Bootstrap::getObjectManager()->create(\Magento\Logging\Model\Event::class)->getCollection();
        $eventCountBefore = count($collection);
        $objectManager = Bootstrap::getObjectManager();
        $objectManager->get(\Magento\Backend\Model\UrlInterface::class)->turnOffSecretKey();
        $auth = $objectManager->get(\Magento\Backend\Model\Auth::class);
        $auth->login(
            \Magento\TestFramework\Bootstrap::ADMIN_NAME,
            \Magento\TestFramework\Bootstrap::ADMIN_PASSWORD
        );

        $this->getRequest()->setMethod($method);
        $this->getRequest()->setPostValue(
            array_merge(
                $post,
                [
                    'form_key' => $objectManager->get(\Magento\Framework\Data\Form\FormKey::class)->getFormKey()
                ]
            )
        );
        $this->dispatch($url);
        $collection = $objectManager->create(\Magento\Logging\Model\Event::class)->getCollection();

        // Number 2 means we have "login" event logged first and then the tested one.
        $eventCountAfter = $eventCountBefore + 2;
        $this->assertEquals($eventCountAfter, count($collection), $action . ' event wasn\'t logged');
        $lastEvent = $collection->getLastItem();
        $this->assertEquals($action, $lastEvent['action']);
    }

    /**
     * Test that Url Rewrite In-line editing is logging
     *
     * @magentoDataFixture Magento/UrlRewrite/_files/url_rewrites.php
     */
    public function testLoggingProcessorLogsActionWithUrlRewrites()
    {
        /** @var UrlFinderInterface $urlFinder */
        $urlFinder = Bootstrap::getObjectManager()->create(UrlFinderInterface::class);
        $data = [
            UrlRewrite::REQUEST_PATH => 'строка',
        ];
        $urlRewrite = $urlFinder->findOneByData($data);
        $this->loggingProcessorLogsAction(
            'backend/admin/url_rewrite/inlineEdit/?isAjax=true',
            'save',
            [
                'items' => [
                    $urlRewrite->getUrlRewriteId() => [
                        'request_path' => $urlRewrite->getUrlRewriteId() . '23',
                        'redirect_type' => 0,
                        'url_rewrite_id' => $urlRewrite->getUrlRewriteId()
                    ]
                ]
            ]
        );
    }

    /**
     * Test that configured admin actions are properly logged
     *
     * @param string $url
     * @param string $action
     * @param array $post
     * @param string $method
     * @dataProvider adminActionDataProvider
     * @magentoDataFixture Magento/Logging/_files/user_and_role.php
     * @magentoDbIsolation enabled
     */
    public function testLoggingProcessorLogsAction($url, $action, array $post = [], $method = 'POST')
    {
        $this->loggingProcessorLogsAction($url, $action, $post, $method);
    }

    /**
     * Test that configured shipment actions are properly logged
     *
     * @param string $url
     * @param string $action
     * @param array $post
     * @magentoDataFixture Magento/Logging/_files/order.php
     * @magentoDataFixture Magento/Logging/_files/user_and_role.php
     * @magentoDbIsolation enabled
     */
    public function testLoggingProcessorLogsActionShipping()
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = Bootstrap::getObjectManager()->create(\Magento\Sales\Model\Order::class);
        $order->loadByIncrementId('100000001');
        $shipmentId = 1;
        foreach ($order->getShipmentsCollection() as $item) {
            $shipmentId = $item->getId();
        }
        $url = 'backend/admin/order_shipment/view/shipment_id/' . $shipmentId . '/order_id/' . $order->getId();
        $action = 'view';
        $this->loggingProcessorLogsAction($url, $action, [], 'GET');
    }

    /**
     * @return array
     */
    public function adminActionDataProvider()
    {
        return [
            ['backend/admin/user/edit/user_id/2', 'view'],
            [
                'backend/admin/user/save',
                'save',
                [
                    'firstname' => 'firstname',
                    'lastname' => 'lastname',
                    'email' => 'newuniqueuser@example.com',
                    'roles[]' => 1,
                    'username' => 'newuniqueuser',
                    'password' => 'password123'
                ]
            ],
            ['backend/admin/user/delete/user_id/2', 'delete'],
            ['backend/admin/user_role/editrole/rid/2', 'view'],
            [
                'backend/admin/user_role/saverole',
                'save',
                [
                    'rolename' => 'newrole2',
                    'gws_is_all' => '1',
                    'current_password' => \Magento\TestFramework\Bootstrap::ADMIN_PASSWORD
                ]
            ],
            ['backend/admin/user_role/delete/rid/2', 'delete'],
            ['backend/tax/tax/ajaxDelete', 'delete', ['class_id' => 2, 'isAjax' => true]],
            [
                'backend/tax/tax/ajaxSave',
                'save',
                ['class_id' => null, 'class_name' => 'test', 'class_type' => 'PRODUCT', 'isAjax' => true]
            ],
            [
                'backend/sales/order_status/save/status/teststatus',
                'save',
                ['status' => 'teststatus', 'label' => 'teststatus', 'store_labels' => [1 => '']]
            ],
            ['backend/cms/page/edit/page_id/1', 'view', [], 'GET'],
            [
                'backend/cms/page/save/back/edit',
                'save',
                [
                    'title' => 'test',
                    'is_active' => 1,
                    'page_layout' => 'cms-full-width',
                    'layout_update_selected' =>'_no_update_',
                    'store_id' => [0],
                    'content' => '<div>test</div>',
                    'website_root' => 0
                ]
            ],
        ];
    }
}
