<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

use App\Exception\NotFoundException;
use App\Http\Application;
use App\Http\ControllerResolver;
use App\Http\HtmlResponse;
use App\Http\JsonResponse;
use App\Http\Request;
use App\Http\Router;

final class ErrorResponseTest extends TestCase
{
    public function testMissingWebRoutesReturnHtml404(): void
    {
        $application = $this->application();

        $response = $application->handle(
            new Request(
                'GET',
                '/missing-page'
            )
        );

        self::assertInstanceOf(
            HtmlResponse::class,
            $response
        );

        self::assertSame(
            404,
            $response->getStatusCode()
        );

        self::assertStringContainsString(
            'Página no encontrada',
            $response->getContent()
        );

        self::assertStringContainsString(
            'Volver a reservas',
            $response->getContent()
        );
    }

    public function testMissingApiRoutesKeepJson404(): void
    {
        $response = $this->application()->handle(
            new Request(
                'GET',
                '/api/missing'
            )
        );

        self::assertInstanceOf(
            JsonResponse::class,
            $response
        );

        self::assertSame(
            404,
            $response->getStatusCode()
        );

        self::assertSame(
            [
                'error' => 'Not Found'
            ],
            $response->getData()
        );
    }

    public function testMissingWebReservationReturnsHtml404(): void
    {
        $router = new Router(
            new ControllerResolver()
        );

        $router->add(
            'GET',
            '/reservas/{id}',
            static function (Request $request, int $id): HtmlResponseç
            {
                throw new NotFoundException('Reservation not found');
            }
        );

        $response = $this->application($router)->handle(
            new Request(
                'GET',
                '/reservas/999999'
            )
        );

        self::assertInstanceOf(
            HtmlResponse::class,
            $response
        );

        self::assertSame(
            404,
            $response->getStatusCode()
        );
    }

    private function application(?Router $router = null): Application
    {
        return new Application(
            $router ?? new Router(
                new ControllerResolver()
            ),
            new Environment(
                new FilesystemLoader(
                    dirname(__DIR__, 3) . '/templates'
                )
            )
        );
    }
}