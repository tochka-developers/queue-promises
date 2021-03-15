<?php

namespace Tochka\Promises\Tests\Models\Casts;

use Illuminate\Database\Eloquent\Model;
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
}
