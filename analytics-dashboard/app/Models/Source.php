<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;

#[Fillable(['name', 'slug'])]
class Source extends Model
{
    private const SESSION_KEY = 'traffic_source';

    /** @return HasMany<Metric, $this> */
    public function metrics(): HasMany
    {
        return $this->hasMany(Metric::class);
    }

    /** Источник: utm → внешний Referer → сессия → direct. */
    public static function resolveFromRequest(Request $request): self
    {
        $utm = strtolower((string) $request->query('utm_source', ''));

        if ($utm !== '') {
            $slug = static::slugFromKeyword($utm);
            static::rememberSource($slug);

            return static::findBySlug($slug);
        }

        $referrer = strtolower($request->headers->get('referer', ''));
        $ownHost = strtolower($request->getHost());

        if ($referrer !== '' && ! str_contains($referrer, $ownHost)) {
            $slug = static::slugFromKeyword($referrer);

            if ($slug !== 'direct') {
                static::rememberSource($slug);
            }

            return static::findBySlug($slug);
        }

        if ($saved = session(static::SESSION_KEY)) {
            return static::findBySlug($saved);
        }

        return static::findBySlug('direct');
    }

    private static function rememberSource(string $slug): void
    {
        if ($slug !== 'direct') {
            session([static::SESSION_KEY => $slug]);
        }
    }

    private static function findBySlug(string $slug): self
    {
        return static::query()->where('slug', $slug)->firstOrFail();
    }

    /** Ключевое слово (utm или referer) → slug источника. */
    private static function slugFromKeyword(string $text): string
    {
        return match (true) {
            str_contains($text, 'google')
                || str_contains($text, 'yandex')
                || str_contains($text, 'bing.')
                || str_contains($text, 'duckduckgo') => 'google',

            str_contains($text, 'facebook')
                || str_contains($text, 'instagram')
                || str_contains($text, 'vk.com')
                || str_contains($text, 'vk.ru')
                || str_contains($text, 't.me')
                || str_contains($text, 'telegram')
                || str_contains($text, 'twitter')
                || str_contains($text, 'x.com')
                || str_contains($text, 'tiktok')
                || str_contains($text, 'ok.ru')
                || str_contains($text, 'social') => 'social',

            str_contains($text, 'mail')
                || str_contains($text, 'gmail')
                || str_contains($text, 'outlook')
                || str_contains($text, 'email')
                || str_contains($text, 'newsletter') => 'email',

            in_array($text, ['google', 'direct', 'social', 'email'], true) => $text,

            default => 'direct',
        };
    }
}
