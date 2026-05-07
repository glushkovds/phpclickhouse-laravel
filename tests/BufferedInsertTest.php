<?php

namespace Tests;

use ClickHouseDB\Statement;
use PhpClickHouseLaravel\BaseModel;
use Throwable;
use Tests\Models\Example;
use Tests\Models\Example3;
use Tests\Models\ExampleNonExistent;

class BufferedInsertTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Example::truncate();
        Example3::truncate();
        Example::clearBuffer();
        Example3::clearBuffer();
        ExampleNonExistent::clearBuffer();
    }

    protected function tearDown(): void
    {
        Example::clearBuffer();
        Example3::clearBuffer();
        ExampleNonExistent::clearBuffer();
        parent::tearDown();
    }

    public function testBufferAndManualFlush()
    {
        Example::buffer(['f_int' => 1, 'f_string' => 'a']);
        Example::buffer(['f_int' => 2, 'f_string' => 'b']);
        $this->assertEquals(2, Example::bufferCount());

        $statement = Example::flushBuffer();
        $this->assertInstanceOf(Statement::class, $statement);
        $this->assertEquals(0, Example::bufferCount());

        $rows = Example::select()->orderBy('f_int')->getRows();
        $this->assertCount(2, $rows);
        $this->assertEquals('a', $rows[0]['f_string']);
        $this->assertEquals('b', $rows[1]['f_string']);
    }

    public function testFlushEmptyBufferReturnsNull()
    {
        $this->assertNull(Example::flushBuffer());
        $this->assertCount(0, Example::select()->getRows());
    }

    public function testBufferAcceptsSingleRowAndArrayOfRows()
    {
        Example::buffer(['f_int' => 1, 'f_string' => 'x']);
        Example::buffer([
            ['f_int' => 2, 'f_string' => 'y'],
            ['f_int' => 3, 'f_string' => 'z'],
        ]);
        $this->assertEquals(3, Example::bufferCount());

        Example::flushBuffer();
        $this->assertCount(3, Example::select()->getRows());
    }

    public function testBufferIgnoresEmptyArray()
    {
        Example::buffer([]);
        $this->assertEquals(0, Example::bufferCount());
        $this->assertNull(Example::flushBuffer());
    }

    public function testCastsAppliedOnBufferedRows()
    {
        Example3::buffer(['f_int' => 1, 'f_bool' => false]);
        $buffered = Example3::getBufferedRows();
        $this->assertSame(0, $buffered[0]['f_bool']);

        Example3::flushBuffer();
        $rows = Example3::where('f_int', 1)->getRows();
        $this->assertEquals(false, $rows[0]['f_bool']);
    }

    public function testFlushFailurePreservesBuffer()
    {
        ExampleNonExistent::buffer(['f_int' => 1, 'f_string' => 'kept']);

        $threw = false;
        try {
            ExampleNonExistent::flushBuffer();
        } catch (Throwable) {
            $threw = true;
        }

        $this->assertTrue($threw, 'flushBuffer should rethrow ClickHouse errors');
        $this->assertEquals(1, ExampleNonExistent::bufferCount());
        $this->assertEquals('kept', ExampleNonExistent::getBufferedRows()[0]['f_string']);
    }

    public function testMultipleModelsHaveIndependentBuffers()
    {
        Example::buffer(['f_int' => 10, 'f_string' => 'ex']);
        Example3::buffer(['f_int' => 20, 'f_bool' => true]);

        $this->assertEquals(1, Example::bufferCount());
        $this->assertEquals(1, Example3::bufferCount());

        Example::flushBuffer();
        $this->assertEquals(0, Example::bufferCount());
        $this->assertEquals(1, Example3::bufferCount());

        Example3::flushBuffer();
        $this->assertEquals(0, Example3::bufferCount());
    }

    public function testTerminatingTriggersAutoFlush()
    {
        Example::buffer(['f_int' => 99, 'f_string' => 'auto']);
        $this->assertEquals(1, Example::bufferCount());

        $this->app->terminate();

        $this->assertEquals(0, Example::bufferCount());
        $rows = Example::where('f_int', 99)->getRows();
        $this->assertCount(1, $rows);
        $this->assertEquals('auto', $rows[0]['f_string']);
    }

    public function testFlushAllBuffersSilentSwallowsErrors()
    {
        Example::buffer(['f_int' => 7, 'f_string' => 'ok']);
        ExampleNonExistent::buffer(['f_int' => 8, 'f_string' => 'broken']);

        BaseModel::flushAllBuffers(silent: true);

        $this->assertEquals(0, Example::bufferCount());
        $this->assertEquals(1, ExampleNonExistent::bufferCount());

        $rows = Example::where('f_int', 7)->getRows();
        $this->assertCount(1, $rows);
    }

    public function testClearBufferDiscardsWithoutFlushing()
    {
        Example::buffer(['f_int' => 1, 'f_string' => 'discard']);
        Example::clearBuffer();

        $this->assertEquals(0, Example::bufferCount());
        $this->assertNull(Example::flushBuffer());
        $this->assertCount(0, Example::select()->getRows());
    }
}
