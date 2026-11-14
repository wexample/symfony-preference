<?php

namespace Wexample\SymfonyPreference\Repository;

use Wexample\SymfonyHelpers\Repository\AbstractRepository;
use Wexample\SymfonyPreference\Entity\Preference;

/**
 * @method Preference saveNewPreference(string $owner, string $key)
 */
class PreferenceRepository extends AbstractRepository
{
    public static function getEntityClassName(): string
    {
        return Preference::class;
    }

    public function createNewPreference(string $owner, string $key): Preference
    {
        return new Preference($owner, $key);
    }

    /**
     * Writes what save() and remove() were told without flushing.
     */
    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }

    /**
     * @return array<string, Preference> keyed by preference key
     */
    public function findByOwnerIndexed(string $owner): array
    {
        $preferences = [];

        foreach ($this->findBy(['owner' => $owner]) as $preference) {
            $preferences[$preference->getKey()] = $preference;
        }

        return $preferences;
    }
}
