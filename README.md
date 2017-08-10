### 应用场景
1. 限制只开启一个或有限个进程运行

### 局限性
只能在同一台服务器内生效

### Install

```
composer require sn01615/file-lock
```
### Usage

```php
use PhpUtils\FileLock;
FileLock::lock('lockKey');
```
