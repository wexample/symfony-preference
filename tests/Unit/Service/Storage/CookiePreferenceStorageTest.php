<?php

declare(strict_types=1);

namespace Wexample\SymfonyPreference\Tests\Unit\Service\Storage;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Wexample\SymfonyPreference\Service\Storage\CookiePreferenceStorage;

/**
 * Preferences read from the cookie the browser sends back, written to the
 * one its response carries: nothing kept on the server, and what the cookie
 * holds that is no map of preferences read as none.
 */
class CookiePreferenceStorageTest extends TestCase
{
    public function testWhatTheBrowserSendsBackIsRead(): void
    {
        $storage = $this->createStorage(new Request(cookies: [CookiePreferenceStorage::COOKIE => '{"ui.color_scheme":"dark"}']));

        $this->assertSame('dark', $storage->get('ui.color_scheme'));
        $this->assertSame('light', $storage->get('ui.density', 'light'));
    }

    public function testACookieHoldingNoMapIsReadAsNone(): void
    {
        foreach (['not json', '["dark"]', '"dark"'] as $value) {
            $storage = $this->createStorage(new Request(cookies: [CookiePreferenceStorage::COOKIE => $value]));

            $this->assertSame([], $storage->all());
        }
    }

    public function testWhatIsWrittenIsReadBackAndGoesOutWithTheResponse(): void
    {
        $request = new Request(cookies: [CookiePreferenceStorage::COOKIE => '{"ui.color_scheme":"dark","ui.layout.menu.left":true}']);
        $storage = $this->createStorage($request);

        $storage->setMany(['ui.color_scheme' => 'light', 'ui.layout.menu.left' => null]);

        $this->assertSame(['ui.color_scheme' => 'light'], $storage->all());
        $this->assertSame('{"ui.color_scheme":"light"}', $storage->cookieFor($request)?->getValue());
    }

    public function testARequestWritingNothingSendsNoCookie(): void
    {
        $request = new Request(cookies: [CookiePreferenceStorage::COOKIE => '{"ui.color_scheme":"dark"}']);

        $this->assertNull($this->createStorage($request)->cookieFor($request));
    }

    public function testForgettingTheLastOneClearsTheCookie(): void
    {
        $request = new Request(cookies: [CookiePreferenceStorage::COOKIE => '{"ui.color_scheme":"dark"}']);
        $storage = $this->createStorage($request);

        $storage->set('ui.color_scheme', null);

        $this->assertTrue($storage->cookieFor($request)?->isCleared());
    }

    public function testMoreThanABrowserKeepsIsNotSent(): void
    {
        $request = new Request();
        $storage = $this->createStorage($request);

        $storage->set('ui.note', str_repeat('a', CookiePreferenceStorage::MAX_LENGTH));

        $this->assertNull($storage->cookieFor($request));
    }

    private function createStorage(Request $request): CookiePreferenceStorage
    {
        $requestStack = new RequestStack();
        $requestStack->push($request);

        return new CookiePreferenceStorage($requestStack);
    }
}
