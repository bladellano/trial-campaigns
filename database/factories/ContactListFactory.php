<?php

namespace Database\Factories;

use App\Models\ContactList;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContactListFactory extends Factory
{
    protected $model = ContactList::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true) . ' List',
        ];
    }
}
