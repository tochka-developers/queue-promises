<?php

namespace Tochka\Promises\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Tochka\Promises\Enums\StateEnum;

/**
 * @property int            $id
 * @property StateEnum      $state
 * @property array          $conditions
 * @property string         $promise_handler
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class Promise extends Model
{
    protected $casts = [
        'state'           => StateEnum::class,
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
