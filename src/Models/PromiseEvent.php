<?php

namespace Tochka\Promises\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;

/**
 * @property int            $id
 * @property int            $job_id
 * @property string         $event_name
 * @property string         $event_unique_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class PromiseEvent extends Model
{
    protected $casts = [
        'job_id'          => 'int',
        'event_name'      => 'string',
        'event_unique_id' => 'string',
    ];

    public function getConnectionName()
    {
        return Config::get('promises.database.connection', null);
    }

    public function getTable()
    {
        return Config::get('promises.database.table_events', 'promise_events');
    }
}
