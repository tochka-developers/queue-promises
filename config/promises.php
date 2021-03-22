<?php

return [

    // Настройки БД
    'database'               => [
        // Используемое подключению к БД. null - используется подключения по умолчанию
        'connection'     => null,
        // Имя таблицы для хранения промисов
        'table_promises' => 'promises',
        // Имя таблицы для хранения обещанных задач
        'table_jobs'     => 'promise_jobs',
        // Имя таблицы для хранения обещанных событий
        'table_events'   => 'promise_events',
    ],

    // Глобальное время таймаута для всех промисов (по умолчанию - 5 суток)
    'global_promise_timeout' => 60 * 60 * 24 * 5,

    // Настройки сборщика мусора
    'gcc'                    => [
        /**
         * Интервал запуска сборщика мусора
         */
        'timeout' => 60 * 10,
        /**
         * Устанавливает время, в течение которого должны оставаться в базе завершенные промисы
         * По умолчанию - 7 суток
         */
        'time'   => 60 * 60 * 24 * 7,
        /**
         * Состояния, которые считаются сборщиком мусора подходящими для удаления
         * Сборщик мусора удаляет только те промисы, состояние которых, а также состояние связанных задач является
         * одним из перечисленных
         */
        'states' => [
            \Tochka\Promises\Enums\StateEnum::SUCCESS,
            \Tochka\Promises\Enums\StateEnum::CANCELED,
            \Tochka\Promises\Enums\StateEnum::TIMEOUT,
            \Tochka\Promises\Enums\StateEnum::FAILED,
        ],
    ],

    // Синхронное обновление состояний
    'fire_updates'           => true,

    /**
     * Интервал времени, с которым вотчер проверяет корректность состояний
     * Данный параметр позволяет с помощью вотчера следить за промисами и корректировать их состояние, в случае
     * возникновения ошибок при синхронной обработке
     * Применяется только при использовании синхронного обновления состояний (включенном параметре fire_updates)
     * По умолчанию - 10 минут
     */
    'watcher_watch_timeout'  => 60 * 10,
];
