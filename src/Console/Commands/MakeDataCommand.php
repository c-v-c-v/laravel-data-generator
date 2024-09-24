<?php

namespace Cv\LaravelDataGenerator\Console\Commands;

use Carbon\Carbon;
use Cv\LaravelDataGenerator\ClassGenerator\ClassGenerator;
use Cv\LaravelDataGenerator\ClassGenerator\ClassProperty;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Schema as DatabaseSchema;
use Illuminate\Support\Str;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Spatie\LaravelData\Attributes\FromRouteParameter;
use Spatie\LaravelData\Attributes\Validation\Between;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Required;

use function Laravel\Prompts\confirm;

class MakeDataCommand extends Command
{
    protected $signature = 'cv:make-data {table-name}';

    protected $description = 'make data';

    protected string $baseDataClass;

    /**
     * 是否禁用添加验证
     */
    protected bool $disableValidation = false;

    /**
     * 默认列注释
     */
    protected array $defaultColumnComment = [];

    /**
     * 主键名称
     */
    protected string $primaryKey = 'id';

    protected array $schemeExcludePropertyNames = [];

    public function __construct(
        private readonly Filesystem $filesystem
    ) {
        parent::__construct();
    }

    protected function getConfig(string $key, $default = null)
    {
        return config('laravel-data-generator.'.$key, $default);
    }

    public function handle(): void
    {
        // 初始化配置
        $this->baseDataClass = config('laravel-data-generator.base_data_class');
        $this->defaultColumnComment = config('laravel-data-generator.default_column_comment');

        $tableName = $this->getTableName();

        // 检查表是否存在
        if (! DatabaseSchema::hasTable($tableName)) {
            $this->error("{$tableName} doesn't exists!");
            exit(1);
        }

        // 检查文件
        if ($this->alreadyExists($tableName)) {
            $isOverride = confirm('is override?');
            if (! $isOverride) {
                $this->error('cancel override');
                exit(1);
            }
        }

        $classGenerator = $this->buildClass($tableName);

        $path = $this->getPath($this->getQualifyClassName($tableName));
        $this->makeDirectory($path);
        $this->filesystem->put($path, $classGenerator->render());

        // 格式化代码
        if (file_exists(base_path('./vendor/bin/pint'))) {
            Process::run('php ./vendor/bin/pint '.$path)->throw();
        }

        if (windows_os()) {
            $path = str_replace('/', '\\', $path);
        }

        $this->info('file path: '.$path);
    }

    protected function getTableName(): string
    {
        return $this->argument('table-name');
    }

    protected function getColumns(string $tableName): array
    {
        return DatabaseSchema::getColumns($tableName);
    }

    protected function getQualifyClassName(string $tableName): string
    {
        return $this->qualifyClass(Str::studly(Str::singular($tableName)));
    }

    protected function buildClass(string $tableName): ClassGenerator
    {
        $qualifyClassName = $this->getQualifyClassName($tableName);

        $classComment = $this->getTableComment($tableName);
        $classGenerator = (new ClassGenerator)
            ->setNamespace($this->getNamespace($qualifyClassName))
            ->setComment($classComment)
            ->setClassName($this->getClassName($qualifyClassName))
            ->setParentClass($this->baseDataClass)
            ->useAttribute(Schema::class);

        $columns = $this->getColumns($tableName);

        foreach ($columns as $column) {
            $this->buildColumn($column, $classGenerator);
        }

        return $classGenerator;
    }

    protected function buildColumn(array $column, ClassGenerator $classGenerator): void
    {
        $phpType = $this->mysqlTypeToPhpType($column['type']);
        $classProperty = $classGenerator->addProperty(Str::camel($column['name']), $phpType, $this->getColumnComment($column))
            ->setNullable($column['nullable']);

        if (! in_array($column['name'], $this->schemeExcludePropertyNames)) {
            $classProperty->useAttribute(Property::class);
        }

        if (! $this->disableValidation) {
            $this->addValidation($classProperty, $column);
        }
    }

    protected function qualifyClass($name): string
    {
        $name = ltrim($name, '\\/');

        $name = str_replace('/', '\\', $name);

        $rootNamespace = $this->rootNamespace();

        if (Str::startsWith($name, $rootNamespace)) {
            return $name;
        }

        return $this->qualifyClass(
            $this->getDefaultNamespace(trim($rootNamespace, '\\')).'\\'.$name
        );
    }

    protected function rootNamespace(): string
    {
        return $this->laravel->getNamespace();
    }

    protected function getDefaultNamespace(string $rootNamespace): string
    {
        $namespace = 'Data';

        return trim($rootNamespace.'\\'.$namespace, '\\');
    }

    protected function getPath($name): string
    {
        $name = Str::replaceFirst($this->rootNamespace(), '', $name);

        return $this->laravel['path'].'/'.str_replace('\\', '/', $name).'.php';
    }

    protected function alreadyExists($rawName): bool
    {
        return $this->filesystem->exists($this->getPath($this->getQualifyClassName($rawName)));
    }

    protected function getNamespace($name): string
    {
        return trim(implode('\\', array_slice(explode('\\', $name), 0, -1)), '\\');
    }

    protected function getClassName(string $name): string
    {
        return str_replace($this->getNamespace($name).'\\', '', $name);
    }

