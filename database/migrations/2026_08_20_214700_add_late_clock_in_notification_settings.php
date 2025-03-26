<?php

use App\Models\Company;
use App\Models\EmailNotificationSetting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $settings = [];
        $companies = Company::select('id')->get();

        foreach ($companies as $company) {
            $settings[] = [
                'send_email' => 'yes',
                'send_push' => 'no',
                'company_id' => $company->id,
                'send_slack' => 'no',
                'setting_name' => 'Late Clock In',
                'slug' => 'late-clock-in',
            ];

            $settings[] = [
                'send_email' => 'yes',
                'send_push' => 'no',
                'company_id' => $company->id,
                'send_slack' => 'no',
                'setting_name' => 'Late Clock In Manager Admin',
                'slug' => 'late-clock-in-manager-admin',
            ];
        }

        if (!empty($settings)) {
            EmailNotificationSetting::insert($settings);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        EmailNotificationSetting::whereIn('slug', [
            'late-clock-in',
            'late-clock-in-manager-admin',
        ])->delete();
    }

};
