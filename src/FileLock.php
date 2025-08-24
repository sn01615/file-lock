<?php

namespace PhpUtils;

class FileLock
{

    private static $fps = [];
    private static $prefix;
    private static $instance = null;

    public static function lock($tag)
    {
        self::getLock($tag, true);
    }

    /**
     * @param string $tag Unique identification
     * @param bool $die Failed to acquire exclusive lock is whether to execute die()
     * @return bool If you get exclusive lock fail return false, else return true.
     */
    public static function getLock($tag, $die = null)
    {
        if (!self::$prefix) self::$prefix = sys_get_temp_dir() . '/_PHP_FileLock_';
        if (!self::$instance) self::$instance = new static();

        $filename = self::getFilename($tag);
        $fp = fopen($filename, "w");
        if (!flock($fp, LOCK_EX | LOCK_NB)) {
            fclose($fp);
            if ($die) die(0);
            return false;
        }
        fwrite($fp, time());
        self::$fps[$filename] = $fp;
        return true;
    }

    private static function getFilename($tag)
    {
        return self::$prefix . sha1($tag);
    }

    public static function unlock($tag)
    {
        $filename = self::getFilename($tag);
        $fp = isset(self::$fps[$filename]) ? self::$fps[$filename] : null;
        self::clear($fp, $filename);
    }

    private static function clear($fp, $filename)
    {
        if ($fp && is_resource($fp)) {
            flock($fp, LOCK_UN);
            fclose($fp);
            unlink($filename);
            unset(self::$fps[$filename]);
        }
    }

    public function __destruct()
    {
        foreach (self::$fps as $filename => $fp) {
            self::clear($fp, $filename);
        }
    }
}
