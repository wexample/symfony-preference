<?php

namespace Wexample\SymfonyPreference\Api\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Wexample\SymfonyApi\Api\Attribute\ValidateRequestContent;
use Wexample\SymfonyApi\Api\Class\ApiResponse;
use Wexample\SymfonyApi\Api\Controller\AbstractApiController;
use Wexample\SymfonyHelpers\Controller\AbstractController;
use Wexample\SymfonyPreference\Api\Dto\Entity\Preference\SetPreferencesDto;
use Wexample\SymfonyPreference\Service\PreferenceService;

/**
 * Where the client writes what a visitor chose (App::persistUiStates). Where
 * it is kept is PreferenceService's business: the client is the same whatever
 * the app chose.
 */
#[Route(path: 'api/preference/', name: 'api_preference_')]
class PreferenceController extends AbstractApiController
{
    #[Route(path: 'set', name: 'set', methods: [Request::METHOD_POST], options: AbstractController::ROUTE_OPTIONS_ONLY_EXPOSE)]
    #[ValidateRequestContent(dto: SetPreferencesDto::class, attributeName: 'setPreferencesDto')]
    public function set(
        SetPreferencesDto $setPreferencesDto,
        PreferenceService $preferenceService,
    ): ApiResponse {
        $preferenceService->set($setPreferencesDto->values);

        return self::apiResponseSuccess();
    }
}
