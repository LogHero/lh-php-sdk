<?php
namespace LogHero\Client\Test;

use LogHero\Client\FileStorage;
use PHPUnit\Framework\TestCase;


class FileStorageTest extends TestCase {
    private $storageFileLocation = __DIR__ . '/storage.loghero.io';
    private $storageFileLocationNoPermissions = __DIR__ . '/storage.loghero.io.no-permissions';
    private $storage;

    public function setUp() {
        $this->storage = new FileStorage($this->storageFileLocation);
        file_put_contents($this->storageFileLocationNoPermissions, 'DATA');
        chmod($this->storageFileLocationNoPermissions, 0400);
        clearstatcache();
    }

    public function tearDown(){
        parent::tearDown();
        if(file_exists($this->storageFileLocation)) {
            unlink($this->storageFileLocation);
        }
        chmod($this->storageFileLocationNoPermissions, 0700);
        unlink($this->storageFileLocationNoPermissions);
    }

    public function testSetData() {
        $this->storage->set('DATA');
        $data = file_get_contents($this->storageFileLocation);
        static::assertEquals('DATA', $data);
    }

    public function testGetData() {
        file_put_contents($this->storageFileLocation, 'DATA');
        static::assertEquals($this->storage->get(), 'DATA');
    }

    public function testGetNullIfFileDoesNotExist() {
        static::assertNull($this->storage->get());
    }

    /**
     * @expectedException \LogHero\Client\PermissionDeniedException
     * @expectedExceptionMessage Permission denied! Cannot write to file
     */
    public function testRaisePermissionDeniedExceptionIfNoWritePermissionsOnStorageFile() {
        new FileStorage($this->storageFileLocationNoPermissions, 100, 300, 1000);
    }


}
