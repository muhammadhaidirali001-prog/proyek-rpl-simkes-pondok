<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateObatTable extends Migration
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
            'nama_obat' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'unique'     => true,
            ],
            'informasi_penggunaan' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'stok' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
            ],
            'satuan' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'default'    => 'Tablet',
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
        $this->forge->createTable('obat', true, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('obat', true);
    }
}
