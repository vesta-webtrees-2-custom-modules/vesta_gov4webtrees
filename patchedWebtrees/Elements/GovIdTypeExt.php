<?php

declare(strict_types=1);

namespace Cissee\WebtreesExt\Elements;

use Cissee\Webtrees\Module\Gov4Webtrees\FunctionsGov;
use Fisharebest\Webtrees\Elements\AbstractElement;
use Fisharebest\Webtrees\Html;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Tree;
use function view;

/**
 * GOV ID, for TYPE_OF_LOCATION
 */
class GovIdTypeExt extends AbstractElement
{    
    protected $module;
    
    public function __construct($module, string $label, array $subtags = null)
    {
        parent::__construct($label, $subtags);
        $this->module = $module;
    }
    
    public function edit(string $id, string $name, string $value, Tree $tree): string
    {
        $values = $this->valuesGrouped();
        
        if ($values !== []) {
            $value = $this->canonical($value);

            // Ensure the current data is in the list.
            $isInList = false;
            foreach ($values as $k => $v) {
                if (array_key_exists($value, $v)) {
                    $isInList = true;
                    break;
                }
            }
            if (!$isInList) {
                $values = ["" => ["" => "", $value => $value]] + $values;
            } else {
                $values = ["" => ["" => ""]] + $values;
            }

            // We may use markup to display values, but not when editing them.
            $values = array_map(static fn (array $xx): array => array_map(static fn (string $x): string => strip_tags($x), $xx), $values);

            return view('components/select-with-optgroup', [
                'id'             => $id,
                'name'           => $name,
                'optionsByGroup' => $values,
                'selected'       => $value,
            ]);
        }

        $attributes = [
            'class'     => 'form-control',
            'dir'       => 'auto',
            'type'      => 'text',
            'id'        => $id,
            'name'      => $name,
            'value'     => $value,
            'maxlength' => static::MAXIMUM_LENGTH,
            'pattern'   => static::PATTERN,
        ];

        return '<input ' . Html::attributes($attributes) . ' />';
    }
    
    /**
     * A list of controlled values for this element
     *
     * @return array<array<string,string>>
     */
    public function valuesGrouped(): array
    {
        $locale = I18N::locale();
        $values = FunctionsGov::getGovTypeIdsByTypeGroup($this->module, $locale);

        /*
        array_walk($values, static function (string &$value, $key): void {
            if (is_int($key)) {
                $value .= ' — ... ' . I18N::number($key);
            }
        });        
        */

        return $values;
    }
}
