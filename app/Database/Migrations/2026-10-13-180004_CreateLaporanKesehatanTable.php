<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateLaporanKesehatanTable extends Migration
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
            'santri_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'pelapor_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'tanggal_mulai_sakit' => [
                'type' => 'DATE',
            ],
            'gejala' => [
                'type' => 'TEXT',
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'default'    => 'Baru', // Konsisten: 'Baru', 'Dalam Perawatan', 'Isolasi', 'Rujukan RS', 'Sembuh'
            ],
            'tanggal_sembuh' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'catatan' => [
                'type' => 'TEXT',
                'null' => true,
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
        $this->forge->addKey('status');
        $this->forge->addKey('tanggal_mulai_sakit');
        $this->forge->addForeignKey('santri_id', 'santri', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('pelapor_id', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('laporan_kesehatan', true, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('laporan_kesehatan', true);
    }
}
