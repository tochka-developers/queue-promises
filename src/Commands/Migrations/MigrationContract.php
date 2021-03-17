<?php

namespace Tochka\Promises\Commands\Migrations;

interface MigrationContract
{
    public function getName(): string;

    public function getTable(): string;

    public function getDefaultTableName(): string;

    public function getStub(): string;

    public function getMigrationName(): string;

    public function isMainMigration(): bool;
}