    public function mysqlTypeToPhpType(string $mysqlType): string
    {
        // Convert MySQL data types to PHP data types
        static $typeMap = [
            // Numeric Types
            'tinyint' => 'int',
            'smallint' => 'int',
            'mediumint' => 'int',
            'int' => 'int',
            'integer' => 'int',
            'bigint' => 'int',
            'float' => 'float',
            'double' => 'float',
            'decimal' => 'float',
            'dec' => 'float',

            // // Date and Time Types
            // 'date' => 'string',
            // 'datetime' => 'string',
            // 'timestamp' => 'string',
            'date' => Carbon::class,
            'datetime' => Carbon::class,
            'timestamp' => Carbon::class,
            'time' => 'string',
            'year' => 'int',

            // String Types
            'char' => 'string',
            'varchar' => 'string',
            'binary' => 'string',
            'varbinary' => 'string',
            'blob' => 'string',
            'text' => 'string',
            'enum' => 'string',
            'set' => 'string',

            // Spatial Types
            'geometry' => 'string',
            'point' => 'string',
            'linestring' => 'string',
            'polygon' => 'string',
            'multipoint' => 'string',
            'multilinestring' => 'string',
            'multipolygon' => 'string',
            'geometrycollection' => 'string',

            // JSON Type
            // 'json' => 'string',
            'json' => 'array',
        ];

        // 处理bool类型
        if ($mysqlType === 'tinyint(1)') {
            return 'bool';
        }

        // Extract the base type in case of additional information like length or attributes
        $baseType = strtolower(preg_replace('/\(.*/', '', $mysqlType));
        // 兼容mysql8
        $baseType = explode(' ', $baseType)[0] ?? null;

        return $typeMap[$baseType] ?? 'string'; // Default to 'string' if type is not found
    }

    /**
     * 添加验证规则
     */
    private function addValidation(ClassProperty $classProperty, array $column): void
    {
        if ($column['type_name'] === 'varchar' || $column['type_name'] === 'char') {
            preg_match('/\((\d+)\)/', $column['type'], $matches);
            $maxLength = $matches[1];
            $classProperty->useAttribute("Max({$maxLength})")
                ->use(Max::class);
        }

        if ($column['type'] === 'tinyint(1)') {
            if ($column['nullable'] === false) {
                // laravel data bool类型默认非必填
                $classProperty->useAttribute(Required::class);
            }
        } elseif ($between = $this->getMysqlIntTypeRange($column['type'])) {
            if ($between['min'] !== PHP_INT_MIN && $between['max'] !== PHP_INT_MAX) {
                $classProperty->useAttribute("Between({$between['min']}, {$between['max']})")
                    ->use(Between::class);
            } elseif ($between['min'] !== PHP_INT_MIN) {
                $classProperty->useAttribute("Min({$between['min']})")
                    ->use(Min::class);
            } elseif ($between['max'] !== PHP_INT_MAX) {
                $classProperty->useAttribute("Max({$between['max']})")
                    ->use(Max::class);
            }
        }

        if ($column['name'] === $this->primaryKey) {
            $classProperty->useAttribute("FromRouteParameter('{$this->primaryKey}')")
                ->use(FromRouteParameter::class);
        }
    }

    /**
     * 获取表的注释
     */
    protected function getTableComment($tableName): ?string
    {
        $databaseName = env('DB_DATABASE');

        $comment = DatabaseSchema::getConnection()->table('information_schema.TABLES')
            ->where('TABLE_SCHEMA', $databaseName)
            ->where('TABLE_NAME', $tableName)
            ->value('TABLE_COMMENT');

        return $comment ?: null;
    }

    /**
     * 根据 MySQL 整数类型获取对应的范围
     *
     * @return array|null 返回范围数组 ['min' => 最小值, 'max' => 最大值]，如果类型无效则返回 null
     */
    protected function getMysqlIntTypeRange($mysqlType): ?array
    {
        // 定义 MySQL 整数类型的范围
        $ranges = [
            'TINYINT' => [
                'signed' => ['min' => -128, 'max' => 127],
                'unsigned' => ['min' => 0, 'max' => 255],
            ],
            'SMALLINT' => [
                'signed' => ['min' => -32768, 'max' => 32767],
                'unsigned' => ['min' => 0, 'max' => 65535],
            ],
            'MEDIUMINT' => [
                'signed' => ['min' => -8388608, 'max' => 8388607],
                'unsigned' => ['min' => 0, 'max' => 16777215],
            ],
            'INT' => [
                'signed' => ['min' => -2147483648, 'max' => 2147483647],
                'unsigned' => ['min' => 0, 'max' => 4294967295],
            ],
            'BIGINT' => [
                'signed' => ['min' => -9223372036854775808, 'max' => 9223372036854775807],
                'unsigned' => ['min' => 0, 'max' => PHP_INT_MAX],
            ],
        ];

        // 返回相应的范围
        // 提取整数类型和是否无符号
        if (preg_match('/^(tinyint|smallint|mediumint|int|bigint)(\(\d+\))?( unsigned)?$/i', $mysqlType, $matches)) {
            $type = strtoupper($matches[1]);
            $unsigned = isset($matches[3]) && trim($matches[3]) === 'unsigned';

            return $unsigned ? $ranges[$type]['unsigned'] : $ranges[$type]['signed'];
        }

        // 返回 null 如果输入无效
        return null;
    }

    private function getColumnComment(array $column): ?string
    {
        if (! empty($column['comment'])) {
            return $column['comment'];
        }

        return $this->defaultColumnComment[$column['name']] ?? null;
    }

    protected function makeDirectory($path)
    {
        if (! $this->filesystem->isDirectory(dirname($path))) {
            $this->filesystem->makeDirectory(dirname($path), 0777, true, true);
        }

        return $path;
    }
}
