<?php

namespace Wexample\SymfonyPreference\Service;

use Wexample\SymfonyPreference\Interface\PreferenceStorageInterface;

/**
 * What a visitor chose and expects to find again: a menu folded, a zone
 * dragged wider, a filter kept. Kept where the app chose
 * (`wexample_symfony_preference.storage`, see PreferenceStorage), written by
 * the client through PreferenceController and read back while a page is
 * drawn, so it arrives as it was left; both ends go through this service,
 * which is why changing the storage needs nothing on the client.
 *
 * Keys read like paths, their first segment saying who they belong to —
 * `ui.layout.menu.left` is the design system's.
 */
class PreferenceService
{
    public function __construct(
        private readonly PreferenceStorageInterface $storage,
    ) {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->storage->get($key, $default);
    }

    /**
     * Every preference, or those whose key starts with `$prefix`, keyed as
     * they are kept.
     *
     * @return array<string, mixed>
     */
    public function all(?string $prefix = null): array
    {
        $preferences = $this->storage->all();

        if (null === $prefix) {
            return $preferences;
        }

        return array_filter(
            $preferences,
            static fn (string $key): bool => str_starts_with($key, $prefix),
            ARRAY_FILTER_USE_KEY
        );
    }

    /**
     * @param array<string, mixed> $values a null value forgets its key
     */
    public function set(array $values): void
    {
        foreach ($values as $key => $value) {
            $this->storage->set((string) $key, $value);
        }
    }
}
