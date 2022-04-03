<?php

declare(strict_types=1);

namespace Cissee\WebtreesExt\Elements;

use Cissee\WebtreesExt\Http\RequestHandlers\CreateSharedPlaceModal;
use Fisharebest\Webtrees\Auth;
use Fisharebest\Webtrees\Elements\GovIdentifier;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Registry;
use Fisharebest\Webtrees\Tree;
use Fisharebest\Webtrees\View;
use Vesta\Hook\HookInterfaces\GovIdEditControlsInterface;
use Vesta\Hook\HookInterfaces\GovIdEditControlsUtils;
use function route;
use function view;

//[RC] replace GovIdentifier because we
//- use a different edit control (select-location, but that's swapped elsewhere)
/**
 * A custom field used in _LOC records
 */
class GovIdentifierExt extends GovIdentifier
{
    /**
     * An edit control for this data.
     *
     * @param string $id
     * @param string $name
     * @param string $value
     * @param Tree   $tree
     *
     * @return string
     */
    public function edit(string $id, string $name, string $value, Tree $tree): string
    {
        //we don't have the place-name here nor an easy way to get it
        //except by using the title from rendered view, which is ok for _LOC:_GOV but not for others!
        //(such as _GOV under PLAC, so we'd require a different edit control there!)
        $placeNameSelector = 'h2 > span';
        
        $html = '';
        
        //this could be simplified now ($module is actually own module)
        //or move this to common? not easy to decide who's responsible for interselection (_LOC:_GOV)
        $additionalControls = GovIdEditControlsUtils::accessibleModules($tree, Auth::user())
              ->map(function (GovIdEditControlsInterface $module) use ($value, $id, $name, $placeNameSelector) {
                //TODO
                return $module->govIdEditControl(
                    ($value === '')?null:$value, 
                    $id, 
                    $name, 
                    '', 
                    $placeNameSelector, 
                    false, 
                    false);
              })
              ->toArray();
        
        foreach ($additionalControls as $additionalControl) {
            $html .= $additionalControl->getMain();
            //apparently handled properly
            View::push('javascript');
            echo $additionalControl->getScript();
            View::endpush();
        }

        if ($html !== '') {
            return $html;
        }
        
        return parent::edit($id, $name, $value, $tree);
    }
}
