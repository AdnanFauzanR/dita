<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('perikanan', function (Blueprint $table) {
            $table->string('id', 13)->primary()->unique()->required();
            $table->string('kecamatan')->required();
            $table->string('komoditi')->required;
            $table->unsignedDecimal('volume', 14, 2)->required();
            $table->unsignedBigInteger('nilai_produksi')->required();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('perikanan');
    }
};
