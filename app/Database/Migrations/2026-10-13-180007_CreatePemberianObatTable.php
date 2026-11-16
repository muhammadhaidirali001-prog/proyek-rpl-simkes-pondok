<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePemberianObatTable extends Migration
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
            'pemeriksaan_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'obat_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'dosis' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'aturan_pakai' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'durasi' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'instruksi_petugas' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'diverifikasi_oleh_medis' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1, // Memastikan resep merupakan instruksi resmi tenaga medis manusia
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
        $this->forge->addForeignKey('pemeriksaan_id', 'pemeriksaan', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('obat_id', 'obat', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->createTable('pemberian_obat', true, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('pemberian_obat', true);
    }
}
