# MediaType

[![Build Status](https://github.com/innmind/mediatype/workflows/CI/badge.svg?branch=master)](https://github.com/innmind/mediatype/actions?query=workflow%3ACI)
[![codecov](https://codecov.io/gh/innmind/mediatype/branch/develop/graph/badge.svg)](https://codecov.io/gh/innmind/mediatype)
[![Type Coverage](https://shepherd.dev/github/innmind/mediatype/coverage.svg)](https://shepherd.dev/github/innmind/mediatype)

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
