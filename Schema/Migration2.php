<?php

namespace Cissee\Webtrees\Module\Gov4Webtrees\Schema;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Schema\Blueprint;
use Fisharebest\Webtrees\Schema\MigrationInterface;

/**
 * Upgrade the database schema from version 2 to version 3.
 */
class Migration2 implements MigrationInterface {

   
  public function upgrade(): void {

    //remove obsolete table gov_descriptions (obsoleted at an earlier date)
    if (DB::schema()->hasTable('gov_descriptions')) {
      DB::schema()->drop('gov_descriptions');
    }
    
    //add 'sticky' column
    if (!DB::schema()->hasColumn('gov_labels', 'sticky')) {
      DB::schema()->table('gov_labels', static function (Blueprint $table): void {
          $table->boolean('sticky')->default(false)->after('to');
      });
    }
    
    if (!DB::schema()->hasColumn('gov_parents', 'sticky')) {
      DB::schema()->table('gov_parents', static function (Blueprint $table): void {
          $table->boolean('sticky')->default(false)->after('to');
      });
    }
    
    if (!DB::schema()->hasColumn('gov_types', 'sticky')) {
      DB::schema()->table('gov_types', static function (Blueprint $table): void {
          $table->boolean('sticky')->default(false)->after('to');
      });
    }
  }
}
