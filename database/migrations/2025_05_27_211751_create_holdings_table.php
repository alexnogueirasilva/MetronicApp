<?php declare(strict_types = 1);

use App\Models\Tenant;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('holdings', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignIdFor(Tenant::class)->constrained()->onDelete('cascade');
            $table->string('name')->index();
            $table->string('legal_name');
            $table->string('tax_id', 14)->index();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('description')->nullable();
            $table->string('logo_path')->nullable();
            $table->json('settings')->nullable();
            $table->json('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'tax_id', 'legal_name'], 'holdings_unique_tenant_tax_id_legal_name');
            $table->index(['tenant_id', 'is_active']);
            $table->index(['tenant_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('holdings');
    }
};
