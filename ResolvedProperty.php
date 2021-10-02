<?php

namespace Cissee\Webtrees\Module\Gov4Webtrees;

class ResolvedProperty {

  private $prop;
  private $sticky;
  
  public function getProp() {
    return $this->prop;
  }
  
  public function getSticky(): bool {
    return $this->sticky;
  }
  
  public function __construct(
          $prop, 
          bool $sticky) {
    
    $this->prop = $prop;
    $this->sticky = $sticky;
  }

}
