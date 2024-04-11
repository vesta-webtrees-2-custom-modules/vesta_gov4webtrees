<?php

namespace Cissee\Webtrees\Module\Gov4Webtrees\Model;

class GovHierarchy {

    protected ?string $govId;
    protected JulianDayInterval $interval;
    protected array $adjustedLanguages;
    protected string $labelsHtml;
    protected string $typesHtml;
    protected bool $hasLocalModifications;

    public function govId(): ?string {
        return $this->govId;
    }

    public function interval(): JulianDayInterval {
        return $this->interval;
    }

    public function adjustedLanguages(): ?array {
        return $this->adjustedLanguages;
    }

    public function labelsHtml(): string {
        return $this->labelsHtml;
    }

    public function typesHtml(): string {
        return $this->typesHtml;
    }

    public function hasLocalModifications(): bool {
        return $this->hasLocalModifications;
    }

    public function __construct(
        ?string $govId,
        JulianDayInterval $interval,
        ?array $adjustedLanguages,
        string $labelsHtml,
        string $typesHtml,
        bool $hasLocalModifications) {

        $this->govId = $govId;
        $this->interval = $interval;
        $this->adjustedLanguages = $adjustedLanguages;
        $this->labelsHtml = $labelsHtml;
        $this->typesHtml = $typesHtml;
        $this->hasLocalModifications = $hasLocalModifications;
    }

    public static function empty(
        JulianDayInterval $interval,
        array $adjustedLanguages): GovHierarchy {

        return new GovHierarchy(null, $interval, $adjustedLanguages, '', '', false);
    }
}
