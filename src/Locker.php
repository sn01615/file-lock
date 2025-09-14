<?php

namespace PhpUtils;

class Locker
{

    private static $instance = null;
    private static $lockers = [];

    /**
     * 获取锁（随机抢占锁，非严格排队获取）
     * @param string $key 锁的唯一标识
     * @param float $timeout 超时时间(秒)，0表示无限等待
     * @return bool 是否获取到锁
     */
    public static function wait($key, $timeout = 0)
    {
        $file = __DIR__ . "/.lock_" . md5($key) . ".tmp";
        $start_time = microtime(true);

        if (!self::$instance) self::$instance = new self(); # 有实例才能触发析构函数

        while (true) {
            // 检查是否超时
            if ($timeout > 0 && (microtime(true) - $start_time) >= $timeout) {
                return false;
            }

            // 尝试打开文件
            $fp = @fopen($file, "c+");
            if (!$fp) {
                usleep(10000); // 等待10毫秒
                continue;
            }
            @chmod($file, 0666);

            // 尝试获取锁
            if (flock($fp, LOCK_EX | LOCK_NB)) {
                // 获取锁成功
                self::$lockers[$key] = [$fp, $file];
                return true;
            } else {
                // 获取锁失败，关闭文件句柄
                fclose($fp);
                if ($timeout == -1) return false;
                usleep(10000); // 等待10毫秒后重试
            }
        }
    }

    /**
     * 检查是否持有某个锁
     * @param string $key 锁的唯一标识
     * @return bool 是否持有锁
     */
    public static function isLocked($key)
    {
        return isset(self::$lockers[$key][0]) && is_resource(self::$lockers[$key][0]);
    }

    /**
     * 析构时释放所有锁
     */
    public function __destruct()
    {
        foreach (array_keys(self::$lockers) as $key) {
            self::release($key);
        }
    }

    /**
     * 释放锁
     * @param string $key 锁的唯一标识
     * @return bool 是否成功释放
     */
    public static function release($key)
    {
        if (!isset(self::$lockers[$key])) {
            return false;
        }

        list($fp, $file) = self::$lockers[$key];
        if (is_resource($fp)) {
            @unlink($file);
            flock($fp, LOCK_UN); // 释放文件锁
            fclose($fp);         // 关闭文件句柄
        }

        unset(self::$lockers[$key]);
        return true;
    }
}