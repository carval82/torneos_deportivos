<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('team_user', function (Blueprint $table) {
            $table->boolean('is_disciplinary_committee')->default(false)->after('role');
        });

        Schema::create('eligibility_exceptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('reason');
            $table->string('status', 20)->default('pending'); // pending|approved|rejected
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();

            $table->unique(['tournament_id', 'player_id']);
        });

        Schema::table('suspensions', function (Blueprint $table) {
            $table->foreignId('issued_by')->nullable()->after('is_active')->constrained('users')->nullOnDelete();
            $table->string('source', 30)->default('match_card')->after('issued_by'); // match_card|committee
            $table->text('notes')->nullable()->after('source');
        });
    }

    public function down(): void
    {
        Schema::table('suspensions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('issued_by');
            $table->dropColumn(['source', 'notes']);
        });

        Schema::dropIfExists('eligibility_exceptions');

        Schema::table('team_user', function (Blueprint $table) {
            $table->dropColumn('is_disciplinary_committee');
        });
    }
};
