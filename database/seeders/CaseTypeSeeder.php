<?php

namespace Database\Seeders;

use App\Models\CaseType;
use Illuminate\Database\Seeder;

/**
 * Seeds realistic case types based on Indian judicial system categories.
 * The `default_track` and `base_priority` here are the foundational
 * tunables for the DCFM engine — adjust these to see how it impacts
 * the system behaviour during your demo.
 */
class CaseTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            // ─── FAST TRACK (time-sensitive) ───
            ['Bail Application', 'BAIL', 'criminal', 'fast', 90, 30, true, 'Application for bail under CrPC Section 437/439'],
            ['Anticipatory Bail', 'ABAIL', 'criminal', 'fast', 88, 30, true, 'Pre-arrest bail under CrPC Section 438'],
            ['Habeas Corpus', 'HC', 'constitutional', 'fast', 95, 21, true, 'Writ for unlawful detention'],
            ['Urgent Injunction', 'INJ-U', 'civil', 'fast', 85, 30, true, 'Urgent interim relief application'],
            ['Domestic Violence', 'DV', 'family', 'fast', 80, 60, true, 'Protection order under DV Act 2005'],
            ['Maintenance', 'MAINT', 'family', 'fast', 75, 90, true, 'Maintenance under CrPC Section 125'],

            // ─── STANDARD TRACK ───
            ['Civil Suit', 'CIV', 'civil', 'standard', 50, 365, false, 'Regular civil suit for monetary claims'],
            ['Property Dispute', 'PROP', 'civil', 'standard', 45, 540, false, 'Land/property title disputes'],
            ['Theft', 'THEFT', 'criminal', 'standard', 55, 180, false, 'IPC Section 378-382 offences'],
            ['Cheating', 'CHEAT', 'criminal', 'standard', 55, 240, false, 'IPC Section 415-420 offences'],
            ['Divorce Petition', 'DIV', 'family', 'standard', 50, 365, false, 'Dissolution of marriage'],
            ['Consumer Complaint', 'CONS', 'civil', 'standard', 60, 180, false, 'Consumer Protection Act matters'],
            ['Motor Accident Claim', 'MACT', 'civil', 'standard', 65, 240, false, 'MV Act Section 166 compensation'],
            ['Cheque Bounce', 'NI138', 'criminal', 'standard', 60, 180, false, 'NI Act Section 138'],

            // ─── COMPLEX TRACK ───
            ['Writ Petition', 'WP', 'constitutional', 'complex', 70, 540, false, 'Article 226/227 writ petition'],
            ['PIL', 'PIL', 'constitutional', 'complex', 75, 730, false, 'Public Interest Litigation'],
            ['Commercial Dispute', 'COMM', 'commercial', 'complex', 65, 540, false, 'Commercial Courts Act matters'],
            ['Tax Appeal', 'TAX', 'commercial', 'complex', 55, 540, false, 'Income tax / GST appellate matters'],
            ['Arbitration Petition', 'ARB', 'commercial', 'complex', 60, 365, false, 'Arbitration Act matters'],
            ['Insolvency', 'IBC', 'commercial', 'complex', 70, 365, false, 'IBC 2016 corporate insolvency'],
            ['Murder Trial', 'MURDER', 'criminal', 'complex', 80, 730, false, 'IPC Section 302 trial'],
            ['POCSO Case', 'POCSO', 'criminal', 'complex', 90, 365, true, 'Protection of Children from Sexual Offences'],
        ];

        foreach ($types as $t) {
            CaseType::updateOrCreate(
                ['code' => $t[1]],
                [
                    'name' => $t[0],
                    'code' => $t[1],
                    'category' => $t[2],
                    'default_track' => $t[3],
                    'base_priority' => $t[4],
                    'typical_duration_days' => $t[5],
                    'is_time_sensitive' => $t[6],
                    'description' => $t[7],
                    'is_active' => true,
                ]
            );
        }

        $this->command->info('Seeded ' . count($types) . ' case types.');
    }
}
