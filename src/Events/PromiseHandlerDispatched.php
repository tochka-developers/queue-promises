<?php

namespace Tochka\Promises\Events;

use Tochka\Promises\Core\BasePromise;

class PromiseHandlerDispatched
{
    private BasePromise $promise;

    public function __construct(BasePromise $promise)
    {
        $this->promise = $promise;
    }

    public function getPromise(): BasePromise
    {
        return $this->promise;
    }
}
