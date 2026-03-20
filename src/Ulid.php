<?php

declare(strict_types=1);

namespace Webpatser\Ulid;

use DateTimeImmutable;
use DateTimeInterface;
use Exception;
use Webpatser\Uuid\Uuid;

class Ulid
{
    private const ENCODE_ALPHABET = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';

    private const DECODE_MAP = [
        '0' => 0, '1' => 1, '2' => 2, '3' => 3, '4' => 4,
        '5' => 5, '6' => 6, '7' => 7, '8' => 8, '9' => 9,
        'A' => 10, 'B' => 11, 'C' => 12, 'D' => 13, 'E' => 14,
        'F' => 15, 'G' => 16, 'H' => 17, 'J' => 18, 'K' => 19,
        'M' => 20, 'N' => 21, 'P' => 22, 'Q' => 23, 'R' => 24,
        'S' => 25, 'T' => 26, 'V' => 27, 'W' => 28, 'X' => 29,
        'Y' => 30, 'Z' => 31,
    ];

    private static ?\Random\Randomizer $randomizer = null;

    private static int $lastTimestampMs = 0;

    private static string $lastRandomBytes = '';

    protected function __construct(
        protected readonly string $bytes,
    ) {
        if (strlen($bytes) !== 16) {
            throw new Exception('Input must be a 128-bit (16-byte) binary string.');
        }
    }

    private static function getRandomizer(): \Random\Randomizer
    {
        return self::$randomizer ??= new \Random\Randomizer;
    }

    #[\NoDiscard]
    public static function generate(?DateTimeInterface $time = null): self
    {
        if ($time !== null) {
            $timestampMs = (int) ($time->format('U.u') * 1000);
        } else {
            $timestampMs = (int) (microtime(true) * 1000);
        }

        if ($timestampMs === self::$lastTimestampMs && self::$lastRandomBytes !== '') {
            // Increment the 80-bit random portion for monotonicity
            $random = self::$lastRandomBytes;
            for ($i = 9; $i >= 0; $i--) {
                $val = ord($random[$i]) + 1;
                $random[$i] = chr($val & 0xFF);
                if ($val < 256) {
                    break;
                }
            }
        } else {
            $random = self::getRandomizer()->getBytes(10);
            self::$lastTimestampMs = $timestampMs;
        }

        self::$lastRandomBytes = $random;

        // Pack 48-bit timestamp as 6 bytes big-endian
        $timestampBytes = '';
        for ($i = 5; $i >= 0; $i--) {
            $timestampBytes .= chr(($timestampMs >> ($i * 8)) & 0xFF);
        }

        return new static($timestampBytes.$random);
    }

    #[\NoDiscard]
    public static function fromBytes(string $bytes): self
    {
        return new static($bytes);
    }

    #[\NoDiscard]
    public static function fromString(string $ulid): self
    {
        $ulid = strtoupper($ulid);

        if (! self::isValid($ulid)) {
            throw new Exception('Invalid ULID string.');
        }

        return new static(self::decode($ulid));
    }

    #[\NoDiscard]
    public static function isValid(string $ulid): bool
    {
        if (strlen($ulid) !== 26) {
            return false;
        }

        $ulid = strtoupper($ulid);

        for ($i = 0; $i < 26; $i++) {
            if (! isset(self::DECODE_MAP[$ulid[$i]])) {
                return false;
            }
        }

        // First character can only be 0-7 (3-bit value)
        if (self::DECODE_MAP[$ulid[0]] > 7) {
            return false;
        }

        return true;
    }

    public function __toString(): string
    {
        return self::encode($this->bytes);
    }

    #[\NoDiscard]
    public function toUuid(): Uuid
    {
        return Uuid::import(bin2hex($this->bytes));
    }

    #[\NoDiscard]
    public static function fromUuid(Uuid $uuid): self
    {
        return new static($uuid->bytes);
    }

    #[\NoDiscard]
    public function getTimestamp(): DateTimeInterface
    {
        $ms = $this->getTimestampMs();
        $seconds = intdiv($ms, 1000);
        $microseconds = ($ms % 1000) * 1000;

        return DateTimeImmutable::createFromFormat(
            'U u',
            sprintf('%d %06d', $seconds, $microseconds),
        );
    }

    #[\NoDiscard]
    public function getTimestampMs(): int
    {
        $ms = 0;
        for ($i = 0; $i < 6; $i++) {
            $ms = ($ms << 8) | ord($this->bytes[$i]);
        }

        return $ms;
    }

    #[\NoDiscard]
    public function toBytes(): string
    {
        return $this->bytes;
    }

    private static function encode(string $bytes): string
    {
        $alphabet = self::ENCODE_ALPHABET;

        $result = '';

        // First character: top 3 bits of byte 0
        $result .= $alphabet[(ord($bytes[0]) >> 5) & 0x07];

        // Remaining 25 characters: 5 bits each
        $bitPosition = 124;

        for ($i = 0; $i < 25; $i++) {
            $byteIndex = (128 - $bitPosition - 1) >> 3;
            $bitOffset = $bitPosition % 8;

            if ($bitOffset >= 4) {
                $value = (ord($bytes[$byteIndex]) >> ($bitOffset - 4)) & 0x1F;
            } else {
                $bitsFromCurrent = $bitOffset + 1;
                $bitsFromNext = 5 - $bitsFromCurrent;
                $value = (ord($bytes[$byteIndex]) & ((1 << $bitsFromCurrent) - 1)) << $bitsFromNext;
                if ($byteIndex + 1 < 16) {
                    $value |= (ord($bytes[$byteIndex + 1]) >> (8 - $bitsFromNext)) & ((1 << $bitsFromNext) - 1);
                }
            }

            $result .= $alphabet[$value & 0x1F];
            $bitPosition -= 5;
        }

        return $result;
    }

    private static function decode(string $ulid): string
    {
        $bytes = str_repeat("\0", 16);

        // First character contributes 3 bits to the top of byte 0
        $bytes[0] = chr(self::DECODE_MAP[$ulid[0]] << 5);

        $bitPosition = 124;

        for ($i = 1; $i < 26; $i++) {
            $value = self::DECODE_MAP[$ulid[$i]];

            $byteIndex = (128 - $bitPosition - 1) >> 3;
            $bitOffset = $bitPosition % 8;

            if ($bitOffset >= 4) {
                $bytes[$byteIndex] = chr(ord($bytes[$byteIndex]) | (($value & 0x1F) << ($bitOffset - 4)));
            } else {
                $bitsFromCurrent = $bitOffset + 1;
                $bitsFromNext = 5 - $bitsFromCurrent;
                $bytes[$byteIndex] = chr(ord($bytes[$byteIndex]) | (($value >> $bitsFromNext) & ((1 << $bitsFromCurrent) - 1)));
                if ($byteIndex + 1 < 16) {
                    $bytes[$byteIndex + 1] = chr(ord($bytes[$byteIndex + 1]) | (($value & ((1 << $bitsFromNext) - 1)) << (8 - $bitsFromNext)));
                }
            }

            $bitPosition -= 5;
        }

        return $bytes;
    }
}
