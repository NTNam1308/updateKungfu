<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->integer('clan')->default(0);
            $table->integer('student')->default(1);
            $table->integer('forever')->default(1);
            $table->integer('limited')->default(1);
            $table->integer('type')->default(0);
            $table->dateTime('promotion_months')->nullable();
            $table->dateTime('activate_date')->nullable();
            $table->string('avatar')->nullable();
            $table->text('last_session')->nullable();
            $table->string('api_token', 80)
            ->unique()
            ->nullable()
            ->default(null);
            $table->string('menuroles');
            $table->string('status')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
