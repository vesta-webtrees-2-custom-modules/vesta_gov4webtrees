
# Webtrees 2 Custom Module: ⚶ Vesta Gov4Webtrees

This [webtrees](https://www.webtrees.net/) custom module hooks provides data to the extended 'Facts and Events' tab, enhancing events with [GOV](http://gov.genealogy.net) (historic gazetteer) data.
The project’s website is [cissee.de](https://cissee.de).

## Contents

* [Features](#features)
* [Demo](#demo)
* [Download](#download)
* [Installation](#installation)
* [License](#license)

### Features<a name="features"/>

* Historic and current GOV data is loaded, cached internally, and displayed for individual events. Map links (to OpenStreetMaps etc) based on geodata obtained from the GOV server are also shown.

![Screenshot](gov.png)
* GOV ids have to be entered manually, once per place name. Alternatively, ids for specific events may be set via a custom _GOV tag under the respective PLAC tag.

### Demo<a name="demo"/>

Access a (webtrees 1.x) demo of the module [here](https://cissee.de/gov4webtreesDemo). Feel free to experiment with setting/resetting GOV Ids.

### Download<a name="download"/>

* Current version: 2.0.0-alpha.5.1
* Based on and tested with webtrees 2.0.0-alpha.5. Cannot be used with webtrees 1.x!
* Requires the ⚶ Vesta Common module ('vesta_common').
* Displays data via the ⚶ Vesta Facts and events module ('vesta_personal_facts'). 
* Provides location data to other custom modules.
* Download the zipped module, including all related modules, [here](https://cissee.de/vesta.latest.zip).
* Support, suggestions, feature requests: <ric@richard-cissee.de>
* Issues also via <https://github.com/ric2016/gov4webtrees/issues>
 
### Installation<a name="installation"/>

* Unzip the files and copy them to the modules_v4 folder of your webtrees installation. All related modules are included in the zip file. It's safe to overwrite the respective directories if they already exist (they are bundled with other custom modules as well), as long as other custom models using these dependencies are also upgraded to their respective latest versions.
* Enable the extended 'Facts and Events' module via Control Panel -> Modules -> Module Administration -> ⚶ Vesta Facts and Events.
* Enable the main module via Control Panel -> Modules -> Module Administration -> ⚶ Vesta Gov4Webtrees. After that, you may configure some options.
* Configure the visibility of the old and the extended 'Facts and Events' tab via Control Panel -> Modules -> Tabs (usually, you'll want to use only one of them. You may just disable the original 'Facts and Events' module altogether).
				
#### Import/Export

If you want to transfer GOV data between different trees or webtrees instances, you only have to copy the table which maps place names to gov ids (##gov_ids), everything else will be re-created automatically.
If you use GEDCOM data with _GOV tags for GOV ids, even this step is unnecessary.


### License<a name="license"/>

* **gov4webtrees: a webtrees custom module**
* Copyright (C) 2019 Richard Cissée
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

