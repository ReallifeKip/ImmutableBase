<?php

namespace Tests;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;
use ReallifeKip\ImmutableBase\Attributes\ArrayOf;
use ReallifeKip\ImmutableBase\Attributes\Defaults;
use ReallifeKip\ImmutableBase\Attributes\KeepOnNull;
use ReallifeKip\ImmutableBase\Attributes\Lax;
use ReallifeKip\ImmutableBase\Attributes\SkipOnNull;
use ReallifeKip\ImmutableBase\Attributes\Spec;
use ReallifeKip\ImmutableBase\Attributes\Strict;
use ReallifeKip\ImmutableBase\Attributes\ValidateFromSelf;
use ReallifeKip\ImmutableBase\CLI\Cacher;
use ReallifeKip\ImmutableBase\Exceptions\DefinitionExceptions\DebugLogDirectoryInvalidException;
use ReallifeKip\ImmutableBase\Exceptions\DefinitionExceptions\InvalidArrayOfTargetException;
use ReallifeKip\ImmutableBase\Exceptions\DefinitionExceptions\InvalidArrayOfUsageException;
use ReallifeKip\ImmutableBase\Exceptions\DefinitionExceptions\InvalidCompareTargetException;
use ReallifeKip\ImmutableBase\Exceptions\DefinitionExceptions\InvalidVisibilityException;
use ReallifeKip\ImmutableBase\Exceptions\DefinitionExceptions\InvalidWithPathException;
use ReallifeKip\ImmutableBase\Exceptions\InitializationExceptions\InvalidEnumValueException;
use ReallifeKip\ImmutableBase\Exceptions\InitializationExceptions\InvalidJsonException;
use ReallifeKip\ImmutableBase\Exceptions\InitializationExceptions\InvalidValueException;
use ReallifeKip\ImmutableBase\Exceptions\InitializationExceptions\RequiredValueException;
use ReallifeKip\ImmutableBase\Exceptions\ValidationExceptions\InvalidArrayOfItemException;
use ReallifeKip\ImmutableBase\Exceptions\ValidationExceptions\StrictViolationException;
use ReallifeKip\ImmutableBase\ImmutableBase;
use ReallifeKip\ImmutableBase\StaticStatus;
use ReflectionClass;
use Tests\DataTransferObjects\ArrayOfDTO;
use Tests\DataTransferObjects\DefaultPriorityDTO;
use Tests\DataTransferObjects\DefaultValuesBothDTO;
use Tests\DataTransferObjects\DefaultValuesByAttributeDTO;
use Tests\DataTransferObjects\DefaultValuesByFunctionDTO;
use Tests\DataTransferObjects\DTO;
use Tests\DataTransferObjects\EmptyArrayOfClassDTO;
use Tests\DataTransferObjects\ExtraDTO;
use Tests\DataTransferObjects\InvalidArrayOfClassDTO;
use Tests\DataTransferObjects\InvalidArrayOfUsageDTO;
use Tests\DataTransferObjects\LaxDTO;
use Tests\DataTransferObjects\PrivatePropertyDTO;
use Tests\DataTransferObjects\ProtectedPropertyDTO;
use Tests\DataTransferObjects\SkipOnNullDTO;
use Tests\DataTransferObjects\StrictDTO;
use Tests\DataTransferObjects\UnionWithImmutableBaseTypeDTO;
use Tests\SingleValueObjects\SVO3;
use Tests\SingleValueObjects\SVO;
use Tests\TestObjects\Enum;
use Tests\ValueObjects\NestedVO;
use Tests\ValueObjects\VO;

ImmutableBase::loadCache();

