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
     * Seed the application's database with structured healing, wellness, and instructional protocols.
     */
    public function run(): void
    {
        // 1. Create Default Users (Wellness & Clinical Community Leaders)
        $admin = User::firstOrCreate(
            ['email' => 'admin@protocol.io'],
            ['name' => 'Protocol Admin', 'password' => Hash::make('password')]
        );

        $andrew = User::firstOrCreate(
            ['email' => 'andrew.h@wellness.io'],
            ['name' => 'Dr. Andrew H.', 'password' => Hash::make('password')]
        );

        $rhonda = User::firstOrCreate(
            ['email' => 'rhonda.p@wellness.io'],
            ['name' => 'Dr. Rhonda P.', 'password' => Hash::make('password')]
        );

        $elena = User::firstOrCreate(
            ['email' => 'elena.r@wellness.io'],
            ['name' => 'Elena Rostova, PT', 'password' => Hash::make('password')]
        );

        $marcus = User::firstOrCreate(
            ['email' => 'marcus.v@wellness.io'],
            ['name' => 'Coach Marcus Vance', 'password' => Hash::make('password')]
        );

        $maya = User::firstOrCreate(
            ['email' => 'maya.lin@wellness.io'],
            ['name' => 'Dr. Maya Lin, MD', 'password' => Hash::make('password')]
        );

        $communityUsers = [$andrew, $rhonda, $elena, $marcus, $maya];

        // 2. Protocols Dataset (12 Structured Healing, Wellness & Instructional Protocols)
        $protocolsData = [
            [
                'title' => 'Circadian Rhythm Alignment & Deep Sleep Architecture',
                'category' => 'Sleep',
                'version' => '2.1.0',
                'description' => 'Morning sunlight exposure, ambient temperature manipulation, and evening blue light mitigation protocols designed to optimize stage 3/4 slow-wave sleep and REM cycles.',
                'tags' => ['sleep', 'circadian', 'recovery', 'wellness'],
                'thread_title' => 'Timing Morning Lux Exposure in Winter Climates',
                'thread_content' => 'What is the optimal duration and lux threshold when natural sunrise occurs later than wake time? Has anyone tested 10,000 lux SAD lamps as a reliable substitute?',
                'comment_1' => 'I use a 10,000 lux daylight lamp placed at 45 degrees 18 inches away for 20 minutes with great results on cortisol awakening response.',
                'comment_2' => 'Confirmed by my Oura ring data—sleep latency dropped from 24 minutes to under 8 minutes within two weeks of consistent morning lux exposure.',
            ],
            [
                'title' => 'Low-FODMAP Gut Microbiome Restoration Protocol',
                'category' => 'Gut Health',
                'version' => '1.4.0',
                'description' => 'A systematic 3-phase elimination, challenge, and personalization dietary framework to repair intestinal mucosal barrier permeability and alleviate functional gastrointestinal distress.',
                'tags' => ['gut-health', 'nutrition', 'microbiome', 'healing'],
                'thread_title' => 'Phase 2 Reintroduction Schedule and Symptom Tracking',
                'thread_content' => 'When challenging fructans and oligosaccharides, how many washout days do you observe between successive food trials to prevent overlapping symptom flares?',
                'comment_1' => 'We recommend a minimum of 3 full washout days on strict baseline foods before testing the next FODMAP group.',
                'comment_2' => 'Adding a daily symptom severity questionnaire alongside a food diary made identifying garlic as my primary trigger unmistakable.',
            ],
            [
                'title' => 'Cold-Water Immersion & Vagus Nerve Stimulation Protocol',
                'category' => 'Biohacking',
                'version' => '1.2.0',
                'description' => 'Structured deliberate cold exposure (cumulative 11 minutes per week at 50-55°F) to upregulate plasma norepinephrine, activate brown adipose tissue, and elevate vagal tone.',
                'tags' => ['biohacking', 'cold-plunge', 'nervous-system', 'recovery'],
                'thread_title' => 'Pre-Training vs Post-Training Cold Exposure Sequencing',
                'thread_content' => 'Should cold plunge immersion be avoided within 4 hours post-hypertrophy resistance training due to blunting of anabolic PGC-1alpha and mTOR signaling pathways?',
                'comment_1' => 'Yes, the literature demonstrates attenuation of hypertrophy adaptations if cold water immersion occurs immediately post-lift.',
                'comment_2' => 'Best practice is to plunge either on rest days or first thing in the morning prior to afternoon resistance sessions.',
            ],
            [
                'title' => 'Rotator Cuff & Scapular Stability Rehabilitation Protocol',
                'category' => 'Physical Therapy',
                'version' => '3.0.0',
                'description' => 'Progressive isometric, concentric, and eccentric tendon loading rehabilitation progression targeting supraspinatus tendinopathy, subacromial impingement, and scapulothoracic dyskinesis.',
                'tags' => ['rehab', 'physical-therapy', 'mobility', 'instructional'],
                'thread_title' => 'Progression Criteria from Isometrics to Dynamic Cable External Rotation',
                'thread_content' => 'At what pain threshold (NPRS 0-10) is it safe to transition from side-lying isometric holds to dynamic side-lying external rotations with resistance tubing?',
                'comment_1' => 'As long as pain remains below 3/10 during exercise and returns to baseline within 24 hours without nocturnal flare-ups, progression is clinically indicated.',
                'comment_2' => 'Adding serratus anterior wall slides in conjunction with the cuff work resolved my persistent subacromial clicking in under 3 weeks.',
            ],
            [
                'title' => 'Zone 2 Aerobic Base & Mitochondrial Biogenesis Protocol',
                'category' => 'Cardiovascular',
                'version' => '2.0.0',
                'description' => 'Low-intensity steady-state cardiovascular training (150-180 minutes weekly at blood lactate 1.5-2.0 mmol/L) to expand mitochondrial volume density and fatty acid oxidation efficiency.',
                'tags' => ['cardio', 'longevity', 'mitochondria', 'fitness'],
                'thread_title' => 'Lactate Meter Calibration vs Heart Rate Reserve Calculation',
                'thread_content' => 'How accurately does the Phil Maffetone 180-formula approximate actual blood lactate threshold measurements for trained athletes?',
                'comment_1' => 'In laboratory testing, the 180-formula often underestimates true Zone 2 ceiling for experienced aerobic athletes by 8-12 bpm.',
                'comment_2' => 'The conversational nasal breathing test provides an exceptionally reliable zero-cost proxy for the lactate turn-point.',
            ],
            [
                'title' => 'Cyclic Physiological Sigh & Autonomic Down-Regulation',
                'category' => 'Breathwork',
                'version' => '1.0.0',
                'description' => 'A structured five-minute acute stress recovery protocol consisting of two rapid consecutive nasal inhalations followed by an elongated, unforced oral exhalation.',
                'tags' => ['breathwork', 'stress-relief', 'mental-health', 'wellness'],
                'thread_title' => 'Acute Anxiety Reduction: 3 Sighs vs 5-Minute Continuous Cycles',
                'thread_content' => 'Does executing 3 rapid physiological sighs during acute workplace stressors produce comparable autonomic calming to a dedicated 5-minute sitting session?',
                'comment_1' => 'Three sighs immediately activate the cardiac vagal brake, reducing heart rate within 30 seconds.',
                'comment_2' => 'We incorporate 5 minutes daily before evening meditation, resulting in significantly higher overnight HRV scores.',
            ],
            [
                'title' => '16:8 Time-Restricted Feeding & Autophagy Protocol',
                'category' => 'Nutrition',
                'version' => '1.5.0',
                'description' => 'An early-circadian aligned intermittent fasting protocol with an 8-hour feeding window terminating 3 hours before sleep to enhance cellular autophagy and postprandial glycemic clearance.',
                'tags' => ['fasting', 'autophagy', 'nutrition', 'metabolism'],
                'thread_title' => 'Electrolyte Supplementation Protocols During the 16-Hour Fasting Window',
                'thread_content' => 'What sodium, potassium, and magnesium ratios prevent morning brain fog and orthostatic hypotension without breaking the fasted metabolic state?',
                'comment_1' => '500mg sodium with 200mg potassium in 500ml water taken upon waking completely resolves electrolyte depletion without stimulating insulin.',
                'comment_2' => 'Be sure to avoid zero-calorie artificial sweeteners as cephalic phase insulin release can disrupt liver glycogen depletion.',
            ],
            [
                'title' => 'Ergonomic Desk Posture & Cervical Spine Decompression',
                'category' => 'Instructional',
                'version' => '1.1.0',
                'description' => 'Evidence-based workstation ergonomics configuration combined with micro-break cervical retraction exercises to counteract forward head posture and upper crossed syndrome.',
                'tags' => ['posture', 'ergonomics', 'workplace', 'instructional'],
                'thread_title' => 'Standing Desk Duration Intervals and Anti-Fatigue Mats',
                'thread_content' => 'What is the recommended ratio between sitting and standing throughout an 8-hour computer workday to minimize lumbar shear stress?',
                'comment_1' => 'The ergonomic consensus suggests a 20:8:2 ratio: 20 minutes sitting, 8 minutes standing, and 2 minutes walking/stretching every 30 minutes.',
                'comment_2' => 'Adjusting monitor height so that the top third of the screen is at direct eye level immediately eliminated my cervical spine tension.',
            ],
            [
                'title' => 'Magnesium Glycinate & L-Theanine Sleep Stack Protocol',
                'category' => 'Supplements',
                'version' => '2.2.0',
                'description' => 'A synergistic evening neuro-nutrient stack (200-400mg Magnesium Bisglycinate, 200mg L-Theanine, 50mg Apigenin) taken 45 minutes before bed to activate GABAergic inhibitory neurotransmission.',
                'tags' => ['supplements', 'sleep', 'nootropics', 'recovery'],
                'thread_title' => 'Bioavailability: Glycinate vs L-Threonate for Central Nervous System Penetration',
                'thread_content' => 'For users struggling primarily with racing thoughts at bedtime, does Magnesium L-Threonate cross the blood-brain barrier more effectively than Bisglycinate?',
                'comment_1' => 'L-Threonate exhibits superior CNS cerebrospinal fluid penetration, making it ideal for rumination and nocturnal anxiety.',
                'comment_2' => 'I combine 144mg elemental Magnesium Threonate with 200mg L-Theanine with remarkable sleep onset speed.',
            ],
            [
                'title' => 'Contrast Hydrotherapy for Lymphatic Drainage & Muscle Recovery',
                'category' => 'Recovery',
                'version' => '1.3.0',
                'description' => 'Alternating cycles of thermal vasodilation (Finnish sauna at 175-195°F for 15 minutes) and cold vasoconstriction (cold plunge at 50°F for 2 minutes) to stimulate venous return and lymphatic pump.',
                'tags' => ['hydrotherapy', 'sauna', 'recovery', 'wellness'],
                'thread_title' => 'Optimal Cycle Counts and Final Cold vs Hot Finish',
                'thread_content' => 'Should contrast hydrotherapy conclude with cold water immersion for anti-inflammatory benefit, or with heat to promote parasympathetic vasodilation before sleep?',
                'comment_1' => 'Ending on cold enhances daytime alertness and energy expenditure; ending on heat promotes muscle relaxation and nocturnal core cooling.',
                'comment_2' => 'Three rounds of 15 min sauna followed by 2 min cold plunge has reduced my delayed onset muscle soreness by half.',
            ],
            [
                'title' => 'Low-Histamine Dietary Protocol for Mast Cell Stabilization',
                'category' => 'Immunology',
                'version' => '1.0.0',
                'description' => 'A targeted elimination diet minimizing aged, fermented, and processed dietary histamines while supplementing natural mast cell stabilizers (Quercetin, Vitamin C) to relieve allergic sensitivities.',
                'tags' => ['histamine', 'immunology', 'diet', 'healing'],
                'thread_title' => 'Safe Protein Freezing Protocols to Halt Histamine Accumulation',
                'thread_content' => 'How critical is flash-freezing fresh pasture-raised meat immediately post-purchase to prevent bacterial histidine-to-histamine conversion?',
                'comment_1' => 'It is the single most important cooking habit for mast cell patients. Never consume refrigerated leftovers past 24 hours.',
                'comment_2' => 'Combining fresh-frozen meal preparation with 500mg Quercetin 20 minutes prior to meals eliminated my postprandial flushing.',
            ],
            [
                'title' => 'VO2 Max Norwegian 4x4 Aerobic Power Protocol',
                'category' => 'Fitness',
                'version' => '3.1.0',
                'description' => 'Four 4-minute maximal aerobic intervals at 90-95% peak heart rate interspersed with 3-minute active recovery periods at 70% HR max to rapidly expand left ventricular stroke volume.',
                'tags' => ['vo2max', 'hiit', 'endurance', 'fitness'],
                'thread_title' => 'Modalities: Airdyne Bike vs Incline Treadmill vs Rowing Ergometer',
                'thread_content' => 'Which exercise modality allows reaching and maintaining 90-95% HR max with the lowest perceived orthopedic joint stress during the final interval?',
                'comment_1' => 'The motorized incline treadmill (10-12% grade at moderate speed) generates the highest sustained heart rate with virtually zero eccentric knee pounding.',
                'comment_2' => 'The assault air bike engages upper and lower extremities simultaneously, making 90% HR max reachable within 45 seconds of each 4-minute block.',
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
                        'tags' => $data['tags'],
                    ],
                ]
            );

            // 3. Discussion Threads (Specific Community Topics)
            $thread = Thread::firstOrCreate(
                ['slug' => "community-discussion-{$protocol->id}"],
                [
                    'protocol_id' => $protocol->id,
                    'user_id' => $communityUsers[($index + 1) % count($communityUsers)]->id,
                    'title' => $data['thread_title'],
                    'content' => $data['thread_content'],
                    'is_pinned' => true,
                    'views_count' => 140 + ($index * 15),
                    'replies_count' => 0,
                ]
            );

            // 4. Nested Comments (Evidence-Based Community Dialogue)
            $comment1 = Comment::create([
                'thread_id' => $thread->id,
                'user_id' => $communityUsers[($index + 2) % count($communityUsers)]->id,
                'content' => $data['comment_1'],
            ]);

            Comment::create([
                'thread_id' => $thread->id,
                'user_id' => $communityUsers[($index + 3) % count($communityUsers)]->id,
                'parent_id' => $comment1->id,
                'content' => $data['comment_2'],
            ]);

            $thread->update(['replies_count' => 2]);

            // 5. Peer Reviews (Ratings + Optional Feedback)
            $reviewers = array_filter($communityUsers, fn ($u) => $u->id !== $author->id);
            $reviewTemplates = [
                ['rating' => 5, 'summary' => 'Scientifically rigorous protocol with exceptional clinical clarity.', 'findings' => 'Clear adherence guidelines and measurable biomarkers of efficacy.'],
                ['rating' => 5, 'summary' => 'Outstanding practical implementation steps for patients and athletes.', 'findings' => 'Step-by-step progressions reduce injury risk and foster high compliance.'],
                ['rating' => 4, 'summary' => 'High clinical utility; minor adjustments suggested for beginners.', 'findings' => 'Could benefit from an expanded warmup/onboarding phase for sedentary individuals.'],
                ['rating' => 4, 'summary' => 'Great evidence synthesis and actionable behavioral interventions.', 'findings' => 'Biomarker tracking parameters are realistic and well documented.'],
            ];

            foreach ($reviewers as $rIdx => $reviewer) {
                $template = $reviewTemplates[$rIdx % count($reviewTemplates)];
                Review::firstOrCreate(
                    [
                        'protocol_id' => $protocol->id,
                        'user_id' => $reviewer->id,
                    ],
                    [
                        'rating' => $template['rating'],
                        'verdict' => 'approved',
                        'summary' => $template['summary'],
                        'findings' => $template['findings'],
                    ]
                );
            }

            // 6. Polymorphic Votes
            foreach ($communityUsers as $vIdx => $voter) {
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
