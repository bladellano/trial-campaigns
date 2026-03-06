<?php

namespace Database\Seeders;

use App\Models\Campaign;
use App\Models\Contact;
use App\Models\ContactList;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $contacts = Contact::factory(50)->create();

        $lists = ContactList::factory(3)->create();

        foreach ($lists as $list) {
            $list->contacts()->attach(
                $contacts->random(rand(10, 20))->pluck('id')
            );
        }

        Campaign::factory(5)->create([
            'contact_list_id' => $lists->random()->id,
        ]);
    }
}
