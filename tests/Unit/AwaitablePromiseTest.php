<?php

namespace Jmoo\React\Chrome\Tests\Unit;

use Jmoo\React\Support\AwaitablePromise;
use React\EventLoop\Factory;
use React\Promise\Deferred;

class AwaitablePromiseTest extends \PHPUnit\Framework\TestCase
{
    /** @test */
    public function promiseAllResolvesAllPromisesAndReturnsResultsInCorrectOrder()
    {
        $loop = Factory::create();

        $a = new Deferred;
        $b = new Deferred;
        $c = new Deferred;

        $promise = AwaitablePromise::all([
            $a->promise(),
            $b->promise(),
            $c->promise()
        ], $loop);

        $promise->then(function ($result) {
            $this->assertEquals('abc', implode('', $result));
        });

        $a->resolve('a');
        $c->resolve('c');
        $b->resolve('b');
    }

    /** @test */
    public function promiseAllRejectsOnSingleRejection()
    {
        $loop = Factory::create();

        $a = new Deferred;
        $b = new Deferred;
        $c = new Deferred;

        $promise = AwaitablePromise::all([
            $a->promise(),
            $b->promise(),
            $c->promise()
        ], $loop);

        $promise->then(
            function () {
                $this->fail('Promise should have rejected.');
            },
            function ($result) {
                $this->assertEquals('rejection', $result);
            }
        );

        $a->resolve('a');
        $b->reject('rejection');
        $c->resolve('c');
    }
}