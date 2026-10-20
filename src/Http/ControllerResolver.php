<?php

declare(strict_types=1);

namespace App\Http;

final class ControllerResolver
{
    private array $controllers = [];

    public function register(string $class, object $instance): void
    {
        if (!$instance instanceof $class) {
            throw new \InvalidArgumentException(
                sprintf('Controller must be an instance of %s', $class)
            );
        }

        $this->controllers[$class] = $instance;
    }

    public function resolve(array $handler): callable
    {
        [$class, $method] = $handler;

        if (!isset($this->controllers[$class])) {
            throw new \LogicException(
                sprintf('Controller "%s" is not registered', $class)
            );
        }

        $controller = $this->controllers[$class];

        if (!is_callable([$controller, $method])) {
            throw new \LogicException(
                sprintf('Controller method "%s::%s" is not callable', $class, $method)
            );
        }

        return [$controller, $method];
    }
}