<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGraDocumentoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('gra_documento', function (Blueprint $table){
            $table->integer('doc_pes_id')->unsigned();
            $table->integer('doc_tpd_id')->unsigned();
            $table->string('doc_conteudo',150);
            $table->date('doc_dataexpedicao')->nullable();
            $table->string('doc_orgao')->nullable();
            $table->string('doc_observacao')->nullable();           

            $table->timestamps();

            $table->foreign('doc_pes_id')->references('pes_id')->on('gra_pessoa');
            $table->foreign('doc_tpd_id')->references('tpd_id')->on('gra_tipodocumento');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('gra_documento');
    }
}