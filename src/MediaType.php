<?php
declare(strict_types = 1);

namespace Innmind\MediaType;

use Innmind\MediaType\Exception\{
    DomainException,
};
use Innmind\Immutable\{
    Attempt,
    Sequence,
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

    private TopLevel $topLevel;
    private string $subType;
    private string $suffix;
    /** @var Sequence<Parameter> */
    private Sequence $parameters;

    /**
     * @no-named-arguments
     */
    private function __construct(
        TopLevel $topLevel,
        string $subType,
        string $suffix = '',
        Parameter ...$parameters,
    ) {
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
     * @no-named-arguments
     */
    public static function from(
        TopLevel $topLevel,
        string $subType,
        string $suffix = '',
        Parameter ...$parameters,
    ): self {
        return new self($topLevel, $subType, $suffix, ...$parameters);
    }

    /**
     * @psalm-pure
     * @throws DomainException
     */
    public static function of(string $string): self
    {
        return self::attempt($string)->unwrap();
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
                    static fn(TopLevel $topLevel, Str $subType, Str $suffix) => self::build(
                        $topLevel,
                        $subType->toString(),
                        $suffix->toString(),
                        $splits->drop(1),
                    ),
                ),
            );
    }

    /**
     * @psalm-pure
     *
     * @return Attempt<self>
     */
    public static function attempt(string $string): Attempt
    {
        return self::maybe($string)->attempt(
            static fn() => throw new DomainException($string),
        );
    }

    /**
     * @psalm-pure
     */
    public static function null(): self
    {
        return new self(TopLevel::application, 'octet-stream');
    }

    public function topLevel(): TopLevel
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
            $this->topLevel->name,
            $this->subType,
            $this->suffix !== '' ? '+'.$this->suffix : '',
            !$parameters->empty() ? '; '.$parameters->toString() : '',
        );
    }

    private static function pattern(): string
    {
        $format = self::FORMAT;

        return \sprintf(
            "~%s/$format(\+$format)?([;,] $format=[\w\-.]+)?~",
            Str::of('|')
                ->join(
                    Sequence::of(...TopLevel::cases())
                        ->map(static fn($level) => $level->name),
                )
                ->toString(),
        );
    }

    /**
     * @param Sequence<Str> $parameters
     *
     * @return Maybe<self>
     */
    private static function build(
        TopLevel $topLevel,
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
                Str::of('|')
                    ->join(
                        Sequence::of(...TopLevel::cases())
                            ->map(static fn($level) => $level->name),
                    )
                    ->toString(),
            )))
            ->match(
                static fn($matches) => Maybe::all(
                    $matches
                        ->get('topLevel')
                        ->map(static fn($level) => $level->toString())
                        ->flatMap(TopLevel::maybe(...)),
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
