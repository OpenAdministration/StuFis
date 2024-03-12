<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('auslagen', static function (Blueprint $table) {
            $table->comment('');
            $table->integer('id', true);
            $table->integer('projekt_id')->index('projekt_id');
            $table->string('name_suffix')->nullable();
            $table->string('state');
            $table->string('ok-belege')->default('');
            $table->string('ok-hv')->default('');
            $table->string('ok-kv')->default('');
            $table->string('payed')->default('');
            $table->string('rejected')->default('');
            $table->string('zahlung-iban', 1023);
            $table->string('zahlung-name', 127);
            $table->string('zahlung-vwzk', 127);
            $table->string('address', 1023)->default('');
            $table->dateTime('last_change')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->string('last_change_by')->default('');
            $table->string('etag');
            $table->integer('version')->default(1);
            $table->string('created')->default('');
        });

        Schema::create('belege', static function (Blueprint $table) {
            $table->comment('');
            $table->integer('id', true);
            $table->integer('auslagen_id')->index('auslagen_id');
            $table->string('short', 45)->nullable();
            $table->dateTime('created_on')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->dateTime('datum')->nullable();
            $table->string('beschreibung', 65535);
            $table->integer('file_id')->nullable();
        });

        Schema::create('beleg_posten', static function (Blueprint $table) {
            $table->comment('');
            $table->integer('id', true);
            $table->integer('beleg_id')->index('beleg_id');
            $table->integer('short');
            $table->integer('projekt_posten_id');
            $table->decimal('ausgaben', 10, 2)->default(0);
            $table->decimal('einnahmen', 10, 2)->default(0);

            $table->unique(['short', 'beleg_id'], 'short');
        });

        Schema::create('booking', static function (Blueprint $table) {
            $table->comment('');
            $table->integer('id', true);
            $table->integer('titel_id')->index('titel_id');
            $table->integer('kostenstelle');
            $table->integer('zahlung_id');
            $table->integer('zahlung_type');
            $table->integer('beleg_id');
            $table->string('beleg_type', 16);
            $table->unsignedBigInteger('user_id', );
            $table->dateTime('timestamp')->default('CURRENT_TIMESTAMP');
            $table->string('comment', 2048);
            $table->float('value', 10, 0);
            $table->integer('canceled')->default(0);

            $table->index(['zahlung_id', 'zahlung_type'], 'zahlung_id');
        });

        Schema::create('booking_instruction', static function (Blueprint $table) {
            $table->comment('');
            $table->integer('id');
            $table->integer('zahlung');
            $table->integer('zahlung_type');
            $table->integer('beleg');
            $table->string('beleg_type', 16);
            $table->unsignedBigInteger('by_user')->index('by_user');
            $table->boolean('done')->default(false);
        });

        Schema::create('comments', static function (Blueprint $table) {
            $table->comment('');
            $table->integer('id', true);
            $table->integer('target_id');
            $table->string('target', 64)->nullable();
            $table->dateTime('timestamp')->default('CURRENT_TIMESTAMP');
            $table->string('creator', 128);
            $table->string('creator_alias', 256);
            $table->string('text', 65535);
            $table->tinyInteger('type')->default(0)->comment('0 = comment, 1 = state_change, 2 = admin only');
        });

        Schema::create('extern_data', static function (Blueprint $table) {
            $table->comment('');
            $table->integer('id', true);
            $table->integer('vorgang_id');
            $table->integer('extern_id')->index('extern_id');
            $table->integer('titel_id');
            $table->dateTime('date')->nullable();
            $table->integer('by_user')->nullable();
            $table->decimal('value', 10, 2)->nullable();
            $table->string('description', 65535)->nullable();
            $table->string('ok-hv', 63)->nullable();
            $table->string('ok-kv', 63)->nullable();
            $table->dateTime('frist')->nullable();
            $table->boolean('flag_vorkasse')->nullable()->default(false);
            $table->boolean('flag_bewilligungsbescheid')->nullable()->default(false);
            $table->boolean('flag_pruefbescheid')->nullable()->default(false);
            $table->boolean('flag_rueckforderung')->nullable()->default(false);
            $table->boolean('flag_mahnung')->nullable()->default(false);
            $table->boolean('flag_done')->nullable()->default(false);
            $table->string('state_instructed', 63)->nullable();
            $table->string('state_payed', 63)->nullable();
            $table->string('state_booked', 63)->nullable();
            $table->integer('ref_file_id')->nullable();
            $table->boolean('flag_widersprochen')->nullable()->default(false);
            $table->dateTime('widerspruch_date')->nullable();
            $table->integer('widerspruch_file_id')->nullable();
            $table->string('widerspruch_text', 65535)->nullable();
            $table->string('etag');

            $table->unique(['vorgang_id', 'extern_id'], 'vorgang_id');
        });

        Schema::create('extern_meta', static function (Blueprint $table) {
            $table->comment('');
            $table->integer('id', true);
            $table->string('projekt_name', 511);
            $table->date('projekt_von')->nullable();
            $table->date('projekt_bis')->nullable();
            $table->string('contact_mail', 127)->nullable();
            $table->string('contact_name', 128)->nullable();
            $table->string('contact_phone', 32)->nullable();
            $table->string('org_address')->nullable();
            $table->string('org_name', 127)->nullable();
            $table->string('org_mail', 127)->nullable();
            $table->string('zahlung_empf', 127)->nullable();
            $table->string('zahlung_iban', 45)->nullable();
            $table->string('beschluss_nr', 15);
            $table->date('beschluss_datum');
            $table->decimal('beschluss_summe', 8, 2);
            $table->decimal('beschluss_vorkasse', 8, 2);
        });

        Schema::create('filedata', static function (Blueprint $table) {
            $table->comment('');
            $table->integer('id', true);
            $table->binary('data')->nullable();
            $table->string('diskpath', 511)->nullable();
        });

        Schema::create('fileinfo', static function (Blueprint $table) {
            $table->comment('');
            $table->integer('id', true);
            $table->string('link', 127);
            $table->dateTime('added_on')->default('CURRENT_TIMESTAMP');
            $table->string('hashname');
            $table->string('filename');
            $table->integer('size')->default(0);
            $table->string('fileextension', 45)->default('');
            $table->string('mime', 256)->nullable();
            $table->string('encoding', 45)->nullable();
            $table->integer('data')->nullable()->index('data');
        });

        Schema::create('haushaltsgruppen', static function (Blueprint $table) {
            $table->comment('');
            $table->integer('id', true);
            $table->integer('hhp_id')->index('hhp_id');
            $table->string('gruppen_name', 128);
            $table->boolean('type');
        });

        Schema::create('haushaltsplan', static function (Blueprint $table) {
            $table->comment('');
            $table->integer('id', true);
            $table->date('von')->nullable();
            $table->date('bis')->nullable();
            $table->string('state', 64);
        });

        Schema::create('haushaltstitel', static function (Blueprint $table) {
            $table->comment('');
            $table->integer('id', true);
            $table->integer('hhpgruppen_id')->index('hhpgruppen_id');
            $table->string('titel_name', 128);
            $table->string('titel_nr', 10);
            $table->float('value', 10, 0);
        });

        Schema::create('konto', static function (Blueprint $table) {
            $table->comment('');
            $table->integer('id');
            $table->integer('konto_id')->index('konto_id');
            $table->date('date');
            $table->date('valuta');
            $table->string('type', 128);
            $table->string('empf_iban', 40)->default('');
            $table->string('empf_bic', 11)->nullable()->default('');
            $table->string('empf_name', 128)->default('');
            $table->float('primanota', 10, 0)->default(0);
            $table->decimal('value', 10, 2);
            $table->decimal('saldo', 10, 2);
            $table->string('zweck', 512);
            $table->string('comment', 128)->default('');
            $table->integer('gvcode')->default(0);
            $table->string('customer_ref', 128)->nullable();

            $table->primary(['id', 'konto_id']);
        });

        Schema::create('konto_bank', static function (Blueprint $table) {
            $table->comment('');
            $table->integer('id', true);
            $table->string('url', 256);
            $table->integer('blz');
            $table->string('name', 256);
        });

        Schema::create('konto_credentials', static function (Blueprint $table) {
            $table->comment('');
            $table->integer('id', true);
            $table->string('name', 63);
            $table->integer('bank_id')->index('bank_id');
            $table->unsignedBigInteger('owner_id')->index('owner_id');
            $table->string('bank_username', 32);
            $table->integer('tan_mode')->nullable();
            $table->string('tan_mode_name', 63)->nullable();
            $table->string('tan_medium_name', 63)->nullable();
        });

        Schema::create('konto_type', static function (Blueprint $table) {
            $table->comment('');
            $table->integer('id', true);
            $table->string('name', 32);
            $table->string('short', 2);
            $table->date('sync_from')->nullable();
            $table->date('sync_until')->nullable();
            $table->string('iban', 32)->nullable();
            $table->string('last_sync', 0)->nullable();
        });

        Schema::create('projekte', static function (Blueprint $table) {
            $table->comment('');
            $table->integer('id', true);
            $table->unsignedBigInteger('creator_id')->index('creator_id');
            $table->dateTime('createdat');
            $table->dateTime('lastupdated');
            $table->integer('version')->default(1);
            $table->string('state', 32);
            $table->unsignedBigInteger('stateCreator_id')->index('stateCreator_id');
            $table->string('name', 128)->nullable();
            $table->string('responsible', 128)->nullable()->comment('EMAIL');
            $table->string('org', 64)->nullable();
            $table->string('org-mail', 128)->nullable();
            $table->string('protokoll', 256)->nullable();
            $table->string('recht', 64)->nullable();
            $table->string('recht-additional', 128)->nullable();
            $table->date('date-start')->nullable();
            $table->date('date-end')->nullable();
            $table->string('beschreibung', 65535)->nullable();
        });

        Schema::create('projektposten', static function (Blueprint $table) {
            $table->comment('');
            $table->integer('id');
            $table->integer('projekt_id')->index('projekt_id');
            $table->integer('titel_id')->nullable();
            $table->float('einnahmen', 10, 0);
            $table->float('ausgaben', 10, 0);
            $table->string('name', 128);
            $table->string('bemerkung', 256);

            $table->primary(['id', 'projekt_id']);
        });

        Schema::table('auslagen', static function (Blueprint $table) {
            $table->foreign(['projekt_id'], 'dev__auslagen_ibfk_1')->references(['id'])->on('projekte');
        });

        Schema::table('belege', static function (Blueprint $table) {
            $table->foreign(['auslagen_id'], 'dev__belege_ibfk_1')->references(['id'])->on('auslagen');
        });

        Schema::table('beleg_posten', static function (Blueprint $table) {
            $table->foreign(['beleg_id'], 'dev__beleg_posten_ibfk_1')->references(['id'])->on('belege');
        });

        Schema::table('booking', static function (Blueprint $table) {
            $table->foreign(['zahlung_id', 'zahlung_type'], 'dev__booking_ibfk_1')->references(['id', 'konto_id'])->on('konto');
            $table->foreign(['titel_id'], 'dev__booking_ibfk_2')->references(['id'])->on('haushaltstitel');
            $table->foreign(['user_id'], 'dev__booking_ibfk_3')->references(['id'])->on('user');
        });

        Schema::table('booking_instruction', static function (Blueprint $table) {
            $table->foreign(['by_user'], 'dev__booking_instruction_ibfk_1')->references(['id'])->on('user');
        });

        Schema::table('extern_data', static function (Blueprint $table) {
            $table->foreign(['extern_id'], 'dev__extern_data_ibfk_1')->references(['id'])->on('extern_meta');
        });

        Schema::table('fileinfo', static function (Blueprint $table) {
            $table->foreign(['data'], 'dev__fileinfo_ibfk_1')->references(['id'])->on('filedata');
        });

        Schema::table('haushaltsgruppen', static function (Blueprint $table) {
            $table->foreign(['hhp_id'], 'dev__haushaltsgruppen_ibfk_1')->references(['id'])->on('haushaltsplan');
        });

        Schema::table('haushaltstitel', static function (Blueprint $table) {
            $table->foreign(['hhpgruppen_id'], 'dev__haushaltstitel_ibfk_1')->references(['id'])->on('haushaltsgruppen');
        });

        Schema::table('konto', static function (Blueprint $table) {
            $table->foreign(['konto_id'], 'dev__konto_ibfk_1')->references(['id'])->on('konto_type');
        });

        Schema::table('konto_credentials', static function (Blueprint $table) {
            $table->foreign(['owner_id'], 'dev__konto_credentials_ibfk_1')->references(['id'])->on('user');
            $table->foreign(['bank_id'], 'dev__konto_credentials_ibfk_2')->references(['id'])->on('konto_bank');
        });

        Schema::table('projekte', static function (Blueprint $table) {
            $table->foreign(['creator_id'], 'dev__projekte_ibfk_1')->references(['id'])->on('user');
            $table->foreign(['stateCreator_id'], 'dev__projekte_ibfk_2')->references(['id'])->on('user');
        });

        Schema::table('projektposten', static function (Blueprint $table) {
            $table->foreign(['projekt_id'], 'dev__projektposten_ibfk_1')->references(['id'])->on('projekte');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('projektposten', static function (Blueprint $table) {
            $table->dropForeign('dev__projektposten_ibfk_1');
        });

        Schema::table('projekte', static function (Blueprint $table) {
            $table->dropForeign('dev__projekte_ibfk_1');
            $table->dropForeign('dev__projekte_ibfk_2');
        });

        Schema::table('konto_credentials', static function (Blueprint $table) {
            $table->dropForeign('dev__konto_credentials_ibfk_1');
            $table->dropForeign('dev__konto_credentials_ibfk_2');
        });

        Schema::table('konto', static function (Blueprint $table) {
            $table->dropForeign('dev__konto_ibfk_1');
        });

        Schema::table('haushaltstitel', static function (Blueprint $table) {
            $table->dropForeign('dev__haushaltstitel_ibfk_1');
        });

        Schema::table('haushaltsgruppen', static function (Blueprint $table) {
            $table->dropForeign('dev__haushaltsgruppen_ibfk_1');
        });

        Schema::table('fileinfo', static function (Blueprint $table) {
            $table->dropForeign('dev__fileinfo_ibfk_1');
        });

        Schema::table('extern_data', static function (Blueprint $table) {
            $table->dropForeign('dev__extern_data_ibfk_1');
        });

        Schema::table('booking_instruction', static function (Blueprint $table) {
            $table->dropForeign('dev__booking_instruction_ibfk_1');
        });

        Schema::table('booking', static function (Blueprint $table) {
            $table->dropForeign('dev__booking_ibfk_1');
            $table->dropForeign('dev__booking_ibfk_2');
            $table->dropForeign('dev__booking_ibfk_3');
        });

        Schema::table('beleg_posten', static function (Blueprint $table) {
            $table->dropForeign('dev__beleg_posten_ibfk_1');
        });

        Schema::table('belege', static function (Blueprint $table) {
            $table->dropForeign('dev__belege_ibfk_1');
        });

        Schema::table('auslagen', static function (Blueprint $table) {
            $table->dropForeign('dev__auslagen_ibfk_1');
        });

        Schema::dropIfExists('projektposten');

        Schema::dropIfExists('projekte');

        Schema::dropIfExists('personal_access_tokens');

        Schema::dropIfExists('konto_type');

        Schema::dropIfExists('konto_credentials');

        Schema::dropIfExists('konto_bank');

        Schema::dropIfExists('konto');

        Schema::dropIfExists('haushaltstitel');

        Schema::dropIfExists('haushaltsplan');

        Schema::dropIfExists('haushaltsgruppen');

        Schema::dropIfExists('fileinfo');

        Schema::dropIfExists('filedata');

        Schema::dropIfExists('extern_meta');

        Schema::dropIfExists('extern_data');

        Schema::dropIfExists('comments');

        Schema::dropIfExists('booking_instruction');

        Schema::dropIfExists('booking');

        Schema::dropIfExists('beleg_posten');

        Schema::dropIfExists('belege');

        Schema::dropIfExists('auslagen');

    }
};
