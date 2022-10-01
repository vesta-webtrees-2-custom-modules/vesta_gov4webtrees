<?php

namespace Cissee\Webtrees\Module\Gov4Webtrees\Model;

use Vesta\Model\GedcomDateInterval;

class JulianDayInterval {

    private $from;
    private $toExclusively;

    public function getFrom(): ?int {
        return $this->from;
    }

    public function getToExclusively(): ?int {
        return $this->toExclusively;
    }

    public function __construct(
        ?int $from,
        ?int $toExclusively) {

        $this->from = $from;
        $this->toExclusively = $toExclusively;
        
        //sanity check
        if (($from ?? PHP_INT_MIN) >= ($toExclusively ?? PHP_INT_MAX)) {
            throw new \Exception();
        }
    }
    
    public static function open(): JulianDayInterval {
        return new JulianDayInterval(null, null);
    }

    public static function single(int $from): JulianDayInterval {
        return new JulianDayInterval($from, $from+1);
    }
    
    public function asGedcomDateInterval(): GedcomDateInterval {
        $toExclusively = $this->getToExclusively();
        return new GedcomDateInterval($this->getFrom(), ($toExclusively === null)?null:$toExclusively-1);
    }
    
    /**
     * 
     * @param JulianDayInterval $other
     * @return JulianDayInterval|null merged interval, if they are directly adjacent; null otherwise
     */
    public function append(
        JulianDayInterval $other): ?JulianDayInterval {
        
        if (($this->getToExclusively() !== null) && ($this->getToExclusively() === $other->getFrom())) {
            return new JulianDayInterval($this->getFrom(), $other->getToExclusively());
        }
        
        return null;
    }
    
    public function overlaps(
        JulianDayInterval $other): bool {
        
        $thisFrom = $this->getFrom() ?? PHP_INT_MIN;
        $thisToExclusively = $this->getToExclusively() ?? PHP_INT_MAX;
        
        $otherFrom = $other->getFrom() ?? PHP_INT_MIN;
        $otherToExclusively = $other->getToExclusively() ?? PHP_INT_MAX;
        
        if ($thisFrom < $otherFrom) {
            return $thisToExclusively > $otherFrom;
        }
        
        if ($thisFrom === $otherFrom) {
            return true;
        }
        
        return $otherToExclusively > $thisFrom;
    }
    
    public function includes(
        JulianDayInterval $other): bool {
        
        $thisFrom = $this->getFrom() ?? PHP_INT_MIN;
        $thisToExclusively = $this->getToExclusively() ?? PHP_INT_MAX;
        
        $otherFrom = $other->getFrom() ?? PHP_INT_MIN;
        $otherToExclusively = $other->getToExclusively() ?? PHP_INT_MAX;
        
        return ($thisFrom <= $otherFrom) &&
            ($thisToExclusively >= $otherToExclusively);
    }
}
