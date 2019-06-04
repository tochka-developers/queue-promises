# Queue Promises
Позволяет создавать цепочки задач в рамках очередей Laravel, после завершения работы которых будет выполнена 
определенная задача (промис). Промис выполнится несмотря на результат работы задач в цепочке, в нем можно настроить 
различное поведение исходя из результатов.
## Установка
* Выполняем команду:
```bash
composer require tochka-developers/queue-promises
```
* (Laravel 5.4) Далее в проекте необходимо зарегистрировать ServiceProvider в файле ```config/app.php```
```php
'providers' => [
    ...
    \Tochka\Queue\Promises\QueuePromisesServiceProvider::class
    ...
]
```
* Опубликуйте конфигурацию:
```bash
php artisan vendor:publish --provider="Tochka\Queue\Promises\QueuePromisesServiceProvider"
```
* В файле `config/promises.php` можно указать настройки подключения к БД, а также таблицу для хранения промежуточных 
данных.
* Создайте таблицу для хранения данных промисов:
```bash
php artisan migrate
```

## Использование
### Создание класса промиса
Промис &mdash; это класс, отнаследованный от абстрактного класса `Tochka\Queue\Promises\Jobs\Promise`. Для создания 
шаблонного класса используйте команду artisan:
```bash
php artisan make:promise TestPromise
```

### Структура класса
Класс промиса имеет простую структуру. Обычно содержит два метода `success` и `errors`. Метод `success` будет вызван, 
если все задачи в цепочке выполнились успешно. Метод `errors` будет вызван, если хотя бы одна из задач в цепочке была 
завершена с ошибкой.
```php
<?php

namespace App\Promises;

use Tochka\Queue\Promises\Jobs\Promise;

class TestPromise extends Promise
{
    /**
     * Действия при создании экземпляра промиса
     *
     * @return void
     */
    public function __construct()
    {
        //
    }
    
    /**
     * Метод вызывается после всех выполненных задач промиса, но до вызова метода success или errors
     * Если метод возвращает FALSE - методы success и errors вызываться не будут
     *
     * @return bool
     */
    public function before(): bool
    {
        // ...
        return true;
    }

    /**
     * Метод вызывается, если все задачи промиса были успешно завершены
     *
     * @return bool
     */
    public function success(): bool
    {
        // ...
        return true;
    }

    /**
     * Метод вызывается, если хотя бы одна из задач промиса завершилась с ошибкой
     *
     * @return bool
     */
    public function errors(): bool
    {
        // ...
        return true;
    }
    
    /**
     * Метод вызывается, если было установлено время ожидания, и оно вышло
     *
     * @return bool
     */
    public function timeout(): bool
    {
        // ...
        return true;
    }
    
    /**
     * Метод вызывается всегда после всех выполненных задач, а также после выполнения методов success или errors
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
Любой из указанных методов может отсутствовать в промисе. В таких случаях предполагается, что такие методы возвращают значение 
`true`, т.е. обработка прошла успешно.

### Запуск цепочки
Для того, чтобы задачи могли использоваться вместе с промисами, необходимо реализовать в них интерфейс 
`Tochka\Queue\Promises\Contracts\MayPromised`. Реализация для наиболее частых случаев уже выполнена в трейте 
`Tochka\Queue\Promises\Jobs\Promised`. Достаточно просто указать зависимость от интерфейса и использовать трейт:
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
Для создания цепочки следует добавить необходимые задачи в промис:
```php
$promise = new TestPromise();

$promise->add(new SomeJob('job 1'))
        ->add(new SomeJob('job 2'))
        ->add(new SomeJob('job 3'))
        ->add(new SomeJob('job 4'));
```
После добавления необходимых задач в цепочку можно их запустить в двух режимах: синхронный и асинхронный:
```php
$promise->runSync(); // запуск в синхронном режиме
$promise->runAsync(); // запуск в асинхронном режиме
```
* При синхронном режиме задачи в цепочке будут запускаться по очереди, пока все задачи не завершатся, либо какая-нибудь
 из задач не завершится с ошибкой. После этого будет вызван соответствующий метод промиса.
* При асинхронном режиме все задачи поставятся в очередь сразу, и промис будет вызван как только эти задачи завершатся.

В асинхронном режиме можно управлять условиями завершения промиса. Для этого следует выполнить:
```php
$promise->setPromiseFinishConditions(
    true, // Выполнять промис в случае успешного завершения любой из задач 
    true  // Выполнять промис в случае возникновения первой же ошибки в любой из задач
);
``` 

Для удобства эти же параметры можно передать прямо в `runAsync`. По умолчанию оба параметра равны `false`, т.е. промис 
будет ожидать (успешного или неуспешного) завершения _всех_ задач из цепочки.

### Ожидание наступления событий
Иногда есть необходимость выполнять промис не только после выполнения нескольких задач, но и при наступлении одного или 
нескольких событий в системе. Для того чтобы заставить промис ждать наступления события, достаточно добавить в цепочку 
задач специальную задачу `Tochka\Queue\Promises\Jobs\WaitEvent`:
```php
$promise = new TestPromise();

