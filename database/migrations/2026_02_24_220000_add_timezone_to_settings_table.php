<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            if (! Schema::hasColumn('settings', 'timezone')) {
                $table->string('timezone', 191)->nullable()->after('locale');
            }
        });

        if (Schema::hasColumn('settings', 'timezone')) {
            DB::table('settings')
                ->whereNull('timezone')
                ->update(['timezone' => config('app.timezone', 'UTC')]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            if (Schema::hasColumn('settings', 'timezone')) {
                $table->dropColumn('timezone');
            }
        });
    }
};
