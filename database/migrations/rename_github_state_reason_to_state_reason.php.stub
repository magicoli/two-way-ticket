<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Oli, 2026-07-26: "state_reason n'est pas spécifique à github, on adopte le même principe" —
 * the field is the app's own concept (why a ticket was closed), aligned on GitHub's vocabulary
 * like everything else here, not a GitHub-only annotation. Hence plain `state_reason`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table): void {
            $table->renameColumn('github_state_reason', 'state_reason');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table): void {
            $table->renameColumn('state_reason', 'github_state_reason');
        });
    }
};
