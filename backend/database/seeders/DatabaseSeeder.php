<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\Protocol;
use App\Models\Review;
use App\Models\Thread;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with demo protocols, discussions, and reviews.
     */
    public function run(): void
    {
        // 1. Create Default Users
        $admin = User::firstOrCreate(
            ['email' => 'admin@protocol.io'],
            ['name' => 'Protocol Admin', 'password' => Hash::make('password')]
        );

        $vitalik = User::firstOrCreate(
            ['email' => 'vitalik@protocol.io'],
            ['name' => 'Vitalik B.', 'password' => Hash::make('password')]
        );

        $alice = User::firstOrCreate(
            ['email' => 'alice@protocol.io'],
            ['name' => 'Alice Cryptographer', 'password' => Hash::make('password')]
        );

        $bob = User::firstOrCreate(
            ['email' => 'bob@protocol.io'],
            ['name' => 'Bob Auditor', 'password' => Hash::make('password')]
        );

        $charlie = User::firstOrCreate(
            ['email' => 'charlie@protocol.io'],
            ['name' => 'Charlie DeFi', 'password' => Hash::make('password')]
        );

        $communityUsers = [$vitalik, $alice, $bob, $charlie];

        // 2. Protocols Dataset
        $protocolsData = [
            [
                'title' => 'Decentralized ZK-Rollup Sequencing Protocol',
                'category' => 'Layer2',
                'version' => '1.2.0',
                'description' => 'A decentralized, threshold-signature-based sequencer network eliminating single points of failure in optimistic and validity rollups.',
            ],
            [
                'title' => 'Cross-Chain Optimistic State Attestation',
                'category' => 'Interoperability',
                'version' => '2.0.1',
                'description' => 'Fault-tolerant cross-chain state verification leveraging optimistic challenge windows and economic bonding.',
            ],
            [
                'title' => 'Fair-Ordering MEV Shielding Protocol',
                'category' => 'DeFi',
                'version' => '1.0.0',
                'description' => 'Threshold encryption pipeline preventing front-running and sandwich attacks in mempools before transaction inclusion.',
            ],
            [
                'title' => 'Decentralized Proof of Solvency Standard',
                'category' => 'Security',
                'version' => '1.1.0',
                'description' => 'Cryptographic zero-knowledge proof framework allowing centralized custodians and DeFi vaults to verify liabilities without disclosing user privacy.',
            ],
            [
                'title' => 'Liquid Staking Derivative Slashing Mitigation',
                'category' => 'Staking',
                'version' => '2.1.0',
                'description' => 'Dynamic risk-tranche insurance pools defending stakers from validator correlation slashing events.',
            ],
            [
                'title' => 'Futarchy-Driven Decentralized Governance',
                'category' => 'Governance',
                'version' => '0.9.0',
                'description' => 'Prediction-market guided treasury allocation protocol where token holders bet on proposal performance metrics.',
            ],
            [
                'title' => 'Zero-Knowledge Decentralized Identity (zkDID)',
                'category' => 'Identity',
                'version' => '1.0.0',
                'description' => 'Selective disclosure of KYC attributes and verifiable credentials without exposing underlying PII.',
            ],
            [
                'title' => 'High-Frequency Decentralized Limit Order Book',
                'category' => 'DeFi',
                'version' => '3.0.0',
                'description' => 'Off-chain matching engine with verifiable zero-knowledge state diff settlement on L2.',
            ],
            [
                'title' => 'Decentralized Time-Weighted Oracle Feed',
                'category' => 'Oracles',
                'version' => '1.4.0',
                'description' => 'Cryptographic TWAP and outlier-resistant volatility estimator resilient to flash loan manipulation.',
            ],
            [
                'title' => 'Post-Quantum Signature Consensus Extension',
                'category' => 'Cryptography',
                'version' => '0.8.0',
                'description' => 'Falcon and Dilithium lattice-based signature aggregation for long-term quantum resistance.',
            ],
            [
                'title' => 'Automated Liquidity Rebalancing Protocol',
                'category' => 'DeFi',
                'version' => '1.5.0',
                'description' => 'Dynamic concentrated liquidity manager maximizing fee yields while hedging impermanent loss.',
            ],
            [
                'title' => 'Account Abstraction Intent Resolver Network',
                'category' => 'Infrastructure',
                'version' => '2.0.0',
                'description' => 'ERC-4337 and ERC-7579 modular intent solving framework enabling gasless multi-chain execution.',
            ],
        ];

        foreach ($protocolsData as $index => $data) {
            $author = $communityUsers[$index % count($communityUsers)];
            $slug = Str::slug($data['title']);

            $protocol = Protocol::firstOrCreate(
                ['slug' => $slug],
                [
                    'user_id' => $author->id,
                    'title' => $data['title'],
                    'description' => $data['description'],
                    'category' => $data['category'],
                    'version' => $data['version'],
                    'status' => 'published',
                    'votes_count' => 0,
                    'score' => 0,
                    'reviews_count' => 0,
                    'average_rating' => 0.00,
                    'metadata' => [
                        'audited' => true,
                        'tags' => [strtolower($data['category']), 'web3', 'protocol'],
                    ],
                ]
            );

            // 3. Discussion Threads
            $thread = Thread::firstOrCreate(
                ['slug' => "specification-review-{$protocol->id}"],
                [
                    'protocol_id' => $protocol->id,
                    'user_id' => $admin->id,
                    'title' => "Formal Specification Discussion for {$protocol->title}",
                    'content' => 'Please review the edge cases outlined in section 3.2 regarding fallback behaviors under network partitions.',
                    'is_pinned' => true,
                    'views_count' => 142,
                    'replies_count' => 0,
                ]
            );

            // 4. Nested Comments
            $comment1 = Comment::create([
                'thread_id' => $thread->id,
                'user_id' => $vitalik->id,
                'content' => 'The liveness assumptions look sound under honest-majority Byzantine fault models.',
            ]);

            Comment::create([
                'thread_id' => $thread->id,
                'user_id' => $alice->id,
                'parent_id' => $comment1->id,
                'content' => 'Agreed, and we can further optimize throughput by batching signatures before broadcast.',
            ]);

            $thread->update(['replies_count' => 2]);

            // 5. Peer Reviews
            $reviewers = array_filter($communityUsers, fn ($u) => $u->id !== $author->id);
            foreach ($reviewers as $reviewer) {
                Review::firstOrCreate(
                    [
                        'protocol_id' => $protocol->id,
                        'user_id' => $reviewer->id,
                    ],
                    [
                        'rating' => 5,
                        'verdict' => 'approved',
                        'summary' => 'Comprehensive specification with robust security guarantees.',
                        'findings' => 'All invariants hold under automated model checking.',
                    ]
                );
            }

            // 6. Polymorphic Votes
            foreach ($communityUsers as $voter) {
                Vote::firstOrCreate(
                    [
                        'user_id' => $voter->id,
                        'votable_type' => Protocol::class,
                        'votable_id' => $protocol->id,
                    ],
                    ['value' => 1]
                );
            }

            // Recalculate Protocol aggregates
            $reviewsCount = $protocol->reviews()->count();
            $avgRating = round((float) $protocol->reviews()->avg('rating'), 2);
            $votesCount = (int) $protocol->votes()->sum('value');
            $score = ($votesCount * 10) + ($reviewsCount * 5);

            $protocol->update([
                'reviews_count' => $reviewsCount,
                'average_rating' => $avgRating,
                'votes_count' => $votesCount,
                'score' => $score,
            ]);
        }
    }
}
