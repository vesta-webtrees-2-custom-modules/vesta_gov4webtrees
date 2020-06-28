<?php

namespace Cissee\Webtrees\Module\Gov4Webtrees\WhatsNew;

use Cissee\WebtreesExt\WhatsNew\WhatsNewInterface;
use Fisharebest\Webtrees\I18N;

class WhatsNew1 implements WhatsNewInterface {
  
  public function getMessage(): string {
    return I18N::translate("Vesta Gov4Webtrees: The displayed GOV hierarchy now additionally links to webtrees places where possible. You can switch back to the classic display (and others) via the module configuration.");
  }
}
