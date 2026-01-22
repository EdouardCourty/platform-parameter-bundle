# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Initial release
- Entity `PlatformParameter` with UUID support
- Abstract entity `AbstractPlatformParameter` as base class (MappedSuperclass)
- Entity extensibility: Users can create custom entities extending `AbstractPlatformParameter`
- Configuration option `entity_class` to specify custom entity FQCN
- Service `PlatformParameterProvider` with type-safe methods
- Support for 5 parameter types: STRING, INTEGER, BOOLEAN, JSON, LIST
- PSR-6 cache integration
- Configurable cache TTL and key prefix
- Custom exception `ParameterNotFoundException`
- Interface `PlatformParameterProviderInterface` for dependency injection
- Optional EasyAdmin CRUD controller (requires easycorp/easyadmin-bundle)
- Separator parameter for `getList()` method (default: "\n")
- Comprehensive test suite (Unit + Functional)
- GitHub Actions CI with matrix testing (PHP 8.3-8.4 × Symfony 6.4-8.0 × Doctrine 3-4)

### Changed
- `PlatformParameter` now extends `AbstractPlatformParameter`
- Entity class is configurable via bundle configuration
- Provider service uses dynamic entity class from configuration
