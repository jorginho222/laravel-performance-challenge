<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->index('name');
            $table->index(['category_id', 'name']);
            $table->index(['category_id', 'status', 'name']);
        });

        // MySQL drops the foreign key's implicit category_id index on its own once an
        // index starting with category_id exists, but not one that was created explicitly
        // (as down() does). The composites make it redundant either way.
        if (Schema::hasIndex('products', 'products_category_id_foreign')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropIndex('products_category_id_foreign');
            });
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // The foreign key needs an index on category_id at all times.
            $table->index('category_id', 'products_category_id_foreign');

            $table->dropIndex(['category_id', 'status', 'name']);
            $table->dropIndex(['category_id', 'name']);
            $table->dropIndex(['name']);
        });
    }
};
