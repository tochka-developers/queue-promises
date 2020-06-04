<?php

namespace Tochka\Promises\Models;

use BenSampo\Enum\Traits\CastsEnums;
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
    use CastsEnums;

    protected $casts = [
        'state'           => 'string',
        'conditions'      => 'array',
        'promise_handler' => 'string',
    ];

    protected $enumCasts = [
        'state' => StateEnum::class,
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