class DefaultTest extends TestCase
{
    private array $array;
    private string $json;
    private vfsStreamDirectory $root;
    private array $arrayOfData = [
        'strings' => ['1', '2', '3'],
        'ints'    => [1, 2, 3],
        'floats'  => [1.1, 2.2, 3.3],
        'bools'   => [true, true, false, false],
    ];
    public function setup(): void
    {
        $this->root               = vfsStream::setup('temp');
        StaticStatus::$cachedMeta = [];
        $this->array              = [
            'string'              => 'string',
            'int'                 => 1,
            'float'               => 1.1,
            'bool'                => true,
            'array'               => [1, 2, 3],
            'emptyArray'          => [],
            'union'               => 'string',
            'unionWithoutArray'   => 'string',
            'unionStringAndInt'   => 'string',
            'unionClasses'        => SVO::from(''),
            'unionSVOs'           => 'example@hotmail.com',
            'enum1'               => 'ONE',
            'enum2'               => 'one',
            'enum3'               => Enum::ONE,
            'enumMixed'           => 'string',
            'nullableString'      => 'string',
            'nullableInt'         => null,
            'nullableArray'       => null,
            'nullableFloat'       => null,
            'nullableBool'        => null,
            'nullableEnum'        => null,
            'mixed'               => 'string',
            'dataTransferObjects' => [],
            'valueObjects'        => [],
            'singleValueObjects'  => [],
        ];
        $this->json = json_encode($this->array);
    }
    public function testBasic() // NOSONAR
    {
        ImmutableBase::debug(__DIR__);
        $dtoFromArray         = DTO::fromArray($this->array + ['redundant' => '']);
        $expectArray          = json_decode(json_encode($this->array), true);
        $expectArray['enum1'] = 'one';
        $expectArray['enum3'] = 'one';
        $this->assertEquals(
            $dtoFromArray->toArray(),
            $expectArray
        );
        ImmutableBase::debug(null);

        $dtoFromJson       = DTO::fromJson($this->json);
        $expectJson        = json_decode(json_encode($this->array));
        $expectJson->enum1 = 'one';
        $expectJson->enum3 = 'one';
        $expectJson        = json_encode($expectJson);
        $this->assertEquals(
            $dtoFromJson->toJson(),
            $expectJson
        );

        $json   = json_encode($this->array);
        $object = json_decode($json);
        $array  = array_merge($this->array, [
            'dataTransferObjects' => [
                $this->array,
                $json,
                $object,
                DTO::fromArray($this->array),
                DTO::fromJson($json),
            ],
            'valueObjects'        => [
                $this->array,
                $json,
                $object,
                VO::fromArray($this->array),
                VO::fromJson($json),
            ],
            'singleValueObjects'  => [
                '',
                SVO::from(''),
            ],
        ]);
        $json         = json_encode($array);
        $dtoFromArray = DTO::fromArray($array);
        $this->assertContainsOnlyInstancesOf(DTO::class, $dtoFromArray->dataTransferObjects);
        $this->assertContainsOnlyInstancesOf(VO::class, $dtoFromArray->valueObjects);
        $this->assertContainsOnlyInstancesOf(SVO::class, $dtoFromArray->singleValueObjects);
        $dtoFromJson = DTO::fromJson($json);
        $this->assertContainsOnlyInstancesOf(DTO::class, $dtoFromJson->dataTransferObjects);
        $this->assertContainsOnlyInstancesOf(VO::class, $dtoFromJson->valueObjects);
        $this->assertContainsOnlyInstancesOf(SVO::class, $dtoFromJson->singleValueObjects);

        $dtoFromArray = $dtoFromArray->with([
            'array'                        => [[1, 2], SVO::from('svo')],
            'enum1'                        => 'TWO',
            'enum2'                        => 'two',
            'enum3'                        => Enum::TWO,
            'nullableString'               => null,
            'dataTransferObjects.0'        => $this->array,
            'dataTransferObjects.0.string' => '1',
            'dataTransferObjects.0.int'    => 1,
            'singleValueObjects[1]'        => 'dto_svo_1',
        ]);
        $this->assertContainsOnlyInstancesOf(Enum::class, [$dtoFromArray->enum1, $dtoFromArray->enum2, $dtoFromArray->enum3]);

        $dtoFromJson = $dtoFromJson->with([
            'array'                        => [[1, 2], SVO::from('svo')],
            'enum1'                        => 'TWO',
            'enum2'                        => 'two',
            'enum3'                        => Enum::TWO,
            'nullableString'               => null,
            'dataTransferObjects/0'        => $this->array,
            'dataTransferObjects/0/string' => '1',
            'dataTransferObjects/0/int'    => 1,
            'singleValueObjects[1]'        => 'dto_svo_1',
        ], '/');
        $this->assertContainsOnlyInstancesOf(Enum::class, [$dtoFromJson->enum1, $dtoFromJson->enum2, $dtoFromJson->enum3]);
        $this->assertTrue($dtoFromArray->equals($dtoFromJson));

        $dtoFromArray = $dtoFromArray->with(['dataTransferObjects.1.string' => '2']);
        $this->assertFalse($dtoFromArray->equals($dtoFromJson));

        $pureDTO   = DTO::fromArray($this->array);
        $extraDTO1 = ExtraDTO::fromArray($this->array + [
            'string2'       => 'string2',
            'dto'           => DTO::fromArray($array),
            'unionClasses2' => SVO::from('unionClasses2'),
        ]);
        $this->assertEquals(
            $extraDTO1->with(['dto' => $this->array])->dto->toArray(),
            $pureDTO->toArray()
        );
        $this->assertObjectNotHasProperty(
            '__redundant__',
            $extraDTO1->with(['__redundant__' => 123]),
        );
        $this->assertEquals(
            $extraDTO1->with(['dto' => $this->json])->dto->toArray(),
            $pureDTO->toArray()
        );
        $this->assertEquals(
            $extraDTO1->with((object) ['dto' => $this->array])->dto->toArray(),
            $pureDTO->toArray()
        );
        $this->assertObjectNotHasProperty(
            '__redundant__',
            $extraDTO1->with((object) ['__redundant__' => 123])
        );
        $this->assertEquals(
            $extraDTO1->with(json_encode(['dto' => $this->array]))->dto->toArray(),
            $pureDTO->toArray()
        );
        $extraDTO2 = $extraDTO1->with(['dto.dataTransferObjects' => []]);
        $this->assertFalse($extraDTO1->equals($extraDTO2));

        DTO::fromArray(array_merge($this->array, ['union' => []]))->with(['union' => '']);
        DTO::fromArray(array_merge($this->array, ['union' => 123]))->with(['union' => []]);

        SVO3::from('');
        $this->expectOutputString("SVO_3\nSVO_2\nSVO_1\n");
        SkipOnNullDTO::fromArray([])->toArray();

        ImmutableBase::strict(true);
        LaxDTO::fromArray([]);
        ImmutableBase::strict(false);
        $nestedDTO = NestedVO::fromArray([
            'nested2' => [
                'value' => 'svo',
            ],
        ]);
        $nestedDTO->with(['nested2.value' => 'svo1']);
        UnionWithImmutableBaseTypeDTO::fromArray([
            'mixed' => DTO::fromArray($this->array),
        ]);

        foreach (
            [
                ArrayOf::class,
                KeepOnNull::class,
                Lax::class,
                SkipOnNull::class,
                Spec::class,
                Strict::class,
                ValidateFromSelf::class,
                Defaults::class,
            ] as $class
        ) {
            $ref         = new ReflectionClass($class);
            $constructor = $ref->getConstructor();
            $constructor->setAccessible(true); // NOSONAR
            $instance = $ref->newInstanceWithoutConstructor(); // NOSONAR
            $constructor->invoke($instance, '');
        }

        $values = [
            'bool'  => true,
            'int'   => 1,
            'array' => [1, 2, 3],
        ];
        $nulls = [
            'bool'  => null,
            'int'   => null,
            'array' => null,
        ];
        $defaults = [
            'bool'  => false,
            'int'   => 0,
            'array' => [],
        ];

        $this->assertEquals(DefaultValuesByFunctionDTO::fromArray($values)->toArray(), $values);
        $this->assertEquals(DefaultValuesByFunctionDTO::fromArray($nulls)->toArray(), $nulls);
        $this->assertEquals(DefaultValuesByFunctionDTO::fromArray([])->toArray(), $defaults);
        $this->assertEquals(DefaultValuesByAttributeDTO::fromArray($values)->toArray(), $values);
        $this->assertEquals(DefaultValuesByAttributeDTO::fromArray($nulls)->toArray(), $nulls);
        $this->assertEquals(DefaultValuesByAttributeDTO::fromArray([])->toArray(), $defaults);
        $this->assertEquals(DefaultValuesBothDTO::fromArray([])->toArray(), $defaults);

        $this->assertEquals(ArrayOfDTO::fromArray($this->arrayOfData)->toArray(), $this->arrayOfData);
    }
    public function testDefaultResolutionPriority()
    {
        $dto = DefaultPriorityDTO::fromArray([
            'required' => 'required',
        ]);
        $this->assertSame('attribute-default', $dto->fromAttribute);
        $this->assertSame('function-default', $dto->fromFunction);
        $this->assertSame('function-overrides-attribute', $dto->both);

        $explicit = DefaultPriorityDTO::fromArray([
            'fromAttribute' => 'input-attr',
            'fromFunction'  => 'input-fn',
            'both'          => 'input-both',
            'required'      => 'required',
        ]);
        $this->assertSame('input-attr', $explicit->fromAttribute);
        $this->assertSame('input-fn', $explicit->fromFunction);
        $this->assertSame('input-both', $explicit->both);

        $nullable = DefaultPriorityDTO::fromArray([
            'fromAttribute' => null,
            'fromFunction'  => null,
            'both'          => null,
            'required'      => 'required',
        ]);
        $this->assertNull($nullable->fromAttribute);
        $this->assertNull($nullable->fromFunction);
        $this->assertNull($nullable->both);
    }

