<?php

declare (strict_types = 1);

namespace ReallifeKip\ImmutableBase;

use ReallifeKip\ImmutableBase\Enums\KeyCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use ReflectionType;

/**
 * PHPStan type definition container. This class holds no runtime logic —
 * it exists solely to declare reusable type aliases imported by
 * ImmutableBase and ValueObject via @phpstan-import-type.
 *
 * @phpstan-type Hydrator Closure(ImmutableBase $obj, array $resolved)
 *
 * @phpstan-type Typename array{
 *     string: string,
 *     array: list<string>,
 * }
 * @phpstan-type BaseType array{
 *     ref: ReflectionType,
 *     propertyRef: ReflectionProperty,
 *     allowsNull: bool,
 *     arrayOf: class-string|null,
 *     propertyName: string,
 *     inputKeyCase: KeyCase|null,
 *     hasInputKeyOverride: bool,
 *     typename: Typename,
 *     skipOnNull: bool,
 *     keepOnNull: bool,
 *     isUnion: bool,
 *     resolver: callable(mixed): mixed
 *   }
 * @phpstan-type NamedTypeFromUnion BaseType & array{
 *     isUnion: false,
 *     isBuiltin: bool,
 *     isEnum: bool
 * }
 * @phpstan-type NamedType NamedTypeFromUnion & array{
 *     isSVO: bool
 * }
 * @phpstan-type UnionType BaseType & array{
 *     isUnion: true,
 *     types: array<int, array<string, mixed>>
 * }
 * @phpstan-type Type NamedType | UnionType
 *
 * @phpstan-type Property array{
 *   ref: ReflectionClass,
 *   name: string,
 *   isStrict: bool,
 *   isLax: bool,
 *   isDTO: bool,
 *   isVO: bool,
 *   isSVO: bool,
 *   validateFromSelf: bool,
 *   skipOnNull: bool,
 *   hasValidate: bool,
 *   validateMethod: ReflectionMethod | false,
 *   hydrator: Hydrator,
 *   spec: string|null,
 *   classTree: list<class-string>,
 *   classTreeReversed: list<class-string>,
 *   inputKeyCase: KeyCase|null,
 *   propertyInputKeyCases: array<string, KeyCase>|null,
 *   types: array<string, Type>,
 * }
 *
 * @phpstan-type Class array{
 *   ref: ReflectionClass,
 *   shortName: string,
 *   namespace: string,
 *   types: array<string, Type>
 * }
 *
 * @phpstan-type NamespaceGroup array{
 *   fullClass: class-string,
 *   shortName: string,
 *   ref: ReflectionClass
 * }
 *
 * @phpstan-type ClassMap array<class-string, Class>
 * @phpstan-type NamespaceGroups array<string, list<NamespaceGroup>>
 * @phpstan-type Caches array<class-string, Property>
 *
 * @phpstan-type State array{
 *   debug: bool,
 *   logPath: ?string,
 *   cachePath: ?string,
 *   strict: bool,
 *   refs: array,
 *   properties: array,
 *   cachedMeta: array
 * }
 */
abstract class Types
{}
