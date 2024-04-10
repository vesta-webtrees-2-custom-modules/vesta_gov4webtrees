<?php

namespace Cissee\Webtrees\Module\Gov4Webtrees\WhatsNew;

use Cissee\WebtreesExt\WhatsNew\WhatsNewInterface;

class WhatsNew2 implements WhatsNewInterface {

  public function getMessage(): string {
    return "Vesta Gov4Webtrees: Option to exclude objects of type 'confederation', such as the European Union.";
  }
}
