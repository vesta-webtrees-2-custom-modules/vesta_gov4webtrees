<?php

namespace Cissee\Webtrees\Module\Gov4Webtrees\WhatsNew;

use Cissee\WebtreesExt\WhatsNew\WhatsNewInterface;
use Fisharebest\Webtrees\I18N;

class WhatsNew0 implements WhatsNewInterface {
  
  public function getMessage(): string {
    return I18N::translate("Vesta Gov4Webtrees: The displayed GOV hierarchy now uses place names and links from webtrees where possible. You can switch back to the traditional display via the module configuration.");
  }
}
