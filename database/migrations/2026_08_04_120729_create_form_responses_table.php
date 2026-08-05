<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_responses', function (Blueprint $table) {

            $table->id();

            $table->foreignId('form_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('submitted_by_ip')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_responses');
    }
};