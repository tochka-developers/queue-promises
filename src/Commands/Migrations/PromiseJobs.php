<?php

namespace Tochka\Promises\Commands\Migrations;

class PromiseJobs implements MigrationContract
{
    public function getName(): string
    {
        return 'promise_jobs';
    }

    public function getTable(): string
    {
        return 'table_jobs';
    }

    public function getDefaultTableName(): string
    {
        return 'promise_jobs';
    }

    public function getStub(): string
    {
        return 'promise_jobs.stub';
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
