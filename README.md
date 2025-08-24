### 应用场景
1. 限制只开启一个或有限个进程运行
2. 单机单线程排队锁

### 局限性
只能在同一台服务器内生效
需要临时目录写入权限
    (会在临时目录创建一个 _PHP_FileLock_xxx 类似的文件)

### Install

```
composer require sn01615/file-lock
```
### Usage

```php
use PhpUtils\FileLock;

# Get lock
$status = FileLock::getLock('lockKey');
if ($status) {
    # Get lock success
} else {
    # It's locked.
}

# Unlock
FileLock::unlock('lockKey');
```
单机单线程排队：
```php
// 一直排队知道随机抢占到锁
\PhpUtils\Locker::wait("aaa");

// 等待10秒, 如果10秒内没有抢占到锁则返回false, 否则返回true
if (\PhpUtils\Locker::wait("aaa", 10)) {
    echo "获取锁成功";
} else {
    echo "获取锁失败";
}

```
