<?php

namespace Cissee\Webtrees\Module\Gov4Webtrees;

use Illuminate\Database\Capsule\Manager as DB;

//cf. AbstractModule
class MinimalModule {

  private $directory;
  private $settings;

  public function __construct($directory) {
    $this->directory = $directory;
  }

  public function name() {
    return basename($this->directory);
  }

  public function getSetting($setting_name, $default = null) {
    return DB::table('module_setting')
                    ->where('module_name', '=', $this->name())
                    ->where('setting_name', '=', $setting_name)
                    ->value('setting_value') ?? $default;
  }

  public function setSetting($setting_name, $setting_value) {
    DB::table('module_setting')->updateOrInsert([
        'module_name' => $this->name(),
        'setting_name' => $setting_name,
            ], [
        'setting_value' => $setting_value,
    ]);
  }

}
