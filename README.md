
# ⚶ Vesta Gov4Webtrees (Webtrees 2 Custom Module)

This [webtrees](https://www.webtrees.net/) custom module provides data to an extended 'Facts and Events' tab, enhancing events with [GOV](http://gov.genealogy.net) (historic gazetteer) data.
The project’s website is [cissee.de](https://cissee.de).

This is a webtrees 2.x module - It cannot be used with webtrees 1.x. For its webtrees 1.x counterpart, see [here](https://github.com/ric2016/gov4webtrees). You can safely upgrade from the 1.x version and its database tables, all data will be preserved.

## Contents

* [Features](#features)
* [Demo](#demo)
* [Download](#download)
* [Installation](#installation)
* [License](#license)

### Features<a name="features"/>

* Historic and current GOV data is loaded from the GOV server, cached internally, and displayed for individual facts and events. 
* Location data (map coordinates) is also used, if available, for map links and other custom modules.
* GOV ids have to be entered manually, once per place name. They may be managed in different ways (depending on the module administration settings):

![Screenshot](gov.png)
1. GOV ids outside GEDCOM data. In this case, the GOV ids are stored in a separate database table, which has to be managed manually when moving the respective tree to a different webtrees installation.
Note that the edit controls have been moved: They are now grouped with the other edit controls (circled red in the screenshot).

2. GOV ids within GEDCOM data. GOV ids may also be set via the Shared Places module. In this case, they are exported via GEDCOM data.
Here, the GOV icon grouped with the edit controls just reloads the place hierarchy from the GOV server. 
When creating or updating shared places, the GOV id edit controls are supported:

![Screenshot](gov2.png)

3. Alternatively, GOV ids may always be set via a custom _GOV tag under the respective PLAC tag (for a specific fact or event). This is not recommended, and there are no edit controls to support this. The module still displays the place hierarchies in this case though.


### Demo<a name="demo"/>

Access a demo of the module [here](https://cissee.de/gov4webtrees2Demo). Feel free to experiment with setting/resetting GOV Ids.

### Download<a name="download"/>

* Current version: 2.0.3.1
* Based on and tested with webtrees 2.0.3. Cannot be used with webtrees 1.x. May not work with earlier 2.x versions!
* Requires the ⚶ Vesta Common module ('vesta_common').
* Displays data via the ⚶ Vesta Facts and events module ('vesta_personal_facts'). 
* Provides location data to other custom modules.
* Download the zipped module, including all related modules, [here](https://cissee.de/vesta.latest.zip).
* Support, suggestions, feature requests: <ric@richard-cissee.de>
* Issues also via <https://github.com/vesta-webtrees-2-custom-modules/vesta_gov4webtrees/issues>
 
### Installation<a name="installation"/>

* Unzip the files and copy them to the modules_v4 folder of your webtrees installation. All related modules are included in the zip file. It's safe to overwrite the respective directories if they already exist (they are bundled with other custom modules as well), as long as other custom models using these dependencies are also upgraded to their respective latest versions.
* Enable the extended 'Facts and Events' module via Control Panel -> Modules -> Module Administration -> ⚶ Vesta Facts and Events.
* Enable the main module via Control Panel -> Modules -> Module Administration -> ⚶ Vesta Gov4Webtrees. After that, you may configure some options.
* Configure the visibility of the old and the extended 'Facts and Events' tab via Control Panel -> Modules -> Tabs (usually, you'll want to use only one of them. You may just disable the original 'Facts and Events' module altogether).
				
#### Import/Export

If you want to transfer GOV data between different webtrees instances, you only have to copy the table which maps place names to gov ids (##gov_ids), all other data will be re-created automatically.
If you use GEDCOM data with _GOV tags for GOV ids (either directly or via the Shared Places module), even this step is unnecessary.


### License<a name="license"/>

* **gov4webtrees: a webtrees custom module**
* Copyright (C) 2019 - 2020 Richard Cissée
* Derived from **webtrees** - Copyright (C) 2010 to 2019 webtrees development team.
* Nutzt Daten des [Geschichtlichen Orts-Verzeichnisses GOV](http://gov.genealogy.net) des [Vereins für Computergenealogie e. V.](http://compgen.de), basierend auf einer [Creative Commons-Lizenz](http://wiki-de.genealogy.net/GOV/Webservice#Lizenz).

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.

