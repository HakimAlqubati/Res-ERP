<?php

declare(strict_types=1);

namespace Tests\Unit\HR\EmployeeDocuments;

use App\Casts\SafeJsonArrayCast;
use App\Models\EmployeeFile;
use PHPUnit\Framework\TestCase;

final class SafeJsonArrayCastTest extends TestCase
{
    public function test_normalizes_array_single_json_and_double_encoded_json(): void
    {
        $expected = ['passport_no' => 'P9988', 'expiry_date' => '2028-10-01'];

        $this->assertSame($expected, SafeJsonArrayCast::normalize($expected));
        $this->assertSame($expected, SafeJsonArrayCast::normalize(json_encode($expected)));
        $this->assertSame($expected, SafeJsonArrayCast::normalize(json_encode(json_encode($expected))));
        $this->assertNull(SafeJsonArrayCast::normalize(null));
        $this->assertNull(SafeJsonArrayCast::normalize(''));
        $this->assertNull(SafeJsonArrayCast::normalize('{invalid-json'));
    }

    public function test_set_always_produces_single_encoded_json(): void
    {
        $cast = new SafeJsonArrayCast();
        $model = new EmployeeFile();

        $doubleEncoded = json_encode(json_encode(['note' => 'renewed']));
        $stored = $cast->set($model, 'dynamic_field_values', $doubleEncoded, []);

        $this->assertSame('{"note":"renewed"}', $stored);
        $this->assertSame(['note' => 'renewed'], $cast->get($model, 'dynamic_field_values', $stored, []));
    }
}
