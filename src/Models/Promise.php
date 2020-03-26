<?php

namespace Tochka\Promises\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;

/**
 * @property int    $id
 * @property string $state
 * @property array  $conditions
 * @property string $promise_handler
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class Promise extends Model
{
    protected $casts = [
        'state'           => 'string',
        'conditions'      => 'array',
        'promise_handler' => 'string',
    ];

    public function getConnectionName()
    {
        return Config::get('promises.database.connection', null);
    }

    public function getTable()
    {
        return Config::get('promises.database.table_promises', 'promises');
    }
}