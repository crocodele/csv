<?php

/**
 * League.Csv (https://csv.thephpleague.com)
 *
 * (c) Ignace Nyamagana Butera <nyamsprod@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace League\Csv\Serializer;

use DateTimeInterface;

use ReflectionNamedType;
use ReflectionParameter;
use ReflectionProperty;
use ReflectionType;
use ReflectionUnionType;

use function class_exists;
use function class_implements;
use function enum_exists;
use function in_array;
use function interface_exists;

use const FILTER_UNSAFE_RAW;
use const FILTER_VALIDATE_BOOL;
use const FILTER_VALIDATE_FLOAT;
use const FILTER_VALIDATE_INT;

enum Type: string
{
    case Bool = 'bool';
    case True = 'true';
    case False = 'false';
    case Null = 'null';
    case Int = 'int';
    case Float = 'float';
    case String = 'string';
    case Mixed = 'mixed';
    case Array = 'array';
    case Iterable = 'iterable';
    case Enum = 'enum';
    case Date = 'date';

    public function equals(mixed $value): bool
    {
        return $value instanceof self
            && $value === $this;
    }

    public function isOneOf(self ...$types): bool
    {
        return in_array($this, $types, true);
    }

    public function filterFlag(): int
    {
        return match ($this) {
            self::Bool,
            self::True,
            self::False => FILTER_VALIDATE_BOOL,
            self::Int => FILTER_VALIDATE_INT,
            self::Float => FILTER_VALIDATE_FLOAT,
            default => FILTER_UNSAFE_RAW,
        };
    }

    public function isScalar(): bool
    {
        return match ($this) {
            self::Bool,
            self::True,
            self::False,
            self::Int,
            self::Float,
            self::String => true,
            default => false,
        };
    }

    /**
     * @return list<array{0:Type, 1: ReflectionNamedType}>
     */
    public static function list(ReflectionParameter|ReflectionProperty $reflectionProperty): array
    {
        $reflectionType = $reflectionProperty->getType() ?? throw new MappingFailed(match (true) {
            $reflectionProperty instanceof ReflectionParameter => 'The setter method argument `'.$reflectionProperty->getName().'` must be typed.',
            $reflectionProperty instanceof ReflectionProperty => 'The property `'.$reflectionProperty->getName().'` must be typed.',
        });

        return self::typeList($reflectionType);
    }

    /**
     * @return list<array{0:Type, 1: ReflectionNamedType}>
     */
    private static function typeList(ReflectionType $reflectionType): array
    {
        $foundTypes = static function (array $res, ReflectionType $reflectionType) {
            if (!$reflectionType instanceof ReflectionNamedType) {
                return $res;
            }

            $type = self::tryFromName($reflectionType->getName());
            if (null !== $type) {
                $res[] = [$type, $reflectionType];
            }

            return $res;
        };

        if ($reflectionType instanceof ReflectionNamedType) {
            $type = self::tryFromName($reflectionType->getName());
            if (null !== $type) {
                return [[$type, $reflectionType]];
            }

            return [];
        }

        if ($reflectionType instanceof ReflectionUnionType) {
            return array_reduce($reflectionType->getTypes(), $foundTypes, []);
        }

        return [];
    }

    public static function tryFromReflectionType(ReflectionType $type): ?self
    {
        if ($type instanceof ReflectionNamedType) {
            return self::tryFromName($type->getName());
        }

        if (!$type instanceof ReflectionUnionType) {
            return null;
        }

        foreach ($type->getTypes() as $innerType) {
            if (!$innerType instanceof ReflectionNamedType) {
                continue;
            }

            $result = self::tryFromName($innerType->getName());
            if ($result instanceof self) {
                return $result;
            }
        }

        return null;
    }

    private static function tryFromName(string $propertyType): ?self
    {
        $type = self::tryFrom($propertyType);

        return match (true) {
            $type instanceof self => $type,
            enum_exists($propertyType) => self::Enum,
            interface_exists($propertyType) && DateTimeInterface::class === $propertyType,
            class_exists($propertyType) && in_array(DateTimeInterface::class, class_implements($propertyType), true) => self::Date,
            default => null,
        };
    }
}