<?php
namespace LogHero\Client\Test;

use LogHero\Client\APIKeyFileStorage;
use PHPUnit\Framework\TestCase;


class APIKeyFileStorageTest extends TestCase {
    private $apiKey = 'LH-123';
    private $keyStorageLocation = __DIR__ . '/key.loghero.io.txt';
    private $keyStorageLocationNoPermissions = __DIR__ . '/key.loghero.io.no-permissions.txt';
    private $keyStorage;
    
    public function setUp() {
        parent::setUp();
        $this->keyStorage = new APIKeyFileStorage($this->keyStorageLocation);
        file_put_contents($this->keyStorageLocationNoPermissions, 'DATA');
        chmod($this->keyStorageLocationNoPermissions, 0400);
    }
    
    public function tearDown() {
        parent::tearDown();
        if(file_exists($this->keyStorageLocation)) {
            unlink($this->keyStorageLocation);
        }
        chmod($this->keyStorageLocationNoPermissions, 0700);
        unlink($this->keyStorageLocationNoPermissions);
    }

    public function testSetKey() {
        static::assertFileNotExists($this->keyStorageLocation);
        $this->keyStorage->setKey($this->apiKey);
        $keyStorageFileContent = file_get_contents($this->keyStorageLocation);
        static::assertEquals($keyStorageFileContent, $this->apiKey);
    }

    public function testGetKey() {
        $this->keyStorage->setKey($this->apiKey);
        static::assertEquals($this->apiKey, $this->keyStorage->getKey());
    }

    public function testGetKeyCachesResult() {
        $this->keyStorage->setKey($this->apiKey);
        static::assertEquals($this->apiKey, $this->keyStorage->getKey());
        file_put_contents($this->keyStorageLocation, 'DUMMY CHANGE');
        static::assertEquals($this->apiKey, $this->keyStorage->getKey());
    }

    /**
     * @expectedException LogHero\Client\APIKeyUndefinedException
     * @expectedExceptionMessage Cannot read API key storage
     */
    public function testThrowExceptionIfKeyStorageFileDoesNotExist() {
        static::assertFileNotExists($this->keyStorageLocation);
        $this->keyStorage->getKey();
    }

    /**
     * @expectedException \LogHero\Client\PermissionDeniedException
     * @expectedExceptionMessage Permission denied! Cannot write to file
     */
    public function testCheckPermissionsOnKeyStorageFile() {
        $keyStorage = new APIKeyFileStorage($this->keyStorageLocationNoPermissions);
        $keyStorage->setKey('SOME_API_KEY');
    }

}
