<?php

namespace PhpClickHouseLaravel;

use ClickHouseDB\Transport\CurlerRequest;

class CurlerRollingWithRetries extends \ClickHouseDB\Transport\CurlerRolling
{

    /**
     * @var int 0 mean only one attempt, 1 mean one attempt + 1 retry while error (2 total attempts)
     */
    protected $retries = 0;

    /** @inheritDoc */
    public function execOne(CurlerRequest $request, $auto_close = false)
    {
        $attempts = 1 + max(0, $this->retries);
        $httpCode = 0;
        while ($attempts-- && 200 !== $httpCode) {
            $httpCode = parent::execOne($request, $auto_close);
        }
        return $httpCode;
    }

    /**
     * @return int
     */
    public function getRetries(): int
    {
        return $this->retries;
    }

    /**
     * @param int $retries
     */
    public function setRetries(int $retries): void
    {
        $this->retries = $retries;
    }
}
