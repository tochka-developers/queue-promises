<?php

namespace Tochka\Promises\Tests\TestHelpers;

use Tochka\Promises\Core\BaseJob;
use Tochka\Promises\Core\BasePromise;

trait TestConditionTrait
{
    public function promiseConditionsTestConditionTrait(BasePromise $promise): void {}

    public function jobConditionsTestConditionTrait(BasePromise $promise, BaseJob $baseJob): void {}

    public function afterRunTestConditionTrait(BasePromise $promise, BaseJob $baseJob): void {}
}
