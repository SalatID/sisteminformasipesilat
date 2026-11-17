<?php

namespace Tests\Unit\Support;

use App\Support\PhoneNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PhoneNormalizerTest extends TestCase
{
    #[DataProvider('validNumbers')]
    public function test_normalizes_to_62_format(string $input, string $expected): void
    {
        $this->assertSame($expected, PhoneNormalizer::normalize($input));
        $this->assertTrue(PhoneNormalizer::isValidInput($input));
    }

    public static function validNumbers(): array
    {
        return [
            'format lokal 0' => ['081234567890', '6281234567890'],
            'format internasional +62' => ['+6281234567890', '6281234567890'],
            'format 62 polos' => ['6281234567890', '6281234567890'],
            'dengan spasi & strip' => ['62 812-3456-789', '628123456789'],
            'spasi di tepi' => ['  081298765432  ', '6281298765432'],
        ];
    }

    #[DataProvider('invalidNumbers')]
    public function test_rejects_invalid_numbers(?string $input): void
    {
        $this->assertFalse(PhoneNormalizer::isValidInput($input));
    }

    public static function invalidNumbers(): array
    {
        return [
            'huruf' => ['abc'],
            'terlalu pendek' => ['12345'],
            'lokal terlalu pendek' => ['0812'],
            'bukan indonesia' => ['15551234567'],
        ];
    }

    public function test_empty_input_is_allowed_and_normalizes_to_null(): void
    {
        $this->assertTrue(PhoneNormalizer::isValidInput(''));
        $this->assertTrue(PhoneNormalizer::isValidInput(null));
        $this->assertNull(PhoneNormalizer::normalize(''));
        $this->assertNull(PhoneNormalizer::normalize(null));
    }

    public function test_canonical_62_number_is_stable(): void
    {
        $once = PhoneNormalizer::normalize('081234567890');
        $this->assertSame($once, PhoneNormalizer::normalize($once));
    }
}
