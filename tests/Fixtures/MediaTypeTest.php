<?php
declare(strict_types = 1);

namespace Tests\Innmind\MediaType\Fixtures;

use Fixtures\Innmind\MediaType\MediaType;
use Innmind\MediaType\MediaType as Model;
use PHPUnit\Framework\TestCase;
use Innmind\BlackBox\Set;

class MediaTypeTest extends TestCase
{
    public function testInterface()
    {
        $set = MediaType::any();

        $this->assertInstanceOf(Set::class, $set);

        foreach ($set->values() as $value) {
            $this->assertInstanceOf(Model::class, $value);
        }
    }
}
