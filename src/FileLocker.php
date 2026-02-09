<?php

namespace PhpUtils;

class FileLocker
{

    private $fps = [];

    /**
     * 获取锁（随机抢占锁，非严格排队获取）
     * @param string $key 锁的唯一标识
     * @param int $timeout 超时时间(秒)，0表示无限等待
     * @return bool 是否获取到锁
     * @throws \Exception
     */
    public function wait($key, $timeout = 0)
    {
        $file = self::getKeyFileName($key);
        $start_time = time();

        $ii = 10000;
        while (true) {
            // 检查是否超时
            if ($timeout > 0 && (time() - $start_time) >= $timeout) {
                return false;
            }

            // 尝试打开文件
            $fp = @fopen($file, "c+");
            if (!$fp) {
                throw new \Exception("无法打开文件 $file");
            }
            @chmod($file, 0666);

            // 尝试获取锁
            if (flock($fp, LOCK_EX | LOCK_NB)) {
                // 获取锁成功
                $this->fps[$key] = [$fp, $file];
                return true;
            } else {
                // 获取锁失败，关闭文件句柄
                fclose($fp);
                if ($timeout == -1) return false;
                usleep($ii); // 等待10毫秒后重试
                if ($ii < 1000000) $ii += 10000;
            }
        }
    }

    private static function getKeyFileName($key)
    {
        return __DIR__ . "/.lock_" . md5($key) . ".tmp";
    }

    /**
     * 释放锁
     * @param string $key 锁的唯一标识
     * @return bool 是否成功释放
     */
    public function release($key)
    {
        if (!isset($this->fps[$key])) {
            return false;
        }

        list($fp, $file) = $this->fps[$key];
        if (is_resource($fp)) {
            flock($fp, LOCK_UN); // 释放文件锁
            fclose($fp);         // 关闭文件句柄
            @unlink($file);
        }

        unset($this->fps[$key]);
        return true;
    }

    public function isLocking($key)
    {
        $file = self::getKeyFileName($key);
        if (!file_exists($file)) return false;

        $fp = fopen($file, "r");
        if (!$fp) return false;

        $result = !flock($fp, LOCK_EX | LOCK_NB);
        if ($result) {
            // 文件确实被锁定，需要释放我们刚获取的锁
            flock($fp, LOCK_UN);
        }
        fclose($fp);

        return $result;
    }

    /**
     * 析构时释放所有锁
     */
    public function __destruct()
    {
        foreach (array_keys($this->fps) as $key) {
            self::release($key);
        }
    }
}
