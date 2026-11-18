<?php

namespace Wexample\SymfonyPreference\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Wexample\SymfonyPreference\Service\PreferenceService;

/**
 * Where the client writes what a visitor chose (symfony-loader's
 * App::persistUiStates). Where it is kept is PreferenceService's business:
 * the client is the same whatever the app chose. Shipped with the
 * preferences themselves, so every app that keeps them can be written to.
 */
#[AsController]
class PreferenceController
{
    /**
     * Far more than a page writes at once: what is sent beyond is no
     * visitor's choice.
     */
    final public const int MAX_VALUES = 100;

    /**
     * The length of the column keeping a key (Entity\Preference).
     */
    final public const int MAX_KEY_LENGTH = 255;

    /**
     * Preferences written together — a theme setting its axes at once —, as
     * `{"values": {key: value}}`, a null value forgetting its key. Who they
     * belong to is never sent: it is the visitor's, or everybody's, as the
     * app chose.
     */
    #[Route(path: '_api/preference/set', name: 'api_preference_set', methods: [Request::METHOD_POST])]
    public function set(
        Request $request,
        PreferenceService $preferenceService,
    ): Response {
        $values = json_decode($request->getContent(), true)['values'] ?? null;

        if (! is_array($values) || count($values) > self::MAX_VALUES) {
            return new Response(null, Response::HTTP_BAD_REQUEST);
        }

        foreach (array_keys($values) as $key) {
            if (! is_string($key) || '' === $key || mb_strlen($key) > self::MAX_KEY_LENGTH) {
                return new Response(null, Response::HTTP_BAD_REQUEST);
            }
        }

        $preferenceService->set($values);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
