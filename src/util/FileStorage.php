<?php
namespace LogHero\Client;


class FileStorage implements StorageInterface {
    private $storageFilename;

    public function __construct($storageFilename) {
        static::verifyWriteAccess($storageFilename);
        $this->storageFilename = $storageFilename;
    }

    public function set($jsonDataAsString) {
        file_put_contents($this->storageFilename, $jsonDataAsString);
        chmod($this->storageFilename, 0666);
    }

    public function get() {
        if (file_exists($this->storageFilename)) {
            return file_get_contents($this->storageFilename);
        }
        return null;
    }

    public static function verifyWriteAccess($fileLocation) {
        $directoryName = dirname($fileLocation);
        if (!is_writable($directoryName)) {
            throw new PermissionDeniedException('Permission denied! Cannot write to directory ' . $directoryName);
        }
        if (file_exists($fileLocation) && !is_writable($fileLocation)) {
            throw new PermissionDeniedException('Permission denied! Cannot write to file ' . $fileLocation);
        }
    }

}
