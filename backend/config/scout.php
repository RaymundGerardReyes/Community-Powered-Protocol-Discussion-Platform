<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Search Engine
    |--------------------------------------------------------------------------
    |
    | This option controls the default search connection that gets used while
    | using Laravel Scout. This connection is used when syncing all models
    | to the search service. You should adjust this based on your needs.
    |
    | Supported: "algolia", "meilisearch", "typesense", "turbopuffer",
    |            "database", "collection", "null"
    |
    */

    'driver' => env('SCOUT_DRIVER', 'collection'),

    /*
    |--------------------------------------------------------------------------
    | Index Prefix
    |--------------------------------------------------------------------------
    |
    | Here you may specify a prefix that will be applied to all search index
    | names used by Scout. This prefix may be useful if you have multiple
    | "tenants" or applications sharing the same search infrastructure.
    |
    */

    'prefix' => env('SCOUT_PREFIX', ''),

    /*
    |--------------------------------------------------------------------------
    | Queue Data Syncing
    |--------------------------------------------------------------------------
    |
    | This option allows you to control if the operations that sync your data
    | with your search engines are queued. When this is set to "true" then
    | all automatic data syncing will get queued for better performance.
    |
    */

    'queue' => env('SCOUT_QUEUE', false),

    /*
    |--------------------------------------------------------------------------
    | Database Transactions
    |--------------------------------------------------------------------------
    |
    | This configuration option determines if your data will only be synced
    | with your search indexes after every open database transaction has
    | been committed, thus preventing any discarded data from syncing.
    |
    */

    'after_commit' => false,

    /*
    |--------------------------------------------------------------------------
    | Chunk Sizes
    |--------------------------------------------------------------------------
    |
    | These options allow you to control the maximum chunk size when you are
    | mass importing data into the search engine. This allows you to fine
    | tune each of these chunk sizes based on the power of the servers.
    |
    */

    'chunk' => [
        'searchable' => 500,
        'unsearchable' => 500,
    ],

    /*
    |--------------------------------------------------------------------------
    | Soft Deletes
    |--------------------------------------------------------------------------
    |
    | This option allows to control whether to keep soft deleted records in
    | the search indexes. Maintaining soft deleted records can be useful
    | if your application still needs to search for the records later.
    |
    */

    'soft_delete' => false,

    /*
    |--------------------------------------------------------------------------
    | Identify User
    |--------------------------------------------------------------------------
    |
    | This option allows you to control whether to notify the search engine
    | of the user performing the search. This is sometimes useful if the
    | engine supports any analytics based on this application's users.
    |
    | Supported engines: "algolia"
    |
    */

    'identify' => env('SCOUT_IDENTIFY', false),

    /*
    |--------------------------------------------------------------------------
    | Algolia Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your Algolia settings. Algolia is a cloud hosted
    | search engine which works great with Scout out of the box. Just plug
    | in your application ID and admin API key to get started searching.
    |
    */

    'algolia' => [
        'id' => env('ALGOLIA_APP_ID', ''),
        'secret' => env('ALGOLIA_SECRET', ''),
        'index-settings' => [
            // 'users' => [
            //     'searchableAttributes' => ['id', 'name', 'email'],
            //     'attributesForFaceting'=> ['filterOnly(email)'],
            // ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Meilisearch Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your Meilisearch settings. Meilisearch is an open
    | source search engine with minimal configuration. Below, you can state
    | the host and key information for your own Meilisearch installation.
    |
    | See: https://www.meilisearch.com/docs/learn/configuration/instance_options#all-instance-options
    |
    */

    'meilisearch' => [
        'host' => env('MEILISEARCH_HOST', 'http://localhost:7700'),
        'key' => env('MEILISEARCH_KEY'),
        'index-settings' => [
            // 'users' => [
            //     'filterableAttributes' => ['id', 'name', 'email'],
            //     'embedders' => [
            //         'default' => [
            //             'source' => 'userProvided',
            //             'dimensions' => 1536,
            //         ],
            //     ],
            // ],
        ],
        'model-settings' => [
            // User::class => [
            //     'embedding' => [
            //         'embedder' => 'default',
            //         'dimensions' => 1536,
            //     ],
            // ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Typesense Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your Typesense settings. Typesense is an open
    | source search engine using minimal configuration. Below, you will
    | state the host, key, and schema configuration for the instance.
    |
    */

    'typesense' => (function () {
        $rawHost = trim((string) env('TYPESENSE_HOST', 'localhost'), " \t\n\r\0\x0B\"'");
        $rawHost = preg_replace('#^https?://#i', '', rtrim($rawHost, '/'));

        // Handle possible host:port formats like mycluster.typesense.net:443
        $parts = explode(':', $rawHost);
        $cleanHost = trim($parts[0], " \t\n\r\0\x0B\"'");
        $embeddedPort = $parts[1] ?? null;

        $isCloud = str_contains($cleanHost, 'typesense.net');
        $port = (string) (env('TYPESENSE_PORT') ?: ($embeddedPort ?: ($isCloud ? '443' : '8108')));
        $protocol = env('TYPESENSE_PROTOCOL');
        if (empty($protocol) || ($protocol === 'http' && ($port === '443' || $isCloud))) {
            $protocol = ($port === '443' || $isCloud) ? 'https' : 'http';
        }

        $adminKey = env('TYPESENSE_ADMIN_API_KEY');
        $regularKey = env('TYPESENSE_API_KEY');

        if (!empty($adminKey) && $adminKey !== 'xyz') {
            $selectedKey = $adminKey;
            $keySource = 'TYPESENSE_ADMIN_API_KEY';
        } elseif (!empty($regularKey) && $regularKey !== 'xyz') {
            $selectedKey = $regularKey;
            $keySource = 'TYPESENSE_API_KEY';
        } else {
            $selectedKey = $adminKey ?: ($regularKey ?: 'xyz');
            $keySource = 'default (xyz)';
        }

        $apiKey = trim((string) $selectedKey, " \t\n\r\0\x0B\"'");

        return [
            'client-settings' => [
                'api_key' => $apiKey,
                'api_key_source' => $keySource,
                'nodes' => [
                    [
                        'host' => $cleanHost,
                        'port' => $port,
                        'path' => env('TYPESENSE_PATH', ''),
                        'protocol' => $protocol,
                    ],
                ],
                'nearest_node' => [
                    'host' => $cleanHost,
                    'port' => $port,
                    'path' => env('TYPESENSE_PATH', ''),
                    'protocol' => $protocol,
                ],
                'connection_timeout_seconds' => env('TYPESENSE_CONNECTION_TIMEOUT_SECONDS', 5),
                'healthcheck_interval_seconds' => env('TYPESENSE_HEALTHCHECK_INTERVAL_SECONDS', 30),
                'num_retries' => env('TYPESENSE_NUM_RETRIES', 3),
                'retry_interval_seconds' => env('TYPESENSE_RETRY_INTERVAL_SECONDS', 1),
            ],
            'model-settings' => [],
            'import_action' => env('TYPESENSE_IMPORT_ACTION', 'upsert'),
        ];
    })(),

    /*
    |--------------------------------------------------------------------------
    | Turbopuffer Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your Turbopuffer connection and the schema and
    | searchable attributes defined by each of your application's models.
    | Turbopuffer is a scalable engine with full-text + vector search.
    |
    */

    'turbopuffer' => [
        'api_key' => env('TURBOPUFFER_API_KEY'),
        'region' => env('TURBOPUFFER_REGION', 'gcp-us-central1'),
        'base_url' => env('TURBOPUFFER_BASE_URL'),
        'timeout' => env('TURBOPUFFER_TIMEOUT', 60),
        'connect_timeout' => env('TURBOPUFFER_CONNECT_TIMEOUT', 5),
        'retries' => env('TURBOPUFFER_RETRIES', 3),
        'model-settings' => [
            // User::class => [
            //     'searchable-attributes' => [
            //         'name' => 2,
            //         'email' => 1,
            //     ],
            //     'embedding' => [
            //         'attribute' => 'embedding',
            //         'dimensions' => 1536,
            //     ],
            //     'schema' => [
            //         'name' => ['type' => 'string', 'full_text_search' => true],
            //         'email' => ['type' => 'string', 'full_text_search' => true],
            //         'embedding' => ['type' => '[1536]f32', 'ann' => true],
            //     ],
            // ],
        ],
    ],

];
