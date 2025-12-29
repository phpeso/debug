<?php

/**
 * @copyright 2025 Anton Smirnov
 * @license MIT https://spdx.org/licenses/MIT.html
 */

declare(strict_types=1);

namespace Peso\Debug\Tests\Services;

use Arokettu\Date\Date;
use Peso\Core\Exceptions\ConversionNotPerformedException;
use Peso\Core\Exceptions\ExchangeRateNotFoundException;
use Peso\Core\Exceptions\RequestNotSupportedException;
use Peso\Core\Requests\CurrentConversionRequest;
use Peso\Core\Requests\CurrentExchangeRateRequest;
use Peso\Core\Requests\HistoricalConversionRequest;
use Peso\Core\Requests\HistoricalExchangeRateRequest;
use Peso\Core\Responses\ErrorResponse;
use Peso\Core\Types\Decimal;
use Peso\Debug\Services\BlackHoleService;
use PHPUnit\Framework\TestCase;
use stdClass;

final class BlackHoleServiceTest extends TestCase
{
    public function testSupportsAllSupported(): void
    {
        $service = new BlackHoleService();

        self::assertTrue($service->supports(new CurrentExchangeRateRequest('PHP', 'USD')));
        self::assertTrue($service->supports(new HistoricalExchangeRateRequest('PHP', 'USD', Date::today())));
        self::assertTrue($service->supports(new CurrentConversionRequest(Decimal::init(1), 'PHP', 'USD')));
        self::assertTrue($service->supports(
            new HistoricalConversionRequest(Decimal::init(1), 'PHP', 'USD', Date::today()),
        ));

        self::assertFalse($service->supports(new stdClass()));
    }

    public function testSupportsLimited(): void
    {
        $service = new BlackHoleService(
            CurrentExchangeRateRequest::class,
            HistoricalConversionRequest::class,
            stdClass::class, // still not supported
        );

        self::assertTrue($service->supports(new CurrentExchangeRateRequest('PHP', 'USD')));
        self::assertFalse($service->supports(new HistoricalExchangeRateRequest('PHP', 'USD', Date::today())));
        self::assertFalse($service->supports(new CurrentConversionRequest(Decimal::init(1), 'PHP', 'USD')));
        self::assertTrue($service->supports(
            new HistoricalConversionRequest(Decimal::init(1), 'PHP', 'USD', Date::today()),
        ));

        self::assertFalse($service->supports(new stdClass()));
    }

    public function testReturnsAppropriateErrors(): void
    {
        $service = new BlackHoleService();
        $today = Date::today();

        $response = $service->send(new CurrentExchangeRateRequest('PHP', 'USD'));
        self::assertInstanceOf(ErrorResponse::class, $response);
        self::assertInstanceOf(ExchangeRateNotFoundException::class, $response->exception);
        self::assertEquals('Unable to find exchange rate for PHP/USD', $response->exception->getMessage());

        $response = $service->send(new HistoricalExchangeRateRequest('PHP', 'USD', $today));
        self::assertInstanceOf(ErrorResponse::class, $response);
        self::assertInstanceOf(ExchangeRateNotFoundException::class, $response->exception);
        self::assertEquals(
            'Unable to find exchange rate for PHP/USD on ' . $today->toString(),
            $response->exception->getMessage(),
        );

        $response = $service->send(new CurrentConversionRequest(Decimal::init(1), 'PHP', 'USD'));
        self::assertInstanceOf(ErrorResponse::class, $response);
        self::assertInstanceOf(ConversionNotPerformedException::class, $response->exception);
        self::assertEquals('Unable to convert 1 PHP to USD', $response->exception->getMessage());

        $response = $service->send(new HistoricalConversionRequest(Decimal::init(1), 'PHP', 'USD', $today));
        self::assertInstanceOf(ErrorResponse::class, $response);
        self::assertInstanceOf(ConversionNotPerformedException::class, $response->exception);
        self::assertEquals(
            'Unable to convert 1 PHP to USD on ' . $today->toString(),
            $response->exception->getMessage(),
        );

        $response = $service->send(new stdClass());
        self::assertInstanceOf(ErrorResponse::class, $response);
        self::assertInstanceOf(RequestNotSupportedException::class, $response->exception);
        self::assertEquals('Unsupported request type: "stdClass"', $response->exception->getMessage());
    }

    public function testReturnsAppropriateErrorsWhenLimited(): void
    {
        $service = new BlackHoleService(
            CurrentExchangeRateRequest::class,
            HistoricalConversionRequest::class,
            stdClass::class, // still not supported
        );
        $today = Date::today();

        $response = $service->send(new CurrentExchangeRateRequest('PHP', 'USD'));
        self::assertInstanceOf(ErrorResponse::class, $response);
        self::assertInstanceOf(ExchangeRateNotFoundException::class, $response->exception);
        self::assertEquals('Unable to find exchange rate for PHP/USD', $response->exception->getMessage());

        $response = $service->send(new HistoricalExchangeRateRequest('PHP', 'USD', $today));
        self::assertInstanceOf(ErrorResponse::class, $response);
        self::assertInstanceOf(RequestNotSupportedException::class, $response->exception);
        self::assertEquals(\sprintf(
            'Unsupported request type: "%s"',
            HistoricalExchangeRateRequest::class,
        ), $response->exception->getMessage());

        $response = $service->send(new CurrentConversionRequest(Decimal::init(1), 'PHP', 'USD'));
        self::assertInstanceOf(ErrorResponse::class, $response);
        self::assertInstanceOf(RequestNotSupportedException::class, $response->exception);
        self::assertEquals(\sprintf(
            'Unsupported request type: "%s"',
            CurrentConversionRequest::class,
        ), $response->exception->getMessage());

        $response = $service->send(new HistoricalConversionRequest(Decimal::init(1), 'PHP', 'USD', $today));
        self::assertInstanceOf(ErrorResponse::class, $response);
        self::assertInstanceOf(ConversionNotPerformedException::class, $response->exception);
        self::assertEquals(
            'Unable to convert 1 PHP to USD on ' . $today->toString(),
            $response->exception->getMessage(),
        );

        $response = $service->send(new stdClass());
        self::assertInstanceOf(ErrorResponse::class, $response);
        self::assertInstanceOf(RequestNotSupportedException::class, $response->exception);
        self::assertEquals('Unsupported request type: "stdClass"', $response->exception->getMessage());
    }
}
