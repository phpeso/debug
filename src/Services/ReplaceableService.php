<?php

declare(strict_types=1);

namespace Peso\Debug\Services;

use Peso\Core\Responses\ConversionResponse;
use Peso\Core\Responses\ErrorResponse;
use Peso\Core\Responses\ExchangeRateResponse;
use Peso\Core\Services\PesoServiceInterface;

final class ReplaceableService implements PesoServiceInterface
{
    public function __construct(
        public PesoServiceInterface $service,
    ) {
    }

    public function send(object $request): ExchangeRateResponse|ConversionResponse|ErrorResponse
    {
        return $this->service->send($request);
    }

    public function supports(object $request): bool
    {
        return $this->service->supports($request);
    }
}
