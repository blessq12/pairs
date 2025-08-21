<?php

namespace App\Jobs;

use App\Models\ExchangePair;
use App\Models\Exchange;
use App\Services\ArbitrageAnalysisService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessArbitrageChunk implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 минут на чанк
    public $tries = 3;

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
    public function handle(ArbitrageAnalysisService $arbitrageService): void
    {
        try {
            Log::info('Начинаем обработку чанка арбитража', [
                'exchange_pairs_count' => count($this->exchangePairIds)
            ]);

            // Получаем активные пары для арбитража
            $exchangePairs = ExchangePair::whereIn('id', $this->exchangePairIds)
                ->where('is_active', true)
                ->with(['currencyPair', 'exchange'])
                ->get();

            if ($exchangePairs->isEmpty()) {
                Log::info('Нет активных пар для анализа арбитража');
                return;
            }

            // Группируем по валютным парам для анализа
            $pairsByCurrency = $exchangePairs->groupBy('currency_pair_id');

            foreach ($pairsByCurrency as $currencyPairId => $exchangePairsForCurrency) {
                $currencyPair = $exchangePairsForCurrency->first()->currencyPair;
                $exchanges = $exchangePairsForCurrency->pluck('exchange');
                
                // Анализируем арбитраж для конкретной валютной пары
                $arbitrageService->analyzePair($currencyPair, $exchanges);
            }

            Log::info('Чанк арбитража обработан успешно', [
                'currency_pairs_processed' => $pairsByCurrency->count(),
                'exchange_pairs_processed' => $exchangePairs->count()
            ]);

        } catch (\Exception $e) {
            Log::error('Ошибка при обработке чанка арбитража', [
                'error' => $e->getMessage(),
                'exchange_pairs' => $this->exchangePairIds
            ]);
            throw $e;
        }
    }
}
