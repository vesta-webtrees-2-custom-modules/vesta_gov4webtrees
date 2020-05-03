<?php

namespace Cissee\Webtrees\Module\Gov4Webtrees\Schema;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Schema\Blueprint;
use Fisharebest\Webtrees\Schema\MigrationInterface;

/**
 * Upgrade the database schema from version 0 (empty database) to version 1.
 */
class Migration0 implements MigrationInterface {

  public function upgrade(): void {

    if (!DB::schema()->hasTable('gov_ids')) {
      DB::schema()->create('gov_ids', function (Blueprint $table): void {
        $table->integer('id', true);
        $table->text('name');
        $table->string('gov_id', 32);
      });
    }

    if (!DB::schema()->hasTable('gov_objects')) {
      DB::schema()->create('gov_objects', function (Blueprint $table): void {
        $table->string('gov_id', 32);
        $table->double('lat')->nullable();
        $table->double('lon')->nullable();
        $table->bigInteger('version', false, true);
        $table->primary(['gov_id']);
      });
    }

    if (!DB::schema()->hasTable('gov_labels')) {
      DB::schema()->create('gov_labels', function (Blueprint $table): void {
        $table->integer('key', true);
        $table->string('gov_id', 32);
        $table->string('label', 128);
        $table->string('language', 32)->nullable();
        $table->mediumInteger('from')->nullable();
        $table->mediumInteger('to')->nullable();
        $table->index(['gov_id']);
      });
    }

    if (!DB::schema()->hasTable('gov_types')) {
      DB::schema()->create('gov_types', function (Blueprint $table): void {
        $table->integer('key', true);
        $table->string('gov_id', 32);
        $table->integer('type');
        $table->mediumInteger('from')->nullable();
        $table->mediumInteger('to')->nullable();
        $table->index(['gov_id']);
      });
    }

    if (!DB::schema()->hasTable('gov_parents')) {
      DB::schema()->create('gov_parents', function (Blueprint $table): void {
        $table->integer('key', true);
        $table->string('gov_id', 32);
        $table->string('parent_id', 32);
        $table->mediumInteger('from')->nullable();
        $table->mediumInteger('to')->nullable();
        $table->index(['gov_id']);
      });
    }

    if (!DB::schema()->hasTable('gov_descriptions')) {
      DB::schema()->create('gov_descriptions', function (Blueprint $table): void {
        $table->integer('type');
        $table->string('lang', 8);
        $table->string('description', 128);
        $table->mediumInteger('from')->nullable();
        $table->mediumInteger('to')->nullable();
        $table->primary(['type', 'lang']);
      });
    }
  }

}
