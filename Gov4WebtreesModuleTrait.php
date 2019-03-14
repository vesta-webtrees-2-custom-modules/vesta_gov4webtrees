<?php

namespace Cissee\Webtrees\Module\Gov4Webtrees;

use Fisharebest\Webtrees\I18N;
use Vesta\ControlPanel\Model\ControlPanelCheckbox;
use Vesta\ControlPanel\Model\ControlPanelPreferences;
use Vesta\ControlPanel\Model\ControlPanelSection;
use Vesta\ControlPanel\Model\ControlPanelSubsection;

trait Gov4WebtreesModuleTrait {

  protected function getMainTitle() {
    return I18N::translate('Vesta Gov4Webtrees');
  }

  public function getShortDescription() {
    return
            I18N::translate('A module integrating GOV (historic gazetteer) data.');
  }

  protected function getFullDescription() {
    $description = array();
    $description[] = I18N::translate('A module integrating GOV (historic gazetteer) data. Enhances places with GOC data via the extended \'Facts and events\' tab.') . ' ' .
            I18N::translate('Place hierarchies are displayed historically, i.e. according to the date of the respective event.') . ' ' .
            I18N::translate('All data (except for the mapping of places to GOV ids, which has to be done manually) is retrieved via the GOV web service interface and cached internally.') . ' ' .
            I18N::translate('Consequently, GOV place hierarchy information can only be changed indirectly, via the GOV website.');
    $description[] = I18N::translate('Requires the \'%1$s Vesta Common\' module, and the \'%1$s Vesta Facts and events\' module.', $this->getVestaSymbol());
    $description[] = I18N::translate('Provides location data to other custom modules.');
    return $description;
  }

  protected function createPrefs() {
    $generalSub = array();
    $generalSub[] = new ControlPanelSubsection(
            /* I18N: Configuration option */I18N::translate('Displayed title'),
            array(new ControlPanelCheckbox(
                /* I18N: Configuration option */I18N::translate('Include the ' . $this->getVestaSymbol() . ' symbol in the module title'),
                null,
                'VESTA',
                '1')));

    $generalSub[] = new ControlPanelSubsection(
            /* I18N: Configuration option */I18N::translate('Local GOV data'),
            array(new ControlPanelCheckbox(
                /* I18N: Configuration option */I18N::translate('reset all cached data once'),
                /* I18N: Configuration option */ I18N::translate('Subsequently, all data is retrieved again from the GOV server. ') .
                /* I18N: Configuration option */I18N::translate('Usually only required in case of substantial changes of the GOV data. ') .
                /* I18N: Configuration option */I18N::translate('Mappings of places to GOV ids are not affected.'),
                'RESET',
                '0'))); //not a persistent setting, see overridden setSetting/setPreference!

    $factsAndEventsSub = array();
    $factsAndEventsSub[] = new ControlPanelSubsection(
            /* I18N: Configuration option */I18N::translate('Editing'),
            array(new ControlPanelCheckbox(
                /* I18N: Configuration option */I18N::translate('Visitors may edit GOV ids (not recommended)'),
                /* I18N: Configuration option */ I18N::translate('This option mainly exists for demo servers. '),
                'VISITORS_MAY_EDIT',
                '0'),
        new ControlPanelCheckbox(
                /* I18N: Configuration option */I18N::translate('Nobody may edit GOV ids'),
                /* I18N: Configuration option */ I18N::translate('Useful to (temporarily) hide the edit controls. If all GOV ids are provided via GEDCOM tags, the edit controls may not be required at all. '),
                'NO_ONE_MAY_EDIT',
                '0')));

    $factsAndEventsSub[] = new ControlPanelSubsection(
            /* I18N: Configuration option */I18N::translate('Displayed data'),
            array(new ControlPanelCheckbox(
                /* I18N: Configuration option */I18N::translate('Compact display (administrative levels as tooltips)'),
                null,
                'COMPACT_DISPLAY',
                '1'),
        new ControlPanelCheckbox(
                /* I18N: Configuration option */I18N::translate('Additionally show GOV hierarchy for present time'),
                null,
                'SHOW_CURRENT_DATE',
                '0'),
        new ControlPanelCheckbox(
                /* I18N: Configuration option */I18N::translate('Allow objects of type \'settlement\' in hierarchy'),
                /* I18N: Configuration option */ I18N::translate('According to the current GOV specification, settlements are not supposed to be parents of other settlements.') .
                /* I18N: Configuration option */I18N::translate('This policy hasn\'t been strictly followed though. Check this option if you end up with incomplete hierarchies otherwise.') .
                /* I18N: Configuration option */I18N::translate('Note: Ultimately it\'s probably preferable to correct the respective GOV data itself.'),
                'ALLOW_SETTLEMENTS',
                '1'),
        new ControlPanelCheckbox(
                /* I18N: Configuration option */I18N::translate('For events with a date range, use the median date'),
                /* I18N: Configuration option */ I18N::translate('Otherwise, the start date is used (this is more consistent with other date-based calculations in webtrees).'),
                'USE_MEDIAN_DATE',
                '0')));

    $factsAndEventsSub[] = new ControlPanelSubsection(
            /* I18N: Configuration option */I18N::translate('Internals (adjusted automatically if necessary)'),
            array(new ControlPanelCheckbox(
                /* I18N: Configuration option */I18N::translate('Use fast ajax requests'),
                /* I18N: Configuration option */ I18N::translate('Execute ajax requests directly (not via the regular webtrees entrypoint url). This won\'t work if your server doesn\'t allow non-standard endpoints (*.php files within the module directory), in which case this option is unchecked automatically. Direct requests are faster because requests via the regular webtrees entrypoint url carry out lots of unnecessary initialization tasks.'),
                'FAST_AJAX',
                '1'),
        new ControlPanelCheckbox(
                /* I18N: Configuration option */I18N::translate('Use NuSOAP instead of SoapClient'),
                /* I18N: Configuration option */ I18N::translate('Execute requests to the GOV server via NuSOAP, rather than using the native php SoapClient. The native SoapClient is usually enabled (you can check this in your php.ini settings), but may not be provided by all hosters. If the native client is not enabled/available, this option is checked automatically.'),
                'USE_NUSOAP',
                '0')));

    $sections = array();
    $sections[] = new ControlPanelSection(
            /* I18N: Configuration option */I18N::translate('General'),
            null,
            $generalSub);
    $sections[] = new ControlPanelSection(
            /* I18N: Configuration option */I18N::translate('Facts and Events Tab Settings'),
            null,
            $factsAndEventsSub);

    return new ControlPanelPreferences($sections);
  }

}
