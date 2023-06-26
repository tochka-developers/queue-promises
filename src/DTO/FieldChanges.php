<?php

namespace Tochka\Promises\DTO;

trait FieldChanges
{
    private array $changes = [];

    private function fieldChange(string $fieldName): void
    {
        $this->changes[$fieldName] = $fieldName;
    }

    public function getChangedFields(): array
    {
        return array_values($this->changes);
    }

    public function getChangedValues(array $values): array
    {
        return array_intersect_key($values, $this->changes);
    }

    public function isChanged(): bool
    {
        return !empty($this->changes);
    }
}
