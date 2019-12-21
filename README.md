### 应用场景
1. 限制只开启一个或有限个进程运行

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
