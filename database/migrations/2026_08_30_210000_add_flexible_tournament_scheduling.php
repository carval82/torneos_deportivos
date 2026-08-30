<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tournaments', function (Blueprint $table) {
            $table->string('category_label')->nullable()->after('age_category_id');
            $table->unsignedTinyInteger('min_age')->nullable()->after('category_label');
            $table->unsignedTinyInteger('max_age')->nullable()->after('min_age');
            $table->string('gender_rule')->default('mixto')->after('max_age');
            $table->unsignedSmallInteger('max_teams')->nullable()->after('gender_rule');
            $table->string('complex_name')->nullable()->after('venue');
            $table->json('fields')->nullable()->after('complex_name');
            $table->json('play_days')->nullable()->after('fields');
            $table->time('match_start_time')->nullable()->after('play_days');
            $table->unsignedSmallInteger('match_interval_minutes')->default(90)->after('match_start_time');
            $table->boolean('rules_published')->default(true)->after('rules');
            $table->text('rules_summary')->nullable()->after('rules_published');
        });

        Schema::table('games', function (Blueprint $table) {
            $table->string('field_name')->nullable()->after('venue');
            $table->boolean('is_tentative')->default(true)->after('field_name');
            $table->dateTime('original_scheduled_at')->nullable()->after('scheduled_at');
            $table->string('postpone_reason')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('tournaments', function (Blueprint $table) {
            $table->dropColumn([
                'category_label',
                'min_age',
                'max_age',
                'gender_rule',
                'max_teams',
                'complex_name',
                'fields',
                'play_days',
                'match_start_time',
                'match_interval_minutes',
                'rules_published',
                'rules_summary',
            ]);
        });

        Schema::table('games', function (Blueprint $table) {
            $table->dropColumn([
                'field_name',
                'is_tentative',
                'original_scheduled_at',
                'postpone_reason',
            ]);
        });
    }
};
