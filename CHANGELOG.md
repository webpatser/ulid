# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.0] - 2026-03-20

### Added
- Initial release of `webpatser/ulid`
- `Ulid::generate()` — monotonic ULID generation with 48-bit ms timestamp + 80-bit random
- `Ulid::fromString()` — parse 26-char Crockford Base32 strings
- `Ulid::fromBytes()` — create from 16-byte binary
- `Ulid::isValid()` — validate ULID format
- `Ulid::fromUuid()` / `$ulid->toUuid()` — bidirectional UUID conversion via `webpatser/uuid`
- `$ulid->getTimestamp()` — extract embedded timestamp as `DateTimeImmutable`
- `$ulid->getTimestampMs()` — raw millisecond timestamp
- `$ulid->toBytes()` — 16-byte binary representation
- Crockford Base32 encoding with static lookup tables
- Monotonic sub-millisecond ordering (increments random portion within same ms)
- `#[\NoDiscard]` attribute on all factory and query methods
- `Random\Randomizer` for cryptographic random bytes
- `readonly` property for immutability
- 17 Pest tests (37 assertions)

### Requirements
- PHP ^8.5
- `webpatser/uuid ^2.0`

[2.0.0]: https://github.com/webpatser/ulid/releases/tag/v2.0.0
