<?php

/**
 * @copyright 2025 Anton Smirnov
 * @license MIT https://spdx.org/licenses/MIT.html
 */

declare(strict_types=1);

namespace Peso\Debug\Services;

use Override;
use Peso\Core\Exceptions\ConversionNotPerformedException;
use Peso\Core\Exceptions\ExchangeRateNotFoundException;
use Peso\Core\Exceptions\RequestNotSupportedException;
use Peso\Core\Requests\CurrentConversionRequest;
use Peso\Core\Requests\CurrentExchangeRateRequest;
use Peso\Core\Requests\HistoricalConversionRequest;
use Peso\Core\Requests\HistoricalExchangeRateRequest;
use Peso\Core\Responses\ErrorResponse;
use Peso\Core\Services\PesoServiceInterface;

/**
 * Accepts all valid requests and returns the corresponding "not found" error
 */
final readonly class BlackHoleService implements PesoServiceInterface
{
    /**
     * @var array<class-string>
     */
    private array $requests;

    /**
     * @param class-string ...$requests
     */
    public function __construct(string ...$requests)
    {
        $this->requests = $requests;
    }

    #[Override]
    public function send(object $request): ErrorResponse
    {
        if ($this->requests !== [] && !\in_array($request::class, $this->requests)) {
            goto notSupported;
        }

        if ($request instanceof CurrentExchangeRateRequest || $request instanceof HistoricalExchangeRateRequest) {
            return new ErrorResponse(ExchangeRateNotFoundException::fromRequest($request));
        }

        if ($request instanceof CurrentConversionRequest || $request instanceof HistoricalConversionRequest) {
            return new ErrorResponse(ConversionNotPerformedException::fromRequest($request));
        }

        notSupported:
        return new ErrorResponse(RequestNotSupportedException::fromRequest($request));
    }

    #[Override]
    public function supports(object $request): bool
    {
        if ($this->requests !== [] && !\in_array($request::class, $this->requests)) {
            return false;
        }

        // only standard requests
        return
            $request instanceof CurrentExchangeRateRequest ||
            $request instanceof HistoricalExchangeRateRequest ||
            $request instanceof CurrentConversionRequest ||
            $request instanceof HistoricalConversionRequest;
    }
}
