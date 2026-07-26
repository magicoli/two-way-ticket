<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Oli, 2026-07-26: "Je veux une COPIE CONFORME de ce que GitHub gère (Assignee, Labels, Projects,
 * Milestone) [...] SANS RIEN DE PLUS" — drops `priority` (not a GitHub concept at all), renames
 * `resolved_at` to `closed_at` (GitHub's own field name), and adds `assignees`/`projects` so every
 * field GitHub tracks on an issue has a local counterpart. `status` keeps its column name but is
 * now restricted to GitHub's own two literal values (see TicketStatus).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table): void {
            // Guarded: an install that ran create_tickets_table BEFORE `priority` was removed
            // from that stub still physically has the column; a fresh install never does.
            if (Schema::hasColumn('tickets', 'priority')) {
                $table->dropColumn('priority');
            }

            $table->renameColumn('resolved_at', 'closed_at');
            $table->json('assignees')->nullable()->after('milestone');
            $table->json('projects')->nullable()->after('assignees');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table): void {
            $table->dropColumn(['assignees', 'projects']);
            $table->renameColumn('closed_at', 'resolved_at');
        });
    }
};
