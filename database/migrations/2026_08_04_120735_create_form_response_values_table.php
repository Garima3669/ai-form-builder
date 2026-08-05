<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_response_values', function (Blueprint $table) {

            $table->id();

            $table->foreignId('form_response_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('form_field_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->longText('value')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_response_values');
    }
};