<?php
declare(strict_types = 1);

namespace Tests\Innmind\MediaType;

use Innmind\MediaType\{
    MediaType,
    Parameter,
    Exception\InvalidTopLevelType,
    Exception\InvalidMediaTypeString,
    Exception\DomainException,
};
use Innmind\Immutable\Sequence;
use function Innmind\Immutable\unwrap;
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

        $this->assertTrue($mediaType->parameters()->equals(Sequence::of(Parameter::class, $parameter)));
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
            ))->toString()
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

    public function testOf()
    {
        $mediaType = MediaType::of(
            'application/tree.octet-stream+suffix;charset=UTF-8, another=param,me=too'
        );

        $this->assertInstanceOf(MediaType::class, $mediaType);
        $this->assertSame('application', $mediaType->topLevel());
        $this->assertSame('tree.octet-stream', $mediaType->subType());
        $this->assertSame('suffix', $mediaType->suffix());
        $this->assertSame(3, $mediaType->parameters()->size());
        $parameters = unwrap($mediaType->parameters());
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

    public function testThrowWhenInvalidMediaTypeString()
    {
        $this
            ->forAll(new Set\Either(
                Set\Strings::any(),
                Set\Elements::of('application/vnd.openxmlformats-officedocument.wordprocessingml.documentapplication/vnd.openxmlformats-officedocument.wordprocessingml.document'),
            ))
            ->then(function($string) {
                // this may optimistically generate a valid media type string at
                // some point but generally any random string is invalid
                $this->expectException(InvalidMediaTypeString::class);
                $this->expectExceptionMessage($string);

                MediaType::of($string);
            });
    }

    public function testThrowWhenSubTypeInvalid()
    {
        $this
            ->forAll(
                Set\Strings::any()->filter(fn($type) => !(bool) \preg_match('~^[A-Za-z0-9][A-Za-z0-9!#$&^_.-]{0,126}$~', $type))
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
                Set\Strings::any()->filter(fn($suffix) => !(bool) \preg_match('~^[A-Za-z0-9][A-Za-z0-9!#$&^_.-]{0,126}$~', $suffix))
            )
            ->then(function($suffix) {
                $this->expectException(DomainException::class);
                $this->expectExceptionMessage($suffix);

                new MediaType('application', 'json', $suffix);
            });
    }
}
