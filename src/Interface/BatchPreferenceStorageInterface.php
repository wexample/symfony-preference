<?php

namespace Wexample\SymfonyPreference\Interface;

/**
 * A storage that writes several keys at once for less than one at a time —
 * the database, in one flush. PreferenceService hands it what is written
 * together; a storage without it is written key by key.
 */
interface BatchPreferenceStorageInterface extends PreferenceStorageInterface
{
    /**
     * As set() for each key, a null value forgetting its key.
     *
     * @param array<string, mixed> $values
     */
    public function setMany(array $values): void;
}
