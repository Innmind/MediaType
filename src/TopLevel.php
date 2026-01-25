<?php
declare(strict_types = 1);

namespace Innmind\MediaType;

use Innmind\Immutable\Maybe;

/**
 * @psalm-immutable
 */
enum TopLevel
{
    case application;
    case audio;
    case font;
    case example;
    case image;
    case message;
    case model;
    case multipart;
    case text;
    case video;

    /**
     * @psalm-pure
     *
     * @return Maybe<self>
     */
    #[\NoDiscard]
    public static function maybe(string $value): Maybe
    {
        return Maybe::of(match ($value) {
            'application' => self::application,
            'audio' => self::audio,
            'font' => self::font,
            'example' => self::example,
            'image' => self::image,
            'message' => self::message,
            'model' => self::model,
            'multipart' => self::multipart,
            'text' => self::text,
            'video' => self::video,
            default => null,
        });
    }
}
