<?php

namespace Cissee\Webtrees\Module\Gov4Webtrees;

use Fisharebest\Webtrees\I18N;

class HelpTexts {

  public static function helpText($help) {
    switch ($help) {

      //note: currently nowhere used!
      case 'Cleanup Required!':
        $title = I18N::translate('Cleanup Required!');
        $text = '<p>' .
                I18N::translate('You have multiple GOV Ids for the same place name.') . ' ' .
                I18N::translate('This was allowed in the webtrees 1.x version of this module, but turned out to be a bad idea for various reasons.') . ' ' .
                I18N::translate('Resetting this GOV Id will resolve this by assigning the new GOV Id as the single GOV Id of this place.') . ' ' .
                '</p><p>' .
                I18N::translate('You can still use GOV parish ids for religious events, and administrative ids for other events, but you\'ll have to disambiguate your place names accordingly.') .
                '</p>';
        break;

      default:
        $title = I18N::translate('Help');
        $text = I18N::translate('The help text has not been written for this item.');
        break;
    }

    return view('modals/help', [
        'title' => $title,
        'text' => $text,
    ]);
  }

}
