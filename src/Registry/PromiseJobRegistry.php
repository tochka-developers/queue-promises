<?php

namespace Tochka\Promises\Registry;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Tochka\Promises\Core\BaseJob;
use Tochka\Promises\Contracts\MayPromised;
use Tochka\Promises\Exceptions\IncorrectResolvingClass;
use Tochka\Promises\Models\PromiseJob;

class PromiseJobRegistry
{
    use SerializeConditions;

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

    public function loadByPromiseId(int $promise_id): array
    {
        /** @var PromiseJob[]|\Illuminate\Support\Collection $jobModels */
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $jobModels = PromiseJob::where('promise_id', $promise_id)->get();
        $jobs = $jobModels->map([$this, 'mapJobModel']);

        return $jobs->toArray();
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
        $jobModel->conditions = $this->getSerializedConditions($job->getConditions());
        $jobModel->initial_job = serialize($job->getInitialJob());
        $jobModel->result_job = serialize($job->getResultJob());

        $jobModel->save();

        if ($jobId === null) {
            $job->setJobId($jobModel->id);
        }
    }

    private function mapJobModel(PromiseJob $jobModel): BaseJob
    {
        $initialJob = unserialize($jobModel->initial_job, ['allowed_classes' => true]);
        $resultJob = unserialize($jobModel->result_job, ['allowed_classes' => true]);
        if (!$initialJob instanceof MayPromised || !$resultJob instanceof MayPromised) {
            throw new IncorrectResolvingClass(
                sprintf(
                    'Promised job must implements contract [%s], but class [%s] is incorrect',
                    MayPromised::class,
                    get_class($initialJob)
                )
            );
        }

        $conditions = $this->getUnserializedConditions($jobModel->conditions);

        $job = new BaseJob($jobModel->promise_id, $initialJob, $resultJob);
        $job->setConditions($conditions);
        $job->restoreState($jobModel->state);
        $job->setJobId($jobModel->id);

        return $job;
    }
}