<?php
declare(strict_types = 1);

namespace Tests\Innmind\MediaType;

use Innmind\MediaType\Parameter;
use Innmind\BlackBox\{
    PHPUnit\Framework\TestCase,
    PHPUnit\BlackBox,
    Set,
};

class ParameterTest extends TestCase
{
    use BlackBox;

    public function testInterface(): BlackBox\Proof
    {
        return $this
            ->forAll(
                Set::strings()->filter(static fn($name) => (bool) \preg_match('~^[A-Za-z0-9][A-Za-z0-9!#$&^_.-]{0,126}$~', $name)),
                Set::strings(),
            )
            ->prove(function($name, $value) {
                $parameter = Parameter::from($name, $value);

                $this->assertSame($name, $parameter->name());
                $this->assertSame($value, $parameter->value());
                $this->assertSame("$name=$value", $parameter->toString());
            });
    }

    public function testThrowWhenNameInvalid(): BlackBox\Proof
    {
        return $this
            ->forAll(
                Set::strings()->exclude(static fn($name) => (bool) \preg_match('~^[\w\-.]+$~', $name)),
                Set::strings(),
            )
            ->prove(function($name, $value) {
                $this
                    ->assert()
                    ->throws(
                        static fn() => Parameter::from($name, $value),
                        \DomainException::class,
                    );
            });
    }

    public function testAcceptValueContainedInDoubleQuotes(): BlackBox\Proof
    {
        return $this
            ->forAll(
                Set::compose(
                    static fn($first, $rest) => $first.$rest,
                    Set::strings()->chars()->alphanumerical(),
                    Set::strings()
                        ->madeOf(
                            Set::strings()->chars()->alphanumerical(),
                            Set::of('!', '#', '$', '&', '^', '_', '.', '-'),
                        )
                        ->between(0, 125),
                ),
                Set::strings()->madeOf(
                    Set::strings()->chars()->alphanumerical(),
                    Set::of('!', '#', '$', '&', '^', '_', '.', '-', "'", '*', '+', '`', '|', '~'),
                ),
            )
            ->prove(function($name, $value) {
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
