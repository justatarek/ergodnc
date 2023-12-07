<?php

use App\Enums\OfficeApprovalStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('offices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->index();
            $table->foreignId('featured_image_id')->index()->nullable()->constrained('images')->nullOnDelete();
            $table->string('title');
            $table->text('description');
            $table->decimal('lat', 11, 8);
            $table->decimal('lng', 11, 8);
            $table->text('address_line1');
            $table->text('address_line2')->nullable();
            $table->tinyInteger('approval_status')->default(OfficeApprovalStatus::Pending->value);
            $table->boolean('is_hidden')->default(false);
            $table->integer('price_per_day');
            $table->integer('monthly_discount')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offices');
    }
};
