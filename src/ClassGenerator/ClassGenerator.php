<?php

namespace Cv\LaravelDataGenerator\ClassGenerator;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\View;
use InvalidArgumentException;

class ClassGenerator
{
    /**
     * 命名空间
     */
    public string $namespace;

    /**
     * uses
     *
     * @var array<string>
     */
    public array $imports = [];

    /**
     * 注释
     */
    public ?string $comment = null;

    /**
     * 类注解属性
     *
     * @var array<string>
     */
    public array $attributes = [];

    /**
     * 类名
     */
    public string $className;

    /**
     * 父类
     */
    public ?string $parentClass = null;

    /**
     * @var array<string, ClassProperty>
     */
    public array $properties = [];

    /**
     * 渲染代码
     */
    public function render(): string
    {
        return View::make('laravel-data-generator::stubs.data', [
            'classGenerator' => $this,
        ])->render();
    }

    /**
     * @return $this
     */
    public function setNamespace(string $namespace): static
    {
        $this->namespace = $namespace;

        return $this;
    }

    /**
     * @return $this
     */
    public function setClassName(string $className): static
    {
        $this->className = $className;

        return $this;
    }

    /**
     * @return $this
     */
    public function setParentClass(?string $parentClass): static
    {
        if (class_exists($parentClass)) {
            $this->use($parentClass);
        }

        $this->parentClass = $this->getClassName($parentClass);

        return $this;
    }

    /**
     * @return $this
     */
    public function setComment(?string $comment): static
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * @return $this
     */
    public function use(string ...$imports): static
    {
        foreach ($imports as $import) {
            if (! in_array($import, $this->imports)) {
                $this->imports[] = $import;
            }
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function useAttribute(string ...$attributes): static
    {
        foreach ($attributes as $attribute) {
            if (class_exists($attribute)) {
                $this->use($attribute);
                $attribute = $this->getClassName($attribute);
            }

            $this->attributes[] = $attribute;
        }

        return $this;
    }

    public function addProperty(string $name, string $type, ?string $comment = null): ClassProperty
    {
        if (isset($this->properties[$name])) {
            throw new InvalidArgumentException;
        }

        $this->properties[$name] = new ClassProperty($this, $name, $type, $comment);

        return $this->properties[$name];
    }

    public function getClassName(string $className): string
    {
        return Arr::last(explode('\\', $className));
    }
}
