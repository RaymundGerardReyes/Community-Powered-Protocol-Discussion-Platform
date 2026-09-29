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

    'driver' => (function () {
        $serverVal = $_SERVER['SCOUT_DRIVER'] ?? $_ENV['SCOUT_DRIVER'] ?? null;
        if ($serverVal === 'null') {
            return 'null';
        }

        $raw = env('SCOUT_DRIVER');
        if ($raw === null || $raw === '') {
            return 'typesense';
        }

        $driver = strtolower(trim((string) $raw));
        if ($driver === 'null') {
            return 'null';
        }

        return 'typesense';
    })(),

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
        $rawHost = (string) env('TYPESENSE_HOST', 'localhost');
        // 1. Strip all non-ASCII, non-printable characters (NBSP \xC2\xA0, BOM, zero-width spaces, control chars)
        $clean = preg_replace('/[^\x21-\x7E]/', '', $rawHost);
        // 2. Strip protocol prefix
        $clean = preg_replace('#^https?://#i', '', $clean);
        // 3. Strip trailing slashes and paths
        $clean = explode('/', $clean)[0];
        // 4. Extract embedded port if present
        $parts = explode(':', $clean);
        $cleanHost = trim($parts[0], " \t\n\r\0\x0B\"'");
        $embeddedPort = isset($parts[1]) && is_numeric($parts[1]) ? $parts[1] : null;

        $hostStatus = 'local';
        $originalHost = $cleanHost;

        if ($cleanHost !== 'localhost' && $cleanHost !== '127.0.0.1' && !empty($cleanHost)) {
            // Direct DNS check
            if (gethostbyname($cleanHost) !== $cleanHost) {
                $hostStatus = 'resolved_direct';
            } else {
                // Generate candidate variants for Typesense Cloud
                $candidates = [];

                // Case A: User pasted only the Cluster ID (e.g. 10-25 alphanumeric chars without dots)
                if (preg_match('/^[a-z0-9]{10,25}$/i', $cleanHost)) {
                    $candidates[] = $cleanHost . '-1.a1.typesense.net';
                    $candidates[] = $cleanHost . '.a1.typesense.net';
                }

                // Case B: Host ends in typesense.net
                if (str_ends_with($cleanHost, 'typesense.net')) {
                    // Sub-case: Missing .a1. or .a[0-9].
                    if (!preg_match('/\.a[0-9]\./i', $cleanHost)) {
                        $withA1 = preg_replace('/\.typesense\.net$/i', '.a1.typesense.net', $cleanHost);
                        $candidates[] = $withA1;
                        if (!str_contains($withA1, '-1.')) {
                            $candidates[] = preg_replace('/^([a-z0-9]+)(\.a1\.typesense\.net)$/i', '$1-1$2', $withA1);
                        }
                    }

                    // Sub-case: Toggle -1 suffix
                    if (preg_match('/^([a-z0-9]+)-1(\..+)$/i', $cleanHost, $m)) {
                        $candidates[] = $m[1] . $m[2];
                    } elseif (preg_match('/^([a-z0-9]+)(\.a[0-9]\.typesense\.net)$/i', $cleanHost, $m)) {
                        $candidates[] = $m[1] . '-1' . $m[2];
                    }
                }

                foreach ($candidates as $candidate) {
                    if (gethostbyname($candidate) !== $candidate) {
                        $cleanHost = $candidate;
                        $hostStatus = 'auto_corrected';
                        break;
                    }
                }

                if ($hostStatus !== 'auto_corrected') {
                    $hostStatus = 'unresolved';
                }
            }
        }

        $isCloud = str_contains($cleanHost, 'typesense.net');
        $port = (string) (env('TYPESENSE_PORT') ?: ($embeddedPort ?: ($isCloud ? '443' : '8108')));
        $protocol = env('TYPESENSE_PROTOCOL');
        if (empty($protocol) || ($protocol === 'http' && ($port === '443' || $isCloud))) {
            $protocol = ($port === '443' || $isCloud) ? 'https' : 'http';
        }

        $rawAdminKey = env('TYPESENSE_ADMIN_API_KEY');
        $rawRegularKey = env('TYPESENSE_API_KEY');

        $cleanAdminKey = preg_replace('/[^\x21-\x7E]/', '', (string) $rawAdminKey);
        $cleanAdminKey = trim($cleanAdminKey, " \t\n\r\0\x0B\"'");

        $cleanRegularKey = preg_replace('/[^\x21-\x7E]/', '', (string) $rawRegularKey);
        $cleanRegularKey = trim($cleanRegularKey, " \t\n\r\0\x0B\"'");

        if (!empty($cleanAdminKey) && $cleanAdminKey !== 'xyz') {
            $selectedKey = $cleanAdminKey;
            $keySource = 'TYPESENSE_ADMIN_API_KEY';
        } elseif (!empty($cleanRegularKey) && $cleanRegularKey !== 'xyz') {
            $selectedKey = $cleanRegularKey;
            $keySource = 'TYPESENSE_API_KEY';
        } else {
            $selectedKey = $cleanAdminKey ?: ($cleanRegularKey ?: 'xyz');
            $keySource = 'default (xyz)';
        }

        $apiKey = $selectedKey;
        $hasKey = (!empty($cleanAdminKey) && $cleanAdminKey !== 'xyz') || (!empty($cleanRegularKey) && $cleanRegularKey !== 'xyz');
        $hasHost = !empty(env('TYPESENSE_HOST'));
        $isConfigured = $hasKey && $hasHost;

        return [
            'is_configured' => $isConfigured,
            'client-settings' => [
                'api_key' => $apiKey,
                'api_key_source' => $keySource,
                'host_status' => $hostStatus,
                'original_host' => $originalHost,
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
                'connection_timeout_seconds' => (int) env('TYPESENSE_CONNECTION_TIMEOUT_SECONDS', 10),
                'healthcheck_interval_seconds' => (int) env('TYPESENSE_HEALTHCHECK_INTERVAL_SECONDS', 30),
                'num_retries' => (int) env('TYPESENSE_NUM_RETRIES', 3),
                'retry_interval_seconds' => (int) env('TYPESENSE_RETRY_INTERVAL_SECONDS', 1),
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
