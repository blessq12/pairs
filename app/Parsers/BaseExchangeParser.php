<?php

namespace App\Parsers;

use App\Enums\KlineInterval;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use App\Exceptions\ExchangeParserException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

abstract class BaseExchangeParser implements ExchangeParserInterface
{
    protected Client $client;
    protected string $spotApiUrl;
    protected string $klineApiUrl;
    protected ?string $apiKey;
    protected ?string $apiSecret;

    public function __construct(string $spotApiUrl, string $klineApiUrl, ?string $apiKey = null, ?string $apiSecret = null)
    {
        $this->spotApiUrl = $spotApiUrl;
        $this->klineApiUrl = $klineApiUrl;
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;

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
                if ($retries >= 3) { // Фиксированное значение
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
                return 1000 * pow(2, $retries); // Фиксированное значение
            }
        ));

        $this->client = new Client([
            'timeout' => 10, // Фиксированное значение
            'connect_timeout' => 5, // Фиксированное значение
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
        if (!in_array($interval, KlineInterval::values())) {
            throw new ExchangeParserException(
                "Unsupported interval: {$interval}. Allowed intervals: " . implode(', ', KlineInterval::values())
            );
        }
    }

    /**
     * Получает лимит свечей
     */
    protected function getKlineLimit(): int
    {
        return 100; // Фиксированное значение
    }

    /**
     * Нормализует символ пары под формат конкретной биржи
     */
    abstract protected function normalizeSymbol(string $symbol): string;

    /**
     * Нормализует интервал под формат конкретной биржи
     */
    abstract protected function normalizeInterval(string $interval): string;

    /**
     * Получает список всех доступных валют на бирже
     * Базовая реализация извлекает валюты из торговых пар
     */
    public function getAllCurrencies(): array
    {
        $symbols = $this->getAllSymbols();
        $currencies = [];

        foreach ($symbols as $symbol) {
            // Извлекаем валюты из символа пары (например, BTCUSDT -> BTC, USDT)
            $currencies = array_merge($currencies, $this->extractCurrenciesFromSymbol($symbol));
        }

        // Убираем дубликаты и сортируем
        return array_unique($currencies);
    }

    /**
     * Извлекает валюты из символа торговой пары
     */
    protected function extractCurrenciesFromSymbol(string $symbol): array
    {
        // Популярные котируемые валюты
        $quoteCurrencies = ['USDT', 'USDC', 'BTC', 'ETH', 'BNB', 'BUSD', 'DAI', 'TUSD', 'PAX', 'USDK'];

        foreach ($quoteCurrencies as $quote) {
            if (str_ends_with($symbol, $quote)) {
                $base = substr($symbol, 0, -strlen($quote));
                if (!empty($base)) {
                    return [$base, $quote];
                }
            }
        }

        // Если не нашли стандартную котируемую валюту, делим пополам
        $length = strlen($symbol);
        if ($length >= 6) {
            $base = substr($symbol, 0, $length - 4);
            $quote = substr($symbol, -4);
            return [$base, $quote];
        }

        return [];
    }
}
