<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
       try{
           DB::beginTransaction();

           DB::table('oauth_clients')
               ->insert([
                   'id' => 1,
                   'name' => 'JNM Password Grant Client',
                   'secret' => 'XAi3SQMv0SPBxSE73KD0gHNYW6FFHlx0ZUXxESd7', // default for the environment
                   'provider' => 'users',
                   'redirect' => '/',
                   'personal_access_client' => false,
                   'password_client' => true,
                   'revoked' => false,
               ]);

           DB::table('oauth_clients')
               ->insert([
                   'id' => 2,
                   'name' => 'JNM Personal Access Client',
                   'secret' => '8X2FMnlfar9bPaFrFnsbGpvZZGDFyRMQ5QKuT0we', // default for the environment
                   'provider' => null,
                   'redirect' => '/',
                   'personal_access_client' => true,
                   'password_client' => false,
                   'revoked' => false,
               ]);

           DB::commit();
       }catch (Exception $exception){
           DB::rollBack();
           dd($exception->getMessage());
       }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
