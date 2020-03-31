<?php

return [

    // Таблица для сохранения промисов
    'database'      => [
        'connection'     => null,  //null = подключение по умолчанию
        'table_promises' => 'promises',
        'table_jobs'     => 'promise_jobs',
        'table_events'   => 'promise_events',
    ],

    // Очередь для задач проверки таймаута
    'timeout_queue' => 'default',

    // Канал для логов
    'log_channel'   => 'default',

];