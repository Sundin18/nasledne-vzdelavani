<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Categories available right after installation.
     *
     * @var array<int, string>
     */
    private array $defaultCategories = [
        'Obecné',
        'Životní pojištění',
        'Neživotní pojištění',
        'Spotřebitelské úvěry',
        'Investice',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->dateTime('start')->index();
            $table->dateTime('end');
            $table->string('name');
            $table->string('place');
            $table->unsignedInteger('capacity');
            $table->longText('content')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
        });

        Schema::create('category_course', function (Blueprint $table) {
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();

            $table->primary(['category_id', 'course_id']);
        });

        Schema::create('course_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('attended')->default(false);
            $table->timestamps();

            $table->unique(['course_id', 'user_id']);
        });

        DB::table('categories')->insert(
            array_map(fn (string $name) => ['name' => $name], $this->defaultCategories),
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_registrations');
        Schema::dropIfExists('category_course');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('courses');
    }
};
