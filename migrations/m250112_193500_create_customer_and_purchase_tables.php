<?php

namespace craft\contentmigrations;

use Craft;
use craft\db\Migration;

/**
 * m250112_193500_create_customer_and_purchase_tables migration.
 */
class m250112_193500_create_customer_and_purchase_tables extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // Create customers table
        $this->createTable('{{%customers}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
            'email' => $this->string()->unique()->notNull(),
            'phone_number' => $this->string(12)->notNull(),
            'created_at' => $this->dateTime()->notNull(),
            'loyalty_points' => $this->integer()->defaultValue(0),
        ]);

        // Create purchase_history table
        $this->createTable('{{%purchase_history}}', [
            'id' => $this->primaryKey(),
            'customer_id' => $this->integer()->notNull(),
            'purchasable' => $this->string()->notNull(),
            'price' => $this->decimal(10, 2)->notNull(),
            'quantity' => $this->integer()->notNull(),
            'total' => $this->decimal(10, 2)->notNull(),
            'purchase_date' => $this->dateTime()->notNull(),
        ]);

        $this->addForeignKey(
            'fk-purchase_history-customer_id',
            '{{%purchase_history}}',
            'customer_id',
            '{{%customers}}',
            'id',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        $this->dropTable('{{%purchase_history}}');
        $this->dropTable('{{%customers}}');
    }

}
