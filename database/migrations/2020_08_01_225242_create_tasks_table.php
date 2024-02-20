<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Kompo\Tasks\Models\Enums\TaskStatusEnum;
use Kompo\Tasks\Models\Enums\TaskVisibilityEnum;

class CreateTasksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tasks', function (Blueprint $table)
        {
            addMetaData($table);

            $table->foreignId('team_id')->constrained();
            $table->foreignId('assigned_to')->nullable()->constrained('users');
            $table->string('title');
            $table->integer('status')->default(TaskStatusEnum::OPEN);
            $table->tinyInteger('visibility')->default(TaskVisibilityEnum::MANAGERS);
            $table->tinyInteger('urgent')->default(0);
            $table->foreignId('closed_by')->nullable()->constrained('users');
            $table->dateTime('closed_at')->nullable();
            $table->integer('order')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tasks');
    }
}
