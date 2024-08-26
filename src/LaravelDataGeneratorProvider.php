<?php

namespace Cv\LaravelDataGenerator;

use Cv\LaravelDataGenerator\Console\Commands\MakeDataCommand;
use Cv\LaravelDataGenerator\Console\Commands\MakeRequestCommand;
use Cv\LaravelDataGenerator\Console\Commands\MakeVoCommand;
use Illuminate\Support\ServiceProvider;

class LaravelDataGeneratorProvider extends ServiceProvider
{
    private static string $baseConfig = __DIR__.'/../config/laravel-data-generator.php';

    public function boot(): void
    {
        // 发布配置文件
        $this->publishes([
            self::$baseConfig => config_path('laravel-data-generator.php'),
        ], 'config');

        // 发布视图
        $viewPath = __DIR__.'/../resources/views';
        $this->loadViewsFrom($viewPath, 'laravel-data-generator');
        $this->publishes([
            $viewPath => resource_path('views/vendor/cv/laravel-data-generator'),
        ], 'views');

        // 注册自定义命令
        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeDataCommand::class,
                MakeRequestCommand::class,
                MakeVoCommand::class,
            ]);
        }
    }

    public function register(): void
    {
        if (! app()->configurationIsCached()) {
            $this->mergeConfigFrom(self::$baseConfig, 'laravel-data-generator');
        }
    }
}
