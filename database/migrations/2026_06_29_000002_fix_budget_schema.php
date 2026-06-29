<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finance_budgets', function (Blueprint $table) {
            $table->foreignId('account_id')->nullable()->constrained('finance_accounts')->nullOnDelete()->after('name');
            $table->decimal('budgeted_amount', 15, 2)->default(0)->after('status');
            $table->decimal('actual_amount', 15, 2)->default(0)->after('budgeted_amount');
            $table->string('code')->nullable()->after('id');
            $table->date('start_date')->nullable()->after('actual_amount');
            $table->date('end_date')->nullable()->after('start_date');
            $table->unsignedTinyInteger('period_number')->nullable()->after('period');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('finance_budgets', function (Blueprint $table) {
            $table->dropForeign(['account_id']);
            $table->dropColumn([
                'account_id', 'budgeted_amount', 'actual_amount', 'code',
                'start_date', 'end_date', 'period_number', 'deleted_at',
            ]);
        });
    }
};
