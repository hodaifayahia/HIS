<?php

namespace App\Console\Commands;

use Illuminate\Console\GeneratorCommand;

class MakeServiceCommand extends GeneratorCommand
{
    protected $signature = 'make:service {name}';
    protected $description = 'Create a new service class';
    protected $type = 'Service';

    protected function getStub()
    {
        return $this->resolveStubPath('/stubs/service.stub');
    }

    protected function resolveStubPath($stub)
    {
        return file_exists($customPath = $this->laravel->basePath(trim($stub, '/')))
            ? $customPath
            : __DIR__.$stub;
    }

    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Services';
    }
}
