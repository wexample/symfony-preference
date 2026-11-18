<?php

namespace Wexample\SymfonyPreference\Service\Storage;

use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Wexample\SymfonyPreference\Interface\BatchPreferenceStorageInterface;

/**
 * A cookie on the visitor's browser, the preferences in it as json: nothing is
 * kept on the server, and reading them opens no session — a page drawn for a
 * visitor costs what it would without them.
 *
 * What a request writes waits on the request itself, not on this service a
 * worker keeps from one visitor to the next, and goes out with its response
 * (PreferenceCookieSubscriber).
 */
class CookiePreferenceStorage implements BatchPreferenceStorageInterface
{
    final public const string COOKIE = 'preferences';

    private const string WRITTEN_ATTRIBUTE = '_preferences_written';

    /**
     * Under the four kilobytes a browser keeps for a cookie, its name and
     * attributes included: beyond, it would drop the whole cookie silently.
     */
    final public const int MAX_LENGTH = 3800;

    public function __construct(
        private readonly RequestStack $requestStack,
    ) {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    public function all(): array
    {
        $request = $this->requestStack->getMainRequest();

        if (! $request) {
            return [];
        }

        if ($request->attributes->has(self::WRITTEN_ATTRIBUTE)) {
            return $request->attributes->get(self::WRITTEN_ATTRIBUTE);
        }

        // Sent back by the browser, so whatever it holds: what is not a map of
        // preferences is read as none.
        $preferences = json_decode((string) $request->cookies->get(self::COOKIE, ''), true);

        return is_array($preferences) && ! array_is_list($preferences) ? $preferences : [];
    }

    public function set(string $key, mixed $value): void
    {
        $this->setMany([$key => $value]);
    }

    public function setMany(array $values): void
    {
        $request = $this->requestStack->getMainRequest();

        if (! $request) {
            return;
        }

        $preferences = $this->all();

        foreach ($values as $key => $value) {
            if (null === $value) {
                unset($preferences[(string) $key]);
            } else {
                $preferences[(string) $key] = $value;
            }
        }

        $request->attributes->set(self::WRITTEN_ATTRIBUTE, $preferences);
    }

    /**
     * The cookie the response carries for what $request wrote, null when it
     * wrote nothing — or more than a browser would keep, which then keeps
     * what it had.
     */
    public function cookieFor(Request $request): ?Cookie
    {
        if (! $request->attributes->has(self::WRITTEN_ATTRIBUTE)) {
            return null;
        }

        $preferences = $request->attributes->get(self::WRITTEN_ATTRIBUTE);

        if ([] === $preferences) {
            return Cookie::create(self::COOKIE)->withExpires(1);
        }

        $value = json_encode($preferences, \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE);

        if (strlen($value) > self::MAX_LENGTH) {
            return null;
        }

        return Cookie::create(self::COOKIE, $value, new \DateTimeImmutable('+1 year'))
            ->withSameSite(Cookie::SAMESITE_LAX);
    }
}
