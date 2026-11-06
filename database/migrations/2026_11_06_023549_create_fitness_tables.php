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
        // 1. 目標設定テーブル
        Schema::create('goals', function (Blueprint $table) {
            $table->id();
            $table->decimal('target_weight', 5, 2)->comment('目標体重(kg)');
            $table->decimal('target_body_fat', 4, 2)->nullable()->comment('目標体脂肪率(%)');
            $table->decimal('start_weight', 5, 2)->nullable()->comment('開始時体重(kg)');
            $table->decimal('start_body_fat', 4, 2)->nullable()->comment('開始時体脂肪率(%)');
            $table->date('target_date')->nullable()->comment('目標達成期日');
            $table->string('objective')->default('ボディメイク・減量')->comment('目的・フォーカス');
            $table->string('activity_level')->default('moderate')->comment('日常活動レベル');
            $table->boolean('is_active')->default(true)->comment('現在有効な目標');
            $table->timestamps();
        });

        // 2. 日別計測値テーブル
        Schema::create('daily_metrics', function (Blueprint $table) {
            $table->id();
            $table->date('record_date')->unique()->comment('計測日');
            $table->decimal('weight', 5, 2)->nullable()->comment('体重(kg)');
            $table->decimal('body_fat', 4, 2)->nullable()->comment('体脂肪率(%)');
            $table->text('raw_input')->nullable()->comment('ユーザー自然言語入力原文');
            $table->text('memo')->nullable()->comment('体調・食事・備考メモ');
            $table->json('ai_feedback')->nullable()->comment('Gemini AIアドバイスJSON');
            $table->timestamps();
        });

        // 3. ワークアウト・種目別実績テーブル
        Schema::create('workouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_metric_id')->constrained('daily_metrics')->cascadeOnDelete();
            $table->string('exercise_name')->comment('種目名 (例: ベンチプレス)');
            $table->string('target_muscle')->nullable()->comment('対象部位 (胸/背中/脚/肩/腕/腹)');
            $table->decimal('weight', 6, 2)->nullable()->comment('重量(kg)');
            $table->integer('reps')->nullable()->comment('回数');
            $table->integer('sets')->nullable()->comment('セット数');
            $table->decimal('rpe', 3, 1)->nullable()->comment('主観的強度 RPE 1-10');
            $table->text('notes')->nullable()->comment('セット別メモ');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workouts');
        Schema::dropIfExists('daily_metrics');
        Schema::dropIfExists('goals');
    }
};
