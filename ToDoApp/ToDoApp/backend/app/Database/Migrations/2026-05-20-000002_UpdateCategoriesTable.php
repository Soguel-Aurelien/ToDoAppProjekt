<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateCategoriesTable extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('domains')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'name' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                ],
                'description' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'is_protected' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 0,
                ],
                'password_hash' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => true,
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
            $this->forge->createTable('domains');
        }

        if (! $this->db->fieldExists('description', 'domains')) {
            $this->forge->addColumn('domains', [
                'description' => [
                    'type' => 'TEXT',
                    'null' => true,
                    'after' => 'name',
                ],
            ]);
        }

        if (! $this->db->fieldExists('is_protected', 'domains')) {
            $this->forge->addColumn('domains', [
                'is_protected' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 0,
                    'after' => 'description',
                ],
            ]);
        }

        if (! $this->db->fieldExists('password_hash', 'domains')) {
            $this->forge->addColumn('domains', [
                'password_hash' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
                    'after' => 'is_protected',
                ],
            ]);
        }

        if (! $this->db->fieldExists('created_at', 'domains')) {
            $this->forge->addColumn('domains', [
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);
        }

        if (! $this->db->fieldExists('updated_at', 'domains')) {
            $this->forge->addColumn('domains', [
                'updated_at' => [
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
