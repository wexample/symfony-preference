<?php

declare(strict_types=1);

namespace Wexample\SymfonyPreference\Tests\Unit\Service\Storage;

use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Wexample\SymfonyPreference\Entity\Preference;
use Wexample\SymfonyPreference\Repository\PreferenceRepository;
use Wexample\SymfonyPreference\Service\PreferenceService;
use Wexample\SymfonyPreference\Service\Storage\DatabasePreferenceStorage;
use Wexample\SymfonyPreference\Service\Storage\CookiePreferenceStorage;

/**
 * Preferences written together reach the database in one flush; forgetting
 * a key never kept writes nothing.
 */
class DatabasePreferenceStorageTest extends TestCase
{
    public function testKeysWrittenTogetherAreFlushedOnce(): void
    {
        $kept = new Preference('ada', 'ui.layout.menu.left');
        $kept->setValue(true);

        $repository = $this->createMock(PreferenceRepository::class);
        $repository->expects($this->once())->method('findByOwnerIndexed')->with('ada')->willReturn(['ui.layout.menu.left' => $kept]);
        $repository->method('createNewPreference')->willReturnCallback(static fn (string $owner, string $key) => new Preference($owner, $key));
        $repository->expects($this->exactly(2))->method('save')->with($this->isInstanceOf(Preference::class), false);
        $repository->expects($this->once())->method('remove')->with($kept, false);
        $repository->expects($this->once())->method('flush');

        $storage = $this->createStorage($repository, 'ada');
        (new PreferenceService($storage))->set([
            'ui.layout.menu.left' => null,
            'ui.layout.zone.aside.size' => 320,
            'ui.color_scheme' => 'dark',
        ]);

        $this->assertNull($storage->get('ui.layout.menu.left'));
        $this->assertSame(320, $storage->get('ui.layout.zone.aside.size'));
        $this->assertSame(['ui.layout.zone.aside.size' => 320, 'ui.color_scheme' => 'dark'], $storage->all());
    }

    public function testForgettingAKeyNeverKeptWritesNothing(): void
    {
        $repository = $this->createMock(PreferenceRepository::class);
        $repository->method('findByOwnerIndexed')->willReturn([]);
        $repository->expects($this->never())->method('remove');
        $repository->expects($this->never())->method('flush');

        $this->createStorage($repository, 'ada')->set('ui.color_scheme', null);
    }

    public function testOneKeyWrittenAloneIsFlushedAtOnce(): void
    {
        $repository = $this->createMock(PreferenceRepository::class);
        $repository->method('findByOwnerIndexed')->willReturn([]);
        $repository->method('createNewPreference')->willReturnCallback(static fn (string $owner, string $key) => new Preference($owner, $key));
        $repository->expects($this->once())->method('flush');

        $storage = $this->createStorage($repository, 'ada');
        $storage->set('ui.color_scheme', 'dark');

        $this->assertSame('dark', $storage->get('ui.color_scheme'));
    }

    public function testAVisitorNotSignedInKeepsThemInACookie(): void
    {
        $repository = $this->createMock(PreferenceRepository::class);
        $repository->expects($this->never())->method('flush');

        $storage = $this->createStorage($repository, null);
        $storage->setMany(['ui.color_scheme' => 'dark', 'ui.layout.menu.left' => false]);

        $this->assertSame(['ui.color_scheme' => 'dark', 'ui.layout.menu.left' => false], $storage->all());
    }

    private function createStorage(PreferenceRepository $repository, ?string $username): DatabasePreferenceStorage
    {
        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn($username ? new InMemoryUser($username, null) : null);

        $request = new Request();
        $requestStack = new RequestStack();
        $requestStack->push($request);

        return new DatabasePreferenceStorage($repository, $security, new CookiePreferenceStorage($requestStack), 'user');
    }
}
