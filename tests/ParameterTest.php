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
                Set\Strings::any()->filter(fn($string) => $string !== ''),
                Set\Strings::any(),
            )
            ->then(function($name, $value){
                $parameter = new Parameter($name, $value);

                $this->assertSame($name, $parameter->name());
                $this->assertSame($value, $parameter->value());
                $this->assertSame("$name=$value", $parameter->toString());
            });
    }

    public function testThrowWhenNameEmpty()
    {
        $this
            ->forAll(Set\Strings::any())
            ->then(function($value) {
                $this->expectException(DomainException::class);

                new Parameter('', $value);
            });
    }
}
