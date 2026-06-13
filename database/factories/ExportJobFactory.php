<?php

namespace Database\Factories;

use App\Models\ExportJob;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExportJobFactory extends Factory
{
    protected $model = ExportJob::class;

    public function definition(): array
    {
        return [
            'user_id' => UserFactory::new(),
            'report' => 'rapports-clients.brouillard-cheques',
            'format' => 'pdf',
            'label' => 'Brouillard des chèques',
            'params' => ['date_debut' => '2026-01-01', 'date_fin' => '2026-12-31'],
            'status' => ExportJob::STATUT_PENDING,
            'progress' => 0,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => ExportJob::STATUT_COMPLETED,
            'progress' => 100,
            'file_path' => 'exports/test.pdf',
            'file_name' => 'brouillard.pdf',
            'completed_at' => now(),
        ]);
    }
}
