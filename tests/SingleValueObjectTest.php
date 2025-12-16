<?php

declare(strict_types=1);

namespace Tests\Unit\Objects;

use LogicException;
use PHPUnit\Framework\TestCase;
use ReallifeKip\ImmutableBase\Objects\SingleValueObject;
use ReallifeKip\ImmutableBase\Exceptions\ValidationChainException;
use ReallifeKip\ImmutableBase\Exceptions\InvalidCompareTargetException;

/**
 * @covers \ReallifeKip\ImmutableBase\Objects\SingleValueObject
 * @covers \ReallifeKip\ImmutableBase\Objects\ValueObject
 */
final class SingleValueObjectTest extends TestCase
{
    // --- 1. 基本型別與實例化測試 ---

    public function test_can_instantiate_from_string(): void
    {
        $vo = StringSVO::from('test_value');

        $this->assertInstanceOf(SingleValueObject::class, $vo);
        $this->assertSame('test_value', $vo());
        $this->assertSame('test_value', $vo->value);
    }

    public function test_can_instantiate_from_int(): void
    {
        $vo = IntSVO::from(123);

        $this->assertSame(123, $vo());
        $this->assertSame(123, $vo->value);
    }

    public function test_can_instantiate_from_float(): void
    {
        $vo = FloatSVO::from(12.34);

        $this->assertSame(12.34, $vo());
    }

    public function test_can_instantiate_from_bool(): void
    {
        $vo = BoolSVO::from(true);

        $this->assertTrue($vo());
    }

    // --- 2. 魔術方法測試 ---

    public function test_to_string_returns_string_casted_value(): void
    {
        $intVo = IntSVO::from(999);
        $this->assertSame('999', (string) $intVo);

        $boolVo = BoolSVO::from(true);
        $this->assertSame('1', (string) $boolVo);
    }

    public function test_invoke_returns_raw_value(): void
    {
        $vo = StringSVO::from('hello');
        $this->assertSame('hello', $vo());
    }

    public function test_get_accessor_returns_value(): void
    {
        $vo = StringSVO::from('data');
        $this->assertSame('data', $vo->value);
    }

    public function test_get_accessor_throws_logic_exception_for_unknown_property(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Single value object only have property 'value'.");

        $vo = StringSVO::from('data');
        /** @noinspection PhpExpressionResultUnusedInspection */
        $vo->undefinedProperty;
    }

    // --- 3. 相等性 (Equals) 測試 ---

    public function test_equals_returns_true_for_same_value(): void
    {
        $vo1 = StringSVO::from('foo');
        $vo2 = StringSVO::from('foo');

        $this->assertTrue($vo1->equals($vo2));
    }

    public function test_equals_returns_false_for_different_value(): void
    {
        $vo1 = StringSVO::from('foo');
        $vo2 = StringSVO::from('bar');

        $this->assertFalse($vo1->equals($vo2));
    }

    public function test_equals_throws_exception_when_comparing_different_classes(): void
    {
        $this->expectException(InvalidCompareTargetException::class);
        $this->expectExceptionMessage('equals() expects an instance of ' . StringSVO::class);

        $vo1 = StringSVO::from('123');
        $vo2 = IntSVO::from(123); // 雖然數值轉字串可能相同，但類別不同

        // @phpstan-ignore-next-line
        $vo1->equals($vo2);
    }

    public function test_equals_throws_exception_when_comparing_non_object(): void
    {
        $this->expectException(InvalidCompareTargetException::class);

        $vo = StringSVO::from('test');
        // @phpstan-ignore-next-line
        $vo->equals('test');
    }

    // --- 4. 驗證邏輯 (Validation) 測試 ---
    // 註：這部分測試 SingleValueObject 如何與 ValueObject 的驗證鏈整合

    public function test_validation_passes_for_valid_data(): void
    {
        $email = ValidatedEmailSVO::from('test@example.com');
        $this->assertSame('test@example.com', $email());
    }

    public function test_validation_throws_exception_for_invalid_data(): void
    {
        $this->expectException(ValidationChainException::class);
        // 預期錯誤訊息包含值和類別名稱，這是 SingleValueObject 特有的錯誤格式
        $this->expectExceptionMessage("'invalid-email' did not pass validation for " . ValidatedEmailSVO::class);

        ValidatedEmailSVO::from('invalid-email');
    }

    public function test_validation_exception_includes_custom_message(): void
    {
        $this->expectException(ValidationChainException::class);
        $this->expectExceptionMessage("Reason: Invalid email format");

        ValidatedEmailSVOWithMsg::from('bad-input');
    }
}

// --- 測試用的具體類別 (Test Fixtures) ---

class StringSVO extends SingleValueObject
{
    public function validate(): bool
    {
        return true;
    }
}

class IntSVO extends SingleValueObject
{
    public function validate(): bool
    {
        return true;
    }
}

class FloatSVO extends SingleValueObject
{
    public function validate(): bool
    {
        return true;
    }
}

class BoolSVO extends SingleValueObject
{
    public function validate(): bool
    {
        return true;
    }
}

class ValidatedEmailSVO extends SingleValueObject
{
    public function validate(): bool
    {
        return str_contains((string)$this->value, '@');
    }
}

class ValidatedEmailSVOWithMsg extends SingleValueObject
{
    public static string $validateErrorMessage = 'Invalid email format';

    public function validate(): bool
    {
        return false; // 總是失敗以測試訊息
    }
}
