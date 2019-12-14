<?php
declare(strict_types = 1);

namespace Innmind\MediaType;

use Innmind\MediaType\Exception\DomainException;
use Innmind\Immutable\Str;

final class Parameter
{
    private string $name;
    private string $value;

    public function __construct(string $name, string $value)
    {
        if (!Str::of($name)->matches('~^[\w\-.]+$~')) {
            throw new DomainException($name);
        }

        $this->name = $name;
        $this->value = $value;
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
