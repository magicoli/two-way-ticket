<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `app_version` was NOT NULL with no default, so any insert that did not name it failed outright.
 * Two of the three ways a ticket gets created set it (the API stamps the build, the GitHub import
 * stores an empty string on purpose); the Filament create page did not, and creating a ticket from
 * the UI crashed on a constraint violation.
 *
 * A default makes that impossible to get wrong from anywhere, including a raw insert or a future
 * call site that has not been written yet — see the model's `creating` hook for the other half,
 * which stamps the real build rather than merely avoiding a crash.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table): void {
            $table->string('app_version')->default('')->change();
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table): void {
            $table->string('app_version')->default(null)->change();
        });
    }
};
