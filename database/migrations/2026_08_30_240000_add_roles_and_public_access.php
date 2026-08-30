<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('document_type', 30)->nullable()->after('role');
            $table->string('document_number', 40)->nullable()->after('document_type');
            $table->string('phone', 40)->nullable()->after('document_number');
            $table->foreignId('player_id')->nullable()->after('phone')->constrained('players')->nullOnDelete();
            $table->unique(['document_type', 'document_number'], 'users_document_unique');
        });

        Schema::table('tournaments', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
            $table->string('public_slug')->nullable()->after('name');
            $table->boolean('is_public')->default(true)->after('public_slug');
            $table->unique('public_slug');
        });

        Schema::create('team_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 30)->default('delegate');
            $table->timestamps();
            $table->unique(['team_id', 'user_id']);
        });

        Schema::create('team_invites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('token', 64)->unique();
            $table->string('email')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->foreignId('accepted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        $ownerId = DB::table('users')->orderBy('id')->value('id');

        foreach (DB::table('tournaments')->orderBy('id')->get() as $tournament) {
            $base = Str::slug($tournament->name) ?: 'torneo-'.$tournament->id;
            $slug = $base;
            $i = 2;
            while (DB::table('tournaments')->where('public_slug', $slug)->exists()) {
                $slug = $base.'-'.$i;
                $i++;
            }

            DB::table('tournaments')->where('id', $tournament->id)->update([
                'user_id' => $ownerId,
                'public_slug' => $slug,
                'is_public' => true,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('team_invites');
        Schema::dropIfExists('team_user');

        Schema::table('tournaments', function (Blueprint $table) {
            $table->dropUnique(['public_slug']);
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn(['public_slug', 'is_public']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_document_unique');
            $table->dropConstrainedForeignId('player_id');
            $table->dropColumn(['document_type', 'document_number', 'phone']);
        });
    }
};
