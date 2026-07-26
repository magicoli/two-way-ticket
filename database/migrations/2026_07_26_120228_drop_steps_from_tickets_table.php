<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Steps are not a GitHub concept, so the field is abandoned — a report's steps are formatted
 * into the description at creation time instead (see ReportIssue/TicketController), so the
 * description alone round-trips with GitHub bidirectionally with no special-casing.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table): void {
            $table->dropColumn('steps');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table): void {
            $table->json('steps')->nullable();
        });
    }
};
