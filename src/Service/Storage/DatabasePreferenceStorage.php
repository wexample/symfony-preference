<?php

namespace Wexample\SymfonyPreference\Service\Storage;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Wexample\SymfonyPreference\DependencyInjection\WexampleSymfonyPreferenceExtension;
use Wexample\SymfonyPreference\Entity\Preference;
use Wexample\SymfonyPreference\Enum\PreferenceStorage;
use Wexample\SymfonyPreference\Interface\PreferenceStorageInterface;
use Wexample\SymfonyPreference\Repository\PreferenceRepository;
use Symfony\Contracts\Service\ResetInterface;

/**
 * The database: preferences follow a user from one machine to another
 * (PreferenceStorage::User), or are the one set of a tool without accounts
 * (PreferenceStorage::Shared). Per user, a visitor not signed in has no row of
 * their own and keeps theirs in the session.
 *
 * A page reads many keys and writes few: the rows of the owner are read once
 * per request and written one at a time.
 */
class DatabasePreferenceStorage implements PreferenceStorageInterface, ResetInterface
{
    private const SHARED_OWNER = '';

    private readonly bool $shared;

    /** @var array<string, array<string, Preference>> by owner, then by key */
    private array $loaded = [];

    public function __construct(
        private readonly PreferenceRepository $repository,
        private readonly Security $security,
        private readonly SessionPreferenceStorage $session,
        #[Autowire(param: WexampleSymfonyPreferenceExtension::PARAMETER_STORAGE)]
        string $storage,
    ) {
        $this->shared = PreferenceStorage::Shared === PreferenceStorage::from($storage);
    }

    /**
     * Read once per request: kept by a worker, a preference changed elsewhere
     * would never be seen again.
     */
    public function reset(): void
    {
        $this->loaded = [];
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $owner = $this->owner();

        if (null === $owner) {
            return $this->session->get($key, $default);
        }

        $preference = $this->preferences($owner)[$key] ?? null;

        return $preference ? $preference->getValue() : $default;
    }

    public function all(): array
    {
        $owner = $this->owner();

        if (null === $owner) {
            return $this->session->all();
        }

        return array_map(
            static fn (Preference $preference): mixed => $preference->getValue(),
            $this->preferences($owner)
        );
    }

    public function set(string $key, mixed $value): void
    {
        $owner = $this->owner();

        if (null === $owner) {
            $this->session->set($key, $value);

            return;
        }

        $preference = $this->preferences($owner)[$key] ?? null;

        if (null === $value) {
            if ($preference) {
                $this->repository->remove($preference);
                unset($this->loaded[$owner][$key]);
            }

            return;
        }

        if (! $preference) {
            $preference = $this->repository->createNewPreference($owner, $key);
            $this->loaded[$owner][$key] = $preference;
        }

        $preference->setValue($value);
        $this->repository->save($preference);
    }

    /**
     * Null when there is no row to keep them in: per user, nobody signed in.
     */
    private function owner(): ?string
    {
        if ($this->shared) {
            return self::SHARED_OWNER;
        }

        return $this->security->getUser()?->getUserIdentifier();
    }

    /**
     * @return array<string, Preference>
     */
    private function preferences(string $owner): array
    {
        return $this->loaded[$owner] ??= $this->repository->findByOwnerIndexed($owner);
    }
}
