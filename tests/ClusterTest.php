<?php

namespace Tests;

use Tests\Models\Example4;
use Tests\Models\Example4Problem;

class ClusterTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();
        Example4::truncate();
    }

    public function testRegularCluster()
    {
        Example4::truncate();
        sleep(1); // clickhouse nodes sync lag
        $this->assertEquals('clickhouse01', (new Example4())->getThisClient()->getConnectHost());
        Example4::insertAssoc([['f_int' => 1, 'f_string' => 'a']]);
        $this->assertNotEmpty(Example4::where('f_int', 1)->getRows());

        (new Example4())->resolveConnection()->getCluster()->slideNode();
        $this->assertEquals('clickhouse02', (new Example4())->getThisClient()->getConnectHost());
        $this->assertEmpty(Example4::where('f_int', 1)->getRows());
        sleep(1); // clickhouse nodes sync lag
        $this->assertNotEmpty(Example4::where('f_int', 1)->getRows());

        (new Example4())->resolveConnection()->getCluster()->slideNode();
        $this->assertEquals('clickhouse01', (new Example4())->getThisClient()->getConnectHost());
    }

    public function testProblemCluster()
    {
        $this->assertEquals('clickhouse02', (new Example4Problem())->getThisClient()->getConnectHost());
        Example4Problem::insertAssoc([['f_int' => 1, 'f_string' => 'a']]);
        $this->assertNotEmpty(Example4Problem::where('f_int', 1)->getRows());

        (new Example4Problem())->resolveConnection()->getCluster()->slideNode();
        $this->assertEquals('clickhouse02', (new Example4Problem())->getThisClient()->getConnectHost());
        (new Example4Problem())->resolveConnection()->getCluster()->slideNode();
        $this->assertEquals('clickhouse02', (new Example4Problem())->getThisClient()->getConnectHost());
    }

}