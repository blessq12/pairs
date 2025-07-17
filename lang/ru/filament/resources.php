<?php

return [
    'resources' => [
        'exchange' => [
            'label' => 'Биржа',
            'plural_label' => 'Биржи',
            'navigation_group' => 'Управление биржами',
            'fields' => [
                'name' => 'Название',
                'slug' => 'Идентификатор',
                'created_at' => 'Создано',
                'updated_at' => 'Обновлено',
            ],
        ],
        'currency-pair' => [
            'label' => 'Валютная пара',
            'plural_label' => 'Валютные пары',
            'navigation_group' => 'Управление биржами',
            'fields' => [
                'symbol' => 'Символ',
                'base_currency' => 'Базовая валюта',
                'quote_currency' => 'Котируемая валюта',
                'created_at' => 'Создано',
                'updated_at' => 'Обновлено',
            ],
        ],
        'price' => [
            'label' => 'Цена',
            'plural_label' => 'Цены',
            'navigation_group' => 'Управление биржами',
            'fields' => [
                'exchange' => 'Биржа',
                'currency_pair' => 'Валютная пара',
                'bid' => 'Цена покупки',
                'ask' => 'Цена продажи',
                'fetched_at' => 'Время получения',
                'created_at' => 'Создано',
                'updated_at' => 'Обновлено',
                'recommendation' => 'Рекомендация',
            ],
        ],
        'exchange-api-key' => [
            'label' => 'API ключ',
            'plural_label' => 'API ключи',
            'navigation_group' => 'Управление биржами',
            'fields' => [
                'exchange' => 'Биржа',
                'api_key' => 'API ключ',
                'api_secret' => 'API секрет',
                'additional_params' => 'Дополнительные параметры',
                'is_active' => 'Активен',
                'description' => 'Описание',
                'created_at' => 'Создано',
                'updated_at' => 'Обновлено',
            ],
        ],
    ],
    'common' => [
        'actions' => [
            'create' => 'Создать',
            'edit' => 'Редактировать',
            'delete' => 'Удалить',
            'save' => 'Сохранить',
            'cancel' => 'Отмена',
        ],
        'filters' => [
            'select_exchange' => 'Выберите биржу',
            'select_currency_pair' => 'Выберите валютную пару',
            'active_only' => 'Только активные',
            'inactive_only' => 'Только неактивные',
        ],
        'messages' => [
            'created' => 'Запись создана',
            'updated' => 'Запись обновлена',
            'deleted' => 'Запись удалена',
        ],
    ],
];
