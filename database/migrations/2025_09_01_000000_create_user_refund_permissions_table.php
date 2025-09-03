<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_refund_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('granter_id')->constrained('users')->onDelete('cascade'); // who grants permission (manager)
            $table->foreignId('grantee_id')->constrained('users')->onDelete('cascade'); // who is allowed to refund
            $table->boolean('is_able_to_force')->nullable()->default(false);
            $table->timestamp('expires_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['granter_id','grantee_id','is_able_to_force'], 'urp_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_refund_permissions');
    }
};