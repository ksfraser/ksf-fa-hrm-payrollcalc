<?php
/**
 * DbStub — stand-in for FA's native db_* layer so PayrollRepository can be
 * unit tested without a database.
 *
 * Mirrors FA's db_query()/db_fetch_assoc()/db_insert_id()/db_escape()
 * semantics using an in-memory row set.
 *
 * @package Ksfraser\FrontAccounting\HRM\PayrollCalc\Tests\Unit
 * @since   1.0.0
 */
declare(strict_types=1);

namespace {
    if (!class_exists('DbStub', false)) {
        /**
         * In-memory database stub for PayrollRepository unit tests.
         */
        final class DbStub
        {
            /** @var array|null Rows to return from db_fetch_assoc; null = not yet set */
            public static $rows = null;

            /** @var int Cursor position within the current result set */
            public static $pos = 0;

            /** @var bool Whether db_query returns a result (true) or false */
            public static $queryResult = true;

            /** @var int Value returned by db_insert_id() */
            public static $insertId = 1;

            /** @var string Last SQL passed to db_query() */
            public static $lastSql = null;

            /** @var array<string> Values passed to db_escape() */
            public static $escaped = [];

            /** @var array<array<int,array>> Queued result sets for setQueryResultSets */
            public static $queryResultSets = [];

            /** @var int Index into $queryResultSets for the next db_query call */
            public static $queryResultSetIndex = 0;

            /** @var array<array{0:string,1:string,2:array}> Recorded hook_invoke_all calls */
            public static $hookCalls = [];

            public static function reset(): void
            {
                self::$rows                 = null;
                self::$pos                  = 0;
                self::$queryResult          = true;
                self::$insertId             = 1;
                self::$lastSql              = null;
                self::$escaped              = [];
                self::$queryResultSets      = [];
                self::$queryResultSetIndex = 0;
                self::$hookCalls            = [];
            }

            public static function setRows(array $rows): void
            {
                self::$rows = $rows;
                self::$pos  = 0;
            }

            public static function setQueryResultSets(array $sets): void
            {
                self::$queryResultSets      = $sets;
                self::$queryResultSetIndex = 0;
                self::$rows                 = null;
                self::$pos                  = 0;
            }
        }
    }

    if (!function_exists('db_query')) {
        function db_query($sql, $errMsg = '')
        {
            \DbStub::$lastSql = $sql;

            // If query result sets have been queued, return them in order.
            if (\DbStub::$queryResultSets !== []) {
                $index = \DbStub::$queryResultSetIndex++;
                if (isset(\DbStub::$queryResultSets[$index])) {
                    \DbStub::$rows = \DbStub::$queryResultSets[$index];
                    \DbStub::$pos = 0;
                    return 'result';
                }
                // No more result sets — return false.
                return false;
            }

            if (\DbStub::$queryResult !== true) {
                return false;
            }
            return 'result';
        }
    }

    if (!function_exists('db_fetch_assoc')) {
        function db_fetch_assoc($result)
        {
            $rows = \DbStub::$rows;
            if ($rows === null) {
                return false;
            }
            if (\DbStub::$pos < count($rows)) {
                return $rows[\DbStub::$pos++];
            }
            return false;
        }
    }

    if (!function_exists('db_insert_id')) {
        function db_insert_id()
        {
            return \DbStub::$insertId;
        }
    }

    if (!function_exists('db_escape')) {
        function db_escape($value)
        {
            \DbStub::$escaped[] = (string) $value;
            return "'" . addslashes((string) $value) . "'";
        }
    }

    if (!function_exists('hook_invoke_all')) {
        function hook_invoke_all($module, $method, $data = null)
        {
            \DbStub::$hookCalls[] = [$module, $method, $data];
            $handler = $GLOBALS['KSF_HOOK_HANDLER'] ?? null;
            if (is_callable($handler)) {
                $handler($module, $method, $data);
            }
        }
    }
}
