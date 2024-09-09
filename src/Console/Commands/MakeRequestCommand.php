<?php

namespace Cv\LaravelDataGenerator\Console\Commands;

use Illuminate\Support\Str;

class MakeRequestCommand extends MakeDataCommand
{
    protected $signature = 'cv:make-request {table-name} {--update}';

    protected $description = 'make request data';

    protected function getQualifyClassName(string $tableName): string
    {
        $className = Str::studly(Str::singular($tableName)).'Request';
        $prefix = $this->isUpdateRequest() ? 'Update' : 'Create';

        return $this->qualifyClass('Requests/'.$prefix.$className);
    }

    protected function getColumns(string $tableName): array
    {
        $columnsCollection = collect(parent::getColumns($tableName));

        // 去除创建和更新时间
        $columnsCollection = $columnsCollection->whereNotIn('name', $this->getConfig('request_exclude_columns', []));

        // 如果是create request，则去除id
        if (! $this->isUpdateRequest()) {
            $columnsCollection = $columnsCollection->where('name', '!=', $this->primaryKey);
        }

        return $columnsCollection->toArray();
    }

    protected function isUpdateRequest(): bool
    {
        return $this->option('update');
    }
}
