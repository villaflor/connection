# Changelog

All notable changes to `connection` will be documented in this file.

## 5.0.0 - 2025-12-17

### What's New

#### 🎯 New Features

**Caching Middleware**

- HTTP response caching with automatic cache key generation
- Support for `Cache-Control` and `Expires` headers
- Configurable default TTL and cacheable HTTP methods
- Only caches successful 2xx responses

**Cookie Middleware**

- Automatic cookie jar management for session persistence
- Parses `Set-Cookie` headers and stores cookies automatically
- Adds appropriate `Cookie` headers to requests based on domain and path matching
- Full support for cookie attributes (domain, path, expires, max-age, secure, httponly)

**Comprehensive Examples**
Added 10 detailed example files demonstrating:

- Basic usage and HTTP methods
- Authentication strategies (Bearer, API Key, Custom Headers, Service Accounts)
- Retry logic and error handling
- Custom middleware implementation
- File uploads (single, multiple, multipart)
- Event system integration
- Proxy and SSL configuration
- Advanced architectural patterns
- Caching strategies
- Cookie management

#### ⚠️ Breaking Changes

- **Minimum PHP version is now 8.3** (previously 8.2)
- **`psr/http-message` now requires v2.0+** (previously allowed v1.0)
  - This ensures proper type hint compatibility across all PHP versions
  

## 4.0.0 - 2025-09-03

### What's Changed

- updated PHP version support to ^8.3

## v3.0.0 - 2023-09-25

- add support to PHP8.1
- remove support to PHP7.4

## 2.1.0 - 2023-01-06

- added support for Google Service Account auth

## 2.0.0 - 2022-12-07

- added support to PHP8.1
- removed support to PHP7.4
- changed automated testing from PHPUnit to PestPHP

## 1.2.1 - 2022-05-11

- Added support for guzzlehttp/guzzle v6

## 1.1.1 - 2022-03-18

- fixed a bug in which the API error code was in string format

## 1.1.0 - 2021-12-27

- Added Custom Header for Authentication

## 1.0.3 - 2021-09-13

- Added support for form_params
- Updated composer.json
- Code improvement

## 1.0.2 - 2021-08-15

- Added ConfigurationsInterface

## 1.0.1 - 2021-08-15

- Added API Interface

## 1.0.0 - 2021-08-15

- initial release
