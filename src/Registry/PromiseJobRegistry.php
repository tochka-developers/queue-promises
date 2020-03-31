<?php

namespace Tochka\Promises\Registry;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;
use Tochka\Promises\Contracts\MayPromised;
use Tochka\Promises\Core\BaseJob;
use Tochka\Promises\Exceptions\IncorrectResolvingClass;
use Tochka\Promises\Facades\Serializer;
use Tochka\Promises\Models\PromiseJob;

class PromiseJobRegistry
{
    public function load(int $id): BaseJob
    {
        /** @var PromiseJob $jobModel */
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $jobModel = PromiseJob::find($id);
        if (!$jobModel) {
            throw (new ModelNotFoundException())->setModel(PromiseJob::class, $id);
        }

        return $this->mapJobModel($jobModel);
    }

    /**
     * @param int $promise_id
     *
     * @return \Illuminate\Support\Collection|BaseJob[]
     */
    public function loadByPromiseId(int $promise_id): Collection
    {
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        return PromiseJob::where('promise_id', $promise_id)
            ->get()
            ->map(function ($jobModel) {
                return $this->mapJobModel($jobModel);
            });
    }

    /**
     * @param int $promise_id
     *
     * @return \Illuminate\Support\LazyCollection|BaseJob[]
     */
    public function loadByPromiseIdCursor(int $promise_id): LazyCollection
    {
        return LazyCollection::make(function () use ($promise_id) {
            /** @var PromiseJob $job */
            /** @noinspection PhpDynamicAsStaticMethodCallInspection */
            foreach (PromiseJob::where('promise_id', $promise_id)->cursor() as $job) {
                yield $this->mapJobModel($job);
            }
        });
    }

    public function countByPromiseId(int $promise_id): int
    {
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        return PromiseJob::where('promise_id', $promise_id)->count();
    }

    public function save(BaseJob $job): void
    {
        $jobModel = new PromiseJob();
        $jobId = $job->getJobId();
        if ($jobId !== null) {
            $jobModel->id = $jobId;
            $jobModel->exists = true;
        } else {
            $jobModel->exists = false;
        }

        $jobModel->promise_id = $job->getPromiseId();
        $jobModel->state = $job->getState();
        $jobModel->conditions = Serializer::getSerializedConditions($job->getConditions());
        $jobModel->initial_job = Serializer::jsonSerialize(clone $job->getInitialJob());
        $jobModel->result_job = json_encode(
            serialize(clone $job->getResultJob()),
            JSON_THROW_ON_ERROR,
            512
        );

        $jobModel->save();

        if ($jobId === null) {
            $job->setJobId($jobModel->id);
        }
    }

    public function deleteByPromiseId(int $promise_id): void
    {
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        PromiseJob::where('promise_id', $promise_id)->delete();
    }

    private function mapJobModel(PromiseJob $jobModel): BaseJob
    {
        $initialJob = Serializer::jsonUnSerialize($jobModel->initial_job);
        $resultJob = Serializer::jsonUnSerialize($jobModel->result_job);

        if (!$initialJob instanceof MayPromised || !$resultJob instanceof MayPromised) {
            throw new IncorrectResolvingClass(
                sprintf(
                    'Promised job must implements contract [%s], but class [%s] is incorrect',
                    MayPromised::class,
                    get_class($initialJob)
                )
            );
        }

        $conditions = Serializer::getUnserializedConditions($jobModel->conditions);

        $job = new BaseJob($jobModel->promise_id, $initialJob, $resultJob);
        $job->setConditions($conditions);
        $job->restoreState($jobModel->state);
        $job->setJobId($jobModel->id);

        return $job;
    }
}