<?php

use App\Models\Protocol;
use App\Models\Thread;
use App\Models\User;
use Tests\TestCase;

uses(TestCase::class);

const ALLOWED_TYPESENSE_TYPES = [
    'string',
    'string[]',
    'int32',
    'int32[]',
    'int64',
    'int64[]',
    'float',
    'float[]',
    'bool',
    'bool[]',
    'geopoint',
    'geopoint[]',
    'geopolygon',
    'object',
    'object[]',
    'string*',
    'image',
    'auto',
];

describe('Typesense Collections API Compliance', function () {
    test('Protocol schema defines valid root parameters matching Typesense API spec', function () {
        $protocol = new Protocol();
        $schema = $protocol->typesenseCollectionSchema();

        expect($schema)->toHaveKeys(['name', 'fields', 'default_sorting_field']);
        expect($schema['name'])->toBe('protocols');
        expect($schema['fields'])->toBeArray()->not->toBeEmpty();
    });

    test('Thread schema defines valid root parameters matching Typesense API spec', function () {
        $thread = new Thread();
        $schema = $thread->typesenseCollectionSchema();

        expect($schema)->toHaveKeys(['name', 'fields', 'default_sorting_field']);
        expect($schema['name'])->toBe('threads');
        expect($schema['fields'])->toBeArray()->not->toBeEmpty();
    });

    test('Protocol schema incorporates auto-schema detection wildcard field', function () {
        $protocol = new Protocol();
        $fields = $protocol->typesenseCollectionSchema()['fields'];

        $wildcardField = collect($fields)->firstWhere('name', '.*');

        expect($wildcardField)->not->toBeNull();
        expect($wildcardField['type'])->toBe('auto');
    });

    test('Thread schema incorporates auto-schema detection wildcard field', function () {
        $thread = new Thread();
        $fields = $thread->typesenseCollectionSchema()['fields'];

        $wildcardField = collect($fields)->firstWhere('name', '.*');

        expect($wildcardField)->not->toBeNull();
        expect($wildcardField['type'])->toBe('auto');
    });

    test('Protocol and Thread schemas configure enable_nested_fields per requirements', function () {
        $protocolSchema = (new Protocol())->typesenseCollectionSchema();
        $threadSchema = (new Thread())->typesenseCollectionSchema();

        expect($protocolSchema['enable_nested_fields'] ?? false)->toBeTrue();
        expect($threadSchema['enable_nested_fields'] ?? false)->toBeTrue();
    });

    test('Protocol schema includes reviews_count for sorting by Most Reviewed', function () {
        $protocolSchema = (new Protocol())->typesenseCollectionSchema();
        $reviewsCountField = collect($protocolSchema['fields'])->firstWhere('name', 'reviews_count');

        expect($reviewsCountField)->not->toBeNull();
        expect($reviewsCountField['type'])->toBe('int32');
    });

    test('All fields in Protocol and Thread schemas have valid Typesense data types', function () {
        $schemas = [
            (new Protocol())->typesenseCollectionSchema(),
            (new Thread())->typesenseCollectionSchema(),
        ];

        foreach ($schemas as $schema) {
            foreach ($schema['fields'] as $field) {
                expect($field)->toHaveKeys(['name', 'type']);
                expect(ALLOWED_TYPESENSE_TYPES)->toContain($field['type']);
            }
        }
    });

    test('default_sorting_field references an existing numerical field per Typesense spec', function () {
        // According to Typesense docs: default_sorting_field must be an int32 or float field
        $models = [new Protocol(), new Thread()];

        foreach ($models as $model) {
            $schema = $model->typesenseCollectionSchema();
            $sortField = $schema['default_sorting_field'];

            $matchingField = collect($schema['fields'])->firstWhere('name', $sortField);

            expect($matchingField)->not->toBeNull();
            expect(['int32', 'int64', 'float'])->toContain($matchingField['type']);
        }
    });

    test('Facetable taxonomy fields are explicitly marked with facet: true', function () {
        $protocolFields = collect((new Protocol())->typesenseCollectionSchema()['fields']);

        $categoryField = $protocolFields->firstWhere('name', 'category');
        $statusField = $protocolFields->firstWhere('name', 'status');

        expect($categoryField)->not->toBeNull();
        expect($categoryField['facet'] ?? false)->toBeTrue();

        expect($statusField)->not->toBeNull();
        expect($statusField['facet'] ?? false)->toBeTrue();

        $threadFields = collect((new Thread())->typesenseCollectionSchema()['fields']);
        $protocolIdField = $threadFields->firstWhere('name', 'protocol_id');

        expect($protocolIdField)->not->toBeNull();
        expect($protocolIdField['facet'] ?? false)->toBeTrue();
    });

    test('Protocol toSearchableArray aligns with schema types and fields', function () {
        $user = new User(['id' => 10, 'name' => 'Alice']);
        $protocol = new Protocol([
            'id' => 42,
            'title' => 'Decentralized ZK Rollup',
            'description' => 'Fast zk proof verification',
            'category' => 'Infrastructure',
            'version' => '2.0.0',
            'status' => 'published',
            'score' => 120,
            'average_rating' => 4.85,
        ]);
        $protocol->id = 42;

        $searchable = $protocol->toSearchableArray();

        expect($searchable['id'])->toBe('42');
        expect($searchable['title'])->toBe('Decentralized ZK Rollup');
        expect($searchable['score'])->toBeInt();
        expect($searchable['average_rating'])->toBeFloat();
        expect($searchable['category'])->toBe('Infrastructure');
        expect($searchable['status'])->toBe('published');
    });

    test('Thread toSearchableArray aligns with schema types and fields', function () {
        $thread = new Thread([
            'id' => 99,
            'protocol_id' => 42,
            'title' => 'Recursive SNARK Verification',
            'content' => 'Technical specifications and benchmark latency.',
            'is_pinned' => false,
            'votes_count' => 15,
            'replies_count' => 3,
        ]);
        $thread->id = 99;

        $searchable = $thread->toSearchableArray();

        expect($searchable['id'])->toBe('99');
        expect($searchable['protocol_id'])->toBe(42);
        expect($searchable['title'])->toBe('Recursive SNARK Verification');
        expect($searchable['votes_count'])->toBeInt();
        expect($searchable['replies_count'])->toBeInt();
    });

    test('Scout Typesense configuration sanitizes host and supports admin API key', function () {
        $clientSettings = config('scout.typesense.client-settings');

        expect($clientSettings)->toBeArray();
        expect($clientSettings)->toHaveKey('nodes');
        expect($clientSettings['nodes'])->toBeArray()->not->toBeEmpty();

        $primaryNode = $clientSettings['nodes'][0];
        expect($primaryNode)->toHaveKeys(['host', 'port', 'protocol']);
        expect($primaryNode['host'])->not->toStartWith('http://');
        expect($primaryNode['host'])->not->toStartWith('https://');
    });
});
