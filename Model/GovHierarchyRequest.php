<?php

namespace Cissee\Webtrees\Module\Gov4Webtrees\Model;

class GovHierarchyRequest {

    protected ?string $govId;
    protected ?int $type;
    protected bool $hasLocalModifications;
    protected JulianDayInterval $interval;

    public function govId(): ?string {
        return $this->govId;
    }
    
    public function type(): ?int {
        return $this->type;
    }
    
    public function hasLocalModifications(): bool {
        return $this->hasLocalModifications;
    }
    
    public function interval(): JulianDayInterval {
        return $this->interval;
    }

    public function __construct(
        ?string $govId,
        ?int $type,
        bool $hasLocalModifications,
        JulianDayInterval $interval) {

        $this->govId = $govId;
        $this->type = $type;
        $this->hasLocalModifications = $hasLocalModifications;
        $this->interval = $interval;
    }
}
