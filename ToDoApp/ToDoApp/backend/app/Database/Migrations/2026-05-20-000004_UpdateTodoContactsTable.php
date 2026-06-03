<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateTodoContactsTable extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('todo_contacts')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'todo_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'email' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('todo_id');
        $this->forge->createTable('todo_contacts');
    }

    public function down()
    {
        if ($this->db->tableExists('todo_contacts')) {
            $this->forge->dropTable('todo_contacts');
        }
    }
}
