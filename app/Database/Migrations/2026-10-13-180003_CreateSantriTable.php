<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSantriTable extends Migration
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
            'nis' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'unique'     => true,
            ],
            'nama' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'jenis_kelamin' => [
                'type'       => 'VARCHAR',
                'constraint' => 15, // 'Laki-laki' / 'Perempuan'
            ],
            'kamar_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'kelas' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'kontak_wali' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
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
        $this->forge->addKey('nama');
        $this->forge->addForeignKey('kamar_id', 'kamar', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('santri', true, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('santri', true);
    }
}
