<?php
declare(strict_types = 1);

namespace Innmind\MediaType;

use Innmind\MediaType\Exception\{
    InvalidTopLevelType,
    InvalidMediaTypeString,
    DomainException,
};
use Innmind\Immutable\{
    Sequence,
    Set,
    Str,
};
use function Innmind\Immutable\{
    join,
    unwrap,
};

final class MediaType
{
    /** @see https://tools.ietf.org/html/rfc6838#section-4.2 */
    private const FORMAT = '[A-Za-z0-9][A-Za-z0-9!#$&^_.-]{0,126}';

    /** @var Set<string>|null */
    private static ?Set $topLevels = null;
    private string $topLevel;
    private string $subType;
    private string $suffix;
    /** @var Sequence<Parameter> */
    private Sequence $parameters;

    public function __construct(
        string $topLevel,
        string $subType,
        string $suffix = '',
        Parameter ...$parameters
    ) {
        if (!self::topLevels()->contains($topLevel)) {
            throw new InvalidTopLevelType($topLevel);
        }

        $format = self::FORMAT;
        $regex = "~^$format$~";

        if (!Str::of($subType)->matches($regex)) {
            throw new DomainException($subType);
        }

        if ($suffix !== '' && !Str::of($suffix)->matches($regex)) {
            throw new DomainException($suffix);
        }

        $this->topLevel = $topLevel;
        $this->subType = $subType;
        $this->suffix = $suffix;
        $this->parameters = Sequence::of(Parameter::class, ...$parameters);
    }

    public static function of(string $string): self
    {
        $string = Str::of($string);
        $format = self::FORMAT;
        $pattern = \sprintf(
            "~^(%s)/$format(\+$format)?([;,] ?$format=[\w\-.]+)*\$~",
            join('|', self::topLevels())->toString(),
        );

        if (!$string->matches($pattern)) {
            throw new InvalidMediaTypeString($string->toString());
        }

        $splits = $string->pregSplit('~[;,] ?~');
        $matches = $splits
            ->get(0)
            ->capture(\sprintf(
                "~^(?<topLevel>%s)/(?<subType>$format)(\+(?<suffix>$format))?$~",
                join('|', self::topLevels())->toString(),
            ));

        $topLevel = $matches->get('topLevel');
        $subType = $matches->get('subType');
        $suffix = $matches->contains('suffix') ? $matches->get('suffix') : Str::of('');

        $params = $splits
            ->drop(1)
            ->toSequenceOf(Parameter::class, static function(Str $param): \Generator {
                yield Parameter::of($param->toString());
            });

        return new self(
            $topLevel->toString(),
            $subType->toString(),
            $suffix->toString(),
            ...unwrap($params),
        );
    }

    public static function null(): self
    {
        return new self('application', 'octet-stream');
    }

    public function topLevel(): string
    {
        return $this->topLevel;
    }

    public function subType(): string
    {
        return $this->subType;
    }

    public function suffix(): string
    {
        return $this->suffix;
    }

    /**
     * @return Sequence<Parameter>
     */
    public function parameters(): Sequence
    {
        return $this->parameters;
    }

    public function toString(): string
    {
        $parameters = $this
            ->parameters
            ->toSequenceOf('string', static fn($parameter): \Generator => yield $parameter->toString());
        $parameters = join(', ', $parameters);

        return \sprintf(
            '%s/%s%s%s',
            $this->topLevel,
            $this->subType,
            $this->suffix !== '' ? '+'.$this->suffix : '',
            $parameters->length() > 0 ? '; '.$parameters->toString() : ''
        );
    }

    /**
     * List of allowed top levels
     *
     * @return Set<string>
     */
    public static function topLevels(): Set
    {
        if (\is_null(self::$topLevels)) {
            self::$topLevels = Set::strings(
                'application',
                'audio',
                'font',
                'example',
                'image',
                'message',
                'model',
                'multipart',
                'text',
                'video',
            );
        }

        return self::$topLevels;
    }
}
