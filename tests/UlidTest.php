<?php

declare(strict_types=1);

use Webpatser\Ulid\Ulid;
use Webpatser\Uuid\Uuid;

it('generates valid 26-char crockford base32 ulid', function () {
    $ulid = Ulid::generate();
    $string = (string) $ulid;

    expect($string)->toBeString()->toHaveLength(26)
        ->and(preg_match('/^[0-9A-HJKMNP-TV-Z]{26}$/', $string))->toBe(1);
});

it('generates unique ulids', function () {
    $ulid1 = (string) Ulid::generate();
    $ulid2 = (string) Ulid::generate();

    expect($ulid1)->not->toBe($ulid2);
});

it('is monotonic within same millisecond', function () {
    $ulids = [];
    for ($i = 0; $i < 100; $i++) {
        $ulids[] = (string) Ulid::generate();
    }

    $sorted = $ulids;
    sort($sorted);

    expect($ulids)->toBe($sorted);
});

it('is lexicographically sortable across time', function () {
    $ulids = [];
    for ($i = 0; $i < 5; $i++) {
        if ($i > 0) {
            usleep(2000);
        }
        $ulids[] = (string) Ulid::generate();
    }

    $sorted = $ulids;
    sort($sorted);

    expect($ulids)->toBe($sorted);
});

it('extracts timestamp correctly', function () {
    $before = (int) (microtime(true) * 1000);
    $ulid = Ulid::generate();
    $after = (int) (microtime(true) * 1000);

    $timestampMs = $ulid->getTimestampMs();

    expect($timestampMs)->toBeGreaterThanOrEqual($before)
        ->and($timestampMs)->toBeLessThanOrEqual($after);
});

it('returns DateTimeInterface from getTimestamp', function () {
    $ulid = Ulid::generate();
    $timestamp = $ulid->getTimestamp();

    expect($timestamp)->toBeInstanceOf(DateTimeInterface::class);

    $diff = abs(time() - $timestamp->getTimestamp());
    expect($diff)->toBeLessThanOrEqual(1);
});

it('generates ulid with custom time', function () {
    $time = new DateTimeImmutable('2024-01-15 12:00:00.000');
    $ulid = Ulid::generate($time);

    $extracted = $ulid->getTimestamp();
    expect($extracted->format('Y-m-d H:i:s'))->toBe('2024-01-15 12:00:00');
});

it('converts to 16 bytes binary', function () {
    $ulid = Ulid::generate();
    $bytes = $ulid->toBytes();

    expect($bytes)->toBeString()
        ->and(strlen($bytes))->toBe(16);
});

it('roundtrips through string encoding', function () {
    $ulid = Ulid::generate();
    $string = (string) $ulid;
    $parsed = Ulid::fromString($string);

    expect((string) $parsed)->toBe($string)
        ->and($parsed->toBytes())->toBe($ulid->toBytes());
});

it('roundtrips through binary', function () {
    $ulid = Ulid::generate();
    $bytes = $ulid->toBytes();
    $restored = Ulid::fromBytes($bytes);

    expect((string) $restored)->toBe((string) $ulid);
});

it('converts ulid to uuid and back', function () {
    $ulid = Ulid::generate();
    $uuid = $ulid->toUuid();

    expect($uuid)->toBeInstanceOf(Uuid::class)
        ->and(Uuid::validate($uuid->string))->toBeTrue();

    $backToUlid = Ulid::fromUuid($uuid);
    expect((string) $backToUlid)->toBe((string) $ulid);
});

it('validates valid ulid strings', function () {
    expect(Ulid::isValid('01ARZ3NDEKTSV4RRFFQ69G5FAV'))->toBeTrue()
        ->and(Ulid::isValid('01arZ3ndektsv4rrffq69g5fav'))->toBeTrue();
});

it('rejects invalid ulid strings', function () {
    expect(Ulid::isValid(''))->toBeFalse()
        ->and(Ulid::isValid('not-a-ulid'))->toBeFalse()
        ->and(Ulid::isValid('01ARZ3NDEKTSV4RRFFQ69G5FA'))->toBeFalse()
        ->and(Ulid::isValid('01ARZ3NDEKTSV4RRFFQ69G5FAVU'))->toBeFalse()
        ->and(Ulid::isValid('I1ARZ3NDEKTSV4RRFFQ69G5FAV'))->toBeFalse()
        ->and(Ulid::isValid('L1ARZ3NDEKTSV4RRFFQ69G5FAV'))->toBeFalse()
        ->and(Ulid::isValid('O1ARZ3NDEKTSV4RRFFQ69G5FAV'))->toBeFalse();
});

it('rejects overflow ulid (first char > 7)', function () {
    expect(Ulid::isValid('81ARZ3NDEKTSV4RRFFQ69G5FAV'))->toBeFalse();
});

it('throws on invalid fromString input', function () {
    (void) Ulid::fromString('invalid');
})->throws(Exception::class, 'Invalid ULID string');

it('throws on invalid constructor bytes length', function () {
    (void) Ulid::fromBytes('too-short');
})->throws(Exception::class, 'Input must be a 128-bit');

it('generates 1000 ulids in sorted order', function () {
    $ulids = [];
    for ($i = 0; $i < 1000; $i++) {
        $ulids[] = (string) Ulid::generate();
    }

    $sorted = $ulids;
    sort($sorted);

    expect($ulids)->toBe($sorted);
});
