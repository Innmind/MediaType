<?php
declare(strict_types = 1);

namespace Tests\Innmind\MediaType;

use Innmind\MediaType\{
    Parameter,
    Exception\DomainException,
};
use PHPUnit\Framework\TestCase;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    Set,
};

class ParameterTest extends TestCase
{
    use BlackBox;

    public function testInterface()
    {
        $this
            ->forAll(
                Set\Strings::any()->filter(static fn($name) => (bool) \preg_match('~^[A-Za-z0-9][A-Za-z0-9!#$&^_.-]{0,126}$~', $name)),
                Set\Strings::any(),
            )
            ->then(function($name, $value) {
                $parameter = new Parameter($name, $value);

                $this->assertSame($name, $parameter->name());
                $this->assertSame($value, $parameter->value());
                $this->assertSame("$name=$value", $parameter->toString());
            });
    }

    public function testThrowWhenNameInvalid()
    {
        $this
            ->forAll(
                Set\Strings::any()->filter(static fn($name) => !(bool) \preg_match('~^[\w\-.]+$~', $name)),
                Set\Strings::any(),
            )
            ->then(function($name, $value) {
                $this->expectException(DomainException::class);
                $this->expectExceptionMessage($name);

                new Parameter($name, $value);
            });
    }
}
