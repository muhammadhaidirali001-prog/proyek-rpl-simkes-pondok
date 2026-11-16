<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAuditLogTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'pengguna_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'aksi' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'waktu' => [
                'type' => 'DATETIME',
            ],
            'detail_perubahan' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('aksi');
        $this->forge->addKey('waktu');
        $this->forge->addForeignKey('pengguna_id', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('audit_log', true, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('audit_log', true);
    }
}
