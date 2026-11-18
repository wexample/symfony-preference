<?php

declare(strict_types=1);

namespace Wexample\SymfonyPreference\Tests\Unit\Controller;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Wexample\SymfonyPreference\Controller\PreferenceController;
use Wexample\SymfonyPreference\Service\PreferenceService;
use Wexample\SymfonyPreference\Service\Storage\CookiePreferenceStorage;

/**
 * What the client writes is kept as sent; a body that is no set of
 * preferences, or more of them than a page writes, is refused whole.
 */
class PreferenceControllerTest extends TestCase
{
    public function testTheValuesSentAreKept(): void
    {
        [$response, $storage] = $this->post(['values' => ['ui.color_scheme' => 'dark', 'ui.layout.menu.left' => false]]);

        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        $this->assertSame(['ui.color_scheme' => 'dark', 'ui.layout.menu.left' => false], $storage->all());
    }

    public function testABodyThatIsNoSetOfPreferencesIsRefused(): void
    {
        foreach ([
            'no values' => [],
            'not a map' => ['values' => 'dark'],
            'a list' => ['values' => ['dark']],
            'too long a key' => ['values' => [str_repeat('k', PreferenceController::MAX_KEY_LENGTH + 1) => 'dark']],
            'too many' => ['values' => array_fill_keys(array_map(static fn (int $i): string => 'ui.k'.$i, range(0, PreferenceController::MAX_VALUES)), true)],
        ] as $case => $body) {
            [$response, $storage] = $this->post($body);

            $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode(), $case);
            $this->assertSame([], $storage->all(), $case);
        }
    }

    /**
     * @return array{Response, CookiePreferenceStorage}
     */
    private function post(array $body): array
    {
        $request = new Request(content: json_encode($body, \JSON_THROW_ON_ERROR));
        $requestStack = new RequestStack();
        $requestStack->push($request);
        $storage = new CookiePreferenceStorage($requestStack);

        return [(new PreferenceController())->set($request, new PreferenceService($storage)), $storage];
    }
}
