<?php

namespace Tochka\Promises\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Composer;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

class PromiseMakeMigration extends Command
{
    private const TABLES = [
        'table_promises' => 'promises',
        'table_jobs'     => 'promise_jobs',
        'table_events'   => 'promise_events',
    ];

    protected $signature = 'promise:make-migration {table?}';

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
        $table = $this->argument('table');

        if ($table === null) {
            $tables = self::TABLES;
        } else {
            if (!array_key_exists($table, self::TABLES)) {
                throw new \InvalidArgumentException(sprintf('Migration for table [%s] does not exists', $table));
            }

            $tables = [$table => self::TABLES[$table]];
        }

        foreach ($tables as $config => $name) {
            $table_name = Config::get('promises.database.' . $config, $name);

            $this->replaceMigration(
                $this->createTableMigration($table_name),
                $name,
                $table_name,
                Str::studly($table_name)
            );
            $this->info(sprintf('Migration for table [%s] created!', $config));
        }

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
