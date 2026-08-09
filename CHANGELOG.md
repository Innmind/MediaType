# Changelog

## [Unreleased]

### Changed

- Requires PHP `8.5`

## 3.1.0 - 2026-05-14

### Changed

- Requires `innmind/black-box:~7.0`

## 3.0.0 - 2026-01-26

### Added

- `Innmind\MediaType\MediaType::attempt()`

### Changed

- Require PHP `8.4`
- `Innmind\MediaType\MediaType` constructor is now private, use `::from()` instead
- `Innmind\MediaType\Parameter` constructor is now private, use `::from()` instead
- `Innmind\MediaType\MediaType` top level is now represented by `Innmind\MediaType\TopLevel`

### Removed

- `Innmind\MediaType\Exception\InvalidTopLevelType`
- `Innmind\MediaType\Exception\Exception`
- `Innmind\MediaType\Exception\DomainException`

## 2.3.0 - 2025-03-20

### Added

- Support for `innmind/black-box` `6`

## 2.2.0 - 2023-09-16

### Added

- Support for `innmind/immutable` `5`

## 2.1.0 - 2023-07-09

### Changed

- Require `innmind/black-box` `5`

### Removed

- Support for PHP `8.0` and `8.1`

## 2.0.1 - 2023-02-12

### Fixed

- Parsing parameters with values inside double quotes
