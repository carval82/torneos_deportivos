<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('organizer')->after('email');
        });

        Schema::create('sports', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('scoring_unit')->default('goles');
            $table->boolean('is_team_sport')->default(true);
            $table->string('icon')->nullable();
            $table->timestamps();
        });

        Schema::create('age_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedTinyInteger('min_age')->nullable();
            $table->unsignedTinyInteger('max_age')->nullable();
            $table->string('gender')->default('mixto');
            $table->timestamps();
        });

        Schema::create('tournaments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sport_id')->constrained()->cascadeOnDelete();
            $table->foreignId('age_category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('season')->nullable();
            $table->string('format')->default('league');
            $table->string('status')->default('draft');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->unsignedTinyInteger('points_win')->default(3);
            $table->unsignedTinyInteger('points_draw')->default(1);
            $table->unsignedTinyInteger('points_loss')->default(0);
            $table->boolean('double_round')->default(false);
            $table->string('venue')->nullable();
            $table->text('rules')->nullable();
            $table->timestamps();
        });

        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('short_name', 10)->nullable();
            $table->string('city')->nullable();
            $table->string('coach')->nullable();
            $table->string('primary_color')->nullable();
            $table->string('logo_path')->nullable();
            $table->timestamps();
        });

        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->nullable()->constrained()->nullOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('document_type')->default('DNI');
            $table->string('document_number');
            $table->date('birthdate')->nullable();
            $table->string('gender')->default('masculino');
            $table->string('nationality')->default('Argentina');
            $table->string('position')->nullable();
            $table->unsignedTinyInteger('jersey_number')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('photo_path')->nullable();
            $table->string('document_photo_path')->nullable();
            $table->timestamps();

            $table->unique(['document_type', 'document_number']);
        });

        Schema::create('tournament_team', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('group_name')->nullable();
            $table->unsignedTinyInteger('seed')->nullable();
            $table->timestamps();

            $table->unique(['tournament_id', 'team_id']);
        });

        Schema::create('rosters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('jersey_number')->nullable();
            $table->string('position')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['tournament_id', 'player_id']);
        });

        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
            $table->foreignId('home_team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('away_team_id')->constrained('teams')->cascadeOnDelete();
            $table->unsignedSmallInteger('matchday')->default(1);
            $table->string('round_name')->nullable();
            $table->dateTime('scheduled_at')->nullable();
            $table->string('venue')->nullable();
            $table->string('status')->default('scheduled');
            $table->unsignedSmallInteger('home_score')->nullable();
            $table->unsignedSmallInteger('away_score')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('game_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('player_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type');
            $table->unsignedSmallInteger('minute')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('present');
            $table->unsignedSmallInteger('minutes_played')->nullable();
            $table->timestamps();

            $table->unique(['game_id', 'player_id']);
        });

        Schema::create('standing_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('matchday');
            $table->unsignedTinyInteger('position');
            $table->smallInteger('points');
            $table->unsignedSmallInteger('played');
            $table->unsignedSmallInteger('won');
            $table->unsignedSmallInteger('drawn');
            $table->unsignedSmallInteger('lost');
            $table->unsignedSmallInteger('goals_for');
            $table->unsignedSmallInteger('goals_against');
            $table->smallInteger('goal_difference');
            $table->timestamps();

            $table->unique(['tournament_id', 'team_id', 'matchday']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('standing_snapshots');
        Schema::dropIfExists('attendances');
        Schema::dropIfExists('game_events');
        Schema::dropIfExists('games');
        Schema::dropIfExists('rosters');
        Schema::dropIfExists('tournament_team');
        Schema::dropIfExists('players');
        Schema::dropIfExists('teams');
        Schema::dropIfExists('tournaments');
        Schema::dropIfExists('age_categories');
        Schema::dropIfExists('sports');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
