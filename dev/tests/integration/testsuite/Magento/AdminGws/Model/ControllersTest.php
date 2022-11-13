<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminGws\Model;

use Magento\Framework\App\Request\Http;
use Magento\Framework\Registry;
use Magento\Store\Model\Group;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManager;
use Magento\Store\Model\Website;
use Magento\TestFramework\TestCase\AbstractController;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Test for Magento\AdminGws\Model\Controllers
 */
class ControllersTest extends AbstractController
{
    /**
     * @var Controllers
     */
    private $model;

    /**
     * @var Role|MockObject
     */
    private $roleMock;

    /**
     * @var Registry|MockObject
     */
    private $registryMock;

    /**
     * @var StoreManager|MockObject
     */
    private $storeManagerMock;

    /**
     * @var Http|MockObject
     */
    private $requestMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->roleMock = $this->createMock(Role::class);
        $this->registryMock = $this->createMock(Registry::class);
        $this->storeManagerMock = $this->createMock(StoreManager::class);
        $this->requestMock = $this->createMock(Http::class);

        $this->model = $this->_objectManager->create(
            Controllers::class,
            [
                'role' => $this->roleMock,
                'registry' => $this->registryMock,
                'storeManager' => $this->storeManagerMock,
                'request' => $this->requestMock
            ]
        );
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        $this->roleMock = null;
        $this->registryMock = null;
        $this->storeManagerMock = null;
        $this->requestMock = null;
        $this->model = null;
        parent::tearDown();
    }

    /**
     * User role has access to specific store view scope. No redirect should be expected in this case.
     *
     * @return void
     */
    public function testValidateSystemConfigValidStoreCodeWithStoreAccess(): void
    {
        $this->requestMock->expects($this->any())->method('getParam')->with('store')->willReturn(
            'testStore'
        );

        $storeMock = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId'])
            ->getMock();
        $storeMock->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $this->storeManagerMock->expects($this->any())
            ->method('getStore')
            ->willReturn($storeMock);

        $this->roleMock->expects($this->any())
            ->method('hasStoreAccess')
            ->willReturn(true);

        $this->model->validateSystemConfig();
    }

    /**
     * User role has access to specific website view scope. No redirect should be expected in this case.
     *
     * @return void
     */
    public function testValidateSystemConfigValidWebsiteCodeWithWebsiteAccess(): void
    {

        $this->requestMock->method('getParam')
            ->withConsecutive(['store'], ['website'])
            ->willReturnOnConsecutiveCalls(null, 'testWebsite');

        $websiteMock = $this->getMockBuilder(Website::class)->disableOriginalConstructor()
            ->onlyMethods(['getId'])
            ->getMock();
        $websiteMock->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $this->storeManagerMock->expects($this->any())
            ->method('getWebsite')
            ->willReturn($websiteMock);

        $this->roleMock->expects($this->any())
            ->method('hasWebsiteAccess')
            ->willReturn(true);

        $this->model->validateSystemConfig();
    }

    /**
     * User role has no access to specific store view scope or website. Redirect to first allowed website.
     *
     * @return void
     */
    public function testValidateSystemConfigRedirectToWebsite(): void
    {
        $this->requestMock->expects($this->any())->method('getParam')->willReturn(
            null
        );

        $websiteMock = $this->getMockBuilder(Website::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getCode'])
            ->getMock();
        $websiteMock->expects($this->any())
            ->method('getCode')
            ->willReturn('default');

        $storeMock = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getWebsite'])
            ->getMock();
        $storeMock->expects($this->any())
            ->method('getWebsite')
            ->willReturn($websiteMock);

        $this->storeManagerMock->expects($this->any())
            ->method('getDefaultStoreView')
            ->willReturn($storeMock);

        $this->roleMock->expects($this->any())
            ->method('getWebsiteIds')
            ->willReturn(true);

        $this->model->validateSystemConfig();
        $this->assertRedirect();
    }

    /**
     * User role has no access to specific store view scope or website. Redirect to first allowed store view.
     *
     * @return void
     */
    public function testValidateSystemConfigRedirectToStore(): void
    {
        $this->requestMock->expects($this->any())->method('getParam')->willReturn(
            null
        );

        $websiteMock = $this->getMockBuilder(Website::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getCode'])
            ->getMock();
        $websiteMock->expects($this->any())
            ->method('getCode')
            ->willReturn('default');

        $storeMock = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getWebsite', 'getCode', 'getId'])
            ->getMock();
        $storeMock->expects($this->any())
            ->method('getWebsite')
            ->willReturn($websiteMock);
        $storeMock->expects($this->any())
            ->method('getCode')
            ->willReturn('base');
        $storeMock->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $this->storeManagerMock->expects($this->any())
            ->method('getDefaultStoreView')
            ->willReturn($storeMock);

        $this->roleMock->expects($this->any())
            ->method('getWebsiteIds')
            ->willReturn(false);

        $this->roleMock->expects($this->any())
            ->method('hasStoreAccess')
            ->with(1)
            ->willReturn(true);

        $this->model->validateSystemConfig();
        $this->assertRedirect();
    }

    /**
     * User role has no access to any store view scope or website. Redirect to access denied page.
     *
     * @return void
     */
    public function testValidateSystemConfigRedirectToDenied(): void
    {
        $this->requestMock->expects($this->any())->method('getParam')->willReturn(
            null
        );

        $websiteMock = $this->getMockBuilder(Website::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getCode'])
            ->getMock();
        $websiteMock->expects($this->any())
            ->method('getCode')
            ->willReturn('default');

        $storeMock = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getWebsite', 'getCode', 'getId'])
            ->getMock();
        $storeMock->expects($this->any())
            ->method('getWebsite')
            ->willReturn($websiteMock);
        $storeMock->expects($this->any())
            ->method('getCode')
            ->willReturn('base');
        $storeMock->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $this->storeManagerMock->expects($this->any())
            ->method('getDefaultStoreView')
            ->willReturn($storeMock);
        $this->storeManagerMock->expects($this->any())
            ->method('getStores')
            ->willReturn([$storeMock]);

        $this->roleMock->expects($this->any())
            ->method('getWebsiteIds')
            ->willReturn(false);

        $this->roleMock->expects($this->any())
            ->method('hasStoreAccess')
            ->with(1)
            ->willReturn(false);

        $this->model->validateSystemConfig();
        $this->assertRedirect($this->stringContains('admin/noroute'));
    }

    /**
     * Test when system store is validated to be matched.
     *
     * @return void
     */
    public function testValidateSystemStoreMatched(): void
    {
        $this->registryMock->expects($this->any())
            ->method('registry')
            ->willReturn(true);
        $this->model->validateSystemStore();
    }

    /**
     * Test "save" action when request is forwarded to website view.
     *
     * @return void
     */
    public function testValidateSystemStoreActionNameSaveForwardToWebsite(): void
    {
        $this->registryMock->expects($this->any())
            ->method('registry')
            ->willReturn(null);
        $this->requestMock->expects($this->any())
            ->method('getActionName')
            ->willReturn('save');
        $this->requestMock->expects($this->any())
            ->method('getParams')
            ->willReturn(['website' => 'testWebsite']);
        $this->requestMock->expects($this->any())
            ->method('setActionName')
            ->willReturnSelf();
        $this->model->validateSystemStore();
    }

    /**
     * Test "save" action when request is forwarded to store view.
     *
     * @return void
     */
    public function testValidateSystemStoreActionNameSaveForwardToStore(): void
    {
        $this->registryMock->expects($this->any())
            ->method('registry')
            ->willReturn(null);
        $this->requestMock->expects($this->any())
            ->method('getActionName')
            ->willReturn('save');
        $this->requestMock->expects($this->any())
            ->method('getParams')
            ->willReturn(['website' => null, 'store' => 'testStore']);
        $this->roleMock->expects($this->any())
            ->method('getWebsiteIds')
            ->willReturn(null);
        $this->requestMock->expects($this->any())
            ->method('setActionName')
            ->willReturnSelf();
        $this->model->validateSystemStore();
    }

    /**
     * Test "newWebsite" action.
     *
     * @return void
     */
    public function testValidateSystemStoreActionNameNewWebsite(): void
    {
        $this->registryMock->expects($this->any())
            ->method('registry')
            ->willReturn(null);
        $this->requestMock->expects($this->any())
            ->method('getActionName')
            ->willReturn('newWebsite');
        $this->requestMock->expects($this->any())
            ->method('setActionName')
            ->willReturnSelf();
        $this->model->validateSystemStore();
    }

    /**
     * Test "newGroup" action.
     *
     * @return void
     */
    public function testValidateSystemStoreActionNameNewGroup(): void
    {
        $this->registryMock->expects($this->any())
            ->method('registry')
            ->willReturn(null);
        $this->requestMock->expects($this->any())
            ->method('getActionName')
            ->willReturn('newGroup');
        $this->roleMock->expects($this->any())
            ->method('getWebsiteIds')
            ->willReturn(null);
        $this->requestMock->expects($this->any())
            ->method('setActionName')
            ->willReturnSelf();
        $this->model->validateSystemStore();
    }

    /**
     * Test "newStore" action.
     *
     * @return void
     */
    public function testValidateSystemStoreActionNameNewStore(): void
    {
        $this->registryMock->expects($this->any())
            ->method('registry')
            ->willReturn(null);
        $this->requestMock->expects($this->any())
            ->method('getActionName')
            ->willReturn('newStore');
        $this->roleMock->expects($this->any())
            ->method('getWebsiteIds')
            ->willReturn(null);
        $this->requestMock->expects($this->any())
            ->method('setActionName')
            ->willReturnSelf();
        $this->model->validateSystemStore();
    }

    /**
     * Test "editWebsite" action.
     *
     * @return void
     */
    public function testValidateSystemStoreActionNameEditWebsite(): void
    {
        $this->registryMock->expects($this->any())
            ->method('registry')
            ->willReturn(null);
        $this->requestMock->expects($this->any())
            ->method('getActionName')
            ->willReturn('editWebsite');
        $this->requestMock->expects($this->any())
            ->method('setActionName')
            ->willReturnSelf();
        $this->roleMock->expects($this->any())
            ->method('hasWebsiteAccess')
            ->willReturn(null);
        $this->model->validateSystemStore();
    }

    /**
     * Test "editGroup" action.
     *
     * @return void
     */
    public function testValidateSystemStoreActionNameEditGroup(): void
    {
        $this->registryMock->expects($this->any())
            ->method('registry')
            ->willReturn(null);
        $this->requestMock->expects($this->any())
            ->method('getActionName')
            ->willReturn('editGroup');
        $this->requestMock->expects($this->any())
            ->method('setActionName')
            ->willReturnSelf();
        $this->roleMock->expects($this->any())
            ->method('hasStoreGroupAccess')
            ->willReturn(null);
        $this->model->validateSystemStore();
    }

    /**
     * Test "editStore" action.
     *
     * @return void
     */
    public function testValidateSystemStoreActionNameEditStore(): void
    {
        $this->registryMock->expects($this->any())
            ->method('registry')
            ->willReturn(null);
        $this->requestMock->expects($this->any())
            ->method('getActionName')
            ->willReturn('editStore');
        $this->requestMock->expects($this->any())
            ->method('setActionName')
            ->willReturnSelf();
        $this->roleMock->expects($this->any())
            ->method('hasStoreAccess')
            ->willReturn(null);
        $this->model->validateSystemStore();
    }

    /**
     * Test "deleteWebsite" action.
     *
     * @return void
     */
    public function testValidateSystemStoreActionNameDeleteWebsite(): void
    {
        $this->registryMock->expects($this->any())
            ->method('registry')
            ->willReturn(null);
        $this->requestMock->expects($this->any())
            ->method('getActionName')
            ->willReturn('deleteWebsite');
        $this->requestMock->expects($this->any())
            ->method('setActionName')
            ->willReturnSelf();
        $this->model->validateSystemStore();
    }

    /**
     * Test "deleteWebsitePost" action.
     *
     * @return void
     */
    public function testValidateSystemStoreActionNameDeleteWebsitePost(): void
    {
        $this->registryMock->expects($this->any())
            ->method('registry')
            ->willReturn(null);
        $this->requestMock->expects($this->any())
            ->method('getActionName')
            ->willReturn('deleteWebsitePost');
        $this->requestMock->expects($this->any())
            ->method('setActionName')
            ->willReturnSelf();
        $this->model->validateSystemStore();
    }

    /**
     * Test "deleteGroup" action.
     *
     * @return void
     */
    public function testValidateSystemStoreActionNameDeleteGroup(): void
    {
        $this->registryMock->expects($this->any())
            ->method('registry')
            ->willReturn(null);
        $this->requestMock->expects($this->any())
            ->method('getActionName')
            ->willReturn('deleteGroup');
        $this->requestMock->expects($this->any())
            ->method('setActionName')
            ->willReturnSelf();
        $this->model->validateSystemStore();
    }

    /**
     * Test "deleteGroupPost" action with website access.
     *
     * @return void
     */
    public function testValidateSystemStoreActionNameDeleteGroupPostHasWebsiteAccess(): void
    {
        $this->registryMock->expects($this->any())
            ->method('registry')
            ->willReturn(null);
        $this->requestMock->expects($this->any())
            ->method('getActionName')
            ->willReturn('deleteGroupPost');
        $groupMock = $this->getMockBuilder(Group::class)->disableOriginalConstructor()
            ->onlyMethods(['getWebsiteId'])
            ->getMock();
        $groupMock->expects($this->any())
            ->method('getWebsiteId')
            ->willReturn('testWebsite');
        $this->roleMock->expects($this->any())
            ->method('getGroup')
            ->willReturn($groupMock);
        $this->roleMock->expects($this->any())
            ->method('hasWebsiteAccess')
            ->willReturn(true);
        $this->model->validateSystemStore();
    }

    /**
     * Test "deleteGroupPost" action with no website access.
     *
     * @return void
     */
    public function testValidateSystemStoreActionNameDeleteGroupPostNoWebsiteAccess(): void
    {
        $this->registryMock->expects($this->any())
            ->method('registry')
            ->willReturn(null);
        $this->requestMock->expects($this->any())
            ->method('getActionName')
            ->willReturn('deleteGroupPost');
        $groupMock = $this->getMockBuilder(Group::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getWebsiteId'])
            ->getMock();
        $groupMock->expects($this->any())
            ->method('getWebsiteId')
            ->willReturn('testWebsite');
        $this->roleMock->expects($this->any())
            ->method('getGroup')
            ->willReturn($groupMock);
        $this->roleMock->expects($this->any())
            ->method('getGroup')
            ->willReturn(true);
        $this->roleMock->expects($this->any())
            ->method('hasWebsiteAccess')
            ->willReturn(false);
        $this->requestMock->expects($this->any())
            ->method('setActionName')
            ->willReturnSelf();
        $this->model->validateSystemStore();
    }

    /**
     * Test "deleteStore" action.
     *
     * @return void
     */
    public function testValidateSystemStoreActionNameDeleteStore(): void
    {
        $this->registryMock->expects($this->any())
            ->method('registry')
            ->willReturn(null);
        $this->requestMock->expects($this->any())
            ->method('getActionName')
            ->willReturn('deleteStore');
        $this->requestMock->expects($this->any())
            ->method('setActionName')
            ->willReturnSelf();
        $this->model->validateSystemStore();
    }

    /**
     * Test "deleteStorePost" action with website access.
     *
     * @return void
     */
    public function testValidateSystemStoreActionNameDeleteStorePostHasWebsiteAccess(): void
    {
        $this->registryMock->expects($this->any())
            ->method('registry')
            ->willReturn(null);
        $this->requestMock->expects($this->any())
            ->method('getActionName')
            ->willReturn('deleteStorePost');
        $storeMock = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getWebsiteId'])
            ->getMock();
        $storeMock->expects($this->any())
            ->method('getWebsiteId')
            ->willReturn('testWebsite');
        $this->storeManagerMock->expects($this->any())
            ->method('getStore')
            ->willReturn($storeMock);
        $this->roleMock->expects($this->any())
            ->method('hasWebsiteAccess')
            ->willReturn(true);
        $this->model->validateSystemStore();
    }

    /**
     * Test "deleteStorePost" action with no website access.
     *
     * @return void
     */
    public function testValidateSystemStoreActionNameDeleteStorePostNoWebsiteAccess(): void
    {
        $this->registryMock->expects($this->any())
            ->method('registry')
            ->willReturn(null);
        $this->requestMock->expects($this->any())
            ->method('getActionName')
            ->willReturn('deleteStorePost');
        $storeMock = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getWebsiteId'])
            ->getMock();
        $storeMock->expects($this->any())
            ->method('getWebsiteId')
            ->willReturn('testWebsite');
        $this->storeManagerMock->expects($this->any())
            ->method('getStore')
            ->willReturn($storeMock);
        $this->roleMock->expects($this->any())
            ->method('hasWebsiteAccess')
            ->willReturn(false);
        $this->requestMock->expects($this->any())
            ->method('setActionName')
            ->willReturnSelf();
        $this->model->validateSystemStore();
    }
}
