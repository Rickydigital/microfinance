<?php

namespace Database\Factories;

use App\Models\LoanApplication;
use App\Models\PaymentSubmission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentSubmission>
 */
class PaymentSubmissionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'loan_application_id' => LoanApplication::factory(),
            'customer_id' => fn (array $attributes): int => LoanApplication::findOrFail($attributes['loan_application_id'])->customer_id,
            'purpose' => 'Assurance',
            'amount' => 1500,
            'payment_method' => 'Bank',
            'external_reference' => fake()->unique()->bothify('PAY-########'),
            'reference_key' => fn (array $attributes): string => hash('sha256', $attributes['payment_method'].'|'.strtoupper($attributes['external_reference'])),
            'attachment_path' => null,
            'paid_at' => now(),
            'status' => 'Pending',
        ];
    }
}
