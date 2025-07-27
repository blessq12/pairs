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
        // Добавляй новые биржи здесь
        // 'Bybit' => BybitParser::class,
        // 'BingX' => BingXParser::class,
        // 'CoinEx' => CoinExParser::class,
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

        return new $parserClass(
            $exchange->spot_api_url,
            $exchange->kline_api_url
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
