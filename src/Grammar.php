<?php

namespace PhpClickHouseLaravel;


class Grammar extends \Tinderbox\ClickhouseBuilder\Query\Grammar
{
    public function __construct()
    {
        $this->selectComponents[] = 'settings';
    }

    public function compileSettingsComponent($_, array $settings): string
    {
        if (empty($settings)) {
            return "";
        }
        $strAr = [];
        foreach ($settings as $k => $v) {
            $strAr[] = is_int($v) ? "$k=$v" : "$k='$v'";
        }
        return 'SETTINGS ' . implode(', ', $strAr);
    }
}