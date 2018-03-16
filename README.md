# Queue Promises
Позволяет создавать цепочки задач в рамках очередей Laravel, после завершения работы которых будет выполнена определенная задача (промис). Промис выполнится несмотря на результат работы задач в цепочке, в нем можно настроить различное поведение исходя из результатов.
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
* В файле `config/promises.php` можно указать настройки подключения к БД, а также таблицу дял хранения промежуточных данных.
* Создайте таблицу для хранения промежуточных данных запросов (если вам это необходимо):
```bash
php artisan migrate
```

## Использование
### Создание класса промиса
Промис - это класс, отнаследованный от абстрактного класса `Tochka\Queue\Promises\Jobs\Promise`. Для создания шаблонного класса используйте команду artisan:
```bash
php artisan make:promise TestPromise
```

### Структура класса
Класс промиса имеет простую структуру. Обычно содержит два метода `success` и `errors`. Метод `success` будет вызван, если все задачи в цепочке выполнились успешно. Метод `errors` будет вызван, если хотя бы одна из задач в цепочке была завершена с ошибкой.
```php
<?php

namespace App\Promises;

use Tochka\Queue\Promises\Jobs\Promise;

class TestPromise extends Promise
{
    /**
     * Create a new promise instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Actions after successfully completed tasks
     *
     * @return bool
     */
    public function success(): bool
    {
        //
        return true;
    }

    /**
     * Actions after tasks completed with errors
     *
     * @return bool
     */
    public function errors(): bool
    {
        //
        return true;
    }
}
```
Кроме того, вместо этих методов можно использовать метод `done`, который будет вызван при любом исходе (но только если в классе нет подходящего метода `success` или `errors`).
### Запуск цепочки
Для того, чтобы задачи могли использоваться вместе с промисами, необходимо реализовать в них интерфейс `Tochka\Queue\Promises\Contracts\MayPromised`. Реализация для наиболее частых случаев уже выполнена в трейте `Tochka\Queue\Promises\Jobs\Promised`. Достаточно просто указать зависимость от интерфейса и использовать трейт:
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
Для создания цепочки, необходимо добавить необходимые задачи в промис:
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
* При синхронном режиме задачи в цепочке будут запускаться по очереди, пока все задачи не завершатся, либо какая-нибудь из задач не завершится с ошибкой. После этого будет вызван соответствующий метод промиса.
* При асинхронном режиме все задачи поставятся в очередь сразу, и промис будет вызван как только все задачи завершатся.

### Обработка результатов
Для получения массива результатов работы всех задач в цепочке воспользуйтесь методом `getResults`:
```php
public function done(): bool
{
    $results = $this->getResults();
    foreach ($results as $result) {
        $status = $result->getJobStatus(); // вернет статус работы задачи
    }
}
```
Данный метод возвращает классы, реализующие интерфейс `Tochka\Queue\Promises\Contracts\MayPromised`.
Также вы можете воспользоваться DependencyInjection в объявлении методов `success`, `done` и `errors`:
```php
public function errors(SomeJob1 $job1, SomeJob2 $job2): bool
{
    echo $job1->getJobStatus();
    echo $job2->getJobStatus();
}
```
DI работает так:
* если вы указали в качестве типа класс с интерфейсом `Tochka\Queue\Promises\Contracts\MayPromised`, то при вызове метода в качестве аргумента будет передан результат работы задачи с указанным классом
* если в результатах имеется несколько подходящих классов - то будет передан первый из них
* если вы указали несколько аргументов с одинаковым типом, а в результатах имеется несколько подходящих классов - в качестве аргументов будут передаваться результаты по очереди:
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
Учтите! Если вы использовали асинхронный запуск задач, то результаты их работы могут придти в порядке, отличном от заданного вами порядка, потому в последнем примере возможна, например, следующая ситуация:
```php
public function errors(SomeJob $job1, SomeJob $job2): bool
{
    echo $job1->text; // job 3
    echo $job2->text; // job 1
    echo $job3->text; // job 2
    echo $job4->text; // job 4
}
```
* если в результатах нет или уже не осталось подходящих классов - то в качестве аргумента будет передан null
* если вы указали в качестве типа класс без интерфейса `Tochka\Queue\Promises\Contracts\MayPromised`, то в качестве аргумента будет передан объект, созданный стандартным механизмом DI Laravel, как если бы вы вызвали функцию app с указанным классом.

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