<?php

declare(strict_types=1);

namespace Peso\Debug\Tests\Services;

use Peso\Core\Exceptions\ExchangeRateNotFoundException;
use Peso\Core\Exceptions\RequestNotSupportedException;
use Peso\Core\Requests\CurrentExchangeRateRequest;
use Peso\Core\Responses\ErrorResponse;
use Peso\Core\Services\NullService;
use Peso\Debug\Services\BlackHoleService;
use Peso\Debug\Services\ReplaceableService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ReplaceableService::class)]
final class ReplaceableServiceTest extends TestCase
{
    public function testSend(): void
    {
        $service = new ReplaceableService(new BlackHoleService());

        $response = $service->send(new CurrentExchangeRateRequest('EUR', 'PHP'));
        self::assertInstanceOf(ErrorResponse::class, $response);
        self::assertInstanceOf(ExchangeRateNotFoundException::class, $response->exception);

        $service->service = new NullService();

        $response = $service->send(new CurrentExchangeRateRequest('EUR', 'PHP'));
        self::assertInstanceOf(ErrorResponse::class, $response);
        self::assertInstanceOf(RequestNotSupportedException::class, $response->exception);
    }

    public function testSupports(): void
    {
        $service = new ReplaceableService(new BlackHoleService());

        self::assertTrue($service->supports(new CurrentExchangeRateRequest('EUR', 'PHP')));

        $service->service = new NullService();

        self::assertFalse($service->supports(new CurrentExchangeRateRequest('EUR', 'PHP')));
    }
}
