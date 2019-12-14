# MediaType

| `develop` |
|-----------|
| [![codecov](https://codecov.io/gh/Innmind/MediaType/branch/develop/graph/badge.svg)](https://codecov.io/gh/Innmind/MediaType) |
| [![Build Status](https://github.com/Innmind/MediaType/workflows/CI/badge.svg)](https://github.com/Innmind/MediaType/actions?query=workflow%3ACI) |

Model to validate media types (follows [RFC6838](https://tools.ietf.org/html/rfc6838)).

## Installation

```sh
composer install innmind/media-type
```

## Usage

```php
use Innmind\MediaType\MediaType;

$type = MediaType::of('application/json+some-extension; charset=utf-8');
$type->topLevel(); // application
$type->subType(); // json
$type->suffix(); // some-extension
$type->parameters()->first()->name(); // charset
$type->parameters()->first()->value(); // utf-8
$type->toString(); // application/json+some-extension; charset=utf-8
```

If the values are incorrect it will throw an exception.
