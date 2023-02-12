<?php
declare(strict_types = 1);

namespace Innmind\MediaType;

use Innmind\MediaType\Exception\DomainException;
use Innmind\Immutable\{
    Str,
    Maybe,
};

/**
 * @psalm-immutable
 */
final class Parameter
{
    /** @see https://tools.ietf.org/html/rfc6838#section-4.2 */
    private const NAME = '[A-Za-z0-9][A-Za-z0-9!#$&^_.-]{0,126}';
    /** @see https://datatracker.ietf.org/doc/html/rfc9110#section-5.6.2 */
    private const VALUE = '[A-Za-z0-9!#$%&\'*+^_.\-`|\~]';

    private string $name;
    private string $value;

    public function __construct(string $name, string $value)
    {
        $format = self::NAME;

        if (!Str::of($name)->matches("~^$format$~")) {
            throw new DomainException($name);
        }

        $this->name = $name;
        $this->value = $value;
    }

    /**
     * @psalm-pure
     *
     * @return Maybe<self>
     */
    public static function of(string $string): Maybe
    {
        $name = self::NAME;
        $value = self::VALUE;
        $string = Str::of($string);

        return self::capture($string, "~^(?<key>$name)=(?<value>$value+)$~")->otherwise(
            static fn() => self::capture($string, "~^(?<key>$name)=\"(?<value>$value*)\"$~"),
        );
    }

    public function name(): string
    {
        return $this->name;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function toString(): string
    {
        return \sprintf(
            '%s=%s',
            $this->name,
            $this->value,
        );
    }

    /**
     * @psalm-pure
     *
     * @return Maybe<self>
     */
    private static function capture(Str $string, string $format): Maybe
    {
        $matches = $string->capture($format);

        return Maybe::all($matches->get('key'), $matches->get('value'))->map(
            static fn(Str $key, Str $value) => new self(
                $key->toString(),
                $value->toString(),
            ),
        );
    }
}
