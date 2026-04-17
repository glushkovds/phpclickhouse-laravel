<?php

namespace Tests;

use Tests\Models\Example4;
use Tests\Models\Example4Problem;

class ClusterTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();

        if (! env('CLICKHOUSE_CLUSTER_AVAILABLE')) {
            $this->markTestSkipped('company_cluster config requires docker-compose services');
        }

        Example4::truncate();
    }

    public function testRegularCluster()
    {
        $node1Port = (int) env('CLICKHOUSE_PORT', 18123);
        $node2Port = (int) env('CLICKHOUSE2_PORT', 18124);

        Example4::truncate();
        sleep(2); // clickhouse nodes sync lag
        $this->assertEquals($node1Port, (int) (new Example4())->getThisClient()->getConnectPort());
        Example4::insertAssoc([['f_int' => 1, 'f_string' => 'a']]);
        $this->assertNotEmpty(Example4::where('f_int', 1)->getRows());

        (new Example4())->resolveConnection()->getCluster()->slideNode();
        $this->assertEquals($node2Port, (int) (new Example4())->getThisClient()->getConnectPort());
        sleep(2); // clickhouse nodes sync lag
        $this->assertNotEmpty(Example4::where('f_int', 1)->getRows());

        (new Example4())->resolveConnection()->getCluster()->slideNode();
        $this->assertEquals($node1Port, (int) (new Example4())->getThisClient()->getConnectPort());
    }

    public function testProblemCluster()
    {
        $node2Port = (int) env('CLICKHOUSE2_PORT', 18124);

        $this->assertEquals($node2Port, (int) (new Example4Problem())->getThisClient()->getConnectPort());
        Example4Problem::insertAssoc([['f_int' => 1, 'f_string' => 'a']]);
        $this->assertNotEmpty(Example4Problem::where('f_int', 1)->getRows());

        (new Example4Problem())->resolveConnection()->getCluster()->slideNode();
        $this->assertEquals($node2Port, (int) (new Example4Problem())->getThisClient()->getConnectPort());
        (new Example4Problem())->resolveConnection()->getCluster()->slideNode();
        $this->assertEquals($node2Port, (int) (new Example4Problem())->getThisClient()->getConnectPort());
    }

}