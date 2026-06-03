<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        if ($this->db->table('domains')->countAllResults() > 0) {
            return;
        }

        $now = date('Y-m-d H:i:s');

        $this->db->table('domains')->insertBatch([
            [
                'name' => 'Schule',
                'description' => 'Aufgaben fuer Unterricht und Projektarbeit.',
                'is_protected' => 0,
                'password_hash' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Privat',
                'description' => 'Persoenliche TODOs.',
                'is_protected' => 0,
                'password_hash' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Admin',
                'description' => 'Geschuetzte Beispiel-Kategorie.',
                'is_protected' => 1,
                'password_hash' => password_hash('Admin123!', PASSWORD_DEFAULT),
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        $schoolId = (int) $this->db->table('domains')->where('name', 'Schule')->get()->getRow()->id;
        $privateId = (int) $this->db->table('domains')->where('name', 'Privat')->get()->getRow()->id;

        $this->db->table('todos')->insertBatch([
            [
                'domain_id' => $schoolId,
                'title' => 'REST API testen',
                'description' => 'GET, POST, PATCH und DELETE mit JSON testen.',
                'end_date' => '2026-05-25',
                'priority' => 'Hoch',
                'status' => 'In Arbeit',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'domain_id' => $privateId,
                'title' => 'Einkauf planen',
                'description' => 'Liste vorbereiten und erledigte Punkte markieren.',
                'end_date' => '2026-05-22',
                'priority' => 'Niedrig',
                'status' => 'Offen',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        $todoId = (int) $this->db->table('todos')->where('title', 'REST API testen')->get()->getRow()->id;

        $this->db->table('todo_contacts')->insert([
            'todo_id' => $todoId,
            'email' => 'lehrer@example.com',
            'created_at' => $now,
        ]);
    }
}
