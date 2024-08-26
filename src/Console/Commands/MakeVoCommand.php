<?php

namespace Cv\LaravelDataGenerator\Console\Commands;

use Illuminate\Support\Str;

class MakeVoCommand extends MakeDataCommand
{
    protected $signature = 'cv:make-vo {table-name} {--class-name=}';

    protected $description = 'make vo data';

    protected bool $disableValidation = true;

    protected function getQualifyClassName(string $tableName): string
    {
        $className = $this->input->getOption('class-name');
        $className = $className ?: Str::studly(Str::singular($tableName)).'Vo';
        if (! str_ends_with($className, 'Vo')) {
            $className .= 'Vo';
        }

        return $this->qualifyClass('Vos/'.$className);
    }
}
