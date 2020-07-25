<?php

namespace Cissee\Webtrees\Module\Gov4Webtrees\Schema;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Schema\Blueprint;
use Fisharebest\Webtrees\Schema\MigrationInterface;

/**
 * Upgrade the database schema from version 1 to version 2.
 */
class Migration1 implements MigrationInterface {

  //TODO: Migration2: remove obsolete table gov_descriptions!
   
  public function upgrade(): void {

    if (DB::schema()->hasColumn('gov_ids', 'type') || DB::schema()->hasColumn('gov_ids', 'version')) {
      if (!DB::schema()->hasTable('gov_ids_temp')) {
        DB::schema()->create('gov_ids_temp', function (Blueprint $table): void {
          $table->integer('id', true);
          $table->text('name');
          $table->string('gov_id', 32);
        });
      } else {
        DB::table('gov_ids_temp')->delete();
      }

      $datas = DB::table('gov_ids')->select(['id', 'name', 'gov_id'])->get();
      $inserts = array();
      foreach ($datas as $data) {
        $inserts[] = ['id' => $data->id, 
                 'name' => $data->name, 
                 'gov_id' => $data->gov_id];
      }
      DB::table('gov_ids_temp')->insert($inserts);
      DB::schema()->drop('gov_ids');
      DB::schema()->rename('gov_ids_temp', 'gov_ids');
    }
  }
}