    public function testDefaultResolutionPriorityWithCache()
    {
        $file         = getcwd() . '/tests';
        $cacheFile    = 'ib-cache.php';
        $initialLevel = ob_get_level();
        ob_start();
        try {
            (new Cacher())->scan($file, true);
            StaticStatus::$properties = [];
            StaticStatus::$refs       = [];
            StaticStatus::$cachePath  = null;
            StaticStatus::$cachedMeta = [];
            ImmutableBase::loadCache();
        } finally {
            while (ob_get_level() > $initialLevel) {
                ob_end_clean();
            }
        }

        try {
            $this->testDefaultResolutionPriority();
        } finally {
            if (file_exists($cacheFile)) {
                unlink($cacheFile);
            }
            StaticStatus::$cachedMeta = [];
        }
    }
    public function testMissingRequiredPropertyAfterDefaultResolutionThrows()
    {
        $this->expectException(RequiredValueException::class);
        $this->expectExceptionMessage("Property 'required' must be present and non-null.");
        DefaultPriorityDTO::fromArray([]);
    }
    public function testBasicWithCache()
    {
        $file         = getcwd() . '/tests';
        $cacheFile    = 'ib-cache.php';
        $initialLevel = ob_get_level();
        ob_start();
        try {
            (new Cacher())->scan($file, true);
            if (!file_exists($cacheFile)) {
                $this->fail('Cacher failed to create cache file.');
            }
            StaticStatus::$properties = [];
            StaticStatus::$refs       = [];
            StaticStatus::$cachePath  = null;
            StaticStatus::$cachedMeta = [];
            ImmutableBase::loadCache();
        } finally {
            while (ob_get_level() > $initialLevel) {
                ob_end_clean();
            }
        }
        try {
            $this->testBasic();
        } finally {
            if (file_exists($cacheFile)) {
                unlink($cacheFile);
            }
            StaticStatus::$cachedMeta = [];
        }
    }
    public function testLoadCacheSuccessfullyPopulatesStaticStatus()
    {
        $mockData = ['SomeClass' => ['methods' => [], 'properties' => []]];

        $cacheFile = vfsStream::newFile('ib-cache-' . uniqid('', true) . '.php')
            ->at($this->root)
            ->setContent('<?php return ' . var_export($mockData, true) . ';')
            ->url();

        StaticStatus::$cachedMeta = [];
        StaticStatus::$cachePath  = $cacheFile;

        ImmutableBase::loadCache();

        $this->assertSame($mockData, StaticStatus::$cachedMeta);
    }

