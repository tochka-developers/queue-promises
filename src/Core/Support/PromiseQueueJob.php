<?php

namespace Tochka\Promises\Core\Support;

use Illuminate\Container\Container;
use Illuminate\Contracts\Queue\ShouldQueue;
use Tochka\Promises\Contracts\JobFacadeContract;
use Tochka\Promises\Contracts\JobStateContract;
use Tochka\Promises\Contracts\MayPromised;
use Tochka\Promises\Contracts\PromisedEvent;
use Tochka\Promises\Contracts\PromiseHandler;
use Tochka\Promises\Enums\StateEnum;
use Tochka\Promises\Models\PromiseJob;
use Tochka\Promises\Support\PromisedJob;

/**
 * Задача, выполняющая обработку результата промиса
 */
class PromiseQueueJob implements ShouldQueue, MayPromised, JobStateContract, JobFacadeContract
{
    use PromisedJob;

    private int $promise_id;
    private PromiseHandler $promise_handler;
    private StateEnum $state;
    /** @var array<string, array<MayPromised> */
    private array $job_results = [];

    public function __construct(int $promise_id, PromiseHandler $promise_handler, StateEnum $state)
    {
        $this->promise_id = $promise_id;
        $this->promise_handler = $promise_handler;
        $this->state = $state;
        if ($this->promise_handler instanceof MayPromised) {
            $this->base_job_id = $this->promise_handler->getBaseJobId();
        }
    }

    /**
     * @throws \ReflectionException
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function handle(): void
    {
        $this->promise_handler->setPromiseId($this->promise_id);
        $this->job_results = $this->getResults();

        $result = $this->dispatchMethodWithParams('before');

        if ($result !== false) {
            switch ($this->state->value) {
                case StateEnum::SUCCESS:
                    $this->dispatchMethodWithParams('success');
                    break;
                case StateEnum::FAILED:
                    $this->dispatchMethodWithParams('failed');
                    break;
                case StateEnum::TIMEOUT:
                    $this->dispatchMethodWithParams('timeout');
                    break;
            }

            $this->dispatchMethodWithParams('handle');
        }

        $this->dispatchMethodWithParams('after');
    }

    /**
     * @param string $method
     *
     * @return mixed
     * @throws \ReflectionException
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    protected function dispatchMethodWithParams(string $method)
    {
        if (!method_exists($this->promise_handler, $method)) {
            return true;
        }

        $results = $this->job_results;

        $params = [];
        // подготавливаем аргументы для вызова метода
        $reflectionMethod = new \ReflectionMethod($this->promise_handler, $method);

        foreach ($reflectionMethod->getParameters() as $i => $parameter) {
            $param = null;

            $type = $this->getParamType($parameter);
            if (
                \in_array(MayPromised::class, class_implements($type), true) ||
                \in_array(PromisedEvent::class, class_implements($type), true)
            ) {
                if (!empty($results[$type])) {
                    $param = array_shift($results[$type]);
                } else {
                    $param = null;
                }
            } else {
                $param = Container::getInstance()->make($type);
            }

            $params[$i] = $param;
        }

        return $this->promise_handler->$method(...$params);
    }

    /**
     * @return array<string, array<MayPromised>>
     */
    private function getResults(): array
    {
        $results = [];

        $jobs = PromiseJob::byPromise($this->getPromiseId())->get();
        /** @var PromiseJob $job */
        foreach ($jobs as $job) {
            $resultJob = $job->getBaseJob()->getResultJob();
            $results[\get_class($resultJob)][] = $resultJob;
        }

        return $results;
    }

    private function getParamType(\ReflectionParameter $parameter): string
    {
        $paramType = $parameter->getType();
        if (!$paramType instanceof \ReflectionNamedType) {
            return (string)$paramType;
        }

        return $paramType->getName();
    }

    public function getState(): StateEnum
    {
        return $this->state;
    }

    public function getPromiseId(): int
    {
        return $this->promise_id;
    }

    public function getJobHandler(): MayPromised
    {
        return $this->promise_handler;
    }

    public function getPromiseHandler(): PromiseHandler
    {
        return $this->promise_handler;
    }

    /**
     * Tags for Horizon UI
     *
     * @return array<string>
     */
    public function tags(): array
    {
        return [
            get_class($this->promise_handler) . ':' . $this->promise_id,
        ];
    }
}
