<?php

namespace Wexample\SymfonyPreference\Enum;

/**
 * Where preferences are kept, as the app chooses it through the
 * `wexample_symfony_preference.storage` parameter.
 */
enum PreferenceStorage: string
{
    // The visitor's session: gone with the session.
    case Session = 'session';

    // The database, one set per signed-in user, following them from one machine
    // to another; a visitor not signed in keeps theirs in the session.
    case User = 'user';

    // The database, one set for everybody: a tool with no accounts, used by the
    // one person running it.
    case Shared = 'shared';

    public function isDatabase(): bool
    {
        return self::Session !== $this;
    }
}
