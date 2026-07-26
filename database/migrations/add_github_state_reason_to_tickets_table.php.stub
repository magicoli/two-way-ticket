<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table): void {
            // Verbatim mirror of GitHub's own issue.state_reason (completed/not_planned/reopened/
            // null) — Oli, 2026-07-26: "si github a un champ status_reason, on en a un aussi, si
            // il n'en a pas, on n'en a pas". Never interpreted into a local status; the reason a
            // closed issue was closed lives here and in its labels, not as a status guess.
            $table->string('github_state_reason')->nullable()->after('github_issue_number');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table): void {
            $table->dropColumn('github_state_reason');
        });
    }
};
