<?php

namespace Tochka\Promises\Core\Support;

class Serializer
{
    /**
     * @param array $conditions
     *
     * @return array
     */
    public function getSerializedConditions(array $conditions): array
    {
        return array_map(static function (ConditionTransition $conditionTransition) {
            return $conditionTransition->toArray();
        }, $conditions);
    }

    /**
     * @param array $conditions
     *
     * @return array
     */
    public function getUnSerializedConditions(array $conditions): array
    {
        return array_map(static function ($conditionTransition) {
            return ConditionTransition::fromArray($conditionTransition);
        }, $conditions);
    }

    /**
     * @param mixed $value
     *
     * @return string
     * @throws \JsonException
     */
    public function jsonSerialize($value): string
    {
        return json_encode(serialize($value), JSON_THROW_ON_ERROR, 512);
    }

    /**
     * @param string $value
     *
     * @return mixed
     * @throws \JsonException
     */
    public function jsonUnSerialize(string $value)
    {
        return unserialize(
            json_decode($value, true, 512, JSON_THROW_ON_ERROR),
            ['allowed_classes' => true]
        );
    }
}