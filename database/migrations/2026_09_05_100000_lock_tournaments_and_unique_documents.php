<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tournaments', function (Blueprint $table) {
            $table->timestamp('locked_at')->nullable()->after('end_date');
        });

        DB::table('users')->where('document_number', '')->update(['document_number' => null]);

        Schema::table('users', function (Blueprint $table) {
            $table->unique('document_number');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['document_number']);
        });

        Schema::table('tournaments', function (Blueprint $table) {
            $table->dropColumn('locked_at');
        });
    }
};
