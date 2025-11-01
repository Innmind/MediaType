<?php
declare(strict_types = 1);

namespace Tests\Innmind\MediaType\Fixtures;

use Fixtures\Innmind\MediaType\MediaType;
use Innmind\MediaType\MediaType as Model;
use Innmind\BlackBox\{
    PHPUnit\Framework\TestCase,
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
            $this->assertTrue($value->immutable());
            $this->assertInstanceOf(Model::class, $value->unwrap());
        }
    }

    public function testAllGeneratedMediaTypesAreParseable(): BlackBox\Proof
    {
        return $this
            ->forAll(MediaType::any())
            ->prove(function($mediaType) {
                $this->assertSame(
                    $mediaType->toString(),
                    Model::of($mediaType->toString())->toString(),
                );
            });
    }
}
