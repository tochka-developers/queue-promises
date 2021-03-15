<?php

return [

    // Таблица для сохранения промисов
    'database'     => [
        'connection'     => null,  //null = подключение по умолчанию
        'table_promises' => 'promises',
        'table_jobs'     => 'promise_jobs',
        'table_events'   => 'promise_events',
        'table_updates'  => 'promise_updates',
    ],

    // Синхронное обновление состояний
    'fire_updates' => false,

];
