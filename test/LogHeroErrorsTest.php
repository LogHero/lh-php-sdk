<?php
namespace LogHero\Client\Test;

use PHPUnit\Framework\TestCase;
use LogHero\Client\LogHeroErrors;


class LogHeroErrorsTest extends TestCase {
    public $errors;
    public $errorFilename;
    public $errorFilenameNoPermissions;

    public function setUp() {
        parent::setUp();
        $errorFilePrefix = __DIR__ . '/errors.loghero.io';
        $this->errorFilename = $errorFilePrefix . '.test-error.txt';
        $this->errorFilenameNoPermissions = $errorFilePrefix . '.no-permissions.txt';
        $this->errors = new LogHeroErrors($errorFilePrefix);
        file_put_contents($this->errorFilenameNoPermissions, 'DATA');
        chmod($this->errorFilenameNoPermissions, 0400);
    }

    public function tearDown() {
        parent::tearDown();
        if(file_exists($this->errorFilename)) {
            unlink($this->errorFilename);
        }
        chmod($this->errorFilenameNoPermissions, 0700);
        unlink($this->errorFilenameNoPermissions);
    }

    public function testCreateErrorFile() {
        $this->errors->writeError('test-error', "My Error\nSTACK TRACE");
        static::assertFileExists($this->errorFilename);
        static::assertEquals("My Error\n", $this->errors->getError('test-error'));
    }

    public function testResolveErrorFile() {
        $this->errors->writeError('test-error', "My Error\nSTACK TRACE");
        static::assertFileExists($this->errorFilename);
        static::assertNotNull($this->errors->getError('test-error'));
        $this->errors->resolveError('test-error');
        static::assertFileNotExists($this->errorFilename);
        static::assertNull($this->errors->getError('test-error'));
    }

    /**
     * @expectedException \LogHero\Client\PermissionDeniedException
     * @expectedExceptionMessage Permission denied! Cannot write to file
     */
    public function testCheckPermissionsForReadingErrorFiles() {
        $this->errors->getError('no-permissions');
    }

    /**
     * @expectedException \LogHero\Client\PermissionDeniedException
     * @expectedExceptionMessage Permission denied! Cannot write to file
     */
    public function testCheckPermissionsForWritingErrorFiles() {
        $this->errors->writeError('no-permissions', 'My Error');
    }

}