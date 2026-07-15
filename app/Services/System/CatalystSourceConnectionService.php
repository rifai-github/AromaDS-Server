<?php

namespace App\Services\System;

use Illuminate\Support\Facades\DB;

class CatalystSourceConnectionService
{
    public function connectionName(): string
    {
        $name = (string) config('catalyst-import.connection_name', 'catalyst_import');

        if (!config("database.connections.$name")) {
            $connection = [
                'driver' => config('catalyst-import.source.driver', 'sqlsrv'),
                'host' => config('catalyst-import.source.host'),
                'port' => config('catalyst-import.source.port'),
                'database' => config('catalyst-import.source.database'),
                'charset' => config('catalyst-import.source.charset', 'utf8'),
                'prefix' => config('catalyst-import.source.prefix', ''),
                'prefix_indexes' => true,
                'encrypt' => config('catalyst-import.source.encrypt', false),
                'trust_server_certificate' => config('catalyst-import.source.trust_server_certificate', true),
                'trusted_connection' => config('catalyst-import.source.trusted_connection', false),
            ];

            $username = config('catalyst-import.source.username');
            $password = config('catalyst-import.source.password');

            if (!blank($username)) {
                $connection['username'] = $username;
            }

            if (!blank($password)) {
                $connection['password'] = $password;
            }

            config([
                "database.connections.$name" => $connection,
            ]);
        }

        return $name;
    }

    public function probe(): array
    {
        $connection = DB::connection($this->connectionName());
        $connection->getPdo();

        $databaseName = (string) config('catalyst-import.source.database', 'PinkAds');
        $tableCount = (int) collect($connection->select(
            'SELECT COUNT(*) AS aggregate FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_CATALOG = ?',
            [$databaseName]
        ))->first()->aggregate;

        return [
            'database' => $databaseName,
            'driver' => (string) config('catalyst-import.source.driver', 'sqlsrv'),
            'host' => (string) config('catalyst-import.source.host'),
            'port' => (string) config('catalyst-import.source.port'),
            'table_count' => $tableCount,
        ];
    }
}
