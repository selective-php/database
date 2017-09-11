## Compression

The compress and uncompress methods are compatible with MySQL COMPRESS.

```php
$compression = new \Odan\Database\Compression($db);
```

### Compress

```php
$compressed = $compression->compress('data');
```

### Uncompress

```php
$uncompressed = $compression->uncompress('compressed data');
```
