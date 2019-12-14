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

        if (!Str::of($subType)->matches('~^[\w\-.]+$~')) {
            throw new DomainException($subType);
        }

        if ($suffix !== '' && !Str::of($suffix)->matches('~^[\w\-.]+$~')) {
            throw new DomainException($suffix);
        }

        $this->topLevel = $topLevel;
        $this->subType = $subType;
        $this->suffix = $suffix;
        /** @var Sequence<Parameter> */
        $this->parameters = Sequence::of(Parameter::class, ...$parameters);
    }

    public static function of(string $string): self
    {
        $string = Str::of($string);
        $pattern = \sprintf(
            '~%s/[\w\-.]+(\+\w+)?([;,] [\w\-.]+=[\w\-.]+)?~',
            join('|', self::topLevels())->toString(),
        );

        if (!$string->matches($pattern)) {
            throw new InvalidMediaTypeString($string->toString());
        }

        $splits = $string->pregSplit('~[;,] ?~');
        $matches = $splits
            ->get(0)
            ->capture(\sprintf(
                '~^(?<topLevel>%s)/(?<subType>[\w\-.]+)(\+(?<suffix>\w+))?$~',
                join('|', self::topLevels())->toString(),
            ));

        $topLevel = $matches->get('topLevel');
        $subType = $matches->get('subType');
        $suffix = $matches->contains('suffix') ? $matches->get('suffix') : Str::of('');

        /** @var Sequence<Parameter> $params */
        $params = $splits
            ->drop(1)
            ->toSequenceOf(Parameter::class, static function(Str $param): \Generator {
                $matches = $param->capture('~^(?<key>[\w\-.]+)=(?<value>[\w\-.]+)$~');

                yield new Parameter(
                    $matches->get('key')->toString(),
                    $matches->get('value')->toString(),
                );
            });

        return new self(
            $topLevel->toString(),
            $subType->toString(),
            $suffix->toString(),
            ...unwrap($params),
        );
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
        /** @var Sequence<string> */
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
