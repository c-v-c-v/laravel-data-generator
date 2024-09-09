<?php

namespace Cv\LaravelDataGenerator\ClassGenerator;

class ClassProperty
{
    public ClassGenerator $classGenerator;

    public string $type;

    public bool $nullable = false;

    public string $name;

    public ?string $comment = null;

    /**
     * 注解属性
     *
     * @var array<string>
     */
    public array $attributes = [];

    public function __construct(ClassGenerator $classGenerator, string $name, string $type, ?string $comment = null)
    {
        $this->classGenerator = $classGenerator;

        if (class_exists($type)) {
            $this->classGenerator->use($type);
            $type = $this->classGenerator->getClassName($type);
        }
        $this->type = $type;

        $this->name = $name;
        $this->comment = $comment;
    }

    /**
     * @return $this
     */
    public function useAttribute(string ...$attributes): static
    {
        foreach ($attributes as $attribute) {
            if (class_exists($attribute)) {
                $this->use($attribute);
                $attribute = $this->classGenerator->getClassName($attribute);
            }

            $this->attributes[] = $attribute;
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function use(string ...$imports): static
    {
        $this->classGenerator->use(...$imports);

        return $this;
    }

    /**
     * @return $this
     */
    public function setNullable(bool $nullable = true): static
    {
        $this->nullable = $nullable;

        $this->type = ltrim($this->type, '?');
        if ($this->nullable) {
            $this->type = '?'.$this->type;
        }

        return $this;
    }
}
