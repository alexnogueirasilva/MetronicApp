<?php declare(strict_types = 1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('impersonations', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignId('impersonator_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('impersonated_id')->constrained('users')->onDelete('cascade');
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            $table->index('impersonator_id');
            $table->index('impersonated_id');
            $table->index('ended_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('impersonations');
    }
};
