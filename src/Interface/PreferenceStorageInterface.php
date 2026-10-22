<?php

namespace Wexample\SymfonyPreference\Interface;

/**
 * Where PreferenceService keeps preferences. Keys read like paths —
 * `ui.layout.zone.<id>.size` —, values are whatever json holds.
 */
interface PreferenceStorageInterface
{
    public function get(string $key, mixed $default = null): mixed;

    /**
     * @return array<string, mixed>
     */
    public function all(): array;

    /**
     * A null value forgets the key: what reads it gets its default back.
     */
    public function set(string $key, mixed $value): void;
}
