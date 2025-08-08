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
        Schema::create('archive_dm_skill_ideas', function (Blueprint $table) {
            $table->id();
            $table->string('members_have_skills');
            $table->string('type_skills')->nullable();
            $table->string('type_skills_other')->nullable();
            $table->string('skills_want_learn')->nullable();
            $table->foreignId('submission_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('archive_dm_skill_ideas');
    }
};
