<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Pabrik data palsu untuk model Mahasiswa.
 * Pemakaian: Mahasiswa::factory()->count(20)->create();
 *
 * @extends Factory<\App\Models\Mahasiswa>
 */
class MahasiswaFactory extends Factory
{
    public function definition(): array
    {
        $angkatan = fake()->numberBetween(2021, 2025);

        return [
            'nim'      => substr((string) $angkatan, 2) . fake()->unique()->numerify('#####'),
            'nama'     => fake()->name(),
            'prodi'    => fake()->randomElement(['Teknik Informatika', 'Sistem Informasi', 'Bisnis Digital']),
            'angkatan' => $angkatan,
        ];
    }
}
