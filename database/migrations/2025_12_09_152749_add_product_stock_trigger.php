<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

class AddProductStockTrigger extends Migration
{
    public function up()
    {
        // Only for MySQL
        if (config('database.default') === 'mysql') {
            $this->addMySqlTriggers();
        }
        
        // For PostgreSQL
        if (config('database.default') === 'pgsql') {
            $this->addPostgresTriggers();
        }
    }

    public function down()
    {
        if (config('database.default') === 'mysql') {
            DB::unprepared('DROP TRIGGER IF EXISTS update_product_stock_after_stock_insert');
            DB::unprepared('DROP TRIGGER IF EXISTS update_product_stock_after_stock_update');
            DB::unprepared('DROP TRIGGER IF EXISTS update_product_stock_after_stock_delete');
        }
        
        if (config('database.default') === 'pgsql') {
            DB::unprepared('DROP FUNCTION IF EXISTS update_product_stock() CASCADE');
        }
    }
    
    private function addMySqlTriggers()
    {
        // Trigger for INSERT
        DB::unprepared('
            CREATE TRIGGER update_product_stock_after_stock_insert
            AFTER INSERT ON stocks
            FOR EACH ROW
            BEGIN
                UPDATE products
                SET stock = (
                    SELECT COALESCE(SUM(
                        CASE 
                            WHEN type IN ("in", "adjustment", "transfer_in", "return") THEN quantity
                            WHEN type IN ("out", "damage", "transfer") THEN -quantity
                            ELSE 0
                        END
                    ), 0)
                    FROM stocks
                    WHERE product_id = NEW.product_id
                )
                WHERE id = NEW.product_id;
            END
        ');
        
        // Trigger for UPDATE
        DB::unprepared('
            CREATE TRIGGER update_product_stock_after_stock_update
            AFTER UPDATE ON stocks
            FOR EACH ROW
            BEGIN
                UPDATE products
                SET stock = (
                    SELECT COALESCE(SUM(
                        CASE 
                            WHEN type IN ("in", "adjustment", "transfer_in", "return") THEN quantity
                            WHEN type IN ("out", "damage", "transfer") THEN -quantity
                            ELSE 0
                        END
                    ), 0)
                    FROM stocks
                    WHERE product_id = NEW.product_id
                )
                WHERE id = NEW.product_id;
            END
        ');
        
        // Trigger for DELETE
        DB::unprepared('
            CREATE TRIGGER update_product_stock_after_stock_delete
            AFTER DELETE ON stocks
            FOR EACH ROW
            BEGIN
                UPDATE products
                SET stock = (
                    SELECT COALESCE(SUM(
                        CASE 
                            WHEN type IN ("in", "adjustment", "transfer_in", "return") THEN quantity
                            WHEN type IN ("out", "damage", "transfer") THEN -quantity
                            ELSE 0
                        END
                    ), 0)
                    FROM stocks
                    WHERE product_id = OLD.product_id
                )
                WHERE id = OLD.product_id;
            END
        ');
    }
    
    private function addPostgresTriggers()
    {
        // Create function for PostgreSQL
        DB::unprepared('
            CREATE OR REPLACE FUNCTION update_product_stock()
            RETURNS TRIGGER AS $$
            BEGIN
                UPDATE products
                SET stock = (
                    SELECT COALESCE(SUM(
                        CASE 
                            WHEN type IN (\'in\', \'adjustment\', \'transfer_in\', \'return\') THEN quantity
                            WHEN type IN (\'out\', \'damage\', \'transfer\') THEN -quantity
                            ELSE 0
                        END
                    ), 0)
                    FROM stocks
                    WHERE product_id = COALESCE(NEW.product_id, OLD.product_id)
                )
                WHERE id = COALESCE(NEW.product_id, OLD.product_id);
                RETURN NULL;
            END;
            $$ LANGUAGE plpgsql;
        ');
        
        // Create triggers
        DB::unprepared('
            CREATE TRIGGER update_product_stock_after_stock_insert
            AFTER INSERT ON stocks
            FOR EACH ROW
            EXECUTE FUNCTION update_product_stock();
        ');
        
        DB::unprepared('
            CREATE TRIGGER update_product_stock_after_stock_update
            AFTER UPDATE ON stocks
            FOR EACH ROW
            EXECUTE FUNCTION update_product_stock();
        ');
        
        DB::unprepared('
            CREATE TRIGGER update_product_stock_after_stock_delete
            AFTER DELETE ON stocks
            FOR EACH ROW
            EXECUTE FUNCTION update_product_stock();
        ');
    }
}