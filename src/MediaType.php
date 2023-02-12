<?php
declare(strict_types = 1);

namespace Innmind\MediaType;

use Innmind\MediaType\Exception\{
    InvalidTopLevelType,
    DomainException,
};
use Innmind\Immutable\{
    Sequence,
    Set,
    Str,
    Maybe,
};

/**
 * @psalm-immutable
 */
final class MediaType
{
    /** @see https://tools.ietf.org/html/rfc6838#section-4.2 */
    private const FORMAT = '[A-Za-z0-9][A-Za-z0-9!#$&^_.-]{0,126}';

    private string $topLevel;
    private string $subType;
    private string $suffix;
    /** @var Sequence<Parameter> */
    private Sequence $parameters;

    /**
     * @no-named-arguments
     */
    public function __construct(
        string $topLevel,
        string $subType,
        string $suffix = '',
        Parameter ...$parameters,
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
        $this->parameters = Sequence::of(...$parameters);
    }

    /**
     * @psalm-pure
     * @throws DomainException
     */
    public static function of(string $string): self
    {
        return self::maybe($string)->match(
            static fn($self) => $self,
            static fn() => throw new DomainException($string),
        );
    }

    /**
     * @psalm-pure
     *
     * @return Maybe<self>
     */
    public static function maybe(string $string): Maybe
    {
        return Maybe::just(Str::of($string))
            ->filter(static fn($string) => $string->matches(self::pattern()))
            ->map(static fn($string) => $string->pregSplit('~[;,] ?~'))
            ->flatMap(
                static fn($splits) => self::capture($splits->first())->flatMap(
                    static fn(Str $topLevel, Str $subType, Str $suffix) => self::build(
                        $topLevel->toString(),
                        $subType->toString(),
                        $suffix->toString(),
                        $splits->drop(1),
                    ),
                ),
            );
    }

    /**
     * @psalm-pure
     */
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
            ->map(static fn($parameter) => $parameter->toString());
        $parameters = Str::of(', ')->join($parameters);

        return \sprintf(
            '%s/%s%s%s',
            $this->topLevel,
            $this->subType,
            $this->suffix !== '' ? '+'.$this->suffix : '',
            !$parameters->empty() ? '; '.$parameters->toString() : '',
        );
    }

    /**
     * List of allowed top levels
     *
     * @return Set<string>
     */
    public static function topLevels(): Set
    {
        return Set::strings(
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

    private static function pattern(): string
    {
        $format = self::FORMAT;

        return \sprintf(
            "~%s/$format(\+$format)?([;,] $format=[\w\-.]+)?~",
            Str::of('|')->join(self::topLevels())->toString(),
        );
    }

    /**
     * @param Sequence<Str> $parameters
     *
     * @return Maybe<self>
     */
    private static function build(
        string $topLevel,
        string $subType,
        string $suffix,
        Sequence $parameters,
    ): Maybe {
        if ($parameters->empty()) {
            return Maybe::just(new self($topLevel, $subType, $suffix));
        }

        /** @psalm-suppress NamedArgumentNotAllowed */
        return self::captureParameters($parameters)->map(
            static fn(Parameter ...$parameters) => new self(
                $topLevel,
                $subType,
                $suffix,
                ...$parameters,
            ),
        );
    }

    /**
     * @param Maybe<Str> $string
     */
    private static function capture(Maybe $string): Maybe\Comprehension
    {
        $format = self::FORMAT;

        return $string
            ->map(static fn($string) => $string->capture(\sprintf(
                "~^(?<topLevel>%s)/(?<subType>$format)(\+(?<suffix>$format))?$~",
                Str::of('|')->join(self::topLevels())->toString(),
            )))
            ->match(
                static fn($matches) => Maybe::all(
                    $matches->get('topLevel'),
                    $matches->get('subType'),
                    $matches->get('suffix')->otherwise(static fn() => Maybe::just(Str::of(''))),
                ),
                static fn() => Maybe::all(Maybe::nothing()),
            );
    }

    /**
     * @param Sequence<Str> $parameters
     */
    private static function captureParameters(Sequence $parameters): Maybe\Comprehension
    {
        return $parameters
            ->map(static fn($parameter) => Parameter::of($parameter->toString()))
            ->match(
                static fn($first, $rest) => Maybe::all($first, ...$rest->toList()),
                static fn() => Maybe::all(Maybe::nothing()),
            );
    }
}
