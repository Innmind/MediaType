<?php
declare(strict_types = 1);

namespace Tests\Innmind\MediaType\Fixtures;

use Fixtures\Innmind\MediaType\MediaType;
use Innmind\MediaType\MediaType as Model;
use PHPUnit\Framework\TestCase;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    Set,
    Random,
};

class MediaTypeTest extends TestCase
{
    use BlackBox;

    public function testInterface()
    {
        $set = MediaType::any();

        $this->assertInstanceOf(Set::class, $set);

        foreach ($set->values(Random::default) as $value) {
            $this->assertInstanceOf(Set\Value::class, $value);

            if (\interface_exists(Set\Implementation::class)) {
                $this->assertTrue($value->immutable());
            } else {
                $this->assertTrue($value->isImmutable());
            }

            $this->assertInstanceOf(Model::class, $value->unwrap());
        }
    }

    public function testAllGeneratedMediaTypesAreParseable()
    {
        $this
            ->forAll(MediaType::any())
            ->then(function($mediaType) {
                $this->assertSame(
                    $mediaType->toString(),
                    Model::of($mediaType->toString())->toString(),
                );
            });
    }
}
