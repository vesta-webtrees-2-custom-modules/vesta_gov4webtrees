<?php

namespace Cissee\Webtrees\Module\Gov4Webtrees\Model;

use Fisharebest\Localization\Locale\LocaleInterface;

class GovHierarchyRequestArgs {

    protected LocaleInterface $locale;
    protected array $languages;
    protected array $languagesForTypes;
    protected bool $compactDisplay;
    protected int $withInternalLinks;
    protected bool $showSettlements;
    protected bool $showOrganizational;

    public function locale(): LocaleInterface {
        return $this->locale;
    }
    
    public function languages(): array {
        return $this->languages;
    }
    
    public function languagesForTypes(): array {
        return $this->languagesForTypes;
    }
    
    public function compactDisplay(): bool {
        return $this->compactDisplay;
    }

    public function withInternalLinks(): int {
        return $this->withInternalLinks;
    }

    public function showSettlements(): bool {
        return $this->showSettlements;
    }

    public function showOrganizational(): bool {
        return $this->showOrganizational;
    }

    public function __construct(
        LocaleInterface $locale,
        array $languages,
        array $languagesForTypes,
        bool $compactDisplay,
        int $withInternalLinks,
        bool $showSettlements,
        bool $showOrganizational) {

        $this->locale = $locale;
        $this->languages = $languages;
        $this->languagesForTypes = $languagesForTypes;
        $this->compactDisplay = $compactDisplay;
        $this->withInternalLinks = $withInternalLinks;
        $this->showSettlements = $showSettlements;
        $this->showOrganizational = $showOrganizational;
    }


}
