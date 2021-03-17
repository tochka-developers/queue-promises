# Queue Promises
[![Quality Gate Status](https://sonarcloud.io/api/project_badges/measure?project=tochka-developers_queue-promises&metric=alert_status)](https://sonarcloud.io/dashboard?id=tochka-developers_queue-promises)
[![Bugs](https://sonarcloud.io/api/project_badges/measure?project=tochka-developers_queue-promises&metric=bugs)](https://sonarcloud.io/dashboard?id=tochka-developers_queue-promises)
[![Code Smells](https://sonarcloud.io/api/project_badges/measure?project=tochka-developers_queue-promises&metric=code_smells)](https://sonarcloud.io/dashboard?id=tochka-developers_queue-promises)
[![Coverage](https://sonarcloud.io/api/project_badges/measure?project=tochka-developers_queue-promises&metric=coverage)](https://sonarcloud.io/dashboard?id=tochka-developers_queue-promises)

The module allows chaining of Laravel jobs. The promise will be executed after all chained jobs are finished 
(either completed successfully or failed). The promise has access to job results. 
## Installation
* Install the package with `composer`:
```bash
composer require tochka-developers/queue-promises
```
* (Laravel 5.4) Register a `ServiceProvider` in `config/app.php`
```php
'providers' => [
    ...
    \Tochka\Queue\Promises\QueuePromisesServiceProvider::class
    ...
]
```
* Publish the configuration:
```bash
php artisan vendor:publish --provider="Tochka\Queue\Promises\QueuePromisesServiceProvider"
```
* Configure the promise storage in `config/promises.php`. 

* Create the promise storage tables:
```bash
php artisan migrate
```

## Usage
### Creating a Promise
All promises are derived from `Tochka\Queue\Promises\Jobs\Promise`. You can create a template promise with `artisan`:
```bash
php artisan make:promise TestPromise
```

### Class details
The promise class is rather straightforward, as only two methods are needed: `success` and `errors`. The `success` 
is called by the provider if all chained jobs have completed successfully. The `errors` is called if any
of the jobs failed.
```php
<?php

namespace App\Promises;

use Tochka\Queue\Promises\Jobs\Promise;

class TestPromise extends Promise
{
    /**
     * Instance initialization
     *
     * @return void
     */
    public function __construct()
    {
        // Jobs chaining may be done here
    }
    
    /**
     * This will be called after all jobs of the promise have finished execution, but before success or errors.
     * If this method returns false, success and errors won't be called. 
     *
     * @return bool
     */
    public function before(): bool
    {
        // ...
        return true;
    }

    /**
     * This will be called if all the jobs have completed successfully.
     *
     * @return bool
     */
    public function success(): bool
    {
        // ...
        return true;
    }

    /**
     * This will be called if one or more jobs have failed.
     *
     * @return bool
     */
    public function errors(): bool
    {
        // ...
        return true;
    }
    
    /**
     * This will be called if the promise timed out.
     *
     * @return bool
     */
    public function timeout(): bool
    {
        // ...
        return true;
    }
    
    /**
     * This will be called after the execution of success or errors
     *
     * @return bool
     */
    public function after()
    {
        // ...
        return true;
    }
}
```
Any of the methods shown above may not be implemented. If a methods is missing it is assumed to do nothing and return `true`.

### Chain Initialization
In order for jobs to attach to a promise they must implement the `Tochka\Queue\Promises\Contracts\MayPromised` interface. 
The most common use cases are collected in the `Tochka\Queue\Promises\Jobs\Promised` trait.
You may simply attach these to your class like this:
```php
<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Tochka\Queue\Promises\Contracts\MayPromised;
use Tochka\Queue\Promises\Jobs\Promised;

class SomeJob implements ShouldQueue, MayPromised
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Promised;

    public $text;

    public function __construct($text)
    {
        $this->text = $text;
    }

    public function handle()
    {
        // some actions
    }
}
```
The chain is constructed by adding any number of jobs to the promise:
```php
$promise = new TestPromise();

$promise->add(new SomeJob('job 1'))
    ->add(new SomeJob('job 2'))
    ->add(new SomeJob('job 3'))
    ->add(new SomeJob('job 4'));
```
After that the promise can be run in one of two modes:
```php
$promise->runSync();    // Run the promise synchronously 
$promise->runAsync();   // Run the promise asynchronously
```
* In synchronous mode all chained jobs will be run sequentially, one at a time, until either one of them fails or
all complete successfully. After that, the `success` or `errors` of the promise will be executed.
* In asynchronous mode all jobs will be queued immediately, and the promise will wait for them to finish.

The termination conditions can be finely configured via method call: 
```php
$promise->setPromiseFinishConditions(
    true, // The promise must execute when a job completes successfully for the first time.  
    true  // The promise must execute when a job fails for the first time.
);
``` 
For convenience, `runSync` and `runAsync` may also take the same parameters.

### Waiting For Events
Sometimes it is necessary to execute a promise not just after a number of jobs completed, but also when an event is dispatched.
Waiting for the events is done with a `Tochka\Queue\Promises\Jobs\WaitEvent` job:
```php
$promise = new TestPromise();

$promise->add(new WaitEvent(SomeEvent1::class, 100))
        ->add(new WaitEvent(SomeEvent2::class, 100));
    
$promise->run()
```

The constructor of a `WaitEvent` takes a class of the event to wait for and an unique identifier (of unspecified type).
If the identifier is not provided, the `WaitEvent` will wait for any event of the expected class.
If the identifier is given to the constructor, the `WaitEvent` will complete only when the event with this identifier is dispatched. 
In order for this to work, the event must implement the `Tochka\Queue\Promises\Contracts\PromisedEvent` interface:
```php
class SomeEvent implements PromisedEvent
{
    public $id;
    public $data;

    public function __construct($id, string $data)
    {
        $this->id = $id;
        $this->data = $data;
    }

    /**
     * Get the event id
     * @return string
     */
    public function getUniqueId()
    {
        return $this->id;
    }
}
```
`getUniqueId` must return either an identifier or `null` if no identifier exists.

**Note that `WaitEvent` will never complete if it waits for a class that does not implement the interface.**

### Promise Timeout
It may be useful at times to execute the promise after a timeout event if some of chained jobs haven't yet finished. 
This is achieved with a special delayed checker job.

You have to configure the timeout subsystem:
* set the configuration parameter `timeout_queue` to contain the name of the queue where the checker jobs 
will be posted.
By default the checker jobs are posted to the `default` queue. 
* run a listener for the timeout queue or add it to a list of queues for existing listener.

After that the promises may be set to expire. Two ways of setting a timeout are possible:
* `setTimeout($timeout)` &mdash; the promise will be executed in the given time (in seconds) or after all jobs have 
completed (whatever happens first).
* `setExpiredAt(Carbon $expired_at)` &mdash; the promise will be executed at the given timestamp (more or less accurately) 
or after all jobs have completed (whatever happens first).

If the promise times out, the `timeout` method is called (if defined).

### Processing The Results
The `getResults` method returns the results of all chained jobs:
```php
public function before(): bool
{
    $results = $this->getResults();
    foreach ($results as $result) {
        $status = $result->getJobStatus(); // This returns the job execution status
    }
}
```
Job results are returned as classes implementing `Tochka\Queue\Promises\Contracts\MayPromised`.

#### Dependency Injection

An alternative way to get the job results is dependency injection versions of generic methods 
`before`, `success`, `errors`, `timeout`, and `after`:
```php
public function errors(SomeJob1 $job1, SomeJob2 $job2): bool
{
    echo $job1->getJobStatus();
    echo $job2->getJobStatus();
}
```
The DI works like follows: 
* if a parameter has a type implementing `Tochka\Queue\Promises\Contracts\MayPromised`, the corresponding result will be
injected into this parameter;
* if a parameter has a type implementing `Tochka\Queue\Promises\Contracts\PromisedEvent` (which is useful when 
the promise waits for an event), the event itself will be passed as this parameter;
* if there are more than one result of the required class, only the first one will bind;
* if there are more than one *parameter* of the same class (for example, if the promise waits for several jobs of the same class),
the order of results corresponds to the order of the jobs in the chain:
```php
$promise->add(new SomeJob('job 1'))
        ->add(new SomeJob('job 2'))
        ->add(new SomeJob('job 3'))
        ->add(new SomeJob('job 4'));
    
//...

public function errors(SomeJob $job1, SomeJob $job2, SomeJob $job3, SomeJob $job4): bool
{
    echo $job1->text; // job 1
    echo $job2->text; // job 2
    echo $job3->text; // job 3
    echo $job4->text; // job 4
}
```
**Note that if the promise is run asynchronously, the order of job execution cannot be guaranteed.** 
This may have undesired consequences:
```php
public function errors(SomeJob $job1, SomeJob $job2, SomeJob $job3, SomeJob $job4): bool
{
    echo $job1->text; // job 3
    echo $job2->text; // job 1
    echo $job3->text; // job 2
    echo $job4->text; // job 4
}
```
* if there is no appropriate result for a parameter, `null` is passed;
* if a parameter type does not implement `Tochka\Queue\Promises\Contracts\MayPromised`, then the value passed to it 
will be constructed with Laravel DI (like the result of calling `app()` with the class name).

`Tochka\Queue\Promises\Contracts\MayPromised` interface declares the following methods:
```php
/**
 * Get the job execution status 
 * Returns either MayPromised::JOB_STATUS_SUCCESS or MayPromised::JOB_STATUS_ERROR
 * @return string
 */
public function getJobStatus(): string;

/**
 * Get the job execution errors
 * Returns an array ['code' => ..., 'message' => ...]
 * @return array
 */
public function getJobErrors(): array;
```
