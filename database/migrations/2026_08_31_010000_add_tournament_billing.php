<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('free_tournament_used')->default(false)->after('player_id');
            $table->unsignedInteger('tournament_credits')->default(0)->after('free_tournament_used');
        });

        Schema::table('tournaments', function (Blueprint $table) {
            $table->string('billing_type', 20)->default('free')->after('is_public'); // free|paid
            $table->foreignId('renewed_from_id')->nullable()->after('billing_type')->constrained('tournaments')->nullOnDelete();
        });

        Schema::create('tournament_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('amount')->default(70000);
            $table->string('currency', 10)->default('COP');
            $table->string('purpose', 30)->default('create'); // create|renew
            $table->string('status', 20)->default('pending'); // pending|approved|rejected
            $table->foreignId('reference_tournament_id')->nullable()->constrained('tournaments')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_payments');

        Schema::table('tournaments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('renewed_from_id');
            $table->dropColumn('billing_type');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['free_tournament_used', 'tournament_credits']);
        });
    }
};
