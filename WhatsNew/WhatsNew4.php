<?php

namespace Cissee\Webtrees\Module\Gov4Webtrees\WhatsNew;

use Cissee\WebtreesExt\WhatsNew\WhatsNewInterface;

class WhatsNew4 implements WhatsNewInterface {

  public function getMessage(): string {
    return "Vesta Gov4Webtrees: Option to modify all GOV data locally, via the control panel.";
  }
}
