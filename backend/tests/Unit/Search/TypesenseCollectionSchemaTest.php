<?php

namespace Tests\Unit\Search;

use App\Models\Protocol;
use App\Models\Thread;
use App\Models\User;
use Tests\TestCase;

class TypesenseCollectionSchemaTest extends TestCase
{
    private const ALLOWED_TYPESENSE_TYPES = [
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

    public function test_protocol_schema_defines_valid_root_parameters_matching_typesense_api_spec(): void
    {
        $protocol = new Protocol();
        $schema = $protocol->typesenseCollectionSchema();

        $this->assertArrayHasKey('name', $schema);
        $this->assertArrayHasKey('fields', $schema);
        $this->assertArrayHasKey('default_sorting_field', $schema);
        $this->assertSame('protocol', $schema['name']);
        $this->assertIsArray($schema['fields']);
        $this->assertNotEmpty($schema['fields']);
    }

    public function test_thread_schema_defines_valid_root_parameters_matching_typesense_api_spec(): void
    {
        $thread = new Thread();
        $schema = $thread->typesenseCollectionSchema();

        $this->assertArrayHasKey('name', $schema);
        $this->assertArrayHasKey('fields', $schema);
        $this->assertArrayHasKey('default_sorting_field', $schema);
        $this->assertSame('threads', $schema['name']);
        $this->assertIsArray($schema['fields']);
        $this->assertNotEmpty($schema['fields']);
    }

    public function test_protocol_schema_incorporates_auto_schema_detection_wildcard_field(): void
    {
        $protocol = new Protocol();
        $fields = $protocol->typesenseCollectionSchema()['fields'];

        $wildcardField = collect($fields)->firstWhere('name', '.*');

        $this->assertNotNull($wildcardField);
        $this->assertSame('auto', $wildcardField['type']);
    }

    public function test_thread_schema_incorporates_auto_schema_detection_wildcard_field(): void
    {
        $thread = new Thread();
        $fields = $thread->typesenseCollectionSchema()['fields'];

        $wildcardField = collect($fields)->firstWhere('name', '.*');

        $this->assertNotNull($wildcardField);
        $this->assertSame('auto', $wildcardField['type']);
    }

    public function test_protocol_and_thread_schemas_configure_enable_nested_fields_per_requirements(): void
    {
        $protocolSchema = (new Protocol())->typesenseCollectionSchema();
        $threadSchema = (new Thread())->typesenseCollectionSchema();

        $this->assertTrue($protocolSchema['enable_nested_fields'] ?? false);
        $this->assertTrue($threadSchema['enable_nested_fields'] ?? false);
    }

    public function test_protocol_schema_includes_reviews_count_for_sorting_by_most_reviewed(): void
    {
        $protocolSchema = (new Protocol())->typesenseCollectionSchema();
        $reviewsCountField = collect($protocolSchema['fields'])->firstWhere('name', 'reviews_count');

        $this->assertNotNull($reviewsCountField);
        $this->assertSame('int32', $reviewsCountField['type']);
    }

    public function test_all_fields_in_protocol_and_thread_schemas_have_valid_typesense_data_types(): void
    {
        $schemas = [
            (new Protocol())->typesenseCollectionSchema(),
            (new Thread())->typesenseCollectionSchema(),
        ];

        foreach ($schemas as $schema) {
            foreach ($schema['fields'] as $field) {
                $this->assertArrayHasKey('name', $field);
                $this->assertArrayHasKey('type', $field);
                $this->assertContains($field['type'], self::ALLOWED_TYPESENSE_TYPES);
            }
        }
    }

    public function test_default_sorting_field_references_an_existing_numerical_field_per_typesense_spec(): void
    {
        $models = [new Protocol(), new Thread()];

        foreach ($models as $model) {
            $schema = $model->typesenseCollectionSchema();
            $sortField = $schema['default_sorting_field'];

            $matchingField = collect($schema['fields'])->firstWhere('name', $sortField);

            $this->assertNotNull($matchingField);
            $this->assertContains($matchingField['type'], ['int32', 'int64', 'float']);
        }
    }

    public function test_facetable_taxonomy_fields_are_explicitly_marked_with_facet_true(): void
    {
        $protocolFields = collect((new Protocol())->typesenseCollectionSchema()['fields']);

        $categoryField = $protocolFields->firstWhere('name', 'category');
        $statusField = $protocolFields->firstWhere('name', 'status');

        $this->assertNotNull($categoryField);
        $this->assertTrue($categoryField['facet'] ?? false);

        $this->assertNotNull($statusField);
        $this->assertTrue($statusField['facet'] ?? false);

        $threadFields = collect((new Thread())->typesenseCollectionSchema()['fields']);
        $protocolIdField = $threadFields->firstWhere('name', 'protocol_id');

        $this->assertNotNull($protocolIdField);
        $this->assertTrue($protocolIdField['facet'] ?? false);
    }

    public function test_protocol_to_searchable_array_aligns_with_schema_types_and_fields(): void
    {
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

        $this->assertSame('42', $searchable['id']);
        $this->assertSame('Decentralized ZK Rollup', $searchable['title']);
        $this->assertIsInt($searchable['score']);
        $this->assertIsFloat($searchable['average_rating']);
        $this->assertSame('Infrastructure', $searchable['category']);
        $this->assertSame('published', $searchable['status']);
    }

    public function test_thread_to_searchable_array_aligns_with_schema_types_and_fields(): void
    {
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

        $this->assertSame('99', $searchable['id']);
        $this->assertSame('42', $searchable['protocol_id']);
        $this->assertSame('Recursive SNARK Verification', $searchable['title']);
        $this->assertIsInt($searchable['votes_count']);
        $this->assertIsInt($searchable['replies_count']);
    }

    public function test_scout_typesense_configuration_sanitizes_host_and_supports_admin_api_key(): void
    {
        $clientSettings = config('scout.typesense.client-settings');

        $this->assertIsArray($clientSettings);
        $this->assertArrayHasKey('nodes', $clientSettings);
        $this->assertIsArray($clientSettings['nodes']);
        $this->assertNotEmpty($clientSettings['nodes']);

        $primaryNode = $clientSettings['nodes'][0];
        $this->assertArrayHasKey('host', $primaryNode);
        $this->assertArrayHasKey('port', $primaryNode);
        $this->assertArrayHasKey('protocol', $primaryNode);
        $this->assertStringStartsNotWith('http://', $primaryNode['host']);
        $this->assertStringStartsNotWith('https://', $primaryNode['host']);
    }
}
