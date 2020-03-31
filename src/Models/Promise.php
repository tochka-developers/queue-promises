<?php

namespace Tochka\Promises\Models;

use Tochka\Promises\Enums\StateEnum;
use BenSampo\Enum\Traits\CastsEnums;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;

/**
 * @property int       $id
 * @property StateEnum $state
 * @property array     $conditions
 * @property string    $promise_handler
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