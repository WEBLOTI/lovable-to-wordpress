# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2025-10-23

### Added
- Initial release of Lovable to WordPress plugin
- Export designs from Lovable to WordPress
- Full Elementor compatibility
- Component mapping system
- REST API endpoints for design management
- Support for animations and dynamic content
- Admin interface for managing exports
- Translation support (i18n)
- Comprehensive documentation

### Features
- Export Lovable designs as WordPress posts
- Automatic mapping to Elementor widgets
- Preserve styles and animations
- Support for custom post types
- Conflict detection with existing plugins
- Dry-run mode for testing exports
- Batch export functionality

### Security
- Input sanitization on all user inputs
- Nonce verification on all forms
- Capability checks on admin functions
- SQL injection protection with prepared statements
- XSS protection on output

---

## Format Guidelines

### Types of Changes

- **Added** for new features
- **Changed** for changes in existing functionality
- **Deprecated** for soon-to-be removed features
- **Removed** for now removed features
- **Fixed** for any bug fixes
- **Security** for security fixes

### Version Format

Follow Semantic Versioning: MAJOR.MINOR.PATCH
- MAJOR: Breaking changes
- MINOR: New features (backward compatible)
- PATCH: Bug fixes
