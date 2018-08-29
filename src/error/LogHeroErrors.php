<?php

namespace LogHero\Client;


class LogHeroErrors {
    private $errorFilePrefix;

    public function __construct($errorFilePrefix) {
        $this->errorFilePrefix = $errorFilePrefix;
    }

    public function setErrorFilenamePrefix($errorFilePrefix) {
        $this->errorFilePrefix = $errorFilePrefix;
    }

    public function writeError($errorTypeId, $fullError) {
        $errorFilename = $this->getErrorFilename($errorTypeId);
        FileStorage::verifyWriteAccess($errorFilename);
        file_put_contents($errorFilename, $fullError);
        chmod($errorFilename, 0666);
    }

    public function getError($errorTypeId) {
        $errorFilename = $this->getErrorFilename($errorTypeId);
        FileStorage::verifyWriteAccess($errorFilename);
        if (file_exists($errorFilename)) {
            return fgets(fopen($errorFilename, 'r'));
        }
        return null;
    }

    public function resolveError($errorTypeId) {
        $errorFilename = $this->getErrorFilename($errorTypeId);
        if (file_exists($errorFilename)) {
            unlink($errorFilename);
        }
    }

    private function getErrorFilename($errorTypeId) {
        return $this->errorFilePrefix . '.' . $errorTypeId . '.txt';
    }
}