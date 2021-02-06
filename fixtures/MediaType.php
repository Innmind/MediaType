<?php
declare(strict_types = 1);

namespace Fixtures\Innmind\MediaType;

use Innmind\MediaType\{
    MediaType as Model,
    Parameter,
};
use Innmind\BlackBox\Set;
use function Innmind\Immutable\unwrap;

final class MediaType
{
    /**
     * @return Set<Model>
     */
    public static function any(): Set
    {
        $alphaNumerical = [...\range('A', 'Z'), ...\range('a', 'z'), ...\range(0, 9)];
        $validChars = Set\Composite::immutable(
            static fn($first, array $rest): string => \implode('', [$first, ...$rest]),
            Set\Elements::of(...$alphaNumerical),
            Set\Sequence::of(
                Set\Elements::of('!', '#', '$', '&', '^', '_', '.', '-', ...$alphaNumerical),
                Set\Integers::between(0, 126),
            ),
        );

        return Set\Composite::immutable(
            static function($topLevel, $subType, $suffix, $parameterName, $parameterValue): Model {
                if ($parameterName) {
                    return new Model(
                        $topLevel,
                        $subType,
                        $suffix,
                        new Parameter(
                            $parameterName,
                            $parameterValue,
                        ),
                    );
                }

                return new Model(
                    $topLevel,
                    $subType,
                    $suffix,
                );
            },
            Set\Elements::of(...unwrap(Model::topLevels())),
            $validChars,
            new Set\Either(
                Set\Elements::of(''),
                $validChars,
            ),
            new Set\Either(
                $validChars,
                Set\Elements::of(null), // to generate a type without a parameter
            ),
            Set\Decorate::immutable(
                static fn(array $chars): string => \implode('', $chars),
                Set\Sequence::of(
                    Set\Chars::any()->filter(static fn(string $char): bool => !\in_array($char, [' ', '\\', '-', '.'], true)),
                    Set\Integers::between(1, 100),
                ),
            ),
        );
    }
}
