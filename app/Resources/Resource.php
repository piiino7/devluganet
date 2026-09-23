<?php

namespace App\Resources;

abstract class Resource implements \JsonSerializable
{
    /**
     * @return array<string,mixed>
     */
    abstract public function toArray(): array;

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public static function collection(iterable $models): array
    {
        $result = [];
        foreach ($models as $model) {
            $result[] = (new static($model))->toArray();
        }
        return $result;
    }
}