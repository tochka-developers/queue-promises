<?php

namespace Tochka\Queue\Promises\Console;

use Illuminate\Console\GeneratorCommand;

class PromiseMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:promise';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new promise class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Promise';

    /**
     * Determine if the class already exists.
     *
     * @param  string  $rawName
     * @return bool
     */
    protected function alreadyExists($rawName)
    {
        return class_exists($rawName);
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__.'/stubs/promise.stub';
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Promises';
    }
}
