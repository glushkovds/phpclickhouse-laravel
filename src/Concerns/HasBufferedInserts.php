<?php

declare(strict_types=1);

namespace PhpClickHouseLaravel\Concerns;

use ClickHouseDB\Statement;
use Throwable;

/**
 * In-memory buffered inserts for ClickHouse models.
 *
 * Rows are accumulated per-class via buffer() and sent to ClickHouse as a
 * single insertAssocBulk HTTP request when flushBuffer() is called. The cast
 * pipeline used by insertAssoc() is applied at buffer time, not at flush time,
 * so the buffer always holds prepared rows ready for the wire.
 *
 * On flush failure the buffer is preserved so the caller can retry.
 */
trait HasBufferedInserts
{
    /** @var array<class-string, array<int, array<string, mixed>>> */
    protected static array $buffers = [];

    /** @var array<class-string, true> */
    protected static array $modelsWithBuffer = [];

    /**
     * Accept a single associative row or an array of associative rows.
     */
    public static function buffer(array $rowOrRows): void
    {
        if ($rowOrRows === []) {
            return;
        }

        $rows = self::isSingleAssocRow($rowOrRows) ? [$rowOrRows] : array_values($rowOrRows);

        $prepared = static::prepareAssocRowsForInsert($rows);

        if (!isset(static::$buffers[static::class])) {
            static::$buffers[static::class] = [];
        }
        array_push(static::$buffers[static::class], ...$prepared);
        static::$modelsWithBuffer[static::class] = true;
    }

    /**
     * Send all buffered rows to ClickHouse as a single HTTP request.
     * Returns null when the buffer is empty. On failure the buffer is preserved.
     */
    public static function flushBuffer(): ?Statement
    {
        $rows = static::$buffers[static::class] ?? [];
        if ($rows === []) {
            return null;
        }

        $instance = new static();
        $statement = $instance->getThisClient()->insertAssocBulk(
            $instance->getTableForInserts(),
            $rows
        );

        unset(static::$buffers[static::class], static::$modelsWithBuffer[static::class]);

        return $statement;
    }

    /**
     * Flush every model that currently has buffered rows.
     *
     * @param bool $silent When true, exceptions are reported via report() instead of bubbling.
     *                     Used by the script-shutdown auto-flush hook.
     */
    public static function flushAllBuffers(bool $silent = false): void
    {
        foreach (array_keys(self::$modelsWithBuffer) as $class) {
            try {
                /** @var class-string<self> $class */
                $class::flushBuffer();
            } catch (Throwable $e) {
                if (!$silent) {
                    throw $e;
                }
                if (function_exists('report')) {
                    report($e);
                }
            }
        }
    }

    public static function bufferCount(): int
    {
        return count(static::$buffers[static::class] ?? []);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function getBufferedRows(): array
    {
        return static::$buffers[static::class] ?? [];
    }

    /**
     * Discard the buffer without sending anything to ClickHouse.
     */
    public static function clearBuffer(): void
    {
        unset(static::$buffers[static::class], static::$modelsWithBuffer[static::class]);
    }

    private static function isSingleAssocRow(array $value): bool
    {
        foreach (array_keys($value) as $key) {
            if (!is_string($key)) {
                return false;
            }
        }
        return true;
    }
}
