<?php

namespace App\Jobs;

use App\Models\ExchangePair;
use App\Models\Exchange;
use App\Parsers\ExchangeParserFactory;
use App\Services\PollExchangesService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdatePricesChunk implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 180; // 3 минуты на чанк
    public $tries = 2;

    private array $exchangePairIds;

    /**
     * Create a new job instance.
     */
    public function __construct(array $exchangePairIds)
    {
        $this->exchangePairIds = $exchangePairIds;
    }

    /**
     * Execute the job.
     */
    public function handle(ExchangeParserFactory $parserFactory): void
    {
        try {
            Log::info('Начинаем обновление цен для чанка', [
                'exchange_pairs_count' => count($this->exchangePairIds)
            ]);

            // Получаем активные пары для арбитража
            $exchangePairs = ExchangePair::whereIn('id', $this->exchangePairIds)
                ->where('is_active', true)
                ->with(['currencyPair', 'exchange'])
                ->get();

            if ($exchangePairs->isEmpty()) {
                Log::info('Нет активных пар для обновления цен');
                return;
            }

            // Группируем по биржам для оптимизации
            $pairsByExchange = $exchangePairs->groupBy('exchange_id');

            foreach ($pairsByExchange as $exchangeId => $pairsForExchange) {
                $exchange = $pairsForExchange->first()->exchange;
                $parser = $parserFactory->createParser($exchange);
                
                foreach ($pairsForExchange as $exchangePair) {
                    try {
                        // Получаем тикер и сохраняем цену
                        $ticker = $parser->getTicker($exchangePair->symbol_on_exchange);
                        
                        \App\Models\Price::create([
                            'exchange_id' => $exchange->id,
                            'currency_pair_id' => $exchangePair->currency_pair_id,
                            'bid_price' => $ticker['bid'],
                            'ask_price' => $ticker['ask'],
                            'created_at' => now(),
                        ]);
                        
                        Log::debug("Обновлена цена для {$exchangePair->symbol_on_exchange} на {$exchange->name}");
                        
                    } catch (\Exception $e) {
                        Log::warning("Ошибка обновления цены для {$exchangePair->symbol_on_exchange} на {$exchange->name}: {$e->getMessage()}");
                        continue;
                    }
                }
            }

            Log::info('Чанк цен обновлен успешно', [
                'exchange_pairs_processed' => $exchangePairs->count(),
                'exchanges_processed' => $pairsByExchange->count()
            ]);

        } catch (\Exception $e) {
            Log::error('Ошибка при обновлении цен чанка', [
                'error' => $e->getMessage(),
                'exchange_pairs' => $this->exchangePairIds
            ]);
            throw $e;
        }
    }
}
