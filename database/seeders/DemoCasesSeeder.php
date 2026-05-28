<?php

namespace Database\Seeders;

use App\Models\CaseType;
use App\Models\Court;
use App\Models\Judge;
use App\Models\User;
use App\Services\CaseRegistrationService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;

/**
 * Creates ~60 realistic demo cases across all tracks, with varying ages
 * and adjournment counts. This produces a meaningful spread of priority
 * scores so the cause list demo actually shows interesting ordering.
 */
class DemoCasesSeeder extends Seeder
{
    public function run(): void
    {
        $registrar = App::make(CaseRegistrationService::class);

        $court = Court::first();
        $judges = Judge::all();
        $lawyer = User::where('user_type', User::TYPE_LAWYER)->first();
        $caseTypes = CaseType::all()->keyBy('code');

        $samples = $this->getCaseSamples();

        foreach ($samples as $idx => $sample) {
            $caseType = $caseTypes[$sample['type_code']] ?? null;
            if (! $caseType) continue;

            $judge = $judges->random();
            $filingDate = Carbon::today()->subDays($sample['age_days']);

            $case = $registrar->register([
                'title' => $sample['title'],
                'description' => $sample['description'] ?? null,
                'case_type_id' => $caseType->id,
                'court_id' => $court->id,
                'filed_by_user_id' => $lawyer->id,
                'filing_date' => $filingDate,
                'has_interim_relief_pending' => $sample['interim_relief'] ?? false,
                'parties' => [
                    [
                        'party_type' => 'petitioner',
                        'name' => $sample['petitioner'],
                        'is_senior_citizen' => $sample['senior_citizen'] ?? false,
                    ],
                    [
                        'party_type' => 'respondent',
                        'name' => $sample['respondent'],
                        'is_in_custody' => $sample['in_custody'] ?? false,
                    ],
                ],
                'lawyers' => [
                    ['lawyer_id' => $lawyer->id, 'role' => 'lead'],
                ],
            ]);

            // Manually update for spread
            $case->update([
                'assigned_judge_id' => $judge->id,
                'adjournment_count' => $sample['adjournments'] ?? 0,
                'hearing_count' => ($sample['adjournments'] ?? 0) + 1,
                'current_stage' => $sample['stage'] ?? 'registered',
            ]);

            // Rescore now that adjournments/stage are set
            App::make(\App\Services\PriorityScorer::class)->scoreAndPersist($case);
        }

        $this->command->info('Created ' . count($samples) . ' demo cases.');
    }