$promise->add(new WaitEvent(SomeEvent1::class, 100))
        ->add(new WaitEvent(SomeEvent2::class, 100));
    
$promise->run()
```
В конструктор класса WaitEvent передается два параметра: имя класса-события, которое ожидается, а также уникальный 
идентификатор события. Уникальный идентификатор необходим в том случае, если промис должен быть завязан на событие, 
произошедшее с конкретным объектом системы. Если вы просто ждете какого-либо события системы, можно не указывать этот 
параметр, но в таком случае данная задача в цепочке будет считаться успешно выполненной, как только произойдет любое 
событие указанного класса.

Кроме того, для правильной работы этого функционала необходимо, чтобы класс-событие реализовывал интерфейс 
`Tochka\Queue\Promises\Contracts\PromisedEvent`:
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
     * Получение уникального идентификатора события
     * @return string
     */
    public function getUniqueId()
    {
        return $this->id;
    }
}
```
Метод `getUniqueId` должен возвращать уникальный идентификатор события, либо null, если такого идентификатора нет.

Учтите, что промис не будет выполнен, если в цепочке задач будет задано ожидание события, класс которого не реализует 
указанный выше интерфейс.

### Установка времени ожидания (таймаут)
Иногда необходимо выполнить промис в любом случае спустя некоторое время, даже если связанные с ним задачи еще не 
были завершены. 
Для слежения за временем ожидания промиса используются специальные отложенные задачи.
Для их работы необходимо:
* указать в конфигурации в параметре `timeout_queue` имя очереди, в которую будут ставиться отложенные задачи проверки. 
По умолчанию такие задачи будут ставиться в очередь `default`
* запустить слушателя указанной очереди (либо указать эту очередь в списке обрабатываемых уже существуюим слушателем)

Для установки времени ожидания у промиса есть два метода:
* setTimeout($timeout) &mdash; промис выполнится через указанное в секундах время (либо раньше, если все входящие в него 
задачи будут завершены)
* setExpiredAt(Carbon $expired_at) &mdash; промис выполнится в указанное время (либо раньше, если все входящие в него задачи 
будут завершены)

Если промис выполняется по истечении времени ожидания, то в промисе будет вызван метод `timeout` (если он есть).

### Обработка результатов
Для получения массива результатов работы всех задач в цепочке воспользуйтесь методом `getResults`:
```php
public function before(): bool
{
    $results = $this->getResults();
    foreach ($results as $result) {
        $status = $result->getJobStatus(); // вернет статус работы задачи
    }
}
```
Данный метод возвращает классы, реализующие интерфейс `Tochka\Queue\Promises\Contracts\MayPromised`.
Также вы можете воспользоваться DependencyInjection в объявлении методов `before`, `success`, `errors`, `timeout` и 
`after`:
```php
public function errors(SomeJob1 $job1, SomeJob2 $job2): bool
{
    echo $job1->getJobStatus();
    echo $job2->getJobStatus();
}
```
DI работает так:
* если вы указали в качестве типа класс с интерфейсом `Tochka\Queue\Promises\Contracts\MayPromised`, то при вызове 
метода в качестве аргумента будет передан результат работы задачи с указанным классом
* если вы указали в качестве аргумента класс с интерфейсом `Tochka\Queue\Promises\Contracts\PromisedEvent`, то при 
вызове методы в качестве аргумента будет передан класс события, которое ожидалось промисом
* если в результатах имеется несколько подходящих классов, то будет передан первый из них
* если вы указали несколько аргументов с одинаковым типом, а в результатах имеется несколько подходящих классов, то 
в качестве аргументов будут передаваться результаты по очереди:
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
Учтите! Если вы использовали асинхронный запуск задач, то результаты их работы могут придти в порядке, отличном от 
заданного вами порядка, потому в последнем примере возможна, например, следующая ситуация:
```php
public function errors(SomeJob $job1, SomeJob $job2, SomeJob $job3, SomeJob $job4): bool
{
    echo $job1->text; // job 3
    echo $job2->text; // job 1
    echo $job3->text; // job 2
    echo $job4->text; // job 4
}
```
* если в результатах нет или уже не осталось подходящих классов, то в качестве аргумента будет передан null.
* если вы указали в качестве типа класс без интерфейса `Tochka\Queue\Promises\Contracts\MayPromised`, то в качестве 
аргумента будет передан объект, созданный стандартным механизмом DI Laravel, как если бы вы вызвали функцию app с 
указанным классом.

Объекты с реализованным интерфесом `Tochka\Queue\Promises\Contracts\MayPromised` всегда содержат методы:
```php
/**
 * Возвращает статус задачи
 * Одна из констант MayPromised::JOB_STATUS_SUCCESS или MayPromised::JOB_STATUS_ERROR
 * @return string
 */
public function getJobStatus(): string;

/**
 * Возвращает ошибки из задачи
 * Ассоциативный массив с ключами code и message
 * @return array
 */
public function getJobErrors(): array;
 
```