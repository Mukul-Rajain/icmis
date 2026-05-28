<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles/permissions
        app()['cache']->forget('spatie.permission.cache');

        $permissions = [
            'register-case', 'view-case', 'update-case-stage', 'rescore-case', 'dispose-case',
            'view-cause-list', 'generate-cause-list', 'publish-cause-list',
            'schedule-hearing', 'record-hearing-outcome', 'request-adjournment',
            'view-analytics', 'manage-users', 'manage-courts', 'manage-case-types',
            'upload-document', 'view-document',
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p]);
        }

        // ─── Role → Permission mapping ───────────────────────
        $admin = Role::findByName('admin');
        $admin->syncPermissions(Permission::all());

        $judge = Role::findByName('judge');
        $judge->syncPermissions([
            'view-case', 'update-case-stage', 'rescore-case', 'dispose-case',
            'view-cause-list', 'publish-cause-list',
            'schedule-hearing', 'record-hearing-outcome',
            'view-analytics', 'upload-document', 'view-document',
        ]);

        $staff = Role::findByName('court_staff');
        $staff->syncPermissions([
            'register-case', 'view-case', 'view-cause-list', 'generate-cause-list',
            'schedule-hearing', 'view-document', 'upload-document',
        ]);

        $lawyer = Role::findByName('lawyer');
        $lawyer->syncPermissions([
            'register-case', 'view-case', 'request-adjournment',
            'view-cause-list', 'upload-document', 'view-document',
        ]);

        $litigant = Role::findByName('litigant');
        $litigant->syncPermissions([
            'view-case', 'view-cause-list', 'view-document',
        ]);

        $this->command->info('Permissions and role mappings seeded.');
    }
}