    private function getCaseSamples(): array
    {
        return [
            // Fast track — recent + urgent
            ['type_code' => 'BAIL', 'title' => 'Bail Application - Ramesh Kumar', 'petitioner' => 'Ramesh Kumar', 'respondent' => 'State of Delhi', 'age_days' => 5, 'in_custody' => true, 'adjournments' => 0, 'stage' => 'registered'],
            ['type_code' => 'BAIL', 'title' => 'Bail Application - Sunita Devi', 'petitioner' => 'Sunita Devi', 'respondent' => 'State of Delhi', 'age_days' => 15, 'in_custody' => true, 'adjournments' => 1, 'stage' => 'notice_issued'],
            ['type_code' => 'HC', 'title' => 'Habeas Corpus - Mohan Lal', 'petitioner' => 'Family of Mohan Lal', 'respondent' => 'State of Delhi', 'age_days' => 10, 'in_custody' => true, 'adjournments' => 0, 'stage' => 'arguments'],
            ['type_code' => 'INJ-U', 'title' => 'Urgent Injunction - Trade Secret Misuse', 'petitioner' => 'TechCorp Ltd', 'respondent' => 'Former Employee', 'age_days' => 8, 'interim_relief' => true, 'adjournments' => 0],
            ['type_code' => 'DV', 'title' => 'DV Act - Protection Order', 'petitioner' => 'Anjali Sharma', 'respondent' => 'Vikram Sharma', 'age_days' => 35, 'adjournments' => 2, 'stage' => 'evidence'],
            ['type_code' => 'MAINT', 'title' => 'Maintenance Petition', 'petitioner' => 'Kavita Singh', 'respondent' => 'Rohit Singh', 'age_days' => 60, 'adjournments' => 3, 'stage' => 'evidence'],
            ['type_code' => 'ABAIL', 'title' => 'Anticipatory Bail - Suresh', 'petitioner' => 'Suresh Pandey', 'respondent' => 'State of Delhi', 'age_days' => 20, 'adjournments' => 1],
            ['type_code' => 'BAIL', 'title' => 'Bail Application - Aged petitioner', 'petitioner' => 'Hari Singh (68)', 'respondent' => 'State', 'age_days' => 25, 'in_custody' => true, 'senior_citizen' => true, 'adjournments' => 2],

            // Standard track — varying ages
            ['type_code' => 'CIV', 'title' => 'Recovery Suit - ABC vs XYZ', 'petitioner' => 'ABC Enterprises', 'respondent' => 'XYZ Traders', 'age_days' => 200, 'adjournments' => 4, 'stage' => 'evidence'],
            ['type_code' => 'CIV', 'title' => 'Money Recovery Suit', 'petitioner' => 'M/s Krishna', 'respondent' => 'Mohan Industries', 'age_days' => 90, 'adjournments' => 1, 'stage' => 'reply_filed'],
            ['type_code' => 'PROP', 'title' => 'Title Dispute - Sector 22', 'petitioner' => 'Ramlal', 'respondent' => 'Mukesh', 'age_days' => 450, 'adjournments' => 7, 'stage' => 'evidence'],
            ['type_code' => 'PROP', 'title' => 'Partition Suit - Family Property', 'petitioner' => 'Sharma Brothers', 'respondent' => 'Sharma Brothers (Others)', 'age_days' => 320, 'adjournments' => 5, 'stage' => 'arguments'],
            ['type_code' => 'THEFT', 'title' => 'IPC 379 - Mobile Theft', 'petitioner' => 'State', 'respondent' => 'Accused 1', 'age_days' => 150, 'adjournments' => 3, 'stage' => 'evidence'],
            ['type_code' => 'CHEAT', 'title' => 'IPC 420 - Investment Fraud', 'petitioner' => 'State', 'respondent' => 'Accused 2', 'age_days' => 220, 'adjournments' => 4, 'stage' => 'arguments'],
            ['type_code' => 'DIV', 'title' => 'Divorce Petition - Mutual Consent', 'petitioner' => 'Priya Mehta', 'respondent' => 'Arjun Mehta', 'age_days' => 180, 'adjournments' => 2, 'stage' => 'evidence'],
            ['type_code' => 'DIV', 'title' => 'Divorce Petition - Contested', 'petitioner' => 'Neha Gupta', 'respondent' => 'Akash Gupta', 'age_days' => 340, 'adjournments' => 6, 'stage' => 'evidence'],
            ['type_code' => 'CONS', 'title' => 'Consumer Complaint - Defective Product', 'petitioner' => 'Suresh Verma', 'respondent' => 'XYZ Electronics', 'age_days' => 120, 'adjournments' => 2, 'stage' => 'reply_filed'],
            ['type_code' => 'MACT', 'title' => 'MACT Claim - Road Accident', 'petitioner' => 'Family of deceased', 'respondent' => 'Insurance Co.', 'age_days' => 250, 'adjournments' => 5, 'stage' => 'evidence', 'senior_citizen' => true],
            ['type_code' => 'NI138', 'title' => 'NI 138 - Cheque Bounce ₹5L', 'petitioner' => 'Anil Trader', 'respondent' => 'Defaulter Co.', 'age_days' => 160, 'adjournments' => 3, 'stage' => 'evidence'],
            ['type_code' => 'NI138', 'title' => 'NI 138 - Cheque Bounce ₹2L', 'petitioner' => 'Vendor Ltd', 'respondent' => 'Buyer Inc.', 'age_days' => 100, 'adjournments' => 1, 'stage' => 'reply_filed'],

            // Aged standard cases — should bubble up due to age
            ['type_code' => 'PROP', 'title' => 'Old Property Suit - 3 years', 'petitioner' => 'Vinod', 'respondent' => 'Suman', 'age_days' => 980, 'adjournments' => 12, 'stage' => 'arguments'],
            ['type_code' => 'CIV', 'title' => 'Pending Recovery - High Adjournments', 'petitioner' => 'Old Trader', 'respondent' => 'Disputed Co.', 'age_days' => 600, 'adjournments' => 10, 'stage' => 'evidence'],

            // Complex track
            ['type_code' => 'WP', 'title' => 'Writ Petition - Service Matter', 'petitioner' => 'Govt Employee', 'respondent' => 'Union of India', 'age_days' => 280, 'adjournments' => 4, 'stage' => 'arguments'],
            ['type_code' => 'PIL', 'title' => 'PIL - Environmental Pollution', 'petitioner' => 'NGO XYZ', 'respondent' => 'State + Industries', 'age_days' => 400, 'adjournments' => 6, 'stage' => 'evidence'],
            ['type_code' => 'COMM', 'title' => 'Commercial Dispute - Contract Breach', 'petitioner' => 'BuildCorp', 'respondent' => 'ContractorCo', 'age_days' => 320, 'adjournments' => 5, 'stage' => 'evidence'],
            ['type_code' => 'ARB', 'title' => 'Arbitration Award Challenge', 'petitioner' => 'M/s Vendor', 'respondent' => 'M/s Buyer', 'age_days' => 260, 'adjournments' => 3, 'stage' => 'arguments'],
            ['type_code' => 'TAX', 'title' => 'GST Appeal - ₹50L assessment', 'petitioner' => 'Taxpayer Ltd', 'respondent' => 'GST Dept', 'age_days' => 380, 'adjournments' => 4, 'stage' => 'arguments'],
            ['type_code' => 'IBC', 'title' => 'Insolvency Petition - Operational Creditor', 'petitioner' => 'Vendor Pvt Ltd', 'respondent' => 'Insolvent Co.', 'age_days' => 200, 'adjournments' => 3],
            ['type_code' => 'MURDER', 'title' => 'IPC 302 Trial', 'petitioner' => 'State', 'respondent' => 'Accused (in custody)', 'age_days' => 540, 'in_custody' => true, 'adjournments' => 8, 'stage' => 'evidence'],
            ['type_code' => 'POCSO', 'title' => 'POCSO Act Case', 'petitioner' => 'State', 'respondent' => 'Accused', 'age_days' => 180, 'in_custody' => true, 'adjournments' => 2, 'stage' => 'evidence'],

            // Newly filed cases (low scores expected)
            ['type_code' => 'CIV', 'title' => 'Fresh Civil Filing', 'petitioner' => 'Plaintiff A', 'respondent' => 'Defendant B', 'age_days' => 3, 'adjournments' => 0],
            ['type_code' => 'CONS', 'title' => 'New Consumer Complaint', 'petitioner' => 'Customer X', 'respondent' => 'Brand Y', 'age_days' => 1, 'adjournments' => 0],
            ['type_code' => 'CHEAT', 'title' => 'IPC 420 - Recent Filing', 'petitioner' => 'State', 'respondent' => 'Accused Person', 'age_days' => 7, 'adjournments' => 0],
        ];
    }
}
