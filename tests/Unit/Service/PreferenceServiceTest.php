<?php

namespace Wexample\SymfonyPreference\Tests\Unit\Service;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Wexample\SymfonyPreference\Service\PreferenceService;
use Wexample\SymfonyPreference\Service\Storage\SessionPreferenceStorage;

/**
 * Preferences written together and read back, kept in the session.
 */
class PreferenceServiceTest extends TestCase
{
    private PreferenceService $preferences;

    protected function setUp(): void
    {
        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $this->preferences = new PreferenceService(new SessionPreferenceStorage($requestStack));
    }

    public function testWhatIsWrittenIsReadBack(): void
    {
        $this->preferences->set(['ui.layout.menu.left' => false, 'ui.layout.zone.aside.size' => 320]);

        $this->assertFalse($this->preferences->get('ui.layout.menu.left', true));
        $this->assertSame(320, $this->preferences->get('ui.layout.zone.aside.size'));
    }

    public function testANullValueForgetsItsKey(): void
    {
        $this->preferences->set(['ui.layout.menu.left' => false]);
        $this->preferences->set(['ui.layout.menu.left' => null]);

        $this->assertTrue($this->preferences->get('ui.layout.menu.left', true));
    }

    public function testAPrefixKeepsTheKeysOfItsOwner(): void
    {
        $this->preferences->set(['ui.color_scheme' => 'dark', 'board.last_app' => 'acme']);

        $this->assertSame(['ui.color_scheme' => 'dark'], $this->preferences->all('ui.'));
        $this->assertCount(2, $this->preferences->all());
    }
}
