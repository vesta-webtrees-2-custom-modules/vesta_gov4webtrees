<?php

namespace Cissee\Webtrees\Module\Gov4Webtrees\Schema;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Schema\Blueprint;
use Fisharebest\Webtrees\Schema\MigrationInterface;

/**
 * Upgrade the database schema from version 1 to version 2.
 */
class Migration1 implements MigrationInterface {

  public function upgrade(): void {

    if (DB::schema()->hasColumn('gov_ids', 'type')) {
        DB::schema()->table('gov_ids', static function (Blueprint $table): void {
            $table->dropColumn('type');
            $table->dropColumn('version');
        });
    }
  }
}
