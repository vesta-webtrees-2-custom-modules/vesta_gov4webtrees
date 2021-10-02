<?php

namespace Cissee\Webtrees\Module\Gov4Webtrees;

class GovProperty extends ResolvedProperty {

  private $key;
  private $govId;
  private $prop;
  private $language;
  private $from;
  private $to;
  private $sticky;

  public function getKey(): int {
    return $this->key;
  }
  
  public function getGovId(): string {
    return $this->govId;
  }
  
  public function getProp() {
    return $this->prop;
  }

  public function getLanguage(): ?string {
    return $this->language;
  }

  public function getFrom(): ?int {
    return $this->from;
  }

  public function getTo(): ?int {
    return $this->to;
  }
  
  public function getSticky(): bool {
    return $this->sticky;
  }
  
  public function __construct(
          int $key,
          string $govId,
          $prop, 
          ?string $language, 
          ?int $from, 
          ?int $to,
          bool $sticky) {
    
    parent::__construct($prop, $sticky);
    
    $this->key = $key;
    $this->govId = $govId;
    $this->prop = $prop;
    $this->language = $language;
    $this->from = $from;
    $this->to = $to;
    $this->sticky = $sticky;
  }

  public function toString() {
    $str = " " . $this->getProp();
    if ($this->getFrom() != null) {
      $ymd = cal_from_jd($this->getFrom(), CAL_GREGORIAN);
      $str .= " (from " . $ymd["year"] . "-" . $ymd["month"] . "-" . $ymd["day"] . ")";
    }
    if ($this->getTo() != null) {
      $ymd = cal_from_jd($this->getTo(), CAL_GREGORIAN);
      $str .= " (to " . $ymd["year"] . "-" . $ymd["month"] . "-" . $ymd["day"] . ")";
    }

    return $str;
  }

}
