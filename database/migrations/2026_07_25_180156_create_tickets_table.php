<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->json('steps')->nullable();
            $table->string('status')->default('open');
            // Open labels (see DEVELOPERS.md §1) — a plain JSON array of strings, not a fixed enum. Not
            // synced FROM GitHub read-side yet in V1 (see DEVELOPERS.md's open questions); pushed to
            // GitHub as-is when the ticket is created there.
            $table->json('labels')->nullable();
            // A GitHub milestone TITLE (not a relation yet — see DEVELOPERS.md's open questions, deferred
            // to V2 as a real synced model).
            $table->string('milestone')->nullable();
            $table->json('screenshot_paths')->nullable();
            $table->string('page_url')->nullable();
            $table->string('app_version');
            $table->string('role')->default('');
            $table->string('github_issue_url')->nullable();
            $table->unsignedBigInteger('github_issue_number')->nullable();
            // Set to the GitHub issue's real closed_at when linked, or now() for a purely local
            // resolution — display only, status itself is the source of truth.
            $table->timestampTz('resolved_at')->nullable();
            // Not a hard FK so the package works against any users table.
            $table->unsignedBigInteger('user_id')->nullable();
            $table->index('user_id');
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
