<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Создаем базовую таблицу
        Schema::create('prices', function (Blueprint $table) {
            $table->unsignedBigInteger('exchange_id');
            $table->unsignedBigInteger('currency_pair_id');
            $table->decimal('bid_price', 16, 8); // Цена покупки
            $table->decimal('ask_price', 16, 8); // Цена продажи
            $table->timestamp('created_at')->useCurrent(); // Время получения данных

            // Составной первичный ключ, включающий created_at для партиционирования
            $table->primary(['exchange_id', 'currency_pair_id', 'created_at']);

            // Индексы для быстрого поиска
            $table->index('created_at');
            $table->index('exchange_id');
            $table->index('currency_pair_id');
        });

        // Добавляем партиционирование по месяцам
        // Используем конкретные timestamp значения
        $currentTimestamp = strtotime(date('Y-m-01 00:00:00'));
        $nextMonthTimestamp = strtotime(date('Y-m-01 00:00:00', strtotime('+1 month')));

        DB::statement("
            ALTER TABLE prices
            PARTITION BY RANGE (UNIX_TIMESTAMP(created_at)) (
                PARTITION p_current VALUES LESS THAN ($nextMonthTimestamp),
                PARTITION p_future VALUES LESS THAN MAXVALUE
            )
        ");

        // Проверяем, есть ли права на создание Event
        try {
            // Создаем Event для автоматического создания партиций на следующий месяц
            DB::unprepared('
                CREATE EVENT IF NOT EXISTS manage_price_partitions
                ON SCHEDULE EVERY 1 DAY
                DO BEGIN
                    -- Удаляем старые партиции (старше 3 месяцев)
                    SET @drop_partition_sql = (
                        SELECT CONCAT("ALTER TABLE prices DROP PARTITION ", GROUP_CONCAT(partition_name))
                        FROM information_schema.partitions
                        WHERE table_name = "prices"
                        AND partition_name != "p_future"
                        AND partition_name != "p_current"
                        AND partition_description < UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 3 MONTH))
                    );
                    IF @drop_partition_sql IS NOT NULL THEN
                        PREPARE stmt FROM @drop_partition_sql;
                        EXECUTE stmt;
                        DEALLOCATE PREPARE stmt;
                    END IF;

                    -- Создаем партицию на следующий месяц если её ещё нет
                    SET @next_month_start = DATE_FORMAT(DATE_ADD(NOW(), INTERVAL 1 MONTH), "%Y-%m-01 00:00:00");
                    SET @partition_name = CONCAT("p_", DATE_FORMAT(@next_month_start, "%Y%m"));

                    -- Проверяем существует ли уже такая партиция
                    IF NOT EXISTS (
                        SELECT 1 FROM information_schema.partitions
                        WHERE table_name = "prices"
                        AND partition_name = @partition_name
                    ) THEN
                        -- Создаем новую партицию
                        SET @next_month_end = DATE_FORMAT(DATE_ADD(@next_month_start, INTERVAL 1 MONTH), "%Y-%m-01 00:00:00");
                        SET @partition_sql = CONCAT(
                            "ALTER TABLE prices REORGANIZE PARTITION p_future INTO (",
                            "PARTITION ", @partition_name, " VALUES LESS THAN (UNIX_TIMESTAMP(\'", @next_month_end, "\')),",
                            "PARTITION p_future VALUES LESS THAN MAXVALUE)"
                        );
                        PREPARE stmt FROM @partition_sql;
                        EXECUTE stmt;
                        DEALLOCATE PREPARE stmt;
                    END IF;
                END
            ');
        } catch (\Exception $e) {
            // Если нет прав на создание Event, логируем это
            Log::warning('Could not create price partitioning event. You will need to manage partitions manually: ' . $e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            // Пытаемся удалить Event
            DB::unprepared('DROP EVENT IF EXISTS manage_price_partitions');
        } catch (\Exception $e) {
            // Игнорируем ошибку, если нет прав
        }

        // Удаляем таблицу
        Schema::dropIfExists('prices');
    }
};
