<?php

namespace PhpUtils;

class FileLock
{

    private static $fp;

    private static $prefix;

    private static $instances = [];

    private $lockFile;

    public static function lock($tag)
    {
        if (!self::$prefix) {
            self::$prefix = sys_get_temp_dir() . '/_PHP_FileLock_';
        }

        $filename = self::$prefix . sha1($tag);
        file_put_contents($filename, time());
        self::$fp = fopen($filename, "r+");
        if (!flock(self::$fp, LOCK_EX | LOCK_NB)) {
            die(0);
        }
        self::$instances[$tag] = new static();
        self::$instances[$tag]->lockFile = $filename;
    }

    public static function unlock($tag)
    {
        if (self::$fp !== null) {
            flock(self::$fp, LOCK_UN);
        }
    }

    public function __destruct()
    {
        unlink($this->lockFile);
    }
}

