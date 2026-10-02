<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sequences', function (Blueprint $table) {
            $table->string('name')->primary();
            $table->unsignedBigInteger('value')->default(0);
        });

        // Continue from the highest order number already in use.
        DB::table('sequences')->insert([
            'name' => 'orders',
            'value' => DB::table('orders')->max('number') ?? 0,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sequences');
    }
};
