<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tournaments', function (Blueprint $table) {
            $table->json('competition_rules')->nullable()->after('double_yellow_ban_matches');
        });

        Schema::table('tournament_team', function (Blueprint $table) {
            $table->string('status')->default('active')->after('seed');
            $table->unsignedTinyInteger('no_show_count')->default(0)->after('status');
            $table->timestamp('disqualified_at')->nullable()->after('no_show_count');
            $table->string('disqualify_reason')->nullable()->after('disqualified_at');
        });

        Schema::table('games', function (Blueprint $table) {
            $table->string('result_type')->default('played')->after('status');
            $table->foreignId('walkover_against_team_id')->nullable()->after('result_type')->constrained('teams')->nullOnDelete();
            $table->string('walkover_reason')->nullable()->after('walkover_against_team_id');
        });
    }

    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropConstrainedForeignId('walkover_against_team_id');
            $table->dropColumn(['result_type', 'walkover_reason']);
        });

        Schema::table('tournament_team', function (Blueprint $table) {
            $table->dropColumn(['status', 'no_show_count', 'disqualified_at', 'disqualify_reason']);
        });

        Schema::table('tournaments', function (Blueprint $table) {
            $table->dropColumn('competition_rules');
        });
    }
};
