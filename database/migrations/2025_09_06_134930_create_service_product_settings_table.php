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
        Schema::create('service_product_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('product_name'); // Store product name for reference
            $table->string('product_forme')->nullable(); // Store forme for reference
            
            // Alert Settings
            $table->decimal('low_stock_threshold', 10, 2)->default(10);
            $table->decimal('critical_stock_threshold', 10, 2)->nullable();
            $table->decimal('max_stock_level', 10, 2)->nullable();
            $table->decimal('reorder_point', 10, 2)->nullable();
            $table->integer('expiry_alert_days')->default(30);
            $table->integer('min_shelf_life_days')->default(90);
            
            // Notification Settings
            $table->boolean('email_alerts')->default(true);
            $table->boolean('sms_alerts')->default(false);
            $table->enum('alert_frequency', ['immediate', 'daily', 'weekly'])->default('immediate');
            $table->string('preferred_supplier')->nullable();
            
            // Inventory Settings
            $table->boolean('batch_tracking')->default(true);
            $table->boolean('location_tracking')->default(true);
            $table->boolean('auto_reorder')->default(false);
            
            // Display Settings
            $table->string('custom_name')->nullable();
            $table->enum('color_code', ['default', 'red', 'orange', 'yellow', 'green', 'blue', 'purple'])->default('default');
            $table->enum('priority', ['low', 'normal', 'high', 'critical'])->default('normal');
            
            $table->timestamps();
            
            // Unique constraint to prevent duplicate settings
            $table->unique(['service_id', 'product_id', 'product_forme'], 'service_product_settings_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_product_settings');
    }
};
