<?php

namespace Wexample\SymfonyPreference\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Wexample\SymfonyPreference\Service\Storage\CookiePreferenceStorage;

/**
 * Hands the browser the preferences a request wrote to the cookie storage.
 */
class PreferenceCookieSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly CookiePreferenceStorage $storage,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::RESPONSE => 'onResponse'];
    }

    public function onResponse(ResponseEvent $event): void
    {
        if (! $event->isMainRequest()) {
            return;
        }

        $cookie = $this->storage->cookieFor($event->getRequest());

        if ($cookie) {
            $event->getResponse()->headers->setCookie($cookie);
        }
    }
}
