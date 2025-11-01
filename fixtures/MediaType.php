<?php
declare(strict_types = 1);

namespace Fixtures\Innmind\MediaType;

use Innmind\MediaType\{
    MediaType as Model,
    Parameter,
};
use Innmind\BlackBox\Set;

final class MediaType
{
    /**
     * @return Set\Provider<Model>
     */
    public static function any(): Set\Provider
    {
        $alphaNumerical = [...\range('A', 'Z'), ...\range('a', 'z'), ...\range(0, 9)];
        $validChars = Set::compose(
            static fn($first, array $rest): string => \implode('', [$first, ...$rest]),
            Set::of(...$alphaNumerical),
            Set::sequence(
                Set::of('!', '#', '$', '&', '^', '_', '.', '-', ...$alphaNumerical),
            )->between(0, 126),
        );

        return Set::compose(
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
            Set::of(...Model::topLevels()->toList()),
            $validChars,
            Set::either(
                Set::of(''),
                $validChars,
            ),
            $validChars->nullable(), // to generate a type without a parameter
            Set::strings()
                ->madeOf(
                    Set::strings()->chars()->alphanumerical(),
                    Set::of('-', '.'),
                )
                ->between(1, 100),
        );
    }
}
