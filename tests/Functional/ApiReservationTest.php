<?php

declare(strict_types=1);

namespace Tests\Functional;

use App\Enum\ReservationEventType;
use App\Enum\ReservationStatus;

final class ApiReservationTest extends FunctionalTestCase
{
    private function validReservationData(): array
    {
        return [
            'nombre_huesped' => 'Test Funcional',
            'email_huesped' => 'funcional@example.com',
            'nombre_alojamiento' => 'Hotel PHPUnit',
            'fecha_entrada' => '2026-11-20',
            'fecha_salida' => '2026-11-25',
            'importe' => '450.00',
            'notas' => 'Reserva creada por PHPUnit',
        ];
    }
    
    public function testHealthEndpoint(): void
    {
        $response = $this->client->request(
            'GET',
            '/api/health'
        );

        $this->assertSame(
            200,
            $response->getStatusCode()
        );

        $this->assertSame(
            [
                'status' => 'ok'
            ],
            $response->getData()
        );
    }

    public function testNotFoundEndpoint(): void
    {
        $response = $this->client->request(
            'GET',
            '/api/ruta-inexistente'
        );

        $this->assertSame(
            404,
            $response->getStatusCode()
        );

        $this->assertSame(
            [
                'error' => 'Not Found'
            ],
            $response->getData()
        );
    }

    public function testMethodNotAllowed(): void
    {
        $response = $this->client->request(
            'DELETE',
            '/api/reservations'
        );

        $this->assertSame(
            405,
            $response->getStatusCode()
        );

        $this->assertContains(
            'GET',
            $response->getData()['allowed_methods']
        );
    }

    public function testGetNonexistentReservation(): void
    {
        $response = $this->client->request(
            'GET',
            '/api/reservations/999999'
        );

        $this->assertSame(
            404,
            $response->getStatusCode()
        );
    }

    public function testCreateReservationWithInvalidData(): void
    {
        $response = $this->client->request(
            'POST',
            '/api/reservations',
            body: []
        );

        $this->assertSame(
            400,
            $response->getStatusCode()
        );

        $this->assertSame(
            'Validation failed',
            $response->getData()['error']
        );

        $this->assertArrayHasKey(
            'details',
            $response->getData()
        );
    }

    public function testConfirmNonexistentReservation(): void
    {
        $response = $this->client->request(
            'PATCH',
            '/api/reservations/999999/confirm'
        );

        $this->assertSame(
            404,
            $response->getStatusCode()
        );
    }

    public function testCreateReservation(): void
    {
        $response = $this->client->request(
            'POST',
            '/api/reservations',
            body: $this->validReservationData()
        );

        $this->assertSame(
            201,
            $response->getStatusCode()
        );

        $reservation = $response->getData()['data'];

        $this->assertSame(
            ReservationStatus::PENDING->value,
            $reservation['estado']
        );

        $this->assertCount(
            1,
            $reservation['eventos']
        );

        $this->assertSame(
            ReservationEventType::CREATION->value,
            $reservation['eventos'][0]['tipo']
        );
    }

    public function testGetReservation(): void
    {
        $id = $this->createTestReservation();

        $response = $this->client->request(
            'GET',
            "/api/reservations/{$id}"
        );

        $this->assertSame(
            200,
            $response->getStatusCode()
        );

        $this->assertEquals(
            $id,
            $response->getData()['data']['id']
        );
    }

    public function testConfirmReservation(): void
    {
        $id = $this->createTestReservation();

        $response = $this->client->request(
            'PATCH',
            "/api/reservations/{$id}/confirm"
        );

        $this->assertSame(
            200,
            $response->getStatusCode()
        );

        $reservation = $response->getData()['data'];

        $this->assertSame(
            ReservationStatus::CONFIRMED->value,
            $reservation['estado']
        );
        
        $this->assertCount(
            2,
            $reservation['eventos']
        );

        $this->assertSame(
            ReservationEventType::CONFIRMATION->value,
            $reservation['eventos'][1]['tipo']
        );
    }

    public function testCancelReservation(): void
    {
        $id = $this->createTestReservation();

        $response = $this->client->request(
            'PATCH',
            "/api/reservations/{$id}/cancel"
        );

        $this->assertSame(
            200,
            $response->getStatusCode()
        );

        $reservation = $response->getData()['data'];

        $this->assertSame(
            ReservationStatus::CANCELLED->value,
            $reservation['estado']
        );

        $this->assertCount(
            2,
            $reservation['eventos']
        );

        $this->assertSame(
            ReservationEventType::CANCELLATION->value,
            $reservation['eventos'][1]['tipo']
        );
    }

    public function testCannotConfirmCancelledReservation(): void
    {
        $id = $this->createTestReservation();

        $this->client->request(
            'PATCH',
            "/api/reservations/{$id}/cancel"
        );

        $response = $this->client->request(
            'PATCH',
            "/api/reservations/{$id}/confirm"
        );

        $this->assertSame(
            409,
            $response->getStatusCode()
        );

        $detailResponse = $this->client->request(
            'GET',
            "/api/reservations/{$id}"
        );

        $this->assertCount(
            2,
            $detailResponse->getData()['data']['eventos']
        );
    }

    private function createTestReservation(): int
    {
        $response = $this->client->request(
            'POST',
            '/api/reservations',
            body: $this->validReservationData()
        );

        $this->assertSame(
            201,
            $response->getStatusCode()
        );

        return (int) $response->getData()['data']['id'];
    }
}
