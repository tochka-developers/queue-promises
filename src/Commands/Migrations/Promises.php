<?php

namespace Tochka\Promises\Commands\Migrations;

class Promises implements MigrationContract
{
    public function getName(): string
    {
        return 'promises';
    }

    public function getTable(): string
    {
        return 'table_promises';
    }

    public function getDefaultTableName(): string
    {
        return 'promises';
    }

    public function getStub(): string
    {
        return 'promises.stub';
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
