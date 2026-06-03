<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateTodosTable extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('todos')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'domain_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                ],
                'title' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 150,
                ],
                'description' => [
                    'type' => 'TEXT',
                ],
                'end_date' => [
                    'type' => 'DATE',
                ],
                'priority' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'default'    => 'Mittel',
                ],
                'status' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 30,
                    'default'    => 'Offen',
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'updated_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);

            $this->forge->addKey('id', true);
            $this->forge->addKey('domain_id');
            $this->forge->createTable('todos');
        }
    }

    public function down()
    {
    }
}
