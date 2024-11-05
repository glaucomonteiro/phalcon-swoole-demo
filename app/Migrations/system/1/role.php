<?php

use Models\System\Role;
use Phalcon\Db\Column;
use Phalcon\Db\Exception;
use Lib\Base\Migration;

class RoleMigration_1 extends Migration
{
    public function __construct()
    {
        $this->model = new Role();
        $this->version = '1';
        parent::__construct();
    }
    /**
     * Define the table structure
     *
     * @return void
     * @throws Exception
     */
    public function morph(): void
    {
        $this->morphTable($this->table, [
            'columns' => [
                new Column(
                    'id',
                    [
                        'type' => Column::TYPE_INTEGER,
                        'primary' => true,
                        'notNull' => true,
                        'first' => true
                    ]
                ),
                new Column(
                    'title',
                    [
                        'type' => Column::TYPE_TEXT,
                        'notNull' => true,
                        'after' => 'id'
                    ]
                ),
                new Column(
                    'feature_flags',
                    [
                        'type' => Column::TYPE_JSONB,
                        'notNull' => true,
                        'after' => 'role',
                        'default' => '[]'
                    ]
                ),
            ],
        ]);
    }

    /**
     * Run the migrations
     *
     * @return void
     */
    public function up(): void
    {
        $this->import();
    }

    /**
     * Reverse the migrations
     *
     * @return void
     */
    public function down(): void {}
}
