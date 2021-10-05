��    k      t  �   �       	  M   !	     o	  �   	  3   (
  z   \
  l   �
  �   D     �     �            �   0  I   �  �     8   �  ^   �  ]   4  0  �  �   �  1   _  p   �            !     %   =  c   c  n   �  ?   6  6   v  c   �  ;     c   M  M   �  5   �  X   5  ~   �  �     `   �  �     /   �  H   �  �   %  1   �     �     �  "   �  +     /   E  I   u  T   �  �     x   �     &     5  k   ;     �  =   �  |   �  a   v     �     �     �  	     @        ]  T   s  C   �          (  
   <  �   G     �     	  .     ?   M     �  3     :   A  �   |  y   #   |   �       !  [   ;!  "   �!  ^   �!  F   "  �   `"  �    #     �#  /   �#  j   �#  =   f$  ;   �$     �$     �$     �$  ]   %     p%  ^   u%     �%     �%      &  /   &     K&  $   Q&     v&  �  �&  ^   n(     �(  �   �(  D   �)  �   �)  U   M*  �   �*     0+      A+     b+     ~+  �   �+  Y   ),  �   �,  ?   ,-  P   l-  O   �-  Y  .  �   g/  7    0  j   80     �0     �0     �0  $   �0  a   
1  x   l1  B   �1  9   (2  j   b2  ?   �2  ^   3  P   l3  F   �3  e   4  �   j4  �   �4  p   �5  �   6  6   �6  O   �6  �   O7  0   �7  
   ,8     78  %   L8  .   r8  (   �8  F   �8  Q   9  �   c9  w   �9     _:     m:  [   s:     �:  K   �:  �   .;  S   �;     "<     3<     F<     _<  K   m<     �<  X   �<  =   *=     h=     �=     �=  �   �=     k>     �>  4   �>  =   �>  r   ?  4   �?  0   �?  �   �?  �   �@  �   -A  !   �A  n   �A  (   DB  j   mB  F   �B  �   C  �   �C     oD  -   �D  m   �D  N   +E  ?   zE     �E     �E  #   �E  Y   �E     OF  ^   TF     �F  $   �F  5   �F  %   G     @G  $   EG     jG            G       %   C   L   N   ,   c   ?       =   B   6   a   [   V       Y   d   e   <                 _             `   T      R   @          f              W          O                  
   &      X   5              Q          U   F   P      -   h   !   >   *      j      g       $      9   0   7   #         +   ]       S   A                     4   	       M       ^   ;              D   /       (   \       i       K   J   2             :   b                               )   1   8       "   H             E      '   3   Z           .   k   I               'Classic' mode, extended to link to places from the GEDCOM data, if possible. 'Classic' mode. (Why is German in particular singled out like this? Because the GOV gazetteer is currently rather German-language centric, and therefore many places have German names). A module integrating GOV (historic gazetteer) data. A module integrating GOV (historic gazetteer) data. Enhances places with GOV data via the extended 'Facts and events' tab. According to the current GOV specification, settlements are not supposed to be parents of other settlements. Additionally, the module checks if the respective GOV id, or any of its parents within the hierarchy, has languages defined in the csv file '%1$s'. Administrative Administrative (level %1$s) Administrative (other) Administrative levels All data (except for the mapping of places to GOV ids, which has to be done manually) is retrieved from the GOV server and cached internally. As a final fallback, determine the place name according to this checkbox: Check this option if you still want organizations to appear in hierarchies, e.g. the United Nations as a higher-level object of sovereign entities. Compact display (administrative levels only as tooltips) Consequently, place hierarchy information can only be changed indirectly, via the GOV website. Display a tooltip indicating the source of the GOV id. This is intended mainly for debugging. Execute requests to the GOV server via NuSOAP, rather than using the native php SoapClient. The native SoapClient is usually enabled (you can check this in your php.ini settings), but may not be provided by all hosters. If the native client is not enabled/available, this option is checked automatically. For a given place, this module displays one or more names by matching the available names against a list of languages, according to the following strategy: For events with a date range, use the median date For more fine-grained adjustments, and to view the list of the types and type groups, edit the GOV data locally. GOV Id Management GOV id GOV id for %1$s has been removed. GOV id for %1$s has been set to %2$s. GOV ids are stored outside GEDCOM data by default, but ids stored via _GOV tags are also supported. GOV objects belong to different type groups. The GOV place hierarchy is based on objects of type group '%1$s'. GOV place hierarchy for %1$s has been reloaded from GOV server. GOV place hierarchy has been reloaded from GOV server. Hide an object and stop the place hierarchy at that point by moving it to an irrelevant type group. If checked, attempt to fall back to the German place name.  If this is checked, the displayed GOV hierarchy uses place names from the GEDCOM data, if possible. If this option is checked, you usually want to disable the following option.  If unchecked, prefer any language other than German;  In any case, they are still used as fallbacks to determine further higher-level objects. In general, hide an object while preserving the overall place hierarchy by moving it to a hidden type group (see preferences). In particular you may want to revert locally some controversial changes made on the GOV server (such as the object type of the Holy Roman Empire). In particular, the Shared Places custom module may be used to manage GOV ids within GEDCOM data. In this case, the GOV ids are stored in a separate database table, which has to be managed manually when moving the respective tree to a different webtrees installation.  Internals (adjusted automatically if necessary) Invalid GOV id! Valid GOV ids are e.g. 'EITTZE_W3091', 'object_1086218'. It is recommended to use only one of the following options. You may also (temporarily) disable all editing via unchecking all of them. It will not be overwritten by subsequent updates. Judicial Local GOV data Local modifications are preserved. Look up a matching GOV id on the GOV server Mappings of places to GOV ids are not affected. Note: The mapping from place to GOV id is stored outside the gedcom data. Note: Ultimately it's probably preferable to correct the respective GOV data itself. Objects of this type strictly do not belong to the administrative hierarchy in the sense that they are no territorial entities (Gebietskörperschaften). Obvious mistakes should be corrected on the GOV server itself, but there may be cases where this is not easily possible. Organizational Other Otherwise, the start date is used (this is more consistent with other date-based calculations in webtrees). Outside GEDCOM data Outside GEDCOM data - editable by anyone (including visitors) Particularly useful in order to manage GOV ids via the Shared Places module. Ids are stored and exportable via GEDCOM tags.  Place hierarchies are displayed historically, i.e. according to the date of the respective event. Place hierarchy Place names from GOV Place text and links Religious Reset GOV id (outside GEDCOM) and reload the GOV place hierarchy Reset GOV id for %1$s Save the current id in order to reload the place hierarchy data from the GOV server. See also %1$s for the original list of types and type descriptions. Set GOV id (outside GEDCOM) Set GOV id for %1$s Settlement Several object types that are part of this type group in the original model can be seen as problematic in this context, and have been moved to a custom '%1$s' type group. Show GOV hierarchy for Show additional info Show objects of type group '%1$s' in hierarchy Subsequently, all data is retrieved again from the GOV server.  The GOV server provides place names in different languages. However, there is no concept of an 'official language' for a place. The GOV server seems to be temporarily unavailable. The current user language always has the highest priority. These languages are then used, in the given order, either as fallbacks, or (if upper-cased) as additional languages (i.e. 'official languages' for a place hierarchy). This option mainly exists for demo servers and is not recommended otherwise. It has precedence over the preceding option. This policy hasn't been strictly followed though. Check this option if you still want to display settlements in hierarchies. Use NuSOAP instead of SoapClient Use place names and link to places existing in webtrees, additionally link to GOV via icons Use place names and links from GOV Use place names and links from GOV, additionally link to places existing in webtrees via icons Usually only required in case of substantial changes of the GOV data.  Vesta Gov4Webtrees: The displayed GOV hierarchy now additionally links to webtrees places where possible. You can switch back to the classic display (and others) via the module configuration. When this option is disabled, an alternative edit control is provided, which still allows to reload place hierarchies from the GOV server. Where to edit and store GOV ids Within GEDCOM data (via other custom modules).  You can create and modify this csv file according to your personal preferences, see '%1$s' for an example. You may also save an empty id in order to remove the mapping. You may modify all data retrieved from the GOV server %1$s. both date of event fallback to German place names for events without a date, present time hierarchy will be used regardless of this preference. here motivated by the assumption that place names in the local language are more useful in general  present time reload the GOV place hierarchy reset all cached data once this place does not exist at this point in time today unknown GOV type (newly introduced?) with local modifications Project-Id-Version: Czech (Vesta Webtrees Custom Modules)
Report-Msgid-Bugs-To: 
PO-Revision-Date: YEAR-MO-DA HO:MI+ZONE
Last-Translator: FULL NAME <EMAIL@ADDRESS>
Language-Team: Czech <https://hosted.weblate.org/projects/vesta-webtrees-custom-modules/vesta-gov4webtrees/cs/>
Language: cs
MIME-Version: 1.0
Content-Type: text/plain; charset=UTF-8
Content-Transfer-Encoding: 8bit
Plural-Forms: nplurals=3; plural=(n==1) ? 0 : (n>=2 && n<=4) ? 1 : 2;
X-Generator: Weblate 4.9-dev
 'Klasický' způsob, rozšířený o propojení k místům z údajů GEDCOM, je-li to možné. 'Klasický' způsob. (Proč se němčina takto vyzdvihuje? Protože zeměpisný slovník GOV je soustředěn právě kolem němčiny a proto mnohá místa mají německé názvy). Modul začleňující údaje GOV (historický zeměpisný slovník). Modul začleňující údaje GOV (historický zeměpisný slovník). Vylepšuje místa o údaje GOV přes záložku Fakta a události. Podle současné specifikace GOV sídliště nemohou být rodiči jiných sídlišť. Modul dodatečně kontroluje, zda příslušný GOV id, nebo kterýkoliv z hierarchie nad ním, má jazyky definované v csv souboru' %1$s'. Administrativní Administrativní (úroveň %1$s) Administrativní (ostatní) Administrativní úrovně Všechny údaje (vyjma údajů pro mapování míst na GOV idy, což se musí udělat ručně) se berou ze serveru GOV a interně se ukládají. Jako konečný záchytný bod určit název místa podle tohoto zatrhávacího políčka: Tuto možnost zaškrtněte, pokud přesto chcete, aby se organizace objevovaly v hierarchiích, např. organizace OSN jako objekt vyšší úrovně suverénních entit. Kompaktní zobrazení (administrační údaje jen jako tooltip) Tudíž informace o hierarchii místa lze měnit pouze nepřímo, přes web GOV. Zobrazit tooltip naznačující pramen GOV idu. Hlavně pro účely debuggingu. Požadavky směrem k serveru GOV provádět skrze NuSOAP, nikoliv s použitím vrozeného php SoapClient. Vrozený SoapClient je obvykle aktivován (lze ověřit nahlédnutím do nastavení php.ini), ale nemusí jej poskytovat všichni hostitelé. Jestliže vrozený klient není aktivován ani dostupný, pak se tato možnost volí automaticky. Pro dané místo tento modul zobrazí jeden nebo více názvů porovnáním dostupných názvů se seznamem jazyků v souladu s následujcí strategií: Pro události s rozsahem data použít středové datum Chcete-li provést jemnější úpravy a zobrazit seznam typů a skupin typů, upravte data GOV lokálně. Správa GOV id identifikátor GOV GOV id pro %1$s byl odstraněn. GOV id pro %1$s je nastaven na %2$s. GOV idy se normálně ukládají mimo údaje GEDCOM, ale je možno je ukládat i přes tagy _GOV. Objekty GOV patří do různých typových skupin. Hierarchie míst GOV je založena na objektech skupiny typů ' %1$s'. GOV hierarchie místa pro %1$s byla znovu načtena ze serveru GOV. GOV hierarchie místa byla znovu načtena ze serveru GOV. Skrýt objekt a zastavit hierarchii míst v daném bodě jeho přesunutím do nerelevantní skupiny typů. Je-li zvoleno, pokusí se zůstat u německého názvu místa.  Je-li toto zvoleno, zobrazená hierarchie GOV bere jména míst pokud možno z údajů GEDCOM. Je-li zvolena tato možnost, obvykle se chce potlačit následující možnost.  Není-li zvoleno, preferuje se libovolný jiný jazyk než němčina.  V každém případě se použijí jako záložní pro určení dalších objektů vyšší úrovně. Obecně platí, že objekt skryjete při zachování celkové hierarchie míst tak, že jej přesunete do skupiny skrytých typů (viz předvolby). Zejména můžete chtít lokálně vrátit některé kontroverzní změny provedené na serveru GOV (například typ objektu Svatá říše římská). Zejména pro manipulaci s GOV idy uvnitř údajů GEDCOM se dá použít modul Shared Places (Sdílená místa). V tomto případě jsou GOV idy uloženy v samostatné databázové tabulce, se kterou se musí zacházet manuálně, když se příslušný rodokmen přemisťuje do jiné instalace webtrees.  Vnitřnosti (je-li nutno, přizpůsobené automaticky) Neplatný GOV id! Platné GOV idy jsou např. 'EITTZE_W3091', 'object_1086218'. Doporučuje se zvolit právě jen jednu z následujících možností. Je možno také (dočasně) potlačit (odstavit) veškerou editaci tím, že nezaškrtneme žádnou. Nebude přepisován pozdějšími aktualizacemi. Justiční Lokální údaje GOV Lokální modifikace jsou zachovány. Vyhledat odpovídající GOV id na serveru GOV Nedotkne se mapování míst na GOV idy. Poznámka: zobrazení místa na GOV id se ukládá mimo údaje GEDCOM. Poznámka: nakonec dostane pravděpodobně přednost opravit údaje GOV samotné. Objekty tohoto typu striktně nepatří do správní hierarchie v tom smyslu, že nejsou územními celky (Gebietskörperschaften). Zjevné chyby by měly být opraveny na samotném serveru GOV, ale mohou nastat případy, kdy to není snadno možné. Organizační Jiné Jinak se použije datum počátku (to se více shoduje s ostatními výpočty ve webtrees). Mimo údaje GEDCOM Mimo údaje GEDCOM ‒ může editovat kdokoliv (včetně návštěvníků) Zvláště užitečné, má-li se zacházet s GOV idy skrze modul Sdílená místa (Shared Places). Idy se ukládají a dají se exportovat skrze tagy GEDCOM.  Hierarchie míst se zobrazují historicky, t.j. podle data příslušné události. Hierarchie míst Názvy míst z GOV Text a propojení místa Náboženský Znovu nastavit GOV id (vně GEDCOMu) a znovu stáhnout GOV hierarchii míst Obnovit GOV id pro %1$s Uložit současný id kvůli novému stažení údajů hierarchie místa ze serveru GOV. Vizte také %1$s pro původní seznam typů a jejich popisů. Nastavit GOV id (vne GEDCOMu) Nastavit GOV id pro %1$s Sídliště Několik typů objektů, které jsou součástí této skupiny typů v původním modelu, lze v tomto kontextu považovat za problematické a byly přesunuty do vlastní skupiny typů '%1$s'. Zobrazit hierarchii GOV pro Zobrazit přidané informace Zobrazit v hierarchii objekty typové skupiny '%1$s' Následně se všechny údaje znovu odeberou ze serveru GOV.  Server GOV poskytuje názvy míst v různých jazycích. Avšak neexistuje tam pojem 'úřední jazyk' pro místo. GOV server je pravděpodobně dočasně nedostupný. Jazyk uživatele má vždy nejvyšší prioritu. Tyto jazyky se pak použijí v daném pořadí buď jako náhradní jazyky, nebo (jsou-li psány velkými písmeny) jako dodatečné jazyky (tj. 'úřední jazyky' pro hierarchii míst). Tato možnost existuje hlavně pro předváděcí servery a jinak se nedoporučuje. Má přednost před předcházející volbou. Tato zásada však nebyla striktně dodržována. Tuto možnost zaškrtněte, pokud přesto chcete zobrazovat sídla v hierarchiích. Místo SoapClient použít NuSOAP Použít názvy a propojení míst na místa existující ve webtrees, navíc s propojením na GOV skrze ikony Použít názvy a propojení míst z GOV Použít názvy a propojení míst z GOV, navíc skrze ikony propojení na místa existující ve webtrees Obvykle požadováno jen v případě podstatných změn údajů GOV.  Vesta Gov4Webtrees: Tam, kde je to možné, zobrazená hierarchie GOV nyní navíc odkazuje na místa webtrees. V konfiguraci modulu můžete přepnout zpět na klasické (a jiná) zobrazení . Když je tato volba odmítnuta, je po ruce alternativní řízení editace, které stále dovoluje stáhnout hierarchie míst ze serveru GOV. Kde editovat a ukládat GOV idy Uvnitř údajů GEDCOM (skrze jiné moduly).  Tento csv soubor můžete vytvořit a upravit podle svých osobních preferencí, pro příklad vizte '%1$s'. Je možno také uložit prázdný id a tím odstranit zobrazení (mapování). Všechna data získaná ze serveru GOV můžete upravovat %1$s. obojí datum události zůstat u německých názvů míst pro nedatované události se použije hierarchie v současnosti bez ohledu na preferenci. tady motivováno předpokladem, že názvy míst v místním jazyce jsou obecně užitečnější.  současnost znovu stáhnout GOV hierarchii míst znovu nastavit všechna cachovaná data jednorázově toto místo v daném čase neexistuje dnes neznámý GOV typ (nově zavedený?) s lokálními úpravami 