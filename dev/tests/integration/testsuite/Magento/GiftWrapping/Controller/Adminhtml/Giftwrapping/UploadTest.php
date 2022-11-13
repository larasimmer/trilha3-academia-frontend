<?php
/***
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftWrapping\Controller\Adminhtml\Giftwrapping;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Magento\Store\Model\Store;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\AbstractBackendController;

/**
 * Testing upload controller.
 *
 * @magentoAppArea adminhtml
 */
class UploadTest extends AbstractBackendController
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @inheritdoc
     */
    protected function setUp() : void
    {
        $this->resource = 'Magento_GiftWrapping::magento_giftwrapping';
        $this->uri = 'backend/admin/giftwrapping/upload';

        parent::setUp();
    }

    /**
     * Test upload controller.
     *
     * @dataProvider uploadProvider
     * @param $image
     * @param $postData
     * @param $expects
     *
     * @return void
     * @throws FileSystemException
     */
    public function testUpload($image, $postData, $expects) : void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->filesystem = $this->objectManager->get(Filesystem::class);
        $tmpDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::SYS_TMP);
        $fixtureDir = realpath(__DIR__ . '/../../../_files');
        $fileName = 'magento_small_image.jpg';
        $filePath = $tmpDirectory->getAbsolutePath($fileName);
        copy($fixtureDir . DIRECTORY_SEPARATOR . $fileName, $filePath);
        $_FILES['image_name'] = $image;
        $_FILES['image_name']['tmp_name'] = $filePath;
        $imageNamePattern = '/fooImage[_0-9]*\./';
        $this->getRequest()->setPostValue('wrapping', $postData);
        $dispatchUrl = 'backend/admin/giftwrapping/upload/store/' . Store::DEFAULT_STORE_ID . '/';

        $this->dispatch($dispatchUrl);
        $coreRegistry = $this->objectManager->get(Registry::class);
        $model = $coreRegistry->registry('current_giftwrapping_model');

        $this->assertEquals($expects['design'], $model->getDesign());
        $this->assertEquals($expects['website_ids'], $model->getWebsiteIds());
        $this->assertEquals($expects['status'], $model->getStatus());
        $this->assertEquals($expects['base_price'], $model->getBasePrice());
        $this->assertEquals($expects['image_name'], $model->getImageName());
        $this->assertMatchesRegularExpression($imageNamePattern, $model->getTmpImage());
        $this->assertMatchesRegularExpression($imageNamePattern, $model->getImage());
    }

    /**
     * Upload test data provider.
     *
     * @return array
     */
    public function uploadProvider() : array
    {
        return [
            [
                [
                    'name' => 'fooImage.jpg',
                    'type' => 'image/jpeg',
                    'error' => 0,
                    'size' => 12500
                ],
                [
                    'design' => 'Foobar',
                    'website_ids' => [1],
                    'status' => 1,
                    'base_price' => 15,
                    'image_name' => [
                        'value' => 'fooImage.jpg'
                    ]
                ],
                [
                    'design' => 'Foobar',
                    'website_ids' => [1],
                    'status' => 1,
                    'base_price' => 15,
                    'image_name' => [
                        'value' =>  'fooImage.jpg'
                    ]
                ]
            ],
            [
                [
                    'name' => 'fooImage.jpg',
                    'type' => 'image/jpeg',
                    'error' => 0,
                    'size' => 12500
                ],
                [
                    'design' => 'Foobar',
                    'website_ids' => [1],
                    'status' => 1,
                    'base_price' => 15,
                    'image_name' => [
                        'value' => 'fooImage.jpg'
                    ],
                    'tmp_image' => 'barImage.jpg'
                ],
                [
                    'design' => 'Foobar',
                    'website_ids' => [1],
                    'status' => 1,
                    'base_price' => 15,
                    'image_name' => [
                        'value' =>  'fooImage.jpg'
                    ]
                ]
            ],
            [
                [
                    'name' => 'fooImage.jpg',
                    'type' => 'image/jpeg',
                    'error' => 0,
                    'size' => 12500,
                    'tmp_image' => 'barImage.jpg'
                ],
                [
                    'design' => 'Foobar',
                    'website_ids' => [1],
                    'status' => 1,
                    'base_price' => 15,
                    'image_name' => [
                        'value' => 'fooImage.jpg'
                    ]
                ],
                [
                    'design' => 'Foobar',
                    'website_ids' => [1],
                    'status' => 1,
                    'base_price' => 15,
                    'image_name' => [
                        'value' =>  'fooImage.jpg'
                    ]
                ]
            ]
        ];
    }
}
