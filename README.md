# ULID Library for PHP

[![Latest Version](https://img.shields.io/packagist/v/webpatser/ulid.svg)](https://packagist.org/packages/webpatser/ulid)
[![Total Downloads](https://img.shields.io/packagist/dt/webpatser/ulid.svg)](https://packagist.org/packages/webpatser/ulid)
[![License](https://img.shields.io/packagist/l/webpatser/ulid.svg)](https://packagist.org/packages/webpatser/ulid)

A pure PHP library to generate and validate ULIDs (Universally Unique Lexicographically Sortable Identifiers). Crockford Base32 encoded, monotonic, with UUID interop via [webpatser/uuid](https://github.com/webpatser/uuid).

**Requirements:** PHP ^8.5 (no extensions required)

## Installation

```bash
composer require webpatser/ulid
```

## Quick Start

```php
use Webpatser\Ulid\Ulid;

// Generate a ULID
$ulid = Ulid::generate();
echo (string) $ulid; // e.g., "01ARZ3NDEKTSV4RRFFQ69G5FAV"

// Generate with a specific timestamp
$ulid = Ulid::generate(new DateTimeImmutable('2024-01-15 12:00:00'));

// Parse an existing ULID
$ulid = Ulid::fromString('01ARZ3NDEKTSV4RRFFQ69G5FAV');

// Validate
Ulid::isValid('01ARZ3NDEKTSV4RRFFQ69G5FAV'); // true
Ulid::isValid('not-a-ulid');                   // false
```

## API Reference

### Generation

| Method | Description |
|--------|-------------|
| `Ulid::generate(?DateTimeInterface $time = null)` | Generate monotonic ULID (optionally with specific timestamp) |
| `Ulid::fromString(string $ulid)` | Parse a 26-char Crockford Base32 string |
| `Ulid::fromBytes(string $bytes)` | Create from 16-byte binary |
| `Ulid::isValid(string $ulid)` | Validate ULID format |

### Instance Methods

| Method | Description |
|--------|-------------|
| `(string) $ulid` | 26-char Crockford Base32 string |
| `$ulid->toBytes()` | 16-byte binary representation |
| `$ulid->getTimestamp()` | `DateTimeInterface` from embedded timestamp |
| `$ulid->getTimestampMs()` | Raw millisecond timestamp (int) |
| `$ulid->toUuid()` | Convert to `Webpatser\Uuid\Uuid` (same 128 bits) |

### UUID Interop

```php
use Webpatser\Ulid\Ulid;
use Webpatser\Uuid\Uuid;

// ULID → UUID (same 128-bit value, different encoding)
$ulid = Ulid::generate();
$uuid = $ulid->toUuid();
echo $uuid->string; // "019d05e8-dfa1-7009-a8f7-4e1c868ccfa4"

// UUID → ULID
$uuid = Uuid::v7();
$ulid = Ulid::fromUuid($uuid);
echo (string) $ulid; // "01ARZ3NDEKTSV4RRFFQ69G5FAV"
```

### Binary Storage

ULIDs are 16 bytes in binary, same as UUIDs. Ideal for `BINARY(16)` database columns:

```php
$binary = $ulid->toBytes();            // 16 bytes for storage
$ulid   = Ulid::fromBytes($binary);    // Restore from binary
```

### Timestamp Extraction

```php
$ulid = Ulid::generate();
$timestamp = $ulid->getTimestamp();     // DateTimeImmutable
$ms = $ulid->getTimestampMs();          // e.g., 1773920640936
```

## ULID Specification

- **26 characters**, Crockford Base32 encoded (0-9, A-H, J-K, M-N, P-T, V-Z)
- **48-bit millisecond timestamp** (first 10 characters) + **80-bit randomness** (last 16 characters)
- **Monotonic**: multiple ULIDs within the same millisecond increment the random portion
- **Lexicographically sortable**: string comparison preserves chronological order
- **Binary compatible**: 16 bytes, same as UUID; fits in `BINARY(16)` columns
- **Case insensitive**: parsing accepts both upper and lowercase

## Features

- Monotonic generation with sub-millisecond ordering
- Crockford Base32 with static lookup tables (no runtime computation)
- `#[\NoDiscard]` attribute on all factory and query methods
- `Random\Randomizer` for cryptographic random bytes
- `readonly` properties for immutability
- Bidirectional UUID conversion via `webpatser/uuid`
- Zero additional dependencies beyond `webpatser/uuid`

## License

MIT License.
