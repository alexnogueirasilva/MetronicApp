<?php declare(strict_types = 1);

use App\Models\Auth\{Permission, Role};
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('permission_role', function (Blueprint $table): void {
            $table->foreignIdFor(Permission::class)->constrained();
            $table->foreignIdFor(Role::class)->constrained();
            $table->primary(['permission_id', 'role_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permission_role');
    }
};
