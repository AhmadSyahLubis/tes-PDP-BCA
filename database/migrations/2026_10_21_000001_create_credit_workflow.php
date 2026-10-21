<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('areas', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
        });
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('area_id')->constrained()->restrictOnDelete();
            $table->string('name', 100);
            $table->unique(['area_id', 'name']);
        });
        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name', 100);
        });
        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('menus')->nullOnDelete();
            $table->string('name', 100);
            $table->string('url', 100)->unique();
            $table->boolean('is_active')->default(true);
        });
        Schema::create('group_menu', function (Blueprint $table) {
            $table->foreignId('group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('menu_id')->constrained()->cascadeOnDelete();
            $table->primary(['group_id', 'menu_id']);
        });
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 100)->unique();
            $table->boolean('is_active')->default(true);
            $table->foreignId('group_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
        });
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->char('nik', 16)->unique();
            $table->string('name', 100);
            $table->date('birth_date');
            $table->string('marital_status', 20);
            $table->string('spouse_name', 100)->nullable();
            $table->string('phone', 25);
            $table->text('address');
            $table->timestamps();
        });
        Schema::create('credit_applications', function (Blueprint $table) {
            $table->id();
            $table->string('application_no', 30)->unique();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('marketing_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('dealer_name', 100);
            $table->string('vehicle_brand', 100);
            $table->string('vehicle_model', 100);
            $table->string('vehicle_type', 100);
            $table->string('vehicle_color', 50);
            $table->decimal('vehicle_price', 15, 2);
            $table->decimal('down_payment', 15, 2);
            $table->unsignedSmallInteger('tenor_months');
            $table->decimal('monthly_installment', 15, 2);
            $table->string('status', 20)->default('submitted');
            $table->text('decision_note')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });
        Schema::create('application_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('credit_application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->string('type', 30);
            $table->string('original_name');
            $table->string('path');
            $table->string('mime_type', 100);
            $table->timestamps();
            $table->unique(['credit_application_id', 'type']);
        });
        Schema::create('application_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('credit_application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->string('action', 30);
            $table->text('note')->nullable();
            $table->timestamp('created_at');
        });
        Schema::create('disbursements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('credit_application_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('processed_by')->constrained('users')->restrictOnDelete();
            $table->decimal('amount', 15, 2);
            $table->date('disbursed_on');
            $table->string('reference_no', 100)->unique();
            $table->timestamps();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::unprepared(<<<'SQL'
CREATE VIEW active_users AS
SELECT u.id, u.username, u.name, g.name AS group_name, b.name AS branch_name, a.name AS area_name
FROM users u
JOIN groups g ON g.id = u.group_id
JOIN branches b ON b.id = u.branch_id
JOIN areas a ON a.id = b.area_id
WHERE u.is_active = true;

CREATE VIEW user_menu_access AS
SELECT u.id AS user_id, u.username, g.name AS group_name, m.id AS menu_id,
       m.name AS menu_name, m.url, m.parent_id
FROM users u
JOIN groups g ON g.id = u.group_id
JOIN group_menu gm ON gm.group_id = g.id
JOIN menus m ON m.id = gm.menu_id
WHERE u.is_active = true AND m.is_active = true;

CREATE VIEW active_user_count_per_area AS
SELECT a.id AS area_id, a.name AS area_name, COUNT(u.id) AS active_user_count
FROM areas a
LEFT JOIN branches b ON b.area_id = a.id
LEFT JOIN users u ON u.branch_id = b.id AND u.is_active = true
GROUP BY a.id, a.name;
SQL);
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::unprepared('DROP VIEW IF EXISTS active_user_count_per_area, user_menu_access, active_users');
        }
        Schema::dropIfExists('disbursements');
        Schema::dropIfExists('application_events');
        Schema::dropIfExists('application_documents');
        Schema::dropIfExists('credit_applications');
        Schema::dropIfExists('customers');
        Schema::table('users', fn (Blueprint $table) => $table->dropForeign(['created_by']));
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('group_id');
            $table->dropConstrainedForeignId('branch_id');
            $table->dropColumn(['username', 'is_active', 'created_by']);
        });
        Schema::dropIfExists('group_menu');
        Schema::dropIfExists('menus');
        Schema::dropIfExists('groups');
        Schema::dropIfExists('branches');
        Schema::dropIfExists('areas');
    }
};
