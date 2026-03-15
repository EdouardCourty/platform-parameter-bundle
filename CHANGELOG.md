# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.0] - 15-03-2026

### Added
- Twig extension for direct parameter access in templates (auto-registered when Twig is installed)
  - `platform_parameter_string`, `platform_parameter_int`, `platform_parameter_bool`
  - `platform_parameter_float`, `platform_parameter_json`, `platform_parameter_list`, `platform_parameter_datetime`
  - All functions return `null` instead of throwing exceptions when a parameter is not found

## [1.0.0] - 25-10-2025

### Added
- Initial release (See [README.md](README.md) for details)
- Added Symfony services to interact with the platform parameters
  - `PlatformParameterProviderInterface` - Access parameters
  - `PlatformParameterWriterInterface` - Update and delete parameters
- Symfony commands for interacting with the platform parameters
- Handling of built-in or custom entity for storing platform parameters
- Caching for optimal performance
- Configurable automatic cache invalidation upon parameter updates
- Events dispatching for user-defined actions on parameter changes
- Unit and functional tests
