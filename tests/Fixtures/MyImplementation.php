<?php


namespace Mouf\Html\Renderer\Fixtures;


class MyImplementation implements MyInterface
{
    public function getPrivateProperties(): array
    {
        return [
            'foo' => 'bar'
        ];
    }
}