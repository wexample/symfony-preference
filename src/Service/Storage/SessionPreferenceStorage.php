<?php

namespace Wexample\SymfonyPreference\Service\Storage;

use Symfony\Component\HttpFoundation\RequestStack;
use Wexample\SymfonyPreference\Interface\PreferenceStorageInterface;

/**
 * The session, under one namespace, so preferences are told apart from the
 * rest of the session at a glance. It holds for someone who never signed in
 * and costs no row; it is gone with the session.
 */
class SessionPreferenceStorage implements PreferenceStorageInterface
{
    private const NAMESPACE = 'preference.';

    public function __construct(
        private readonly RequestStack $requestStack,
    ) {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->requestStack->getSession()->get(self::NAMESPACE.$key, $default);
    }

    public function all(): array
    {
        $preferences = [];

        foreach ($this->requestStack->getSession()->all() as $key => $value) {
            if (str_starts_with($key, self::NAMESPACE)) {
                $preferences[substr($key, strlen(self::NAMESPACE))] = $value;
            }
        }

        return $preferences;
    }

    public function set(string $key, mixed $value): void
    {
        $session = $this->requestStack->getSession();

        if (null === $value) {
            $session->remove(self::NAMESPACE.$key);

            return;
        }

        $session->set(self::NAMESPACE.$key, $value);
    }
}
