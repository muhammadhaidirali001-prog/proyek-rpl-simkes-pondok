<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRiwayatStatusTable extends Migration
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
            'laporan_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 30, // 'Baru', 'Dalam Perawatan', 'Isolasi', 'Rujukan RS', 'Sembuh'
            ],
            'tanggal' => [
                'type' => 'DATETIME',
            ],
            'catatan' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'pengguna_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('tanggal');
        $this->forge->addForeignKey('laporan_id', 'laporan_kesehatan', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('pengguna_id', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('riwayat_status', true, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('riwayat_status', true);
    }
}
