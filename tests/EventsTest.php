<?php

namespace Tests;

use Illuminate\Contracts\Events\Dispatcher;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use PhpClickHouseLaravel\BaseModel;

class EventsTestModel extends BaseModel
{
    protected $table = 'events_test';

    public function fireModelEvent(string $event, bool $halt = true): mixed
    {
        return parent::fireModelEvent($event, $halt);
    }
}

class EventsTest extends PHPUnitTestCase
{
    protected function tearDown(): void
    {
        BaseModel::unsetEventDispatcher();
        parent::tearDown();
    }

    public function testFireModelEventReturnsTrueWithoutDispatcher(): void
    {
        BaseModel::unsetEventDispatcher();

        $model = new EventsTestModel();

        $this->assertTrue($model->fireModelEvent('creating'));
    }

    public function testCreatingEventCanHaltCreation(): void
    {
        $dispatcher = $this->createMock(Dispatcher::class);
        $dispatcher->method('until')->willReturn(false);
        BaseModel::setEventDispatcher($dispatcher);

        $result = EventsTestModel::create(['field1' => 'value1']);

        $this->assertFalse($result);
    }

    public function testCreatedEventFires(): void
    {
        $firedEvents = [];
        BaseModel::setEventDispatcher($this->mockDispatcher($firedEvents));

        $model = new EventsTestModel();
        $model->fireModelEvent('created', false);

        $this->assertContains('eloquent.created: ' . EventsTestModel::class, $firedEvents);
    }

    public function testSavedEventFires(): void
    {
        $firedEvents = [];
        BaseModel::setEventDispatcher($this->mockDispatcher($firedEvents));

        $model = new EventsTestModel();
        $model->fireModelEvent('saved', false);

        $this->assertContains('eloquent.saved: ' . EventsTestModel::class, $firedEvents);
    }

    /**
     * @param array<string> $firedEvents
     */
    private function mockDispatcher(array &$firedEvents): Dispatcher
    {
        $dispatcher = $this->createMock(Dispatcher::class);

        $dispatcher->method('until')
            ->willReturnCallback(function (string $event) use (&$firedEvents) {
                $firedEvents[] = $event;
                return true;
            });

        $dispatcher->method('dispatch')
            ->willReturnCallback(function (string $event) use (&$firedEvents) {
                $firedEvents[] = $event;
                return [true];
            });

        return $dispatcher;
    }
}
