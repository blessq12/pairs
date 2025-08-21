<?php

namespace App\Console\Commands;

use App\Models\CurrencyPair;
use App\Models\Exchange;
use App\Models\ExchangePair;
use Illuminate\Console\Command;

class ManageExchangePairs extends Command
{
    protected $signature = 'pairs:manage-exchange-pairs 
                            {action : Действие (list, add, remove, activate, deactivate)}
                            {--exchange= : ID или название биржи}
                            {--pair= : ID или символ валютной пары}
                            {--symbol= : Символ на бирже (например, BTCUSDT)}
                            {--min-amount= : Минимальный объем для торговли}
                            {--maker-fee= : Комиссия мейкера (например, 0.001)}
                            {--taker-fee= : Комиссия тейкера (например, 0.001)}';

    protected $description = 'Управление парами для арбитража в exchange_pairs';

    public function handle(): void
    {
        $action = $this->argument('action');

        switch ($action) {
            case 'list':
                $this->listPairs();
                break;
            case 'add':
                $this->addPair();
                break;
            case 'remove':
                $this->removePair();
                break;
            case 'activate':
                $this->activatePair();
                break;
            case 'deactivate':
                $this->deactivatePair();
                break;
            default:
                $this->error("Неизвестное действие: {$action}");
                $this->info("Доступные действия: list, add, remove, activate, deactivate");
        }
    }

    private function listPairs(): void
    {
        $this->info("📊 Список пар для арбитража:");
        
        $pairs = ExchangePair::with(['exchange', 'currencyPair'])
            ->orderBy('exchange_id')
            ->orderBy('currency_pair_id')
            ->get();

        if ($pairs->isEmpty()) {
            $this->warn("❌ Нет пар для арбитража");
            return;
        }

        $headers = ['ID', 'Биржа', 'Пара', 'Символ на бирже', 'Статус', 'Мин. объем', 'Комиссии'];
        $rows = [];

        foreach ($pairs as $pair) {
            $rows[] = [
                $pair->id,
                $pair->exchange->name,
                $pair->currencyPair->symbol,
                $pair->symbol_on_exchange,
                $pair->is_active ? '✅ Активна' : '❌ Неактивна',
                $pair->min_amount ?? 'Не указан',
                "M: " . ($pair->maker_fee ?? 'Не указана') . " | T: " . ($pair->taker_fee ?? 'Не указана'),
            ];
        }

        $this->table($headers, $rows);
    }

    private function addPair(): void
    {
        $exchangeId = $this->getExchangeId();
        $pairId = $this->getPairId();
        $symbol = $this->option('symbol');

        if (!$exchangeId || !$pairId || !$symbol) {
            $this->error("❌ Необходимо указать биржу, пару и символ на бирже");
            $this->info("Пример: php artisan pairs:manage-exchange-pairs add --exchange=1 --pair=1 --symbol=BTCUSDT");
            return;
        }

        // Проверяем, не существует ли уже такая пара
        $existing = ExchangePair::where('exchange_id', $exchangeId)
            ->where('currency_pair_id', $pairId)
            ->first();

        if ($existing) {
            $this->error("❌ Пара уже существует с ID: {$existing->id}");
            return;
        }

        // Создаем новую пару
        $exchangePair = ExchangePair::create([
            'exchange_id' => $exchangeId,
            'currency_pair_id' => $pairId,
            'symbol_on_exchange' => strtoupper($symbol),
            'is_active' => true,
            'min_amount' => $this->option('min-amount'),
            'maker_fee' => $this->option('maker-fee'),
            'taker_fee' => $this->option('taker-fee'),
        ]);

        $this->info("✅ Пара добавлена с ID: {$exchangePair->id}");
    }

    private function removePair(): void
    {
        $exchangeId = $this->getExchangeId();
        $pairId = $this->getPairId();

        if (!$exchangeId || !$pairId) {
            $this->error("❌ Необходимо указать биржу и пару");
            return;
        }

        $pair = ExchangePair::where('exchange_id', $exchangeId)
            ->where('currency_pair_id', $pairId)
            ->first();

        if (!$pair) {
            $this->error("❌ Пара не найдена");
            return;
        }

        $pair->delete();
        $this->info("✅ Пара удалена");
    }

    private function activatePair(): void
    {
        $exchangeId = $this->getExchangeId();
        $pairId = $this->getPairId();

        if (!$exchangeId || !$pairId) {
            $this->error("❌ Необходимо указать биржу и пару");
            return;
        }

        $pair = ExchangePair::where('exchange_id', $exchangeId)
            ->where('currency_pair_id', $pairId)
            ->first();

        if (!$pair) {
            $this->error("❌ Пара не найдена");
            return;
        }

        $pair->update(['is_active' => true]);
        $this->info("✅ Пара активирована");
    }

    private function deactivatePair(): void
    {
        $exchangeId = $this->getExchangeId();
        $pairId = $this->getPairId();

        if (!$exchangeId || !$pairId) {
            $this->error("❌ Необходимо указать биржу и пару");
            return;
        }

        $pair = ExchangePair::where('exchange_id', $exchangeId)
            ->where('currency_pair_id', $pairId)
            ->first();

        if (!$pair) {
            $this->error("❌ Пара не найдена");
            return;
        }

        $pair->update(['is_active' => false]);
        $this->info("✅ Пара деактивирована");
    }

    private function getExchangeId(): ?int
    {
        $exchange = $this->option('exchange');
        if (!$exchange) {
            return null;
        }

        // Если это число, считаем что это ID
        if (is_numeric($exchange)) {
            return (int)$exchange;
        }

        // Иначе ищем по названию
        $exchangeModel = Exchange::where('name', $exchange)->first();
        return $exchangeModel ? $exchangeModel->id : null;
    }

    private function getPairId(): ?int
    {
        $pair = $this->option('pair');
        if (!$pair) {
            return null;
        }

        // Если это число, считаем что это ID
        if (is_numeric($pair)) {
            return (int)$pair;
        }

        // Иначе ищем по символу
        $pairModel = CurrencyPair::where('symbol', strtoupper($pair))->first();
        return $pairModel ? $pairModel->id : null;
    }
}
