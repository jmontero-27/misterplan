<?php

declare(strict_types=1);

namespace App\Http;

use InvalidArgumentException;
use Throwable;
use Twig\Environment;

use App\Exception\ConflictException;
use App\Exception\NotFoundException;
use App\Exception\ValidationException;

final class Application
{
    public function __construct(
        private readonly Router $router,
        private readonly ?Environment $twig = null
    ) {
    }

    public function handle(Request $request): Response
    {
        try {
            return $this->router->dispatch($request);
        } catch (ValidationException $exception) {
            return new JsonResponse(
                [
                    'error' => 'Validation failed',
                    'details' => $exception->getErrors(),
                ],
                400
            );
        } catch (InvalidArgumentException $exception) {
            return new JsonResponse(
                [
                    'error' => $exception->getMessage()
                ],
                400
            );
        } catch (NotFoundException $exception) {
            if (!$this->isApiRequest($request)) {
                return $this->htmlError(404);
            }

            return new JsonResponse(
                [
                    'error' => $exception->getMessage()
                ],
                404
            );
        } catch (ConflictException $exception) {
            return new JsonResponse(
                [
                    'error' => $exception->getMessage()
                ],
                409
            );
        } catch (Throwable $exception) {
            error_log((string) $exception);

            if (!$this->isApiRequest($request)) {
                return $this->htmlError(500);
            }

            return new JsonResponse(
                [
                    'error' => 'Internal Server Error'
                ],
                500
            );
        }
    }

    private function isApiRequest(Request $request): bool
    {
        $path = parse_url($request->getUri(), PHP_URL_PATH);

        return is_string($path) && ($path === '/api' || str_starts_with($path, '/api/'));
    }

    private function htmlError(int $statusCode): HtmlResponse
    {
        if ($this->twig !== null) {
            try {
                return new HtmlResponse(
                    $this->twig->render("errors/{$statusCode}.html.twig"),
                    $statusCode
                );
            } catch (Throwable $exception) {
                error_log((string) $exception);
            }
        }

        return new HtmlResponse(
            '<!DOCTYPE html><html lang="es"><meta charset="utf-8">'
            . '<title>Error</title><h1>No se pudo mostrar la página</h1>'
            . '<a href="/">Volver a reservas</a></html>',
            $statusCode
        );
    }
}
