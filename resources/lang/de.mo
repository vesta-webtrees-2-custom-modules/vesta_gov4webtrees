��    I      d  a   �      0  M   1       �   �  3   8  z   l  l   �     T  �   j  /   �  8   (	  ^   a	  j   �	  ]   +
  0  �
     �  1   �     �       !     %   9  c   _  ?   �  6     ;   :  c   v  M   �  5   (  `   ^  �   �  /   j  H   �  �   �     j  +   y  /   �  I   �  T     k   t     �  =   �  |   2  a   �       @   &     g  T   }     �     �            ?   .  3   n  y   �        [   =  "   �  ^   �  F     �   b  �   "     �  /   �  =   �     ;     @     N  ]   m  ^   �     *     7     V     q  �  w  Z   =     �  �   �  V   F  �   �     R     �  �   �  9   |  5   �  a   �  i   N  n   �    '      �!  @   �!     �!     "      "  (   4"  z   ]"  >   �"  4   #  C   L#  �   �#  a   $  M   u$  y   �$  �   =%  4   	&  \   >&  �   �&     +'  4   <'  6   q'  V   �'  Z   �'  ~   Z(  '   �(  T   )  �   V)  t   �)     ^*  N   z*     �*  M   �*  +   1+     ]+     u+      �+  A   �+  :   �+  �   -,  &   �,  u   -  -   ~-  z   �-  Q   '.  �   y.  �   O/  #   �/  H   0  F   U0     �0     �0      �0  h   �0  a   :1  	   �1     �1  :   �1     �1     %                        G   .         I                      ,      <   ?   @      -   5         /       7          4   $       	      0   :       1   !       B          A         +           (   >   =   '                3          9   *          2         )                       D          
      "           E   6   C       ;   8       F   &   #   H    'Classic' mode, extended to link to places from the GEDCOM data, if possible. 'Classic' mode. (Why is German in particular singled out like this? Because the GOV gazetteer is currently rather German-language centric, and therefore many places have German names). A module integrating GOV (historic gazetteer) data. A module integrating GOV (historic gazetteer) data. Enhances places with GOV data via the extended 'Facts and events' tab. According to the current GOV specification, settlements are not supposed to be parents of other settlements. Administrative levels All data (except for the mapping of places to GOV ids, which has to be done manually) is retrieved from the GOV server and cached internally. Allow objects of type 'settlement' in hierarchy Compact display (administrative levels only as tooltips) Consequently, place hierarchy information can only be changed indirectly, via the GOV website. Determines strategy in case the place name is not available in the current language (for the given date):  Display a tooltip indicating the source of the GOV id. This is intended mainly for debugging. Execute requests to the GOV server via NuSOAP, rather than using the native php SoapClient. The native SoapClient is usually enabled (you can check this in your php.ini settings), but may not be provided by all hosters. If the native client is not enabled/available, this option is checked automatically. Fallback language For events with a date range, use the median date GOV Id Management GOV id GOV id for %1$s has been removed. GOV id for %1$s has been set to %2$s. GOV ids are stored outside GEDCOM data by default, but ids stored via _GOV tags are also supported. GOV place hierarchy for %1$s has been reloaded from GOV server. GOV place hierarchy has been reloaded from GOV server. If checked, attempt to fall back to the German place name.  If this is checked, the displayed GOV hierarchy uses place names from the GEDCOM data, if possible. If this option is checked, you usually want to disable the following option.  If unchecked, prefer any language other than German;  In particular, the Shared Places custom module may be used to manage GOV ids within GEDCOM data. In this case, the GOV ids are stored in a separate database table, which has to be managed manually when moving the respective tree to a different webtrees installation.  Internals (adjusted automatically if necessary) Invalid GOV id! Valid GOV ids are e.g. 'EITTZE_W3091', 'object_1086218'. It is recommended to use only one of the following options. You may also (temporarily) disable all editing via unchecking all of them. Local GOV data Look up a matching GOV id on the GOV server Mappings of places to GOV ids are not affected. Note: The mapping from place to GOV id is stored outside the gedcom data. Note: Ultimately it's probably preferable to correct the respective GOV data itself. Otherwise, the start date is used (this is more consistent with other date-based calculations in webtrees). Outside GEDCOM data Outside GEDCOM data - editable by anyone (including visitors) Particularly useful in order to manage GOV ids via the Shared Places module. Ids are stored and exportable via GEDCOM tags.  Place hierarchies are displayed historically, i.e. according to the date of the respective event. Place text and links Reset GOV id (outside GEDCOM) and reload the GOV place hierarchy Reset GOV id for %1$s Save the current id in order to reload the place hierarchy data from the GOV server. Set GOV id (outside GEDCOM) Set GOV id for %1$s Show GOV hierarchy for Show additional info Subsequently, all data is retrieved again from the GOV server.  The GOV server seems to be temporarily unavailable. This option mainly exists for demo servers and is not recommended otherwise. It has precedence over the preceding option. Use NuSOAP instead of SoapClient Use place names and link to places existing in webtrees, additionally link to GOV via icons Use place names and links from GOV Use place names and links from GOV, additionally link to places existing in webtrees via icons Usually only required in case of substantial changes of the GOV data.  Vesta Gov4Webtrees: The displayed GOV hierarchy now additionally links to webtrees places where possible. You can switch back to the classic display (and others) via the module configuration. When this option is disabled, an alternative edit control is provided, which still allows to reload place hierarchies from the GOV server. Where to edit and store GOV ids Within GEDCOM data (via other custom modules).  You may also save an empty id in order to remove the mapping. both date of event fallback to German place names for events without a date, present time hierarchy will be used regardless of this preference. motivated by the assumption that place names in the local language are more useful in general  present time reload the GOV place hierarchy reset all cached data once today Project-Id-Version: German (Vesta Webtrees Custom Modules)
Report-Msgid-Bugs-To: 
PO-Revision-Date: YEAR-MO-DA HO:MI+ZONE
Last-Translator: FULL NAME <EMAIL@ADDRESS>
Language-Team: German <https://hosted.weblate.org/projects/vesta-webtrees-custom-modules/vesta-gov4webtrees/de/>
Language: de
MIME-Version: 1.0
Content-Type: text/plain; charset=UTF-8
Content-Transfer-Encoding: 8bit
Plural-Forms: nplurals=2; plural=n != 1;
X-Generator: Weblate 4.4.1-dev
 'Klassischer' Modus, erweitert um Verweise zu Orten aus den GEDCOM-Daten, soweit möglich. 'Klassischer' Modus. (Warum ist gerade Deutsch derart hervorgehoben? Da das GOV-Ortsverzeichnis derzeit eher deutschsprachig ist und daher viele Orte deutsche Namen haben) . Ein Modul zur Integration von Daten aus dem GOV (Das Geschichtliche Orts-Verzeichnis). Ein Modul zur Integration von Daten aus dem GOV (Das Geschichtliche Orts-Verzeichnis). Ergänzt Orte um zusätzliche GOV-Daten über den erweiterten Reiter "Fakten und Ereignisse". Nach der aktuellen GOV-Spezifikation sollten Objekte vom Typ "Siedlung" keinen anderen Objekten dieses Typs übergeordnet sein. Verwaltungsebenen Alle Daten (mit Ausnahme der Zuordnung von Orten zu GOV-IDs, die manuell erfolgen muss) werden vom GOV-Server abgerufen und intern zwischengespeichert. Objekte vom Typ "Siedlung" in Hierarchie berücksichtigen Kompakte Anzeige (Verwaltungsebenen nur als Tooltips) Folglich können Ortshierarchieinformationen nur indirekt über die GOV-Website geändert werden. Strategie, falls der Ortsname in der aktuellen Sprache (für das angegebene Datum) nicht verfügbar ist:  Zeigt einen Tooltip an, der die Quelle der GOV-ID angibt. Dieser ist hauptsächlich zur Fehleranalyse gedacht. Führt Anfragen an den GOV-Server über NuSOAP aus, anstatt den nativen PHP-SoapClient zu verwenden. Der native SoapClient ist normalerweise aktiviert (Sie können dies in Ihren php.ini-Einstellungen überprüfen), wird jedoch möglicherweise nicht von allen Hostern bereitgestellt. Wenn der native Client nicht aktiviert bzw. verfügbar ist, wird diese Option automatisch aktiviert. Rückfall-Sprache Für Ereignisse mit Zeitspanne bitte das gemittelte Datum nutzen GOV-ID Verwaltung GOV-ID GOV-ID für %1$s wurde entfernt. GOV-ID für %1$s wurde auf %2$s gesetzt. GOV-IDs werden standardmäßig außerhalb der GEDCOM-Daten gespeichert, es werden aber auch IDs in _GOV-Tags unterstützt. Die GOV-Ortshierarchie für %1$s wurde vom GOV-Server geladen. Die GOV-Ortshierarchie wurde vom GOV-Server geladen. Wenn aktiviert wird der Rückfall auf deutsche Ortsnamen versucht.  Wenn dies aktiviert wurde, werden für die angezeigte GOV-Hierarchie nach Möglichkeit die Ortsnamen aus den GEDCOM-Daten genutzt. Wenn diese Option aktiviert ist, sollte in der Regel die nachfolgende Option deaktiviert werden.  Wenn deaktiviert, wird einer anderen Sprache als Deutsch der Vorzug gegeben.  Namentlich das Gemeinsame-Orte (Shared Places) Benutzer-Modul erlaubt es GOV-IDs innerhalb der GEDCOM-Daten zu verwalten. In diesem Fall werden die GOV-IDs in separaten Tabellen gespeichert, welche manuell berücksichtigt werden müssen, wenn der entsprechende Stammbaum in eine andere Webtrees-Installation verschoben wird.  Interna (automatische Anpassung sofern erforderlich) Keine zulässige GOV-ID! Zulässige IDs haben die Form 'EITTZE_W3091', 'object_1086218' etc. Es wird empfohlen eine der folgenden Optionen zu nutzen. Man kann auch zeitweise jede Änderung unterbinden durch Deaktivierung aller Optionen. Lokale GOV-Daten Eine passende GOV-ID auf dem GOV-Server nachschlagen Zuordnungen von Orten zu GOV-IDs sind nicht betroffen. Hinweis: Die Zuordnung von Ort zu GOV-Id wird außerhalb der GEDCOM-Daten gespeichert. Hinweis: Letzlich dürfte die Korrektur der korrespondierenden GOV-Daten vorzuziehen sein. Andernfalls wird das Start-Datum nicht genutzt (dies stimmt eher mit anderen zeitbasierten Berechnungen in webtrees überein). Speicherung außerhalb der GEDCOM-Daten Speicherung außerhalb der GEDCOM-Daten - von jedem editierbar (inklusive Besuchern) Besonders nützlich um GOV-IDs mittels Gemeinsame-Orte (Shared Places) Modul zu verwalten. IDs sind speicher- und exportierbar durch GEDCOM-Tags.  Orts-Hierachien werden im historischen Kontext angezeigt, dem Datum des korrespondierenden Ereignisses entsprechend. Orts-Text und -Verknüpfung GOV-ID anpassen (außerhalb der GEDCOM-Daten) und die Ortshierarchie neu laden GOV-ID für %1$s anpassen Um die Ortshierarchie neu vom GOV-Server zu laden, aktuelle GOV-Id speichern. GOV-ID setzen (außerhalb der GEDCOM-Daten) GOV-ID für %1$s setzen Zeige GOV-Hierarchie für Zeige zusätzliche Informationen Anschließend werden wieder alle Daten vom GOV-Server abgerufen.  Der GOV-Server scheint zeitweise nicht erreichbar zu sein. Diese Option ist hauptsächlich für Demo-Server vorgesehen und wird andernfalls nicht zur Nutzung empfohlen. Sie hat eine höhere Priorität gegenüber der vorhergehenden Option. Benutze NuSOAP anstelle von SoapClient Verwende Ortsnamen und verknüpfe zu in webtrees existierenden Orten, zusätzlich wird mittels Icon zum GOV verwiesen Verwende Ortsnamen und Verknüpfungen vom GOV Verwende Ortsnamen und Verknüpfungen vom GOV, zusätzlich wird mittels Icon auf existierenden Orten in webtrees verwiesen Normalerweise nur benötigt im Falle von wesentlichen Änderungen der GOV-Daten.  Vesta Gov4Webtrees: Die angezeigte GOV-Hierarchie verweist nun nach Möglichkeit zusätzlich auf Orte in webtrees. EIn Umschalten auf die klassische (und andere) Ansicht ist über die Modul-Konfiguration möglich. Ist dies Option deaktiviert, wird ein alternatives Eingabeelement bereitgestellt, das noch erneutes Laden der Ortshierarchie vom GOV-Server ermöglicht. Wo GOV-IDs bearbeiten und speichern Innerhalb der GEDCOM-Daten (mittels anderem benutzerdefinierten Modul).  Um die Zuordnung zu löschen, kann eine leere GOV-Id verwendet werden. beide Ereignisdatum Rückfall auf deutsche Ortsnamen für Ereignisse ohne Datum wird unabhängig von dieser Einstellung die gegenwärtige Hierarchie genutzt. begründet durch die Annahme, dass Ortsnamen in der regionalen Sprache generell nützlicher sind  Gegenwart Ortshierarchie neu laden Zurücksetzen aller zwischengespeicherten Daten auf einmal heute 