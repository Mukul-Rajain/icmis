<?php

namespace Database\Seeders;

use App\Models\Court;
use App\Models\CourtCase;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Skip entirely if already seeded (idempotent for repeated container restarts)
        if (User::where('email', 'judge1@icmis.test')->exists()) {
            $this->command->info('Demo data already seeded — skipping.');
            return;
        }

        // ─── 1. Court ─────────────────────────────────────────
        $court = Court::firstOrCreate(
            ['code' => 'DLH-DC-01'],
            [
                'name'      => 'District Court, Delhi',
                'city'      => 'New Delhi',
                'state'     => 'Delhi',
                'type'      => 'district',
                'is_active' => true,
            ]
        );
        $courtId = (string) $court->_id;

        // ─── 2. Judges ────────────────────────────────────────
        $judges = [];
        foreach ([
            ['Hon. Justice A. K. Sharma', 'judge1@icmis.test', 'Court Room 1', 'J-001'],
            ['Hon. Justice R. Mehta',      'judge2@icmis.test', 'Court Room 2', 'J-002'],
            ['Hon. Justice S. Iyer',       'judge3@icmis.test', 'Court Room 3', 'J-003'],
        ] as [$name, $email, $designation, $bench]) {
            $judges[] = User::firstOrCreate(['email' => $email], [
                'name'      => $name,
                'password'  => 'password',
                'role'      => User::ROLE_JUDGE,
                'is_active' => true,
                'judge_profile' => [
                    'court_id'     => $courtId,
                    'designation'  => $designation,
                    'bench_number' => $bench,
                ],
            ]);
        }

        // ─── 3. Registry ──────────────────────────────────────
        $registry = User::firstOrCreate(['email' => 'registry@icmis.test'], [
            'name'      => 'Registrar D. Kumar',
            'password'  => 'password',
            'role'      => User::ROLE_REGISTRY,
            'is_active' => true,
            'registry_profile' => ['court_id' => $courtId, 'employee_id' => 'R-0042'],
        ]);

        // ─── 4. Filers ────────────────────────────────────────
        $lawyer = User::firstOrCreate(['email' => 'lawyer@icmis.test'], [
            'name'      => 'Adv. Priya Singh',
            'password'  => 'password',
            'role'      => User::ROLE_FILER,
            'is_active' => true,
            'filer_profile' => [
                'type'               => 'lawyer',
                'bar_council_number' => 'D/12345/2015',
                'years_of_practice'  => 10,
                'phone'              => '+91-9810001234',
            ],
        ]);

        User::firstOrCreate(['email' => 'litigant@icmis.test'], [
            'name'      => 'Rajesh Verma',
            'password'  => 'password',
            'role'      => User::ROLE_FILER,
            'is_active' => true,
            'filer_profile' => ['type' => 'litigant', 'phone' => '+91-9810005678'],
        ]);

        // ─── 5. Sample cases ──────────────────────────────────
        $lawyerId = (string) $lawyer->_id;
        $j1       = (string) $judges[0]->_id;
        $j2       = (string) $judges[1]->_id;
        $j3       = (string) $judges[2]->_id;
        $regId    = (string) $registry->_id;
        $year     = now()->year;

        // [title, type, track, status, judgeId, daysAgo, nextInDays, causeListPos]
        $rows = [
            ['Sharma vs. Mehta — Specific Performance',     'civil',      'regular', CourtCase::STATUS_SCHEDULED,       $j1, 45,  5, 3],
            ['State vs. Raju Kumar — Criminal Assault',     'criminal',   'urgent',  CourtCase::STATUS_SCHEDULED,       $j1, 12,  2, 1],
            ['Gupta Family — Partition Suit',               'family',     'regular', CourtCase::STATUS_ADJOURNED,       $j2, 90, 14, 5],
            ['TechCorp vs. FinServe — Commercial Dispute',  'commercial', 'fast',    CourtCase::STATUS_HEARD,           $j2, 30, null, null],
            ['Malhotra Writ — Unlawful Termination',        'writ',       'fast',    CourtCase::STATUS_SCHEDULED,       $j3, 20,  3, 2],
            ['Singh vs. Singh — Matrimonial',               'family',     'regular', CourtCase::STATUS_UNDER_SCRUTINY, null,  3, null, null],
            ['Defective Filing — Missing Affidavit',        'civil',      'regular', CourtCase::STATUS_DEFECTIVE,      null,  7, null, null],
            ['Draft Case — Not Yet Submitted',              'civil',       null,     CourtCase::STATUS_DRAFT,          null,  1, null, null],
            ['State vs. Accused — Bail Application',        'criminal',   'urgent',  CourtCase::STATUS_SCHEDULED,       $j1,  5,  1, 2],
            ['Consumer Forum Appeal — Defective Goods',     'commercial', 'fast',    CourtCase::STATUS_RESERVED,        $j3, 60, null, null],
        ];

        foreach ($rows as $i => [$title, $type, $track, $status, $judgeId, $daysAgo, $nextInDays, $position]) {
            $seq        = str_pad($i + 1, 5, '0', STR_PAD_LEFT);
            $caseNumber = $status !== CourtCase::STATUS_DRAFT
                ? "DLH-DC-01/{$type}/{$year}/{$seq}" : null;
            $nextHearing = $nextInDays ? now()->addDays($nextInDays) : null;

            CourtCase::create([
                'case_number'                => $caseNumber,
                'title'                      => $title,
                'description'                => "Demo case. Track: {$track}. Status: {$status}.",
                'court_id'                   => $courtId,
                'case_type'                  => $type,
                'track'                      => $track,
                'status'                     => $status,
                'filed_by'                   => $lawyerId,
                'filing_date'                => now()->subDays($daysAgo),
                'assigned_judge_id'          => $judgeId,
                'priority_score'             => match ($track) {
                    'urgent' => rand(75, 95),
                    'fast'   => rand(55, 74),
                    default  => rand(30, 54),
                },
                'next_hearing_date'          => $nextHearing,
                'last_hearing_date'          => $status !== CourtCase::STATUS_DRAFT
                    ? now()->subDays(max(1, $daysAgo - 10)) : null,
                'is_high_priority'           => $track === 'urgent',
                'has_interim_relief_pending' => $i % 3 === 0,
                'hearing_count'              => rand(0, 8),
                'adjournment_count'          => rand(0, 4),
                'cause_list_position'        => $position,
                'expected_disposal_date'     => now()->addMonths(rand(2, 12)),
                'parties' => [
                    [
                        'party_type'       => 'petitioner',
                        'name'             => "Petitioner " . ($i + 1),
                        'user_id'          => null,
                        'address'          => '123 Demo Street, New Delhi - 110001',
                        'is_senior_citizen'=> $i % 4 === 0,
                        'is_in_custody'    => $type === 'criminal' && $i % 2 === 0,
                    ],
                    [
                        'party_type'       => 'respondent',
                        'name'             => "Respondent " . ($i + 1),
                        'user_id'          => null,
                        'address'          => '456 Demo Avenue, New Delhi - 110002',
                        'is_senior_citizen'=> false,
                        'is_in_custody'    => false,
                    ],
                ],
                'lawyers'  => [['user_id' => $lawyerId, 'representing' => 'petitioner', 'is_lead' => true]],
                'documents'=> $status !== CourtCase::STATUS_DRAFT ? [[
                    'doc_id'      => (string) Str::uuid(),
                    'label'       => 'Plaint / Petition',
                    'file_path'   => 'seeded/placeholder.pdf',
                    'mime_type'   => 'application/pdf',
                    'uploaded_by' => $lawyerId,
                    'uploaded_at' => now()->subDays($daysAgo)->toIso8601String(),
                    'is_verified' => true,
                ]] : [],
                'scrutiny' => $status === CourtCase::STATUS_DEFECTIVE ? [
                    'reviewed_by'   => $regId,
                    'reviewed_at'   => now()->subDays(2)->toIso8601String(),
                    'approved_at'   => null,
                    'defect_flags'  => [
                        ['code' => 'MISSING_AFFIDAVIT',  'label' => 'Affidavit of verification missing', 'resolved' => false],
                        ['code' => 'INCOMPLETE_ADDRESS', 'label' => 'Respondent address incomplete',    'resolved' => false],
                    ],
                    'rejection_note'=> 'Please attach the required affidavit and complete respondent address.',
                ] : ['defect_flags' => [], 'reviewed_by' => null, 'approved_at' => null],
                'hearings'            => [],
                'mentioning_requests' => $i === 2 ? [[
                    'request_id'   => (string) Str::uuid(),
                    'requested_by' => $lawyerId,
                    'reason'       => 'Client is a senior citizen facing eviction. Urgent hearing needed.',
                    'urgency'      => 'very_urgent',
                    'status'       => 'pending',
                    'judge_note'   => null,
                    'created_at'   => now()->subHours(2)->toIso8601String(),
                    'resolved_at'  => null,
                ]] : [],
            ]);
        }

        $this->command->info('');
        $this->command->table(['Role', 'Email', 'Password'], [
            ['Judge 1',  'judge1@icmis.test',   'password'],
            ['Judge 2',  'judge2@icmis.test',   'password'],
            ['Judge 3',  'judge3@icmis.test',   'password'],
            ['Registry', 'registry@icmis.test', 'password'],
            ['Lawyer',   'lawyer@icmis.test',   'password'],
            ['Litigant', 'litigant@icmis.test', 'password'],
        ]);
        $this->command->info("\nCreated 10 demo cases across all statuses. Run: php artisan serve");
    }
}
