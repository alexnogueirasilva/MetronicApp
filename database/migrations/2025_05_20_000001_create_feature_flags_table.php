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
        Schema::create('feature_flag_metadata', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('feature_name')->unique();
            $table->string('display_name');
            $table->text('description')->nullable();
            $table->string('type');
            $table->json('parameters')->nullable();
            $table->boolean('default_value')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
        });

        Schema::create('feature_flag_tenant', function (Blueprint $table): void {
            $table->id();
            $table->string('feature_name');
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->boolean('value')->default(false);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->foreign('feature_name')
                  ->references('feature_name')
                  ->on('feature_flag_metadata')
                  ->cascadeOnDelete();

            $table->unique(['feature_name', 'tenant_id']);
        });

        Schema::create('feature_flag_user', function (Blueprint $table): void {
            $table->id();
            $table->string('feature_name');
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('value')->default(false);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->foreign('feature_name')
                  ->references('feature_name')
                  ->on('feature_flag_metadata')
                  ->cascadeOnDelete();

            $table->unique(['feature_name', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feature_flag_user');
        Schema::dropIfExists('feature_flag_tenant');
        Schema::dropIfExists('feature_flag_metadata');
    }
};
