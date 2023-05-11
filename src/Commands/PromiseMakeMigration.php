<?php

/** @noinspection PhpMissingFieldTypeInspection */

namespace Tochka\Promises\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Database\Migrations\MigrationCreator;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Composer;
use Illuminate\Support\Facades\Config;
use Tochka\Promises\Commands\Migrations\MigrationContract;
use Tochka\Promises\Commands\Migrations\PromiseEvents;
use Tochka\Promises\Commands\Migrations\PromiseJobs;
use Tochka\Promises\Commands\Migrations\Promises;
use Tochka\Promises\Commands\Migrations\UpdateV1;
use Tochka\Promises\Commands\Migrations\UpdateV2;

class PromiseMakeMigration extends Command
{
    protected $signature = 'promise:make-migration {name?}';

    protected $description = 'Create a migration for promises';

    private Filesystem $files;
    private Composer $composer;
    /** @var array<MigrationContract> */
    private array $migrations;

    public function __construct(Filesystem $files, Composer $composer)
    {
        parent::__construct();

        $this->files = $files;
        $this->composer = $composer;
        $this->migrations = [
            new Promises(),
            new PromiseJobs(),
            new PromiseEvents(),
            new UpdateV1(),
            new UpdateV2(),
        ];
    }

    /**
     * Execute the console command.
     *
     * @throws \Exception
     */
    public function handle(): void
    {
        $migrationName = $this->argument('name');

        if ($migrationName === null) {
            $migrationsToRun = array_filter(
                $this->migrations,
                fn (MigrationContract $migration) => $migration->isMainMigration()
            );
        } else {
            $migrationsToRun = array_filter(
                $this->migrations,
                fn (MigrationContract $migration) => $migration->getName() === $migrationName
            );
            if (count($migrationsToRun) === 0) {
                throw new \InvalidArgumentException(
                    sprintf('Migrations with name [%s] does not exists', $migrationName)
                );
            }
        }

        foreach ($migrationsToRun as $migration) {
            $tableName = Config::get('promises.database.' . $migration->getTable(), $migration->getDefaultTableName());
            $migrationName = sprintf($migration->getMigrationName(), $tableName);

            $this->replaceMigration(
                $this->createTableMigration($migrationName),
                $migration->getStub(),
                $tableName
            );
            $this->info(sprintf('Migration for table [%s] created!', $tableName));
        }

        $this->info('Migration created successfully!');

        $this->composer->dumpAutoloads();
    }

    /**
     * Create a base migration file for the table.
     *
     * @throws \Exception
     */
    protected function createTableMigration(string $migrationName): string
    {
        /** @var MigrationCreator $migrationCreator */
        $migrationCreator = $this->laravel['migration.creator'];

        return $migrationCreator->create(
            $migrationName,
            $this->laravel->databasePath() . '/migrations'
        );
    }

    /**
     * Replace the generated migration with the failed job table stub.
     *
     * @throws FileNotFoundException
     */
    protected function replaceMigration(string $path, string $stub, string $table): void
    {
        $stub = str_replace('{{table}}', $table, $this->files->get(__DIR__ . '/stubs/' . $stub));

        $this->files->put($path, $stub);
    }
}
