<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePemeriksaanTable extends Migration
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
            'pemeriksa_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'tanggal_periksa' => [
                'type' => 'DATETIME',
            ],
            'diagnosis_atau_dugaan' => [
                'type' => 'TEXT',
            ],
            'tindakan' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'catatan' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'diverifikasi_oleh_medis' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1, // Memastikan diagnosis merupakan hasil verifikasi tenaga medis manusia
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
        $this->forge->addKey('tanggal_periksa');
        $this->forge->addForeignKey('laporan_id', 'laporan_kesehatan', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('pemeriksa_id', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('pemeriksaan', true, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('pemeriksaan', true);
    }
}
