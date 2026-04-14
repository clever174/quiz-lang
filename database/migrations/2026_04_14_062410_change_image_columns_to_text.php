<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_questions', function (Blueprint $table) {
            $table->text('image')->nullable()->change();
        });

        Schema::table('match_pairs', function (Blueprint $table) {
            $table->text('item_a_image')->nullable()->change();
            $table->text('item_b_image')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('quiz_questions', function (Blueprint $table) {
            $table->string('image')->nullable()->change();
        });

        Schema::table('match_pairs', function (Blueprint $table) {
            $table->string('item_a_image')->nullable()->change();
            $table->string('item_b_image')->nullable()->change();
        });
    }
};
