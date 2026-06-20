# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-06-20

### Added

- Standalone `bladcn` CLI binary and Laravel Artisan commands (`bladcn:init`, `bladcn:list`, `bladcn:add`, `bladcn:remove`)
- Component install/remove with transitive dependency resolution from registry `dependencies.json`
- Registry sources: local path, `github:owner/repo`, GitHub URL, or `package:ailuracode/bladcn`
- CSS/JS asset publishing and Composer package management for external dependencies
- `bladcn.json` schema and init stubs publisher

[1.0.0]: https://github.com/ailuracode/bladcn-cli/releases/tag/v1.0.0
