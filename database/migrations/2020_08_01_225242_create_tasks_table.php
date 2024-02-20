<?php

use App\Models\Crm\TaskStatusEnum;
use App\Models\Crm\TaskVisibilityEnum;
use App\Models\Task\Task;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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
            $table->foreignId('union_id')->nullable()->constrained();
            $table->foreignId('unit_id')->nullable()->constrained();
            $table->foreignId('assigned_to')->nullable()->constrained('users');
            $table->string('title');
            $table->integer('status')->default(TaskStatusEnum::OPENED);
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
