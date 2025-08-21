<?php

namespace App\Parsers;

use App\Models\Exchange;
use InvalidArgumentException;

class ExchangeParserFactory
{
    /**
     * Маппинг бирж на их парсеры
     */
    private const PARSERS = [
        'MEXC' => MexcParser::class,
        'Bybit' => BybitParser::class,
        'BingX' => BingXParser::class,
        'CoinEx' => CoinExParser::class,
    ];

    /**
     * Создаёт парсер для указанной биржи
     *
     * @throws InvalidArgumentException Если для биржи нет парсера
     */
    public function createParser(Exchange $exchange): ExchangeParserInterface
    {
        if (!isset(self::PARSERS[$exchange->name])) {
            throw new InvalidArgumentException(
                "Parser not found for exchange: {$exchange->name}"
            );
        }

        $parserClass = self::PARSERS[$exchange->name];

        if (!$exchange->spot_api_url || !$exchange->kline_api_url) {
            throw new InvalidArgumentException(
                "Exchange {$exchange->name} is missing required API URLs"
            );
        }

        // Получаем API ключи для биржи
        $apiKey = null;
        $apiSecret = null;

        // Временно отключаем API ключи из-за проблем с шифрованием
        // $exchangeApiKey = $exchange->apiKeys()->first();
        // if ($exchangeApiKey) {
        //     $apiKey = $exchangeApiKey->api_key;
        //     $apiSecret = $exchangeApiKey->api_secret;
        // }

        return new $parserClass(
            $exchange->spot_api_url,
            $exchange->kline_api_url,
            $apiKey,
            $apiSecret
        );
    }

    /**
     * Проверяет, есть ли парсер для указанной биржи
     */
    public function hasParser(string $exchangeName): bool
    {
        return isset(self::PARSERS[$exchangeName]);
    }
}
