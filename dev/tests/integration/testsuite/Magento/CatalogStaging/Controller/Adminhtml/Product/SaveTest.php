<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogStaging\Controller\Adminhtml\Product;

use Magento\Catalog\Model\ProductRepository;
use Magento\Catalog\Model\ResourceModel\Product as ProductResourceModel;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Eav\Model\AttributeRepository;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * @magentoAppArea adminhtml
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SaveTest extends \Magento\TestFramework\TestCase\AbstractController
{
    /**
     * @var \Magento\Backend\Model\Auth\Session
     */
    protected $session;

    /**
     * @var \Magento\Backend\Model\Auth
     */
    protected $auth;

    /**
     * The resource used to authorize action
     *
     * @var string
     */
    protected $resource = null;

    /**
     * The uri at which to access the controller
     *
     * @var string
     */
    protected $uri = null;

    /**
     * @var ProductRepository
     */
    protected $productRepository;

    /**
     * @var int
     */
    protected $updateVersion;

    /**
     * SetUp restoreFromDbDump
     *
     * @return void
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        \Magento\TestFramework\Helper\Bootstrap::getInstance()->getBootstrap()
            ->getApplication()
            ->getDbInstance()
            ->restoreFromDbDump();
    }

    /**
     * SetUp
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->_objectManager->get(\Magento\Backend\Model\UrlInterface::class)->turnOffSecretKey();

        $this->productRepository = $this->_objectManager->get(ProductRepository::class);
        $this->auth = $this->_objectManager->get(\Magento\Backend\Model\Auth::class);
        $this->session = $this->auth->getAuthStorage();
        $credentials = $this->getAdminCredentials();
        $this->auth->login($credentials['user'], $credentials['password']);
        $this->_objectManager->get(\Magento\Security\Model\Plugin\Auth::class)->afterLogin($this->auth);
    }

    /**
     * Get credentials to login admin user
     *
     * @return array
     */
    protected function getAdminCredentials()
    {
        return [
            'user' => \Magento\TestFramework\Bootstrap::ADMIN_NAME,
            'password' => \Magento\TestFramework\Bootstrap::ADMIN_PASSWORD
        ];
    }

    public static function tearDownAfterClass(): void
    {
        $db = \Magento\TestFramework\Helper\Bootstrap::getInstance()->getBootstrap()
            ->getApplication()
            ->getDbInstance();
        if (!$db->isDbDumpExists()) {
            throw new \LogicException('DB dump does not exist.');
        }
        $db->restoreFromDbDump();
    }

    /**
     * TearDown
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $this->auth->getAuthStorage()->destroy(['send_expire_cookie' => false]);
        $this->auth = null;
        $this->session = null;
        $this->_objectManager->get(\Magento\Backend\Model\UrlInterface::class)->turnOnSecretKey();
        parent::tearDown();
    }

    /**
     * Covers MAGETWO-70922
     * @magentoDbIsolation disabled
     * @magentoDataFixture Magento/CatalogStaging/_files/simple_product.php
     */
    public function testExecute()
    {
        $product = $this->productRepository->get('simple');
        $productId = $product->getId();
        $this->getRequest()->setPostValue($this->getProductData());
        $store = 1;
        $this->dispatch(
            "backend/catalogstaging/product/save/id/{$productId}/type/simple/store/{$store}/set/4/?isAjax=true"
        );

        /** @var AttributeRepository $attributeRepository */
        $attributeRepository = $this->_objectManager->get(AttributeRepository::class);
        $priceAttribute = $attributeRepository->get(\Magento\Catalog\Model\Product::ENTITY, 'price');
        $priceAttributeId = $priceAttribute->getAttributeId();

        /** @var \Magento\Framework\EntityManager\MetadataPool $entityMetadataPool */
        $entityMetadataPool = $this->_objectManager->get(\Magento\Framework\EntityManager\MetadataPool::class);
        $productMetadata = $entityMetadataPool->getMetadata(\Magento\Catalog\Api\Data\ProductInterface::class);
        $linkField = $productMetadata->getLinkField();

        /** @var \Magento\Framework\App\ResourceConnection $resourceConnection */
        $resourceConnection = $this->_objectManager->get(\Magento\Framework\App\ResourceConnection::class);
        $connection = $resourceConnection->getConnection();
        $select = $connection->select()->from(
            [
                'cped' => $resourceConnection->getTableName('catalog_product_entity_decimal')
            ],
            [
                'store_id',
                'value'
            ]
        )->joinInner(
            [
                'cpe' => $resourceConnection->getTableName('catalog_product_entity')
            ],
            "cpe.{$linkField} = cped.{$linkField}",
            []
        )->where(
            'attribute_id = ?',
            $priceAttributeId
        )->where(
            'sku = ?',
            'simple'
        );
        $select->setPart('disable_staging_preview', true);
        $data = $connection->query($select)->fetchAll();

        $this->assertEquals(
            [
                0 => ['store_id' => 0, 'value' => 10],
                1 => ['store_id' => 0, 'value' => 7],
            ],
            $data
        );
    }

    /**
     * Get update version
     *
     * @return int
     */
    protected function getUpdateVersion()
    {
        if (!$this->updateVersion) {
            $this->updateVersion = time() + 86400;
        }
        return $this->updateVersion;
    }

    /**
     * Product version data provider
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @return array
     */
    protected function getProductData()
    {
        /** @var \Magento\Framework\Data\Form\FormKey $formKey */
        $formKey = $this->_objectManager->get(\Magento\Framework\Data\Form\FormKey::class);
        $startDate = date('c', $this->getUpdateVersion());
        return [
            'product' => [
                'status' => '1',
                'name' => 'ppp',
                'price' => '7',
                'tax_class_id' => '2',
                'quantity_and_stock_status' => [
                    'is_in_stock' => '0',
                    'qty' => '0',
                ],
                'visibility' => '4',
                'is_returnable' => '2',
                'url_key' => 'ppp',
                'meta_title' => 'ppp',
                'meta_keyword' => 'ppp',
                'meta_description' => 'ppp ',
                'msrp_display_actual_price_type' => '0',
                'options_container' => 'container2',
                'gift_message_available' => '0',
                'gift_wrapping_available' => '1',
                'use_config_gift_message_available' => '1',
                'stock_data' => [
                    'item_id' => '6160',
                    'product_id' => '6160',
                    'stock_id' => '1',
                    'qty' => '0',
                    'min_qty' => '0',
                    'use_config_min_qty' => '1',
                    'is_qty_decimal' => '0',
                    'backorders' => '0',
                    'use_config_backorders' => '1',
                    'min_sale_qty' => '1',
                    'use_config_min_sale_qty' => '1',
                    'max_sale_qty' => '10000',
                    'use_config_max_sale_qty' => '1',
                    'is_in_stock' => '0',
                    'low_stock_date' => '2017-07-27 14:40:19',
                    'notify_stock_qty' => '1',
                    'use_config_notify_stock_qty' => '1',
                    'manage_stock' => '1',
                    'use_config_manage_stock' => '1',
                    'stock_status_changed_auto' => '1',
                    'use_config_qty_increments' => '1',
                    'qty_increments' => '0',
                    'use_config_enable_qty_inc' => '1',
                    'enable_qty_increments' => '0',
                    'is_decimal_divided' => '0',
                    'website_id' => '0',
                    'deferred_stock_update' => '1',
                    'use_config_deferred_stock_update' => '1',
                    'type_id' => 'simple',
                    'min_qty_allowed_in_shopping_cart' => '1',
                ],
                'use_config_is_returnable' => '1',
                'current_product_id' => '6160',
                'links_title' => 'Links',
                'links_purchased_separately' => '0',
                'samples_title' => 'Samples',
                'use_config_gift_wrapping_available' => '1',
                'attribute_set_id' => '4',
                'affect_product_custom_options' => '1',
                'current_store_id' => '1',
                'is_new' => '0',
                'weight' => '',
                'product_has_weight' => '1',
                'country_of_manufacture' => '',
                'description' => '',
                'short_description' => '',
                'url_key_create_redirect' => 'ppp',
                'page_layout' => '',
                'custom_layout_update' => '',
                'custom_design' => '',
                'gift_wrapping_price' => '',
                'website_ids' => [
                    1 => '1',
                ],
                'special_price' => '',
                'cost' => '',
                'msrp' => '',
            ],
            'is_downloadable' => '0',
            'affect_configurable_product_attributes' => '1',
            'staging' => [
                    'mode' => 'save',
                    'update_id' => '',
                    'name' => 'dsfsdf',
                    'description' => '',
                    'start_time' => $startDate,
                    'end_time' => '',
                    'select_id' => '1501152300',
                ],
            'new-variations-attribute-set-id' => '4',
            'configurable-matrix-serialized' => '[]',
            'associated_product_ids_serialized' => '[]',
            'use_default' => [
                    'status' => '1',
                    'name' => '1',
                    'tax_class_id' => '1',
                    'visibility' => '1',
                    'country_of_manufacture' => '1',
                    'is_returnable' => '1',
                ],
            'form_key' => $formKey->getFormKey(),
        ];
    }

    /**
     * @magentoDataFixture Magento/CatalogStaging/_files/simple_product.php
     * @magentoDataFixture Magento/Catalog/_files/product_simple_add_video.php
     * @magentoDbIsolation disabled
     */
    public function testProductWithMediaGallery(): void
    {
        $store = 1;
        $originalProduct = $this->productRepository->get('simple');
        $productId = (int) $originalProduct->getId();
        $productFormData = $this->getProductData();
        $productFormData['product']['url_key'] =  uniqid('product_with_media_gallery_');
        $this->getRequest()->setPostValue($productFormData);
        $this->dispatch(
            "backend/catalogstaging/product/save/id/{$productId}/type/simple/store/{$store}/set/4/?isAjax=true"
        );
        $this->assertEquals(200, $this->getResponse()->getStatusCode());
        $jsonSerializer = $this->_objectManager->get(Json::class);
        $response = $jsonSerializer->unserialize($this->getResponse()->getBody());
        $this->assertFalse($response['error'], $response['messages'] ?? '');
        $resource = $this->_objectManager->get(ProductResourceModel::class);
        $rowId = $this->getProductLastVersionRowId($productId);
        $this->assertNotEquals($rowId, $originalProduct->getData($resource->getLinkField()));
        $collection = $this->_objectManager->create(ProductCollection::class);
        $collection->addIdFilter($productId);
        $product = $collection->getItemById($productId);
        $product->setData($resource->getLinkField(), $rowId);
        $product->setOrigData($resource->getLinkField(), $rowId);
        $collection->addMediaGalleryData();
        $mediaGalleryEntries = $product->getMediaGalleryEntries();
        $this->assertCount(1, $mediaGalleryEntries);
        $mediaGallery = $mediaGalleryEntries[0];
        $this->assertEquals('Video Label', $mediaGallery->getLabel());
        $this->assertNotEmpty($mediaGallery->getFile());
        $this->assertFalse((bool) $mediaGallery->isDisabled());
        $this->assertEquals(2, $mediaGallery->getPosition());
        $videoContent = $mediaGallery->getExtensionAttributes()->getVideoContent();
        $this->assertNotNull($videoContent);
        $this->assertEquals('external-video', $videoContent->getMediaType());
        $this->assertEquals('youtube', $videoContent->getVideoProvider());
        $this->assertEquals('http://www.youtube.com/v/tH_2PFNmWoga', $videoContent->getVideoUrl());
        $this->assertEquals('Video title', $videoContent->getVideoTitle());
        $this->assertEquals('Video description', $videoContent->getVideoDescription());
        $this->assertEquals('Video Metadata', $videoContent->getVideoMetadata());
    }

    /**
     * @param int $entityId
     * @return int
     */
    private function getProductLastVersionRowId(int $entityId): int
    {
        $resource = $this->_objectManager->get(ProductResourceModel::class);
        $connection = $resource->getConnection();
        $select = $connection->select();
        $select->from(['t' => $resource->getEntityTable()], [$resource->getLinkField()])
            ->where('t.' . $resource->getIdFieldName() . ' = ?', $entityId)
            ->order('t.created_in DESC')
            ->setPart('disable_staging_preview', true);
        return $connection->fetchOne($select);
    }
}
