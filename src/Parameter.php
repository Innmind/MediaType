<?php
declare(strict_types = 1);

namespace Innmind\MediaType;

use Innmind\MediaType\Exception\DomainException;
use Innmind\Immutable\Str;

final class Parameter
{
    /** @see https://tools.ietf.org/html/rfc6838#section-4.2 */
    private const FORMAT = '[A-Za-z0-9][A-Za-z0-9!#$&^_.-]{0,126}';

    private string $name;
    private string $value;

    public function __construct(string $name, string $value)
    {
        $format = self::FORMAT;

        if (!Str::of($name)->matches("~^$format$~")) {
            throw new DomainException($name);
        }

        $this->name = $name;
        $this->value = $value;
    }

    public static function of(string $string): self
    {
        $format = self::FORMAT;
        $matches = Str::of($string)->capture("~^(?<key>$format)=(?<value>[\w\-.]+)$~");

        return new self(
            $matches->get('key')->toString(),
            $matches->get('value')->toString(),
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
}
