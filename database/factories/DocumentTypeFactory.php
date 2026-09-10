<?php

namespace Database\Factories;

use App\Models\DocumentType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentType>
 */
class DocumentTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->lexify('DOC-????'),
            'name' => fake()->unique()->words(2, true),
            'customer_type_code' => null,
            'is_required_for_kyc' => false,
            'is_active' => true,
            'sort_order' => 10,
        ];
    }
}
