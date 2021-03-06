<?php

namespace App\Helpers;

use SleekDB\Store;

/**
 * Helper for SleekDB query
 */
class SleekDBHelper
{
    private const MAX_QUERY_CACHE = 30;
    private const DATA_DIR = __DIR__ . "/../../mydb";
    private const DB_CONFIG = [
        "auto_cache" => true,
        "cache_lifetime" => self::MAX_QUERY_CACHE,
        "timeout" => 120,
        "primary_key" => "_id",
    ];

    /**
     * @param string $appName
     * @param string $tableName
     *
     * @return string
     */
    private static function getTableName(string $appName, string $tableName): string
    {
        return hash($_ENV['DB_HASH'], $appName) . "_" . $appName . "_" . $tableName;
    }

    /**
     * @param array $query
     *
     * @return Store
     */
    public static function getStore(array $query): Store
    {
        return new Store(self::getTableName($query['app_name'], $query['table']), self::DATA_DIR, self::DB_CONFIG);
    }

    /**
     * @param array $param
     *
     * @return array
     * @deprecated
     */
    public static function queryBuilder(array $param): array
    {
        $store = self::getStore($param);
        $builder = $store->createQueryBuilder();

        if (!empty($param['select']) && is_array($param['select'])) {
            $builder = $builder->select($param['select']);
        }

        if (!empty($param['join'])) {
            $joinStore = new Store(
                self::getTableName($param['app_name'], $param['join']['table']),
                self::DATA_DIR,
                self::DB_CONFIG
            );

            $builder = $builder->join(
                function ($var) use ($joinStore, $param) {
                    return $joinStore->findBy([
                        $param['join']['foreign_key'],
                        "===",
                        $var[$param['join']['relation_key']],
                    ]);
                },
                $param['join']['table']
            );
        }

        if (!empty($param['where'])) {
            $builder = $builder->where([$param['where']]);
        }

        if (!empty($param['search'])) {
            $builder = $builder->search($param['search']['fields'], $param['search']['keyword']);
        }

        if (!empty($param['distinct'])) {
            $builder = $builder->distinct($param['distinct']);
        }

        if (!empty($param['skip'])) {
            $builder = $builder->skip($param['skip']);
        }

        if (!empty($param['order_by'])) {
            $builder = $builder->orderBy($param['order_by']);
        }

        $data = $builder
            ->disableCache()
            ->getQuery()
            ->fetch();

        return $data;
    }
}
