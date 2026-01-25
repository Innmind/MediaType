<?php
declare(strict_types = 1);

namespace Innmind\MediaType;

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

    private function __construct(
        private TopLevel $topLevel,
        private string $subType,
        private string $suffix,
        /** @var Sequence<Parameter> */
        private Sequence $parameters,
    ) {
    }

    /**
     * @psalm-pure
     * @no-named-arguments
     */
    #[\NoDiscard]
    public static function from(
        TopLevel $topLevel,
        string $subType,
        string $suffix = '',
        Parameter ...$parameters,
    ): self {
        $format = self::FORMAT;
        $regex = "~^$format$~";

        if (!Str::of($subType)->matches($regex)) {
            throw new \DomainException($subType);
        }

        if ($suffix !== '' && !Str::of($suffix)->matches($regex)) {
            throw new \DomainException($suffix);
        }

        return new self(
            $topLevel,
            $subType,
            $suffix,
            Sequence::of(...$parameters),
        );
    }

    /**
     * @psalm-pure
     *
     * @throws \DomainException
     */
    #[\NoDiscard]
    public static function of(string $string): self
    {
        return self::attempt($string)->unwrap();
    }

    /**
     * @psalm-pure
     *
     * @return Maybe<self>
     */
    #[\NoDiscard]
    public static function maybe(string $string): Maybe
    {
        return Maybe::just(Str::of($string))
            ->filter(static fn($string) => $string->matches(self::pattern()))
            ->map(static fn($string) => $string->pregSplit('~[;,] ?~'))
            ->flatMap(
                static fn($splits) => $splits
                    ->first()
                    ->flatMap(self::capture(...))
                    ->flatMap(
                        static fn($self) => $splits
                            ->drop(1)
                            ->map(static fn($parameter) => $parameter->toString())
                            ->map(Parameter::of(...))
                            ->sink($self)
                            ->maybe(static fn($self, $parameter) => $parameter->map(
                                $self->withParameter(...),
                            )),
                    ),
            );
    }

    /**
     * @psalm-pure
     *
     * @return Attempt<self>
     */
    #[\NoDiscard]
    public static function attempt(string $string): Attempt
    {
        return self::maybe($string)->attempt(
            static fn() => throw new \DomainException($string),
        );
    }

    /**
     * @psalm-pure
     */
    #[\NoDiscard]
    public static function null(): self
    {
        return new self(
            TopLevel::application,
            'octet-stream',
            '',
            Sequence::of(),
        );
    }

    #[\NoDiscard]
    public function topLevel(): TopLevel
    {
        return $this->topLevel;
    }

    #[\NoDiscard]
    public function subType(): string
    {
        return $this->subType;
    }

    #[\NoDiscard]
    public function suffix(): string
    {
        return $this->suffix;
    }

    /**
     * @return Sequence<Parameter>
     */
    #[\NoDiscard]
    public function parameters(): Sequence
    {
        return $this->parameters;
    }

    #[\NoDiscard]
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

    private function withParameter(Parameter $parameter): self
    {
        return new self(
            $this->topLevel,
            $this->subType,
            $this->suffix,
            ($this->parameters)($parameter),
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
     * @return Maybe<self>
     */
    private static function capture(Str $string): Maybe
    {
        $format = self::FORMAT;
        $matches = $string->capture(\sprintf(
            "~^(?<topLevel>%s)/(?<subType>$format)(\+(?<suffix>$format))?$~",
            Str::of('|')
                ->join(
                    Sequence::of(...TopLevel::cases())
                        ->map(static fn($level) => $level->name),
                )
                ->toString(),
        ));

        return Maybe::all(
            $matches
                ->get('topLevel')
                ->map(static fn($level) => $level->toString())
                ->flatMap(TopLevel::maybe(...)),
            $matches->get('subType'),
            $matches->get('suffix')->otherwise(static fn() => Maybe::just(Str::of(''))),
        )->map(static fn(TopLevel $topLevel, Str $subType, Str $suffix) => new self(
            $topLevel,
            $subType->toString(),
            $suffix->toString(),
            Sequence::of(),
        ));
    }
}
