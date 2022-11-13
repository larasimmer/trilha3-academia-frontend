<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Support\Helper;

use Magento\Framework\App\Config\MutableScopeConfigInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;

/**
 * Test for \Magento\Support\Helper\Shell class.
 */
class ShellTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var MutableScopeConfigInterface
     */
    private $mutableScopeConfig;

    /**
     * @var string
     */
    private $fileName;

    /**
     * @var Shell
     */
    private $shell;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->filesystem = $this->objectManager->get(Filesystem::class);
        $this->mutableScopeConfig = $this->objectManager->get(MutableScopeConfigInterface::class);
        $this->fileName = 'test|test \'".txt';
        $this->shell = $this->objectManager->create(
            Shell::class,
            [
                'filesystem' => $this->filesystem,
            ]
        );
    }

    /**
     * @inheritdoc
     */
    protected function assertPreConditions(): void
    {
        parent::assertPreConditions();
        // phpcs:ignore Magento2.Security.InsecureFunction
        if (exec('which lsof') === '') {
            $this->markTestSkipped('Requires lsof utility');
        }
    }

    /**
     * Test with locked file.
     *
     * @return void
     */
    public function testIsFileLockedOnLockedFile(): void
    {
        $varDirectory = $this->prepareFile();
        $fileResource = $varDirectory->openFile($varDirectory->getAbsolutePath($this->fileName));
        $fileResource->lock();
        try {
            $this->assertTrue($this->shell->isFileLocked($this->fileName));
        } finally {
            $fileResource->unlock();
            $fileResource->close();
        }
    }

    /**
     * Test with unlocked file.
     *
     * @return void
     */
    public function testIsFileLockedOnUnlockedFile(): void
    {
        $this->prepareFile();

        $this->assertFalse($this->shell->isFileLocked($this->fileName));
    }

    /**
     * Prepare file for isFileLocked method.
     *
     * @return WriteInterface
     */
    private function prepareFile(): WriteInterface
    {
        $varDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $this->mutableScopeConfig->setValue(
            Shell::XML_OUTPUT_PATH,
            $varDirectory->getAbsolutePath()
        );
        $varDirectory->touch($this->fileName);

        return $varDirectory;
    }

    /**
     * Test on missing file.
     *
     * @return void
     */
    public function testIsFileLockedOnMissingFile(): void
    {
        $this->expectException(\Magento\Framework\Exception\FileSystemException::class);
        $this->expectExceptionMessage('File test|test \'".txt is not found.');

        $this->shell->isFileLocked($this->fileName);
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        /** @var WriteInterface $varDirectory */
        $varDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $varDirectory->delete($this->fileName);
    }
}
