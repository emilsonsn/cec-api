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
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'sector_id',
                'company_position_id',
            ]);

            $table->integer('file_limit')->default(0)->after('remember_token');
            $table->boolean('is_admin')->default(false)->after('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->integer('sector_id')->default(0)->after('is_active');
            $table->integer('company_position_id')->default(0)->after('sector_id');

            $table->dropColumn([
                'is_admin',
                'file_limit',
            ]);
        });
    }
};
