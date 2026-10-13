<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::table('sessions', function (Blueprint $table) {
            $table->string('user_id', 255)->nullable()->change();
        });
    }
    public function down() {
        Schema::table('sessions', function (Blueprint $table) {
            $table->bigInteger('user_id')->unsigned()->nullable()->change();
        });
    }
};