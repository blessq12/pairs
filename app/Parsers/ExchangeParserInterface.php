<?php

namespace App\Parsers;

interface ExchangeParserInterface
{
    /**
     * Получает текущие цены ask/bid для указанной пары
     *
     * @param string $symbol Торговая пара (например, 'BTC/USDT')
     * @return array{ask: float, bid: float} Массив с ценами
     * @throws \App\Exceptions\ExchangeParserException
     */
    public function getTicker(string $symbol): array;

    /**
     * Получает исторические свечи для пары
     *
     * @param string $symbol Торговая пара (например, 'BTC/USDT')
     * @param string $interval Интервал ('1m', '5m', '15m', '30m', '1h', '4h', '1d')
     * @return array<array{
     *     timestamp: int,
     *     open: float,
     *     high: float,
     *     low: float,
     *     close: float,
     *     volume: float
     * }> Массив свечей
     * @throws \App\Exceptions\ExchangeParserException
     */
    public function getKline(string $symbol, string $interval): array;

    /**
     * Получает список всех доступных торговых пар
     *
     * @return array<string> Массив символов пар (например, ['BTCUSDT', 'ETHUSDT'])
     * @throws \App\Exceptions\ExchangeParserException
     */
    public function getAllSymbols(): array;

    /**
     * Получает список всех доступных валют на бирже
     *
     * @return array<string> Массив символов валют (например, ['BTC', 'ETH', 'USDT'])
     * @throws \App\Exceptions\ExchangeParserException
     */
    public function getAllCurrencies(): array;
}
