��    }        �   �      �
  M   �
     �
  �   �
  3   �  z   �  l   ?  �   �     @     O     k     �  �   �  I   &  �   p       8   
  ^   C  9  �  ]   �     :  0  M  �   ~  1     p   L     �     �     �     �     �  
   �                    0  !   7  %   Y       c   �  n   �  ?   n  6   �  c   �  %   I  ;   o  c   �  M     5   ]  X   �  ~   �  �   k  `   �  �   _  /   
  H   :  �   �  1   
     <     E  "   T  +   w  /   �  I   �  T     �   r  x        �     �  k   �       =     |   W  a   �     6     F     [  $   p  	   �     �     �     �  @   �     +   T   A   C   �      �      �   
   
!  �   !     �!     �!  .   �!  ?   "     ["  3   �"  :   #  �   J#  y   �#  |   k$     �$      �$  [   %  "   h%  ^   �%  F   �%  �   1&  �   �&     |'  /   �'  j   �'  =   7(  ;   u(     �(     �(     �(  ]   �(     A)  ^   F)     �)     �)     �)  /   �)     *  $   "*     G*  �  `*  Z    ,     {,  �   �,  V   )-  �   �-     5.  �   �.     `/     n/     �/     �/  �   �/  _   J0  �   �0     b1  5   h1  a   �1  k   2  n   l3     �3    �3  �   u5  @   .6  t   o6     �6     �6     �6     
7     7     !7  	   =7     G7     V7     j7      q7  (   �7  #   �7  z   �7  s   Z8  >   �8  4   9  �   B9  (   �9  C   �9  �   6:  a   �:  M   ;  p   i;  �   �;  �   �<  y   L=  �   �=  4   �>  \   �>  �   $?  =   �?  
   �?     �?  &   @  4   5@  6   j@  V   �@  Z   �@  �   SA  �   �A     nB     ~B  ~   �B  '   C  T   .C  �   �C  t   D     �D     �D     �D  %   �D  	   �D     �D     E  ,   8E  N   eE     �E  M   �E  N   F  +   kF     �F     �F  �   �F     �G      �G  7   �G  A   �G  �   @H  :   �H  ?   I  �   AI  �   	J  �   �J     NK  &   QK  u   xK  -   �K  z   L  Q   �L  �   �L  �   �M  #   XN  H   |N  u   �N  F   ;O  ?   �O     �O     �O      �O  h   �O     `P  a   eP  	   �P     �P  :   �P  ,   %Q     RQ  &   XQ     Q         E   W      X               ^                     w   N                  2   1           G   c   +   0      `   ]           
   3   "   7   [   Z   j   =   :       !   i   |   \              R      >      ?       &   -   F       B                     q              <      y      k       }   I   9   O      J   C              e   d         (   z      s       @   v       Y       g         L             V   m   h       K       p   Q   '              x       U      a       5   f           H   /   6       8      {   .   T   _   S              #   )   b   t   M   ,   ;   $   4   	   u      l          %   A   *   D   P   r   n   o    'Classic' mode, extended to link to places from the GEDCOM data, if possible. 'Classic' mode. (Why is German in particular singled out like this? Because the GOV gazetteer is currently rather German-language centric, and therefore many places have German names). A module integrating GOV (historic gazetteer) data. A module integrating GOV (historic gazetteer) data. Enhances places with GOV data via the extended 'Facts and events' tab. According to the current GOV specification, settlements are not supposed to be parents of other settlements. Additionally, the module checks if the respective GOV id, or any of its parents within the hierarchy, has languages defined in the csv file '%1$s'. Administrative Administrative (level %1$s) Administrative (other) Administrative levels All data (except for the mapping of places to GOV ids, which has to be done manually) is retrieved from the GOV server and cached internally. As a final fallback, determine the place name according to this checkbox: Check this option if you still want organizations to appear in hierarchies, e.g. the United Nations as a higher-level object of sovereign entities. Civil Compact display (administrative levels only as tooltips) Consequently, place hierarchy information can only be changed indirectly, via the GOV website. Data obtained from GOV server. Edited data will be stored as local modifications (outside GEDCOM, just like the original data). Edited data always has precedence over original data. It will not be deleted when hierarchies are reloaded, but can be deleted explicitly here. No data is transferred to the GOV server. Display a tooltip indicating the source of the GOV id. This is intended mainly for debugging. Edit %1$s for %2$s Execute requests to the GOV server via NuSOAP, rather than using the native php SoapClient. The native SoapClient is usually enabled (you can check this in your php.ini settings), but may not be provided by all hosters. If the native client is not enabled/available, this option is checked automatically. For a given place, this module displays one or more names by matching the available names against a list of languages, according to the following strategy: For events with a date range, use the median date For more fine-grained adjustments, and to view the list of the types and type groups, edit the GOV data locally. From GOV Hierarchies GOV Id Management GOV Name GOV Object Type GOV Parent GOV data GOV data for GOV data for %1$s GOV id GOV id for %1$s has been removed. GOV id for %1$s has been set to %2$s. GOV id for type of location GOV ids are stored outside GEDCOM data by default, but ids stored via _GOV tags are also supported. GOV objects belong to different type groups. The GOV place hierarchy is based on objects of type group '%1$s'. GOV place hierarchy for %1$s has been reloaded from GOV server. GOV place hierarchy has been reloaded from GOV server. Hide an object and stop the place hierarchy at that point by moving it to an irrelevant type group. Hide data without local modifications If checked, attempt to fall back to the German place name.  If this is checked, the displayed GOV hierarchy uses place names from the GEDCOM data, if possible. If this option is checked, you usually want to disable the following option.  If unchecked, prefer any language other than German;  In any case, they are still used as fallbacks to determine further higher-level objects. In general, hide an object while preserving the overall place hierarchy by moving it to a hidden type group (see preferences). In particular you may want to revert locally some controversial changes made on the GOV server (such as the object type of the Holy Roman Empire). In particular, the Shared Places custom module may be used to manage GOV ids within GEDCOM data. In this case, the GOV ids are stored in a separate database table, which has to be managed manually when moving the respective tree to a different webtrees installation.  Internals (adjusted automatically if necessary) Invalid GOV id! Valid GOV ids are e.g. 'EITTZE_W3091', 'object_1086218'. It is recommended to use only one of the following options. You may also (temporarily) disable all editing via unchecking all of them. It will not be overwritten by subsequent updates. Judicial Local GOV data Local modifications are preserved. Look up a matching GOV id on the GOV server Mappings of places to GOV ids are not affected. Note: The mapping from place to GOV id is stored outside the gedcom data. Note: Ultimately it's probably preferable to correct the respective GOV data itself. Objects of this type strictly do not belong to the administrative hierarchy in the sense that they are no territorial entities (Gebietskörperschaften). Obvious mistakes should be corrected on the GOV server itself, but there may be cases where this is not easily possible. Organizational Other Otherwise, the start date is used (this is more consistent with other date-based calculations in webtrees). Outside GEDCOM data Outside GEDCOM data - editable by anyone (including visitors) Particularly useful in order to manage GOV ids via the Shared Places module. Ids are stored and exportable via GEDCOM tags.  Place hierarchies are displayed historically, i.e. according to the date of the respective event. Place hierarchy Place names from GOV Place text and links Please enter at least 10 characters. Religious Remove this GOV Name? Remove this GOV Object Type? Remove this GOV Parent? Reset GOV id (outside GEDCOM) and reload the GOV place hierarchy Reset GOV id for %1$s Save the current id in order to reload the place hierarchy data from the GOV server. See also %1$s for the original list of types and type descriptions. Set GOV id (outside GEDCOM) Set GOV id for %1$s Settlement Several object types that are part of this type group in the original model can be seen as problematic in this context, and have been moved to a custom '%1$s' type group. Show GOV hierarchy for Show additional info Show objects of type group '%1$s' in hierarchy Subsequently, all data is retrieved again from the GOV server.  The GOV server provides place names in different languages. However, there is no concept of an 'official language' for a place. The GOV server seems to be temporarily unavailable. The current user language always has the highest priority. These languages are then used, in the given order, either as fallbacks, or (if upper-cased) as additional languages (i.e. 'official languages' for a place hierarchy). This option mainly exists for demo servers and is not recommended otherwise. It has precedence over the preceding option. This policy hasn't been strictly followed though. Check this option if you still want to display settlements in hierarchies. To Use NuSOAP instead of SoapClient Use place names and link to places existing in webtrees, additionally link to GOV via icons Use place names and links from GOV Use place names and links from GOV, additionally link to places existing in webtrees via icons Usually only required in case of substantial changes of the GOV data.  Vesta Gov4Webtrees: The displayed GOV hierarchy now additionally links to webtrees places where possible. You can switch back to the classic display (and others) via the module configuration. When this option is disabled, an alternative edit control is provided, which still allows to reload place hierarchies from the GOV server. Where to edit and store GOV ids Within GEDCOM data (via other custom modules).  You can create and modify this csv file according to your personal preferences, see '%1$s' for an example. You may also save an empty id in order to remove the mapping. You may modify all data retrieved from the GOV server %1$s. both date of event fallback to German place names for events without a date, present time hierarchy will be used regardless of this preference. here motivated by the assumption that place names in the local language are more useful in general  present time reload the GOV place hierarchy reset all cached data once this place does not exist at this point in time today unknown GOV type (newly introduced?) with local modifications Project-Id-Version: German (Vesta Webtrees Custom Modules)
Report-Msgid-Bugs-To: 
PO-Revision-Date: YEAR-MO-DA HO:MI+ZONE
Last-Translator: FULL NAME <EMAIL@ADDRESS>
Language-Team: German <https://hosted.weblate.org/projects/vesta-webtrees-custom-modules/vesta-gov4webtrees/de/>
Language: de
MIME-Version: 1.0
Content-Type: text/plain; charset=UTF-8
Content-Transfer-Encoding: 8bit
Plural-Forms: nplurals=2; plural=n != 1;
X-Generator: Weblate 5.1
 'Klassischer' Modus, erweitert um Verweise zu Orten aus den GEDCOM-Daten, soweit möglich. 'Klassischer' Modus. (Warum ist gerade Deutsch derart hervorgehoben? Da das GOV-Ortsverzeichnis derzeit eher deutschsprachig ist und daher viele Orte deutsche Namen haben) . Ein Modul zur Integration von Daten aus dem GOV (Das Geschichtliche Orts-Verzeichnis). Ein Modul zur Integration von Daten aus dem GOV (Das Geschichtliche Orts-Verzeichnis). Ergänzt Orte um zusätzliche GOV-Daten über den erweiterten Reiter "Fakten und Ereignisse". Nach der aktuellen GOV-Spezifikation sollten Objekte vom Typ "Siedlung" keinen anderen Objekten dieses Typs übergeordnet sein. Zusätzlich überprüft das Modul, ob für die entsprechende GOV id, oder irgendeine in der Hierarchie oberhalb liegende, ein Eintrag in der Sprachdatei '%1$s' existiert. Administrativ Administrativ (Stufe %1$s) Administrativ (andere) Verwaltungsebenen Alle Daten (mit Ausnahme der Zuordnung von Orten zu GOV-IDs, die manuell erfolgen muss) werden vom GOV-Server abgerufen und intern zwischengespeichert. Als letzte Rückfallebene können Sie den Ortsnamen anhand dieses Kontrollkästchens bestimmen: Aktivieren Sie diese Option, wenn Sie möchten, dass Organisationen weiterhin in Hierarchien erscheinen, z. B. die Vereinten Nationen als übergeordnetes Objekt souveräner Einheiten. Zivil Kompakte Anzeige (Verwaltungsebenen nur als Tooltips) Folglich können Ortshierarchieinformationen nur indirekt über die GOV-Website geändert werden. Vom GOV-Server stammende Daten. Bearbeitete Daten werden als lokale Modifikationen gespeichert (außerhalb von GEDCOM, genau wie die Originaldaten). Bearbeitete Daten haben immer Vorrang vor Originaldaten. Sie werden nicht gelöscht, wenn Hierarchien neu geladen werden, sie können hier aber gelöscht werden. Es werden keine Daten an den GOV-Server übertragen. Zeigt einen Tooltip an, der die Quelle der GOV-ID angibt. Dieser ist hauptsächlich zur Fehleranalyse gedacht. Bearbeiten %1$s für %2$s Führt Anfragen an den GOV-Server über NuSOAP aus, anstatt den nativen PHP-SoapClient zu verwenden. Der native SoapClient ist normalerweise aktiviert (Sie können dies in Ihren php.ini-Einstellungen überprüfen), wird jedoch möglicherweise nicht von allen Hostern bereitgestellt. Wenn der native Client nicht aktiviert bzw. verfügbar ist, wird diese Option automatisch aktiviert. Dieses Modul zeigt für einen bestimmten Ort einen oder mehrere Namen an, indem es die verfügbaren Namen mit einer Liste von Sprachen abgleicht, und zwar nach der folgenden Strategie: Für Ereignisse mit Zeitspanne bitte das gemittelte Datum nutzen Für feinere Anpassungen und um die Liste der Typen und Typengruppen anzuzeigen, bearbeiten Sie die GOV-Daten lokal. Von GOV-Hierarchien GOV-ID Verwaltung GOV-Name GOV-Objekttyp Übergeordnetes GOV-Element GOV-Daten GOV-Daten für GOV-Daten für %1$s GOV-ID GOV-ID für %1$s wurde entfernt. GOV-ID für %1$s wurde auf %2$s gesetzt. ID für den GOV-Objekttyp des Ortes GOV-IDs werden standardmäßig außerhalb der GEDCOM-Daten gespeichert, es werden aber auch IDs in _GOV-Tags unterstützt. GOV-Objekte gehören zu verschiedenen Typgruppen. Die GOV-Ortshierarchie basiert auf Objekten der Typgruppe '%1$s'. Die GOV-Ortshierarchie für %1$s wurde vom GOV-Server geladen. Die GOV-Ortshierarchie wurde vom GOV-Server geladen. Blenden Sie ein Objekt aus und stoppen Sie die Ortshierarchie an diesem Punkt, indem Sie es in eine irrelevante Typgruppe verschieben. Daten ohne lokale Änderungen ausblenden Wenn aktiviert wird der Rückfall auf deutsche Ortsnamen versucht.  Wenn dies aktiviert wurde, werden für die angezeigte GOV-Hierarchie nach Möglichkeit die Ortsnamen aus den GEDCOM-Daten genutzt. Wenn diese Option aktiviert ist, sollte in der Regel die nachfolgende Option deaktiviert werden.  Wenn deaktiviert, wird einer anderen Sprache als Deutsch der Vorzug gegeben.  In jedem Fall werden sie weiterhin als Rückfallebene verwendet, um weitere übergeordnete Objekte zu ermitteln. Im Allgemeinen können Sie ein Objekt unter Beibehaltung der gesamten Ortshierarchie ausblenden, indem Sie es in eine ausgeblendete Typgruppe verschieben (siehe Einstellungen). Insbesondere möchten Sie vielleicht einige umstrittene Änderungen, die auf dem GOV-Server vorgenommen wurden, lokal rückgängig machen (z. B. den Objekttyp des Heiligen Römischen Reiches). Namentlich das Gemeinsame-Orte (Shared Places) Benutzer-Modul erlaubt es GOV-IDs innerhalb der GEDCOM-Daten zu verwalten. In diesem Fall werden die GOV-IDs in separaten Tabellen gespeichert, welche manuell berücksichtigt werden müssen, wenn der entsprechende Stammbaum in eine andere Webtrees-Installation verschoben wird.  Interna (automatische Anpassung sofern erforderlich) Keine zulässige GOV-ID! Zulässige IDs haben die Form 'EITTZE_W3091', 'object_1086218' etc. Es wird empfohlen eine der folgenden Optionen zu nutzen. Man kann auch zeitweise jede Änderung unterbinden durch Deaktivierung aller Optionen. Es wird durch spätere Aktualisierungen nicht überschrieben. Justiziell Lokale GOV-Daten Lokale Änderungen werden beibehalten. Eine passende GOV-ID auf dem GOV-Server nachschlagen Zuordnungen von Orten zu GOV-IDs sind nicht betroffen. Hinweis: Die Zuordnung von Ort zu GOV-Id wird außerhalb der GEDCOM-Daten gespeichert. Hinweis: Letzlich dürfte die Korrektur der korrespondierenden GOV-Daten vorzuziehen sein. Objekte dieser Art gehören streng genommen nicht zur Verwaltungshierarchie in dem Sinne, da sie keine Gebietskörperschaften sind. Offensichtliche Fehler sollten auf dem GOV-Server selbst korrigiert werden, doch kann es Fälle geben, in denen dies nicht ohne weiteres möglich ist. Organisatorisch Sonstige Andernfalls wird das Start-Datum nicht genutzt (dies stimmt eher mit anderen zeitbasierten Berechnungen in webtrees überein). Speicherung außerhalb der GEDCOM-Daten Speicherung außerhalb der GEDCOM-Daten - von jedem editierbar (inklusive Besuchern) Besonders nützlich um GOV-IDs mittels Gemeinsame-Orte (Shared Places) Modul zu verwalten. IDs sind speicher- und exportierbar durch GEDCOM-Tags.  Orts-Hierachien werden im historischen Kontext angezeigt, dem Datum des korrespondierenden Ereignisses entsprechend. Ortshierarchie Ortsnamen aus dem GOV Orts-Text und -Verknüpfung Bitte mindestens 10 Zeichen eingeben. Religiös Diesen GOV-Namen entfernen? Diesen GOV-Objekttyp entfernen? Dieses übergeordnete GOV-Element entfernen? GOV-ID anpassen (außerhalb der GEDCOM-Daten) und die Ortshierarchie neu laden GOV-ID für %1$s anpassen Um die Ortshierarchie neu vom GOV-Server zu laden, aktuelle GOV-Id speichern. Siehe auch %1$s für die ursprüngliche Liste der Typen und Typbeschreibungen. GOV-ID setzen (außerhalb der GEDCOM-Daten) GOV-ID für %1$s setzen Siedlung Mehrere Objekttypen, die im ursprünglichen Modell Teil dieser Typgruppe sind, können in diesem Zusammenhang als problematisch angesehen werden und wurden in die benutzerdefinierte Typgruppe "%1$s" verschoben. Zeige GOV-Hierarchie für Zeige zusätzliche Informationen Objekte der Typgruppe '%1$s' in der Hierarchie anzeigen Anschließend werden wieder alle Daten vom GOV-Server abgerufen.  Der GOV-Server bietet Ortsnamen in verschiedenen Sprachen an. Es gibt jedoch kein Konzept einer "offiziellen Sprache" für einen Ort. Der GOV-Server scheint zeitweise nicht erreichbar zu sein. Die aktuelle Benutzersprache hat immer die höchste Priorität. Diese Sprachen werden dann in der angegebenen Reihenfolge entweder als Ausweichsprachen oder (bei Großschreibung) als zusätzliche Sprachen (d. h. "Amtssprachen" für eine Ortshierarchie) verwendet. Diese Option ist hauptsächlich für Demo-Server vorgesehen und wird andernfalls nicht zur Nutzung empfohlen. Sie hat eine höhere Priorität gegenüber der vorhergehenden Option. Diese Richtlinie wurde jedoch nicht streng befolgt. Aktivieren Sie diese Option, wenn Sie weiterhin Siedlungen in Hierarchien anzeigen möchten. Zu Benutze NuSOAP anstelle von SoapClient Verwende Ortsnamen und verknüpfe zu in webtrees existierenden Orten, zusätzlich wird mittels Icon zum GOV verwiesen Verwende Ortsnamen und Verknüpfungen vom GOV Verwende Ortsnamen und Verknüpfungen vom GOV, zusätzlich wird mittels Icon auf existierenden Orten in webtrees verwiesen Normalerweise nur benötigt im Falle von wesentlichen Änderungen der GOV-Daten.  Vesta Gov4Webtrees: Die angezeigte GOV-Hierarchie verweist nun nach Möglichkeit zusätzlich auf Orte in webtrees. EIn Umschalten auf die klassische (und andere) Ansicht ist über die Modul-Konfiguration möglich. Ist dies Option deaktiviert, wird ein alternatives Eingabeelement bereitgestellt, das noch erneutes Laden der Ortshierarchie vom GOV-Server ermöglicht. Wo GOV-IDs bearbeiten und speichern Innerhalb der GEDCOM-Daten (mittels anderem benutzerdefinierten Modul).  Sie können diese CSV-Datei nach Ihren persönlichen Vorlieben erstellen und ändern, siehe "%1$s" für ein Beispiel. Um die Zuordnung zu löschen, kann eine leere GOV-Id verwendet werden. Sie können alle vom GOV-Server %1$s abgerufenen Daten ändern. beide Ereignisdatum Rückfall auf deutsche Ortsnamen für Ereignisse ohne Datum wird unabhängig von dieser Einstellung die gegenwärtige Hierarchie genutzt. hier begründet durch die Annahme, dass Ortsnamen in der regionalen Sprache generell nützlicher sind  Gegenwart Ortshierarchie neu laden Zurücksetzen aller zwischengespeicherten Daten auf einmal diesen Ort gibt es zu diesem Zeitpunkt nicht heute unbekannter GOV-Typ (neu eingeführt?) mit lokalen Änderungen 