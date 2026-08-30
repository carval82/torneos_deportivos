<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tournaments', function (Blueprint $table) {
            $table->unsignedSmallInteger('days_between_rounds')->default(7)->after('match_interval_minutes');
            $table->string('field_surface')->default('natural')->after('days_between_rounds');
            $table->unsignedTinyInteger('red_ban_matches')->default(1)->after('field_surface');
            $table->unsignedTinyInteger('double_yellow_ban_matches')->default(1)->after('red_ban_matches');
        });

        Schema::create('suspensions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('source_game_id')->nullable()->constrained('games')->nullOnDelete();
            $table->foreignId('source_event_id')->nullable()->constrained('game_events')->nullOnDelete();
            $table->string('reason');
            $table->string('card_type');
            $table->unsignedTinyInteger('matches_total')->default(1);
            $table->unsignedTinyInteger('matches_remaining')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suspensions');

        Schema::table('tournaments', function (Blueprint $table) {
            $table->dropColumn([
                'days_between_rounds',
                'field_surface',
                'red_ban_matches',
                'double_yellow_ban_matches',
            ]);
        });
    }
};
