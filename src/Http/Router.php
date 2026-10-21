<?php

declare(strict_types=1);

namespace App\Http;

use App\Exception\NotFoundException;
use App\Http\ControllerResolver;

final class Router
{
    private array $routes = [];

    public function __construct(
        private readonly ControllerResolver $controllerResolver
    ) {
    }

    public function add(
        string $method,
        string $path,
        callable|array $handler
    ): void {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => '/' . trim($path, '/'),
            'handler' => $handler,
        ];
    }

    public function dispatch(Request $request): Response
    {
        $path = parse_url($request->getUri(), PHP_URL_PATH);

        if (!is_string($path)) {
            return new JsonResponse(
                [
                    'error' => 'Invalid request URI'
                ],
                400
            );
        }

        $path = '/' . trim($path, '/');

        $allowedMethods = [];

        foreach ($this->routes as $route) {
            $pattern = preg_replace_callback(
                '/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/',
                static fn (array $matches): string => '(?P<' . $matches[1] . '>[^/]+)',
                $route['path']
            );

            $pattern = '#^' . $pattern . '$#D';

            if (!preg_match($pattern, $path, $matches)) {
                continue;
            }

            if ($route['method'] !== $request->getMethod()) {
                $allowedMethods[] = $route['method'];
                continue;
            }

            $params = array_filter(
                $matches,
                static fn (int|string $key): bool => is_string($key),
                ARRAY_FILTER_USE_KEY
            );

            $handler = $route['handler'];

            if (is_array($handler)) {
                $handler = $this->controllerResolver->resolve($handler);
            }

            $arguments = $this->resolveArguments(
                $handler,
                $request,
                $params
            );

            return $handler(...$arguments);
        }

        if ($allowedMethods !== []) {
            return new JsonResponse(
                [
                    'error' => 'Method Not Allowed',
                    'allowed_methods' => array_values(
                        array_unique($allowedMethods)
                    ),
                ],
                405
            );
        }

        throw new NotFoundException('Not Found');
    }

    private function resolveArguments(
        callable $handler,
        Request $request,
        array $routeParams
    ): array {
        $reflection = match (true) {
            is_array($handler) => new \ReflectionMethod(
                $handler[0],
                $handler[1]
            ),

            is_string($handler) && str_contains($handler, '::') => new \ReflectionMethod($handler),

            $handler instanceof \Closure => new \ReflectionFunction($handler),

            is_object($handler) => new \ReflectionMethod($handler, '__invoke'),

            default => new \ReflectionFunction($handler),
        };

        $arguments = [$request];

        $parameters = array_slice(
            $reflection->getParameters(),
            1
        );

        foreach ($parameters as $parameter) {
            $name = $parameter->getName();

            if (!array_key_exists($name, $routeParams)) {
                if ($parameter->isDefaultValueAvailable()) {
                    $arguments[] = $parameter->getDefaultValue();
                    continue;
                }

                throw new \LogicException("Missing route parameter: {$name}");
            }

            $value = $routeParams[$name];
            $type = $parameter->getType();

            if ($type instanceof \ReflectionNamedType) {
                if ($type->getName() === 'int') {
                    if (
                        !ctype_digit((string) $value)
                        ||
                        filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false
                    ) {
                        throw new \InvalidArgumentException("Parameter {$name} must be a positive integer");
                    }

                    $value = (int) $value;
                }
            }

            $arguments[] = $value;
        }

        return $arguments;
    }
}