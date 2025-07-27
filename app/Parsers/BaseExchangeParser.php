<?php

namespace App\Parsers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use App\Exceptions\ExchangeParserException;
use App\Models\Setting;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

abstract class BaseExchangeParser implements ExchangeParserInterface
{
    protected Client $client;
    protected string $spotApiUrl;
    protected string $klineApiUrl;
    protected array $settings;

    public function __construct(string $spotApiUrl, string $klineApiUrl)
    {
        $this->spotApiUrl = $spotApiUrl;
        $this->klineApiUrl = $klineApiUrl;
        $this->settings = Setting::getAll();

        // Создаем стек обработчиков для ретраев
        $stack = HandlerStack::create();

        // Добавляем middleware для ретраев
        $stack->push(Middleware::retry(
            function (
                $retries,
                Request $request,
                ?Response $response = null,
                ?\Exception $exception = null
            ) {
                // Ретраим при таймаутах и 5xx ошибках
                if ($retries >= $this->settings['parser_retry_attempts']) {
                    return false;
                }

                if ($exception instanceof \GuzzleHttp\Exception\ConnectException) {
                    return true;
                }

                if ($response && $response->getStatusCode() >= 500) {
                    return true;
                }

                return false;
            },
            function ($retries) {
                // Экспоненциальная задержка между попытками
                return $this->settings['parser_retry_delay'] * pow(2, $retries);
            }
        ));

        $this->client = new Client([
            'timeout' => $this->settings['parser_timeout'],
            'connect_timeout' => $this->settings['parser_connect_timeout'],
            'handler' => $stack,
        ]);
    }

    /**
     * Выполняет HTTP-запрос с обработкой ошибок
     *
     * @throws ExchangeParserException
     */
    protected function makeRequest(string $url, array $params = []): array
    {
        try {
            $response = $this->client->get($url, [
                'query' => $params,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new ExchangeParserException('Invalid JSON response');
            }

            return $data;
        } catch (GuzzleException $e) {
            Log::error("Exchange request failed: {$e->getMessage()}", [
                'url' => $url,
                'params' => $params,
                'exception' => $e,
            ]);
            throw new ExchangeParserException(
                "Failed to fetch data from exchange: {$e->getMessage()}",
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Проверяет, поддерживается ли интервал
     */
    protected function validateInterval(string $interval): void
    {
        $allowedIntervals = json_decode($this->settings['parser_allowed_intervals'], true);

        if (!in_array($interval, $allowedIntervals)) {
            throw new ExchangeParserException(
                "Unsupported interval: {$interval}. Allowed intervals: " . implode(', ', $allowedIntervals)
            );
        }
    }

    /**
     * Получает лимит свечей из настроек
     */
    protected function getKlineLimit(): int
    {
        return $this->settings['parser_kline_limit'];
    }

    /**
     * Нормализует символ пары под формат конкретной биржи
     */
    abstract protected function normalizeSymbol(string $symbol): string;

    /**
     * Нормализует интервал под формат конкретной биржи
     */
    abstract protected function normalizeInterval(string $interval): string;
}
