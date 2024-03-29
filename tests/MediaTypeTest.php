<?php
declare(strict_types = 1);

namespace Tests\Innmind\MediaType;

use Innmind\MediaType\{
    MediaType,
    Parameter,
    Exception\InvalidTopLevelType,
    Exception\DomainException,
};
use Innmind\Immutable\Sequence;
use PHPUnit\Framework\TestCase;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    Set,
};

class MediaTypeTest extends TestCase
{
    use BlackBox;

    public function testInterface()
    {
        $mediaType = new MediaType(
            'application',
            'json',
            'whatever',
            $parameter = new Parameter('charset', 'UTF-8'),
        );

        $this->assertTrue($mediaType->parameters()->equals(Sequence::of($parameter)));
        $this->assertSame('application', $mediaType->topLevel());
        $this->assertSame('json', $mediaType->subType());
        $this->assertSame('whatever', $mediaType->suffix());
        $this->assertSame('application/json+whatever; charset=UTF-8', $mediaType->toString());
    }

    public function testNull()
    {
        $this->assertInstanceOf(MediaType::class, MediaType::null());
        $this->assertSame('application/octet-stream', MediaType::null()->toString());
    }

    public function testStringCast()
    {
        $this->assertSame(
            'application/json',
            (new MediaType(
                'application',
                'json',
            ))->toString(),
        );
    }

    public function testThrowWhenTheTopLevelIsInvalid()
    {
        $this
            ->forAll(
                Set\Strings::any()->filter(static fn($string) => !MediaType::topLevels()->contains($string)),
                Set\Strings::any(),
            )
            ->then(function($topLevel, $subType) {
                $this->expectException(InvalidTopLevelType::class);
                $this->expectExceptionMessage($topLevel);

                new MediaType($topLevel, $subType);
            });
    }

    public function testMaybe()
    {
        $mediaType = MediaType::maybe(
            'application/tree.octet-stream+suffix;charset=UTF-8, another=param,me=too',
        )->match(
            static fn($value) => $value,
            static fn() => null,
        );

        $this->assertInstanceOf(MediaType::class, $mediaType);
        $this->assertSame('application', $mediaType->topLevel());
        $this->assertSame('tree.octet-stream', $mediaType->subType());
        $this->assertSame('suffix', $mediaType->suffix());
        $this->assertSame(3, $mediaType->parameters()->size());
        $parameters = $mediaType->parameters()->toList();
        $this->assertSame('charset', $parameters[0]->name());
        $this->assertSame('UTF-8', $parameters[0]->value());
        $this->assertSame('another', $parameters[1]->name());
        $this->assertSame('param', $parameters[1]->value());
        $this->assertSame('me', $parameters[2]->name());
        $this->assertSame('too', $parameters[2]->value());
        $this->assertSame(
            'application/tree.octet-stream+suffix; charset=UTF-8, another=param, me=too',
            $mediaType->toString(),
        );
    }

    public function testMaybeParametersInDoubleQuotes()
    {
        $mediaType = MediaType::maybe(
            'application/octet-stream;charset="UTF-8"',
        )->match(
            static fn($value) => $value,
            static fn() => null,
        );

        $this->assertInstanceOf(MediaType::class, $mediaType);
        $this->assertSame('application', $mediaType->topLevel());
        $this->assertSame('octet-stream', $mediaType->subType());
        $this->assertSame(1, $mediaType->parameters()->size());
        $parameters = $mediaType->parameters()->toList();
        $this->assertSame('charset', $parameters[0]->name());
        $this->assertSame('UTF-8', $parameters[0]->value());
    }

    public function testReturnNothingWhenInvalidMediaTypeString()
    {
        $this
            ->forAll(Set\Strings::any())
            ->then(function($string) {
                // this may optimistically generate a valid media type string at
                // some point but generally any random string is invalid
                $this->assertNull(
                    MediaType::maybe($string)->match(
                        static fn($value) => $value,
                        static fn() => null,
                    ),
                );
            });
    }

    public function testReturnNothingWhenTopLevelInvalid()
    {
        $this->assertNull(
            MediaType::maybe('unknown/json')->match(
                static fn($value) => $value,
                static fn() => null,
            ),
        );
    }

    public function testThrowWhenSubTypeInvalid()
    {
        $this
            ->forAll(
                Set\Strings::any()->filter(static fn($type) => !(bool) \preg_match('~^[A-Za-z0-9][A-Za-z0-9!#$&^_.-]{0,126}$~', $type)),
            )
            ->then(function($type) {
                $this->expectException(DomainException::class);
                $this->expectExceptionMessage($type);

                new MediaType('application', $type);
            });
    }

    public function testThrowWhenSuffixInvalid()
    {
        $this
            ->forAll(
                Set\Strings::atLeast(1)->filter(static fn($suffix) => !(bool) \preg_match('~^[A-Za-z0-9][A-Za-z0-9!#$&^_.-]{0,126}$~', $suffix)),
            )
            ->then(function($suffix) {
                try {
                    new MediaType('application', 'json', $suffix);
                    $this->fail('it should throw');
                } catch (DomainException $e) {
                    $this->assertSame($suffix, $e->getMessage());
                }
            });
    }
}
