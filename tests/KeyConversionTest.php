<?php

declare (strict_types = 1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use ReallifeKip\ImmutableBase\Enums\KeyCase;
use ReallifeKip\ImmutableBase\Exceptions\DefinitionExceptions\InvalidKeyCaseException;
use ReallifeKip\ImmutableBase\Exceptions\InitializationExceptions\RequiredValueException;
use ReallifeKip\ImmutableBase\ImmutableBase;
use Tests\DataTransferObjects\KeyConversion\AllInputKeyCasesDTO;
use Tests\DataTransferObjects\KeyConversion\AllOutputKeyCasesDTO;
use Tests\DataTransferObjects\KeyConversion\BrokenMacroPropertyDTO;
use Tests\DataTransferObjects\KeyConversion\ClassInputCamelDTO;
use Tests\DataTransferObjects\KeyConversion\ClassOutputSnakeDTO;
use Tests\DataTransferObjects\KeyConversion\HeadersMimicDTO;
use Tests\DataTransferObjects\KeyConversion\InvalidKeyCaseDTO;
use Tests\DataTransferObjects\KeyConversion\MixedInputKeyDTO;
use Tests\DataTransferObjects\KeyConversion\MixedOutputKeyDTO;
use Tests\DataTransferObjects\KeyConversion\NestedOutputParentDTO;
use Tests\DataTransferObjects\KeyConversion\PropertyInputKeyDTO;
use Tests\DataTransferObjects\KeyConversion\PropertyOutputKeyDTO;
use Tests\DataTransferObjects\KeyConversion\RoundTripDTO;

ImmutableBase::loadCache();

class KeyConversionTest extends TestCase
{
    public function setup(): void
    {
        $s               = &ImmutableBase::state();
        $s['cachedMeta'] = [];
    }

    // -------------------------------------------------------------------------
    // InputKeyTo — class-level
    // -------------------------------------------------------------------------

    public function testClassLevelInputKeyCamelFromArray(): void
    {
        $dto = ClassInputCamelDTO::fromArray([
            'first_name' => 'John',
            'last_name'  => 'Doe',
            'user_age'   => 30,
        ]);

        $this->assertSame('John', $dto->firstName);
        $this->assertSame('Doe', $dto->lastName);
        $this->assertSame(30, $dto->userAge);
    }

    public function testClassLevelInputKeyCamelFromJson(): void
    {
        $dto = ClassInputCamelDTO::fromJson(json_encode([
            'first_name' => 'Jane',
            'last_name'  => 'Smith',
            'user_age'   => 25,
        ]));

        $this->assertSame('Jane', $dto->firstName);
        $this->assertSame('Smith', $dto->lastName);
        $this->assertSame(25, $dto->userAge);
    }

    public function testClassLevelInputKeyAlreadyInTargetCase(): void
    {
        // Properties are already camelCase — passing camelCase input also works
        $dto = ClassInputCamelDTO::fromArray([
            'firstName' => 'Bob',
            'lastName'  => 'Brown',
            'userAge'   => 40,
        ]);

        $this->assertSame('Bob', $dto->firstName);
        $this->assertSame('Brown', $dto->lastName);
        $this->assertSame(40, $dto->userAge);
    }

    // -------------------------------------------------------------------------
    // InputKeyTo — property-level
    // Semantic: #[InputKeyTo(X)] on property $propName converts each input key
    // to case X. If the result equals $propName, the value is assigned.
    // Therefore the property name MUST already be in case X format.
    // -------------------------------------------------------------------------

    public function testPropertyLevelInputKeyConvertsSnakeInputToCamel(): void
    {
        // $firstName is Camel → InputKeyTo(Camel) accepts first_name (snake → Camel → firstName)
        // $last_name is Snake → InputKeyTo(Snake) accepts lastName (camel → Snake → last_name)
        $dto = PropertyInputKeyDTO::fromArray([
            'first_name' => 'Alice',
            'lastName'   => 'Smith',
            'city'       => 'NY',
        ]);

        $this->assertSame('Alice', $dto->firstName);
        $this->assertSame('Smith', $dto->last_name);
        $this->assertSame('NY', $dto->city);
    }

    public function testPropertyLevelInputKeyAcceptsKebabInput(): void
    {
        // Kebab input also accepted since kebab → Camel → firstName, kebab → Snake → last_name
        $dto = PropertyInputKeyDTO::fromArray([
            'first-name' => 'Bob',
            'last-name'  => 'Jones',
            'city'       => 'LA',
        ]);

        $this->assertSame('Bob', $dto->firstName);
        $this->assertSame('Jones', $dto->last_name);
    }

    public function testPropertyLevelInputKeyAcceptsMacroInput(): void
    {
        // Macro input: FIRST_NAME → Camel → firstName, LAST_NAME → Snake → last_name
        $dto = PropertyInputKeyDTO::fromArray([
            'FIRST_NAME' => 'Carol',
            'LAST_NAME'  => 'White',
            'city'       => 'Chicago',
        ]);

        $this->assertSame('Carol', $dto->firstName);
        $this->assertSame('White', $dto->last_name);
    }

    public function testPropertyLevelInputKeyNoConversionForPlainProperty(): void
    {
        // city has no InputKeyTo — only the exact key 'city' is accepted
        $dto = PropertyInputKeyDTO::fromArray([
            'first_name' => 'Test',
            'lastName'   => 'User',
            'city'       => 'Tokyo',
        ]);

        $this->assertSame('Tokyo', $dto->city);
    }

    public function testAllInputKeyCaseVariants(): void
    {
        // Each input format → convertCase(inputKey, X) → property name
        $dto = AllInputKeyCasesDTO::fromArray([
            'snakeInput'         => 's', // camel → Snake → snake_input
            'camel_input'        => 'c', // snake → Camel → camelInput
            'pascal_input'       => 'p', // snake → Pascal → PascalInput
            'macroInput'         => 'm', // camel → Macro → MACRO_INPUT
            'pascal_snake_input' => 'ps', // snake → PascalSnake → Pascal_Snake_Input
        ]);

        $this->assertSame('s', $dto->snake_input);
        $this->assertSame('c', $dto->camelInput);
        $this->assertSame('p', $dto->PascalInput);
        $this->assertSame('m', $dto->MACRO_INPUT);
        $this->assertSame('ps', $dto->Pascal_Snake_Input);
    }

    // -------------------------------------------------------------------------
    // InputKeyTo — property-level overrides class-level
    // -------------------------------------------------------------------------

    public function testPropertyLevelOverridesClassLevel(): void
    {
        // Class Snake: userName → user_name ✓
        // Property Macro on $USER_ID: userId → Macro → USER_ID ✓
        //   Without property-level, class Snake would give user_id ≠ USER_ID → fail
        $dto = MixedInputKeyDTO::fromArray([
            'userName' => 'john',
            'userId'   => 'u-123',
        ]);

        $this->assertSame('john', $dto->user_name);
        $this->assertSame('u-123', $dto->USER_ID);
    }

    public function testPropertyLevelAcceptsMultipleInputFormatsForMacroProperty(): void
    {
        // Any input format whose words are "user" + "id" → Macro → USER_ID
        foreach (['userId', 'user_id', 'user-id', 'User-Id'] as $inputKey) {
            $s               = &ImmutableBase::state();
            $s['cachedMeta'] = [];
            $dto             = MixedInputKeyDTO::fromArray(['user_name' => 'test', $inputKey => 'u-999']);
            $this->assertSame('u-999', $dto->USER_ID, "Input key '$inputKey' should map to USER_ID");
        }
    }

    // -------------------------------------------------------------------------
    // InputKeyTo — with() respects key conversion
    // -------------------------------------------------------------------------

    public function testWithMethodRespectsClassLevelInputKey(): void
    {
        $original = ClassInputCamelDTO::fromArray([
            'first_name' => 'John',
            'last_name'  => 'Doe',
            'user_age'   => 30,
        ]);

        $updated = $original->with([
            'first_name' => 'Jane',
            'user_age'   => 31,
        ]);

        $this->assertSame('Jane', $updated->firstName);
        $this->assertSame('Doe', $updated->lastName);
        $this->assertSame(31, $updated->userAge);
    }

    public function testWithMethodRespectsPropertyLevelInputKey(): void
    {
        $original = PropertyInputKeyDTO::fromArray([
            'first_name' => 'Alice',
            'lastName'   => 'Smith',
            'city'       => 'Oxford',
        ]);

        // Any format that converts to the property name works in with() too
        $updated = $original->with([
            'first-name' => 'Bob', // kebab → Camel → firstName
            'LAST_NAME'  => 'Brown', // macro → Snake → last_name
        ]);

        $this->assertSame('Bob', $updated->firstName);
        $this->assertSame('Brown', $updated->last_name);
        $this->assertSame('Oxford', $updated->city);
    }

    public function testWithMethodRespectsPropertyOverrideOfClassLevel(): void
    {
        $original = MixedInputKeyDTO::fromArray([
            'user_name' => 'john',
            'userId'    => 'u-123',
        ]);

        $updated = $original->with([
            'userName' => 'jane', // → class Snake → user_name
            'user-id'  => 'u-456', // → property Macro → USER_ID
        ]);

        $this->assertSame('jane', $updated->user_name);
        $this->assertSame('u-456', $updated->USER_ID);
    }

    // -------------------------------------------------------------------------
    // OutputKeyTo — toArray(false) default: no conversion
    // -------------------------------------------------------------------------

    public function testToArrayFalseIgnoresOutputKeyTo(): void
    {
        $dto = ClassOutputSnakeDTO::fromArray([
            'firstName' => 'John',
            'lastName'  => 'Doe',
            'userAge'   => 30,
        ]);

        $this->assertSame([
            'firstName' => 'John',
            'lastName'  => 'Doe',
            'userAge'   => 30,
        ], $dto->toArray());
    }

    public function testToArrayFalseExplicitIgnoresOutputKeyTo(): void
    {
        $dto = ClassOutputSnakeDTO::fromArray([
            'firstName' => 'John',
            'lastName'  => 'Doe',
            'userAge'   => 30,
        ]);

        $this->assertSame([
            'firstName' => 'John',
            'lastName'  => 'Doe',
            'userAge'   => 30,
        ], $dto->toArray(false));
    }

    // -------------------------------------------------------------------------
    // OutputKeyTo — toArray(true): use #[OutputKeyTo] per class/property
    // -------------------------------------------------------------------------

    public function testToArrayTrueUsesClassLevelOutputKey(): void
    {
        $dto = ClassOutputSnakeDTO::fromArray([
            'firstName' => 'John',
            'lastName'  => 'Doe',
            'userAge'   => 30,
        ]);

        $this->assertSame([
            'first_name' => 'John',
            'last_name'  => 'Doe',
            'user_age'   => 30,
        ], $dto->toArray(true));
    }

    public function testToJsonTrueUsesOutputKey(): void
    {
        $dto = ClassOutputSnakeDTO::fromArray([
            'firstName' => 'John',
            'lastName'  => 'Doe',
            'userAge'   => 30,
        ]);

        $this->assertSame(
            '{"first_name":"John","last_name":"Doe","user_age":30}',
            $dto->toJson(true)
        );
    }

    public function testToArrayTrueUsesPropertyLevelOutputKey(): void
    {
        $dto = PropertyOutputKeyDTO::fromArray([
            'firstName' => 'Alice',
            'lastName'  => 'Smith',
        ]);

        $result = $dto->toArray(true);

        // firstName has OutputKeyTo(Snake) → first_name
        $this->assertArrayHasKey('first_name', $result);
        $this->assertSame('Alice', $result['first_name']);

        // lastName has no OutputKeyTo → key unchanged
        $this->assertArrayHasKey('lastName', $result);
        $this->assertSame('Smith', $result['lastName']);
    }

    public function testToArrayTruePropertyLevelOutputKeyNoConversionForOthers(): void
    {
        $dto = PropertyOutputKeyDTO::fromArray([
            'firstName' => 'Alice',
            'lastName'  => 'Smith',
        ]);

        $result = $dto->toArray(true);
        $this->assertArrayNotHasKey('firstName', $result);
        $this->assertArrayNotHasKey('last_name', $result);
    }

    public function testPropertyLevelOutputKeyOverridesClassLevel(): void
    {
        $dto = MixedOutputKeyDTO::fromArray([
            'firstName' => 'John',
            'nickName'  => 'johnny',
        ]);

        $result = $dto->toArray(true);

        // firstName: class-level Snake → first_name
        $this->assertArrayHasKey('first_name', $result);
        $this->assertSame('John', $result['first_name']);

        // nickName: property-level Pascal overrides class Snake → NickName
        $this->assertArrayHasKey('NickName', $result);
        $this->assertSame('johnny', $result['NickName']);
    }

    public function testAllOutputKeyCaseVariants(): void
    {
        $dto = AllOutputKeyCasesDTO::fromArray([
            'snakeProp'       => 'a',
            'camelProp'       => 'b',
            'pascalProp'      => 'c',
            'macroProp'       => 'd',
            'kebabProp'       => 'e',
            'trainProp'       => 'f',
            'pascalSnakeProp' => 'g',
            'camelKebabProp'  => 'h',
        ]);

        $this->assertSame([
            'snake_prop'        => 'a',
            'camelProp'         => 'b',
            'PascalProp'        => 'c',
            'MACRO_PROP'        => 'd',
            'kebab-prop'        => 'e',
            'Train-Prop'        => 'f',
            'Pascal_Snake_Prop' => 'g',
            'camel-Kebab-Prop'  => 'h',
        ], $dto->toArray(true));
    }

    // -------------------------------------------------------------------------
    // OutputKeyTo — toArray(KeyCase::*): forced case overrides #[OutputKeyTo]
    // -------------------------------------------------------------------------

    public function testToArrayForcedKeyCaseOverridesOutputKeyTo(): void
    {
        // ClassOutputSnakeDTO has #[OutputKeyTo(Snake)], but toArray(KeyCase::Pascal)
        // forces Pascal on all keys, ignoring the attribute.
        $dto = ClassOutputSnakeDTO::fromArray([
            'firstName' => 'John',
            'lastName'  => 'Doe',
            'userAge'   => 30,
        ]);

        $this->assertSame([
            'FirstName' => 'John',
            'LastName'  => 'Doe',
            'UserAge'   => 30,
        ], $dto->toArray(KeyCase::Pascal));
    }

    public function testToArrayForcedMacroOverridesOutputKeyTo(): void
    {
        $dto = MixedOutputKeyDTO::fromArray([
            'firstName' => 'John',
            'nickName'  => 'johnny',
        ]);

        $this->assertSame([
            'FIRST_NAME' => 'John',
            'NICK_NAME'  => 'johnny',
        ], $dto->toArray(KeyCase::Macro));
    }

    // -------------------------------------------------------------------------
    // OutputKeyTo — nested DTOs
    // -------------------------------------------------------------------------

    public function testNestedToArrayFalseNoConversion(): void
    {
        $dto = NestedOutputParentDTO::fromArray([
            'parentName' => 'Parent',
            'childItem'  => ['childField' => 'Child'],
        ]);

        $this->assertSame([
            'parentName' => 'Parent',
            'childItem'  => ['childField' => 'Child'],
        ], $dto->toArray(false));
    }

    public function testNestedToArrayTrueEachLayerUsesOwnOutputKeyTo(): void
    {
        // Parent: OutputKeyTo(Snake) → parent_name, child_item
        // Child:  OutputKeyTo(Macro) → CHILD_FIELD
        $dto = NestedOutputParentDTO::fromArray([
            'parentName' => 'Parent',
            'childItem'  => ['childField' => 'Child'],
        ]);

        $this->assertSame([
            'parent_name' => 'Parent',
            'child_item'  => ['CHILD_FIELD' => 'Child'],
        ], $dto->toArray(true));
    }

    public function testNestedToArrayForcedKeyCasePropagatesAcrossLayers(): void
    {
        // toArray(KeyCase::Camel) forces camelCase at every nesting level
        $dto = NestedOutputParentDTO::fromArray([
            'parentName' => 'Parent',
            'childItem'  => ['childField' => 'Child'],
        ]);

        $this->assertSame([
            'parentName' => 'Parent',
            'childItem'  => ['childField' => 'Child'],
        ], $dto->toArray(KeyCase::Camel));
    }

    public function testNestedToArrayForcedSnakePropagates(): void
    {
        $dto = NestedOutputParentDTO::fromArray([
            'parentName' => 'Parent',
            'childItem'  => ['childField' => 'Child'],
        ]);

        $this->assertSame([
            'parent_name' => 'Parent',
            'child_item'  => ['child_field' => 'Child'],
        ], $dto->toArray(KeyCase::Snake));
    }

    // -------------------------------------------------------------------------
    // Combined InputKeyTo + OutputKeyTo round-trip
    // -------------------------------------------------------------------------

    public function testRoundTripSnakeCaseInputOutput(): void
    {
        $input = [
            'first_name' => 'John',
            'last_name'  => 'Doe',
        ];

        $dto = RoundTripDTO::fromArray($input);

        // Properties hold camelCase values internally
        $this->assertSame('John', $dto->firstName);
        $this->assertSame('Doe', $dto->lastName);

        // toArray(true) re-serializes to snake_case
        $this->assertSame($input, $dto->toArray(true));
    }

    public function testRoundTripToArrayFalseReturnsCamelKeys(): void
    {
        $dto = RoundTripDTO::fromArray([
            'first_name' => 'John',
            'last_name'  => 'Doe',
        ]);

        $this->assertSame([
            'firstName' => 'John',
            'lastName'  => 'Doe',
        ], $dto->toArray(false));
    }

    public function testRoundTripForcedOutputOverridesOutputKeyTo(): void
    {
        $dto = RoundTripDTO::fromArray([
            'first_name' => 'John',
            'last_name'  => 'Doe',
        ]);

        // toArray(KeyCase::Macro) overrides the OutputKeyTo(Snake) attribute
        $this->assertSame([
            'FIRST_NAME' => 'John',
            'LAST_NAME'  => 'Doe',
        ], $dto->toArray(KeyCase::Macro));
    }

    // -------------------------------------------------------------------------
    // Edge case: MACRO_CASE property names with class-level InputKeyTo(Snake)
    //
    // Reproduces the index.php Headers pattern where:
    //   - input keys are kebab-case HTTP header names
    //   - class-level InputKeyTo(Snake) converts kebab → snake for snake_case props
    //   - but MACRO_CASE property names (SEC_CH_UA_MOBILE) are unreachable via
    //     the Snake conversion alone and require explicit property-level annotation
    // -------------------------------------------------------------------------

    public function testMacroCasePropertyWithoutAnnotationFailsUnderSnakeClassConversion(): void
    {
        // Class-level Snake converts 'sec-ch-ua-mobile' → 'sec_ch_ua_mobile'
        // but the property is named SEC_CH_UA_MOBILE — these don't match.
        // Without a property-level InputKeyTo on SEC_CH_UA_MOBILE this must fail.
        $this->expectException(RequiredValueException::class);
        $this->expectExceptionMessage("'SEC_CH_UA_MOBILE'");

        BrokenMacroPropertyDTO::fromArray([
            'cache-control'    => 'no-cache',
            'sec-ch-ua-mobile' => '?0',
        ]);
    }

    public function testMacroCasePropertyWithKebabAnnotationAcceptsKebabInput(): void
    {
        // HeadersMimicDTO adds #[InputKeyTo(Kebab)] on SEC_CH_UA_MOBILE:
        //   inputKey = convertCase('SEC_CH_UA_MOBILE', Kebab) = 'sec-ch-ua-mobile'
        // The inputKeyMap then reads 'sec-ch-ua-mobile' from the ORIGINAL input
        // and assigns it to $data['SEC_CH_UA_MOBILE'], bypassing the class-level
        // Snake conversion that would have produced 'sec_ch_ua_mobile'.
        $dto = HeadersMimicDTO::fromArray([
            'cache-control'      => 'no-cache',
            'sec-ch-ua'          => 'Chrome',
            'sec-ch-ua-mobile'   => '?0',
            'sec-ch-ua-platform' => 'Windows',
        ]);

        $this->assertSame('no-cache', $dto->cache_control);
        $this->assertSame('Chrome', $dto->sec_ch_ua);
        $this->assertSame('?0', $dto->SEC_CH_UA_MOBILE);
        $this->assertSame('Windows', $dto->sec_ch_ua_platform);
    }

    public function testMacroCasePropertyOutputKeyTo(): void
    {
        $dto = HeadersMimicDTO::fromArray([
            'cache-control'      => 'no-cache',
            'sec-ch-ua'          => 'Chrome',
            'sec-ch-ua-mobile'   => '?0',
            'sec-ch-ua-platform' => 'Windows',
        ]);

        // Class-level OutputKeyTo(PascalSnake) applied to all props:
        //   cache_control      → Cache_Control
        //   sec_ch_ua          → Sec_Ch_Ua
        //   SEC_CH_UA_MOBILE   → Sec_Ch_Ua_Mobile
        //   sec_ch_ua_platform → Sec_Ch_Ua_Platform
        $this->assertSame([
            'Cache_Control'      => 'no-cache',
            'Sec_Ch_Ua'          => 'Chrome',
            'Sec_Ch_Ua_Mobile'   => '?0',
            'Sec_Ch_Ua_Platform' => 'Windows',
        ], $dto->toArray(true));
    }

    public function testPropertyLevelInputKeyKebabOverridesClassSnakeForMacroProperty(): void
    {
        // The property-level #[InputKeyTo(Kebab)] on SEC_CH_UA_MOBILE reads
        // from ORIGINAL input (pre-class-conversion) so it wins even though
        // the class Snake would have produced a different key (sec_ch_ua_mobile).
        $dto = HeadersMimicDTO::fromArray([
            'cache-control'      => 'no-cache',
            'sec-ch-ua'          => 'Chrome',
            'sec-ch-ua-mobile'   => 'actual-value',
            'sec-ch-ua-platform' => 'Windows',
        ]);

        // Without property-level Kebab, SEC_CH_UA_MOBILE would throw RequiredValueException.
        // With it, the correct kebab key is found in original data.
        $this->assertSame('actual-value', $dto->SEC_CH_UA_MOBILE);
    }

    public function testWrongKeyCaseInInputKeyToThrowsException(): void
    {
        $this->expectException(InvalidKeyCaseException::class);
        $this->expectExceptionMessage("Invalid key case 'Camel' in #[InputKeyTo] on Tests\DataTransferObjects\KeyConversion\InvalidKeyCaseDTO::class");

        InvalidKeyCaseDTO::fromArray([
            'nick_name' => 'test',
        ]);
    }
}
