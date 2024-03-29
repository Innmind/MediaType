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

    public function testAcceptValueContainedInDoubleQuotes()
    {
        $this
            ->forAll(
                Set\Composite::immutable(
                    static fn($first, $rest) => $first.$rest,
                    Set\Chars::alphanumerical(),
                    Set\Strings::madeOf(
                        Set\Chars::alphanumerical(),
                        Set\Elements::of('!', '#', '$', '&', '^', '_', '.', '-'),
                    )->between(0, 125),
                ),
                Set\Strings::madeOf(
                    Set\Chars::alphanumerical(),
                    Set\Elements::of('!', '#', '$', '&', '^', '_', '.', '-', "'", '*', '+', '`', '|', '~'),
                ),
            )
            ->then(function($name, $value) {
                $parameter = Parameter::of(\sprintf(
                    '%s="%s"',
                    $name,
                    $value,
                ))->match(
                    static fn($parameter) => $parameter,
                    static fn() => null,
                );

                $this->assertInstanceOf(Parameter::class, $parameter);
                $this->assertSame($name, $parameter->name());
                $this->assertSame($value, $parameter->value());
            });
    }
}
