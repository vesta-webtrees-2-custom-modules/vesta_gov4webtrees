<?php

namespace Cissee\Webtrees\Module\Gov4Webtrees\WhatsNew;

use Cissee\WebtreesExt\WhatsNew\WhatsNewInterface;

class WhatsNew3 implements WhatsNewInterface {
  
  public function getMessage(): string {
    return "Vesta Gov4Webtrees: Option to define additional languages for place names - See the module configuration for details.";
  }
}
