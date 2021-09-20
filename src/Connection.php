<?php


namespace PhpClickHouseLaravel;


use ClickHouseDB\Client;

class Connection extends \Illuminate\Database\Connection
{
    protected $client;

    /**
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }

    public static function createWithClient($config)
    {
        $conn = new static(null, '', '', $config);
        $conn->client = new Client($config);
        $conn->client->database($config['database']);
        $conn->client->setTimeout($config['timeout_query']);
        $conn->client->setConnectTimeOut($config['timeout_connect']);
        if ($configSettings =& $config['settings']) {
            $settings = $conn->getClient()->settings();
            foreach ($configSettings as $sName => $sValue) {
                $settings->set($sName, $sValue);
            }
        }
        if ($retries = (int)($config['retries'] ?? null)) {
            $curler = new CurlerRollingWithRetries();
            $curler->setRetries($retries);
            $conn->client->transport()->setDirtyCurler($curler);
        }
        return $conn;
    }

}
