<?php

declare(strict_types=1);

namespace Magicoli\TwoWayTicket\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Magicoli\TwoWayTicket\Enums\TicketPriority;
use Magicoli\TwoWayTicket\Enums\TicketStatus;
use Magicoli\TwoWayTicket\Models\Ticket;

/**
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(),
            'status' => TicketStatus::New,
            'priority' => TicketPriority::Low,
            'labels' => [],
            'app_version' => '1.0.0',
            'role' => '',
        ];
    }

    public function withLabels(string ...$labels): static
    {
        return $this->state(['labels' => $labels]);
    }

    public function linked(int $issueNumber = 1): static
    {
        return $this->state([
            'github_issue_url' => "https://github.com/example/example/issues/{$issueNumber}",
            'github_issue_number' => $issueNumber,
            'status' => TicketStatus::Triaged,
        ]);
    }

    public function resolved(): static
    {
        return $this->state([
            'status' => TicketStatus::Resolved,
            'resolved_at' => now(),
        ]);
    }
}
