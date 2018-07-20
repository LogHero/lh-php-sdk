<?php
namespace LogHero\Client\Test;

use PHPUnit\Framework\TestCase;
use LogHero\Client\LogHeroErrors;


class LogHeroErrorsTest extends TestCase {
    public $errors;
    public $errorFilename;

    public function setUp() {
        parent::setUp();
        $errorFilePrefix = __DIR__ . '/errors.loghero.io';
        $this->errorFilename = $errorFilePrefix . '.test-error.txt';
        $this->errors = new LogHeroErrors($errorFilePrefix);
    }

    public function tearDown() {
        parent::tearDown();
        if(file_exists($this->errorFilename)) {
            unlink($this->errorFilename);
        }
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

}