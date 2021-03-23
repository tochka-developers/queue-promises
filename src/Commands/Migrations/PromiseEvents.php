<?php

namespace Tochka\Promises\Commands\Migrations;

final class PromiseEvents implements MigrationContract
{
    public function getName(): string
    {
        return 'promise_events';
    }

    public function getTable(): string
    {
        return 'table_events';
    }

    public function getDefaultTableName(): string
    {
        return 'promise_events';
    }

    public function getStub(): string
    {
        return 'promise_events.stub';
    }

    public function getMigrationName(): string
    {
        return 'create_%s_table';
    }

    public function isMainMigration(): bool
    {
        return true;
    }
}