    public function testInvalidValueWithThrowException()
    {
        $this->expectException(InvalidEnumValueException::class);
        $this->expectExceptionMessage('\'THREE\' does not match any of Tests\TestObjects\Enum defined names or cases.');
        $dto = DTO::fromArray($this->array);
        $dto->with(['enum1' => 'THREE']);
    }
    public function testNullValueWithThrowNonNullablePropertyException()
    {
        $this->expectException(RequiredValueException::class);
        $this->expectExceptionMessage('Property \'string\' must be present and non-null.');

        $dto = DTO::fromArray($this->array);
        $dto->with(['string' => null]);
    }
    public function testInvalidEnumThrowInvalidTypeException()
    {
        $this->expectException(InvalidEnumValueException::class);
        $this->expectExceptionMessage('\'test\' does not match any of Tests\TestObjects\Enum defined names or cases.');
        DTO::fromArray(array_merge($this->array, ['enum1' => 'test']));
    }
    public function testEmptyArrayOfClassThrowInvalidArrayOfClassException()
    {
        $this->expectException(InvalidArrayOfTargetException::class);
        $this->expectExceptionMessage('#[ArrayOf] target must be a subclass of DataTransferObject, ValueObject, or SingleValueObject.');
        EmptyArrayOfClassDTO::fromArray(['regulars' => []]);
    }
    public function testInvalidArrayOfClassThrowInvalidArrayOfClassException()
    {
        $this->expectException(InvalidArrayOfTargetException::class);
        $this->expectExceptionMessage('#[ArrayOf] target must be a subclass of DataTransferObject, ValueObject, or SingleValueObject.');
        InvalidArrayOfClassDTO::fromArray(['regulars' => []]);
    }
    public function testInvalidJsonThrowInvalidJsonException()
    {
        $this->expectException(InvalidJsonException::class);
        $this->expectExceptionMessage('Invalid Json string.'); // NOSONAR
        DTO::fromJson('{_}');
    }
    public function testInvalidJsonThrowInvalidJsonException2()
    {
        $this->expectException(InvalidJsonException::class);
        $this->expectExceptionMessage('Invalid Json string.'); // NOSONAR
        DTO::fromJson('[');
    }
    public function testInvalidArrayForUnionWithoutArrayThrowInvalidTypeException()
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('Invalid value: expected string|int|float|bool, got array.');
        DTO::fromArray(array_merge($this->array, ['unionWithoutArray' => []]));
    }
    public function testUndeclaredValueForUnionTypeThrowInvalidTypeException()
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('Invalid value: expected string|int, got bool.');
        DTO::fromArray(array_merge($this->array, ['unionStringAndInt' => false]));
    }
    public function testUndeclaredValueForUnionTypeThrowInvalidTypeException2()
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('Invalid value: expected string|int, got stdClass.');
        DTO::fromArray(array_merge($this->array, ['unionStringAndInt' => (object) []]));
    }
    public function testPrivatePropertyThrowInvalidPropertyVisibilityException()
    {
        $this->expectException(InvalidVisibilityException::class);
        $this->expectExceptionMessage('\'string\' must be public and readonly.');
        PrivatePropertyDTO::fromArray($this->array);
    }
    public function testProtectedPropertyThrowInvalidPropertyVisibilityException()
    {
        $this->expectException(InvalidVisibilityException::class);
        $this->expectExceptionMessage('\'string\' must be public and readonly.');
        ProtectedPropertyDTO::fromArray($this->array);
    }
    public function testInvalidNamedTypeThrowInvalidTypeException()
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('Invalid value: expected Tests\DataTransferObjects\DTO, got bool.');
        ExtraDTO::fromArray([
            'string2'       => 'string2',
            'dto'           => false,
            'unionClasses2' => SVO::from(''),
        ]);
    }
    public function testInvalidObjectThrowInvalidTypeException()
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('Invalid value: expected string, got stdClass.');
        DTO::fromArray(array_merge($this->array, ['string' => (object) ['test']]));
    }
    public function testStrictDTOThrowStrictViolationException()
    {
        $this->expectException(StrictViolationException::class);
        $this->expectExceptionMessage('Disallowed \'__redundant__\' for Tests\DataTransferObjects\StrictDTO.');
        StrictDTO::fromArray(['__redundant__' => '']);
    }
    public function testDebugLogPathNotFoundThrow()
    {
        try {
            $this->expectException(DebugLogDirectoryInvalidException::class);
            $this->expectExceptionMessage("'./example' for debug log does not exist is not writable, or is not a directory.");
            ImmutableBase::debug('./example');
            LaxDTO::fromArray([
                '__redundant__' => '',
            ]);
        } catch (DebugLogDirectoryInvalidException $e) {
            ImmutableBase::debug(null);
            throw $e;
        }
    }
    public function testArrayOfUsageException()
    {
        $this->expectException(InvalidArrayOfUsageException::class);
        $this->expectExceptionMessage('#[ArrayOf] attribute can only be applied to array properties. $dtos is typed as string');
        InvalidArrayOfUsageDTO::fromArray([]);
    }
    public function testInvalidValueForArrayOfWithThrowException()
    {
        $this->expectException(InvalidJsonException::class);
        $this->expectExceptionMessage('Invalid Json string.');
        $dto = DTO::fromArray($this->array);
        $dto->with([
            'dataTransferObjects' => 'string',
        ]);
    }
    public function testInvalidWithPathException()
    {
        $this->expectException(InvalidWithPathException::class);
        $this->expectExceptionMessage('Cannot deeply update $string as it is not an array or a subclass of ImmutableBase.');
        $dto = DTO::fromArray($this->array);
        $dto->with([
            'string.1' => 'test',
        ]);
    }
    public function testInvalidCompareTargetException()
    {
        $this->expectException(InvalidCompareTargetException::class);
        $this->expectExceptionMessage('stdClass cannot be compared.');
        $dto = DTO::fromArray(['array' => [(object) [1, 2, 3], 2, 3]] + $this->array);
        $dto->equals(DTO::fromArray($this->array));
    }
    public function testInvalidArrayOfNativeStringException()
    {
        $this->expectException(InvalidArrayOfItemException::class);
        ArrayOfDTO::fromArray(array_merge($this->arrayOfData, ['strings' => [1]]));
    }
    public function testInvalidArrayOfNativeIntException()
    {
        $this->expectException(InvalidArrayOfItemException::class);
        ArrayOfDTO::fromArray(array_merge($this->arrayOfData, ['ints' => ['1']]));
    }
    public function testInvalidArrayOfNativeFloatException()
    {
        $this->expectException(InvalidArrayOfItemException::class);
        ArrayOfDTO::fromArray(array_merge($this->arrayOfData, ['strings' => [['nested']]]));
    }
    public function testInvalidArrayOfNativeBoolException()
    {
        $this->expectException(InvalidArrayOfItemException::class);
        ArrayOfDTO::fromArray(array_merge($this->arrayOfData, ['floats' => [1]]));
    }
}
