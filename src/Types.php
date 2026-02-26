<?php

namespace ReallifeKip\ImmutableBase;

use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use ReflectionType;

/**
 * PHPStan type definition container. This class holds no runtime logic —
 * it exists solely to declare reusable type aliases imported by
 * ImmutableBase and ValueObject via @phpstan-import-type.
 *
 * @phpstan-type Typename array{
 *     string: string,
 *     array: list<string>,
 * }
 * @phpstan-type BaseType array{
 *     ref: ReflectionProperty,
 *     propertyRef: ReflectionType,
 *     allowsNull: bool,
 *     arrayOf: class-string|null,
 *     propertyName: string,
 *     typename: Typename,
 *     skipOnNull: bool,
 *     keepOnNull: bool,
 *     isUnion: bool,
 *     resolver: callable(mixed): mixed
 *   }
 * @phpstan-type NamedTypeFromUnion BaseType & array{
 *     isUnion: false,
 *     isBuiltin: bool
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
 *   spec: string|null,
 *   classTree: list<class-string>,
 *   classTreeReversed: list<class-string>,
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
 */
abstract class Types
{}
