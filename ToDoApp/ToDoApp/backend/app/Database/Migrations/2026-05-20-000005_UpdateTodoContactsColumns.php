<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateTodoContactsColumns extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('todo_contacts')) {
            return;
        }

        if (! $this->db->fieldExists('created_at', 'todo_contacts')) {
            $this->forge->addColumn('todo_contacts', [
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);
        }
    }

    public function down()
    {
    }
}
