<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use PHPUnit\Framework\TestCase;

use App\Http\HtmlResponse;
use App\Http\Response;

final class HtmlResponseTest extends TestCase
{
    public function testHtmlResponse(): void
    {
        $response = new HtmlResponse(
            '<h1>Reservations</h1>',
            200
        );

        $this->assertInstanceOf(
            Response::class,
            $response
        );

        $this->assertSame(
            200,
            $response->getStatusCode()
        );

        $this->assertSame(
            '<h1>Reservations</h1>',
            $response->getContent()
        );
    }
}
