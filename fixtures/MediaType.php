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
            Set\Strings::matching('^[A-Za-z0-9][A-Za-z0-9!#$&^_.-]{0,126}$'),
            new Set\Either(
                Set\Elements::of(''),
                Set\Strings::matching('^[A-Za-z0-9][A-Za-z0-9!#$&^_.-]{0,126}$'),
            ),
            new Set\Either(
                Set\Strings::matching('^[A-Za-z0-9][A-Za-z0-9!#$&^_.-]{0,126}$'),
                Set\Elements::of(null), // to generate a type without a parameter
            ),
            Set\Strings::matching('^[ \-.]+$'),
        );
    }
}
