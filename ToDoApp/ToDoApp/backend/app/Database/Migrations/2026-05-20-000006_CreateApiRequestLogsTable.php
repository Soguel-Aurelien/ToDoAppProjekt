<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateApiRequestLogsTable extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('api_request_logs')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'api_user' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
            ],
            'method' => [
                'type' => 'VARCHAR',
                'constraint' => 10,
            ],
            'path' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'query_string' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'status_code' => [
                'type' => 'INT',
                'constraint' => 3,
            ],
            'ip_address' => [
                'type' => 'VARCHAR',
                'constraint' => 45,
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('api_user');
        $this->forge->addKey('created_at');
        $this->forge->createTable('api_request_logs');
    }

    public function down()
    {
        if ($this->db->tableExists('api_request_logs')) {
            $this->forge->dropTable('api_request_logs');
        }
    }
}
