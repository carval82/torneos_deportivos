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
            $table->json('match_start_times')->nullable()->after('match_start_time');
        });

        $driver = Schema::getConnection()->getDriverName();

        foreach (DB::table('tournaments')->select('id', 'match_start_time', 'match_start_times')->get() as $row) {
            if ($row->match_start_times) {
                continue;
            }

            $time = $row->match_start_time
                ? substr((string) $row->match_start_time, 0, 5)
                : '09:00';

            $payload = json_encode([$time]);
            if ($driver === 'pgsql') {
                DB::statement('UPDATE tournaments SET match_start_times = ?::json WHERE id = ?', [$payload, $row->id]);
            } else {
                DB::table('tournaments')->where('id', $row->id)->update([
                    'match_start_times' => $payload,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('tournaments', function (Blueprint $table) {
            $table->dropColumn('match_start_times');
        });
    }
};
