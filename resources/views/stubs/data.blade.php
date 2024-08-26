<?php echo'<?php'; /** @var $classGenerator \Cv\LaravelDataGenerator\ClassGenerator\ClassGenerator */?>

namespace {{ $classGenerator->namespace }};

@foreach ($classGenerator->imports as $import)
    use {{ $import }};
@endforeach
@if($classGenerator->comment)
/**
* {{ $classGenerator->comment }}
*/
@endif
@foreach ($classGenerator->attributes as $attribute)
    #[{!! $attribute !!}]
@endforeach
class {{ $classGenerator->className }} @if(! empty($classGenerator->parentClass)) extends {{ $classGenerator->parentClass }} @endif
{
@foreach ($classGenerator->properties as $property)
    @if($property->comment)
    /**
     * {{ $property->comment }}
     */
    @endif
@foreach ($property->attributes as $attribute)
    #[{!! $attribute !!}]
@endforeach
    public {{ $property->type }} ${{ $property->name }};

@endforeach
}
