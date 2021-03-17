<?php

namespace Tochka\Promises\Commands\Migrations;

class UpdateV1 implements MigrationContract
{
    public function getName(): string
    {
        return 'v1';
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
        return 'add_columns_to_%s_table';
    }

    public function isMainMigration(): bool
    {
        return false;
    }
}
