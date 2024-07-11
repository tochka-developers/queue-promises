<?php

namespace Tochka\Promises\Contracts;

/**
 * Указывает, что BaseJob на самом деле скрывает под собой другой обработчик
 *
 * @api
 */
interface JobFacadeContract
{
    public function getJobHandler(): MayPromised;
}
