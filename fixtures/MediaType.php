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
        return Set\Composite::of(
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
            Set\Strings::any()->filter(fn($type) => (bool) preg_match('~^[\w\-.]+$~', $type)),
            Set\Strings::any()->filter(fn($suffix) => $suffix === '' || (bool) preg_match('~^[\w\-.]+$~', $suffix)),
            new Set\Either(
                Set\Strings::any()->filter(fn($name) => (bool) preg_match('~^[\w\-.]+$~', $name)),
                Set\Elements::of(null), // to generate a type without a parameter
            ),
            Set\Strings::any(),
        );
    }
}
