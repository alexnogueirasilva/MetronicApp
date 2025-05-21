<?php declare(strict_types = 1);

use App\Models\{Tenant, User};
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignIdFor(Tenant::class)->constrained()->onDelete('cascade');
            $table->foreignIdFor(User::class, 'created_by')->constrained()->onDelete('cascade');
            $table->string('name')->index();
            $table->string('description')->nullable();
            $table->string('type')->index();
            $table->string('currency')->index();
            $table->decimal('balance', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
