<?php

namespace Tochka\Promises\Tests\Models\Casts;

use Illuminate\Database\Eloquent\Model;
use Tochka\Promises\Exceptions\IncorrectResolvingClass;
use Tochka\Promises\Models\Casts\SerializableClassCast;
use Tochka\Promises\Tests\TestCase;
use Tochka\Promises\Tests\TestHelpers\TestPromise;

/**
 * @covers \Tochka\Promises\Models\Casts\SerializableClassCast
 */
class SerializableClassCastTest extends TestCase
{
    /**
     * @covers \Tochka\Promises\Models\Casts\SerializableClassCast::get
     * @covers \Tochka\Promises\Models\Casts\SerializableClassCast::set
     * @throws \JsonException
     */
    public function testGetSet(): void
    {
        $model = \Mockery::mock(Model::class);
        $object = new TestPromise();

        $cast = new SerializableClassCast();
        $casted = $cast->set($model, 'test', $object, []);

        $result = $cast->get($model, 'test', $casted['test'], $casted);

        self::assertEquals($object, $result);
    }

    /**
     * @covers \Tochka\Promises\Models\Casts\SerializableClassCast::get
     * @covers \Tochka\Promises\Models\Casts\SerializableClassCast::set
     * @throws \JsonException
     */
    public function testGetSetNull(): void
    {
        $model = \Mockery::mock(Model::class);

        $cast = new SerializableClassCast();
        $casted = $cast->set($model, 'test', null, []);
        $result = $cast->get($model, 'test', $casted['test'], $casted);

        self::assertNull($result);
    }

    /**
     * @covers \Tochka\Promises\Models\Casts\SerializableClassCast::get
     * @throws \JsonException
     */
    public function testGetIncomplete(): void
    {
        $model = \Mockery::mock(Model::class);
        $test = '"O:26:\\"TestIncompleteUnknownClass\\":3:{s:51:\\"\\u0000Tochka\\\\Promises\\\\Tests\\\\TestHelpers\\\\TestPromise\\u0000jobs\\";a:0:{}s:57:\\"\\u0000Tochka\\\\Promises\\\\Tests\\\\TestHelpers\\\\TestPromise\\u0000promise_id\\";N;s:14:\\"\\u0000*\\u0000base_job_id\\";N;}"';

        $this->expectException(IncorrectResolvingClass::class);

        $cast = new SerializableClassCast();
        $cast->get($model, 'test', $test, []);
    }
}
