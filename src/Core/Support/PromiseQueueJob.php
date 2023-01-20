<?php

namespace Tochka\Promises\Core\Support;

use Illuminate\Bus\Queueable;
use Illuminate\Container\Container;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Tochka\Promises\Contracts\JobFacadeContract;
use Tochka\Promises\Contracts\JobStateContract;
use Tochka\Promises\Contracts\MayPromised;
use Tochka\Promises\Contracts\PromisedEvent;
use Tochka\Promises\Contracts\PromiseHandler;
use Tochka\Promises\Enums\StateEnum;
use Tochka\Promises\Models\PromiseJob;
use Tochka\Promises\Support\PromisedJob;
use Tochka\Promises\Support\WaitEvent;

/**
 * Задача, выполняющая обработку результата промиса
 */
class PromiseQueueJob implements ShouldQueue, MayPromised, JobStateContract, JobFacadeContract
{
    use Queueable;
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
     * @throws BindingResolutionException
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
                default:
                    break;
            }

            $this->dispatchMethodWithParams('handle');
        }

        $this->dispatchMethodWithParams('after');
    }

    /**
     * @param string $method
     *
     * @return mixed|bool
     * @throws \ReflectionException
     * @throws BindingResolutionException
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

        foreach ($reflectionMethod->getParameters() as $parameter) {
            $type = $this->getParamType($parameter);
            if (
                \in_array(MayPromised::class, class_implements($type), true)
                || \in_array(PromisedEvent::class, class_implements($type), true)
            ) {
                if (!empty($results[$type])) {
                    if ($parameter->isVariadic()) {
                        array_push($params, ...$results[$type]);
                        unset($results[$type]);
                        continue;
                    }

                    $params[] = array_shift($results[$type]);
                    continue;
                }

                if ($parameter->isVariadic()) {
                    continue;
                }

                if ($parameter->allowsNull()) {
                    $params[] = null;
                } else {
                    throw new \RuntimeException(
                        sprintf(
                            'Error while dispatch promise handler method [%s]. Parameter [%s] not allow null value, but result for this parameter is empty',
                            $reflectionMethod->getDeclaringClass()->getName() . '::' . $method,
                            $parameter->getName()
                        )
                    );
                }

                continue;
            }

            $params[] = Container::getInstance()->make($type);
        }

        return $this->promise_handler->$method(...$params);
    }

    /**
     * @return array<string, array<MayPromised>>
     */
    private function getResults(): array
    {
        $results = [];

        $jobs = PromiseJob::byPromise($this->getPromiseId())->orderBy('id')->get();
        /** @var PromiseJob $job */
        foreach ($jobs as $job) {
            $resultJob = $job->getBaseJob()->getResultJob();
            $results[\get_class($resultJob)][] = $resultJob;
            if ($resultJob instanceof WaitEvent) {
                $resultEvent = $resultJob->getEvent();
                if ($resultEvent !== null) {
                    $results[\get_class($resultEvent)][] = $resultEvent;
                }
            }
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

    public function displayName(): string
    {
        return sprintf('%s[%d]', get_class($this->promise_handler), $this->promise_id);
    }

    public function tags(): array
    {
        return [
            get_class($this->promise_handler) . ':' . $this->promise_id,
        ];
    }
}
