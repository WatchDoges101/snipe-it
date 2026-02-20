<?php

use App\Models\User;
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
        Schema::table('consumables_users', function (Blueprint $table) {
            if (!Schema::hasColumn('consumables_users', 'assigned_type')) {
                $table->string('assigned_type')->nullable()->after('assigned_to');
                $table->index(['assigned_type', 'assigned_to'], 'consumables_users_assigned_type_assigned_to_index');
            }
        });

        DB::table('consumables_users')
            ->whereNull('assigned_type')
            ->update(['assigned_type' => User::class]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consumables_users', function (Blueprint $table) {
            if (Schema::hasColumn('consumables_users', 'assigned_type')) {
                $table->dropIndex('consumables_users_assigned_type_assigned_to_index');
                $table->dropColumn('assigned_type');
            }
        });
    }
};
