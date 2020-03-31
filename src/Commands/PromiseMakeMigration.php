<?php

namespace Tochka\Promises\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Composer;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

class PromiseMakeMigration extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'promise:make-migration';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a migration for promises';

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * @var \Illuminate\Support\Composer
     */
    protected $composer;

    /**
     * Create a new failed queue jobs table command instance.
     *
     * @param \Illuminate\Filesystem\Filesystem $files
     * @param \Illuminate\Support\Composer      $composer
     *
     * @return void
     */
    public function __construct(Filesystem $files, Composer $composer)
    {
        parent::__construct();

        $this->files = $files;
        $this->composer = $composer;
    }

    /**
     * Execute the console command.
     *
     * @return void
     * @throws \Exception
     */
    public function handle(): void
    {
        $promises_table = Config::get('promises.database.table_promises', 'promises');
        $promise_jobs_table = Config::get('promises.database.table_jobs', 'promise_jobs');
        $promise_events_table = Config::get('promises.database.table_events', 'promise_events');

        $this->replaceMigration(
            $this->createTableMigration($promises_table),
            'promises',
            $promises_table,
            Str::studly($promises_table)
        );

        $this->replaceMigration(
            $this->createTableMigration($promise_jobs_table),
            'promise_jobs',
            $promise_jobs_table,
            Str::studly($promise_jobs_table)
        );

        $this->replaceMigration(
            $this->createTableMigration($promise_events_table),
            'promise_events',
            $promise_events_table,
            Str::studly($promise_events_table)
        );

        $this->info('Migration created successfully!');

        $this->composer->dumpAutoloads();
    }

    /**
     * Create a base migration file for the table.
     *
     * @param string $table
     *
     * @return string
     * @throws \Exception
     */
    protected function createTableMigration(string $table): string
    {
        /** @var \Illuminate\Database\Migrations\MigrationCreator $migrationCreator */
        $migrationCreator = $this->laravel['migration.creator'];

        return $migrationCreator->create(
            'create_' . $table . '_table',
            $this->laravel->databasePath() . '/migrations'
        );
    }

    /**
     * Replace the generated migration with the failed job table stub.
     *
     * @param string $path
     * @param string $stub
     * @param string $table
     * @param string $tableClassName
     *
     * @return void
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function replaceMigration(string $path, string $stub, string $table, string $tableClassName): void
    {
        $stub = str_replace(
            ['{{table}}', '{{tableClassName}}'],
            [$table, $tableClassName],
            $this->files->get(__DIR__ . '/stubs/' . $stub . '.stub')
        );

        $this->files->put($path, $stub);
    }
}
