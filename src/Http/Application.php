<?php

declare(strict_types=1);

namespace App\Http;

use InvalidArgumentException;
use Throwable;

use App\Exception\ConflictException;
use App\Exception\NotFoundException;
use App\Exception\ValidationException;

final class Application
{
    public function __construct(
        private readonly Router $router
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

            return new JsonResponse(
                [
                    'error' => 'Internal Server Error'
                ],
                500
            );
        }
    }
}
