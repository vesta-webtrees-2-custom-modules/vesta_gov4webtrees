<?php

namespace Cissee\Webtrees\Module\Gov4Webtrees\Model;

use Cissee\Webtrees\Module\Gov4Webtrees\FunctionsGov;
use Cissee\Webtrees\Module\Gov4Webtrees\Gov4WebtreesModule;
use Cissee\Webtrees\Module\Gov4Webtrees\GovObject;
use Cissee\Webtrees\Module\Gov4Webtrees\GovProperty;
use Cissee\Webtrees\Module\Gov4Webtrees\ResolvedProperty;
use Cissee\WebtreesExt\MoreI18N;
use Exception;
use Fisharebest\Localization\Locale\LocaleInterface;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Module\ModuleInterface;
use Fisharebest\Webtrees\Tree;
use Illuminate\Support\Collection;
use Vesta\Hook\HookInterfaces\FunctionsPlaceUtils;
use Vesta\Model\GenericViewElement;
use Vesta\Model\GovReference;
use Vesta\Model\LocReference;
use Vesta\Model\PlaceStructure;
use Vesta\Model\Trace;
use function view;

class GovHierarchyUtils {

    //for nuaoap settings, language settings (legacy),
    //and resources folder for language overrides,
    //and name for views
    protected Gov4WebtreesModule $module;
    protected Tree $tree; //for gov2plac
    protected int $version;

    public function __construct(
        Gov4WebtreesModule $module,
        Tree $tree,
        int $version = -1) {

        $this->module = $module;
        $this->tree = $tree;
        $this->version = $version;
    }

    protected function retrieveGovObject(
        string $id): ?GovObject {

        /** @var GovObject $gov */
        $gov = FunctionsGov::retrieveGovObject(
            $this->module,
            $id,
            $this->version);

        if ($this->version === -1) {
            //replace placeholder version:
            //in general, use version as set on initial object in hierarchy
            //after a reset, object is reloaded from server, and now() is used as version (triggering reloading of parents)
            $this->version = $gov->getVersion();
        }

        return $gov;
    }

    ////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////

    public static function getHierarchyGVE(
            bool $compactDisplay,
            string $julianDayText,
            ?string $tooltip,
            GovHierarchy $govHierarchy): GenericViewElement {

        $id = $govHierarchy->govId();
        $hierarchy = $govHierarchy->labelsHtml();
        $hierarchy2 = $govHierarchy->typesHtml();
        $hasLocalModifications = $govHierarchy->hasLocalModifications();

        if ($id !== null) {
            //Issue #11
            $link = "https://gov.genealogy.net/item/show/" . $id;
        } else {
            //not sure what this fallback is good for
            $link = "https://gov.genealogy.net/";
        }

        $span = '<div><span class="govText">';
        $span .= '<a href="' . $link . '" target="_blank">';
        //we'd like to use far fa-compass but we'd have to import explicitly
        //TODO: use proper modal here? tooltip isn't helpful on tablets etc
        if ($tooltip) {
            $span .= '<span class="wt-icon-map-gov" title="' . $tooltip . '"><i class="fas fa-play fa-fw" aria-hidden="true"></i></span>';
        } else {
            $span .= '<span class="wt-icon-map-gov"><i class="fas fa-play fa-fw" aria-hidden="true"></i></span>';
        }
        $span .= ' GOV</a>';
        if ($hasLocalModifications) {
            //TODO shorter? replace by help icon?
            $span .= ' (' . I18N::translate('with local modifications') . ')';
        }
        $span .= ' (';
        $span .= $julianDayText;
        $span .= '): ';
        $span .= $hierarchy;
        $span .= '</span>';

        if (!$compactDisplay) {
            $span .= '<div><span class="govText2">';
            $span .= '(' . I18N::translate('Administrative levels') . ': ';
            $span .= $hierarchy2;
            $span .= ')</span>';
        }

        $span .= '</div>';

        //doesn't work "Error resolving module specifier"
        //and anyway seems too much effort just for adding another icon
        /*
          $script =
          '<script type="module">' .
          'import { dom, library } from "@fortawesome/fontawesome-svg-core";' .
          'import {faCompass} from "@fortawesome/free-solid-svg-icons";' .
          'library.add(faCompass);' .
          'dom.watch();' .
          '</script>';
         */

        return GenericViewElement::create($span);
    }

    public static function hierarchy(
        ModuleInterface $module,
        Tree $tree,
        GovReference $govReference,
        int $julianDay): ?GovHierarchy {

        $ret = GovHierarchyUtils::hierarchies(
            $module,
            $tree,
            $govReference,
            JulianDayInterval::single($julianDay),
            true);

        if ($ret->isEmpty()) {
            return null;
        }

        return $ret->pop();
    }

    //throws GOVServerUnavailableException
    /**
     *
     * @param ModuleInterface $module
     * @param Tree $tree
     * @param GovReference $govReference
     * @param JulianDayInterval $interval
     * @param bool $includeNullTypedIntervals
     * @return Collection<GovHierarchy>
     */
    public static function hierarchies(
        ModuleInterface $module,
        Tree $tree,
        GovReference $govReference,
        JulianDayInterval $interval,
        bool $includeNullTypedIntervals = false): Collection {

        $utils = new GovHierarchyUtils($module, $tree);
        $gov = $utils->retrieveGovObject($govReference->getId());

        if ($gov === null) {
            return new Collection();
        }

        $args = $utils->initArgs();

        //initial intervals, per own type
        $filledIntervals = GovHierarchyUtils::collectAndFillIntervals($gov->getTypes(), $interval);

        $ret = new Collection();

        foreach ($filledIntervals as $filledInterval) {
            $typeData = $utils->getRelevantTypeData($gov, $filledInterval);

            //stickyness of type irrelevant from here on, except for hasLocalModifications flag
            $hasLocalModificationsViaType = ($typeData['typeLevel'] > 3);
            $type = $typeData['type'];

            if ($includeNullTypedIntervals || ($type !== null)) {
                $request = new GovHierarchyRequest(
                    $govReference->getId(),
                    $type,
                    $hasLocalModificationsViaType,
                    $filledInterval);

                $hierarchies = $utils->getHierarchies($request, true, $args);

                $ret = $ret->merge($hierarchies);

            }
        }

        $ret = GovHierarchyUtils::joinSuccessiveIntervals($ret);

        //TODO cache here? otherwise retrieved twice or additional facts

        return $ret;
    }

    ////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////

    //throws GOVServerUnavailableException
    /**
     *
     * @param GovHierarchyRequest $request
     * @param bool $isHead
     * @param GovHierarchyRequestArgs $args init via initArgs methods
     * @return Collection<GovHierarchy>
     */
    protected function getHierarchies(
        GovHierarchyRequest $request,
        bool $isHead,
        GovHierarchyRequestArgs $args): Collection {

        /** @var ?string $ref */
        $ref = $request->govId();

        if ($ref === null) {
            //terminate
            $parentless = GovHierarchy::empty($request->interval(), $args->languages());
            return new Collection([$parentless]);
        }

        $type = $request->type();
        $hasLocalModifications = $request->hasLocalModifications();

        /** @var GovObject $gov */
        $gov = $this->retrieveGovObject($ref);

        if ($gov === null) {
            return new Collection();
        }

        //$returnNullInCaseOfNoOverrides: preserve any higher level overrides
        //(without this param would revert to defaults)
        $languagesAdjusted = GovHierarchyUtils::getResolvedLanguages($this->module, $args->locale(), $ref, true);

        //1. determine intervals for parent ids
        $parentRequests = $this->parentIntervals($gov, $request->interval());

        $ret = new Collection();

        /** @var GovHierarchyRequest $parentRequest */
        foreach ($parentRequests as $parentRequest) {

            //2. retrieve for each parent
            $parentHierarchies = $this->getHierarchies($parentRequest, false, $args);

            /** @var GovHierarchy $parentHierarchy */
            foreach ($parentHierarchies as $parentHierarchy) {

                //these are either global defaults or actually adjusted languages, trickling down from parent
                $languages = $parentHierarchy->adjustedLanguages();

                if ($languagesAdjusted !== null) {
                    $languages = $languagesAdjusted;
                }

                //3. determine name intervals, x with $parentHierarchy
                $myHierarchies = GovHierarchyUtils::myHierarchies(
                    $gov,
                    $isHead,
                    $type,
                    $hasLocalModifications,
                    $languages,
                    $parentHierarchy,
                    $args);

                $ret = $ret->merge($myHierarchies);
            }
        }

        //sufficient to do this in final step
        //$ret = GovHierarchyUtils::joinSuccessiveIntervals($ret);

        return $ret;
    }

    //levels (higher is better)
    //7 adm, sticky
    //6 settlement, sticky
    //5 org, sticky
    //4 any other, sticky (won't use actually for parents)
    //3 adm, non-sticky
    //2 settlement, non-sticky
    //1 org, non-sticky
    //0 any other, non-sticky (won't use actually for parents)

    /**
     *
     * @param GovObject $gov
     * @param JulianDayInterval $interval
     * @param bool $skipStickies
     * @return array with keys 'typeLevel' (hasLocalModifications if > 3), 'type'
     */
    public static function getRelevantTypeData(
        GovObject $gov,
        JulianDayInterval $interval,
        bool $skipStickies = false): array {

        //determine best matching type in this interval
        //(usually max one sticky and max one non-sticky expected,
        //but safer to account for all cases)

        $bestMatchTypeLevel = -1;
        $bestMatchType = null;

        /** @var GovProperty $typeProp */
        foreach ($gov->getTypes() as $typeProp) {
            if (!$typeProp->getSticky() || !$skipStickies) {
                if ($typeProp->getInterval()->overlaps($interval)) {

                    $type = (int)($typeProp->getProp());

                    if (array_key_exists($type, FunctionsGov::admTypes())) {
                        $typeLevel = 3;
                    } else if (array_key_exists($type, FunctionsGov::settlementTypes())) {
                        $typeLevel = 2;
                    } else if (array_key_exists($type, FunctionsGov::orgTypes())) {
                        $typeLevel = 1;
                    } else {
                        $typeLevel = 0;
                    }

                    $typeLevel += $typeProp->getSticky()?4:0;

                    if ($typeLevel > $bestMatchTypeLevel) {
                        $bestMatchType = $type;
                        $bestMatchTypeLevel = $typeLevel;
                    } else if (($typeLevel === $bestMatchTypeLevel) && ($bestMatchType !== null)) {
                        //higher type id wins in case of (theoretical) ties
                        if ($type > $bestMatchType) {
                            $bestMatchType = $type;
                        }
                    }
                }
            }
        }

        //normalize
        if ($bestMatchTypeLevel === -1) {
            $bestMatchTypeLevel = 0;
        }

        return [
            'typeLevel' => $bestMatchTypeLevel,
            'type' => $bestMatchType];
    }

    /**
     *
     * @param GovObject $gov
     * @param JulianDayInterval $interval
     * @return Collection<GovHierarchyRequest>
     */
    public function parentIntervals(
        GovObject $gov,
        JulianDayInterval $interval): Collection {

        //error_log("-----------------------------------------------------------");

        //FunctionsGov::loadNonLoaded
        //is just a performance optimization not considered to be relevant here!
        //(common case is: no reload required)

        $allRelevantProps = $gov->getParents();

        //we have to analyze types of parents when determining which parent to use
        foreach ($gov->getParents() as $parentProp) {
            $id = $parentProp->getProp();
            $parent = $this->retrieveGovObject($id);

            if ($parent !== null) {
                //this is a bit much because it splits intervals via all parent types,
                //not only those parent types that are within range of the respective parent
                //
                //never mind, we join successive intervals with same id/type in the final step
                $allRelevantProps = array_merge($allRelevantProps, $parent->getTypes());
            }
        }

        //includes parentless intervals!
        $filledIntervals = GovHierarchyUtils::collectAndFillIntervals($allRelevantProps, $interval);

        $ret = new Collection();

        /*
        error_log("filled intervals for " . $gov->getId());
        foreach ($filledIntervals as $filledInterval) {
            error_log("interval " . $filledInterval->asGedcomDateInterval()->toGedcomString(2, true));
        }
        */

        $stickyParents = [];

        //any sticky parent invalidates non-sticky intervals for the same id!
        foreach ($gov->getParents() as $parentProp) {
            if ($parentProp->getSticky()) {
                $id = $parentProp->getProp();
                $stickyParents[$id] = $id;
            }
        }

        foreach ($filledIntervals as $filledInterval) {

            //error_log("build for interval " . $filledInterval->asGedcomDateInterval()->toGedcomString(2, true));

            //hasLocalModifications doesn't only depend on stickyness of best match:
            //a non-sticky match may only be best due to invalidations,
            //and a sticky match may have matched anyway
            //
            //so we have to determine second match, ignoring all stickies,
            //and check against actual match

            ////////////////////////////////////////////////////////////////////

            $bestMatchParentId = null;
            $bestMatchParentType = null;
            $bestMatchParentLevel = 0;

            /** @var GovProperty $parentProp */
            foreach ($gov->getParents() as $parentProp) {

                $id = $parentProp->getProp();

                //error_log("check parent " . $id);

                $parent = $this->retrieveGovObject($id);

                $invalidated = !$parentProp->getSticky() && array_key_exists($id, $stickyParents);

                //if interval overlaps and this is more relevant than best match, use this!
                //(note that due to how we built $intervals,
                //'overlaps' actually implies the respective prop's interval fully encloses $interval)
                if (!$invalidated && ($parent !== null)) {
                    if ($parentProp->getInterval()->overlaps($filledInterval)) {

                        /*
                        if (!$parentProp->getInterval()->includes($filledInterval)) {
                            throw new \Exception("error in algorithm!");
                        }
                        */

                        /*
                        error_log("parent overlaps!");
                        error_log("parent interval: " . $parentProp->getInterval()->asGedcomDateInterval()->toGedcomString(2, true));
                         */

                        //determine best matching type in this interval

                        $typeData = $this->getRelevantTypeData($parent, $filledInterval);

                        //stickyness of type irrelevant from here on
                        $parentLevel = $typeData['typeLevel'] % 4;
                        $parentType = $typeData['type'];

                        //sticky parents invalidate non-sticky parents, regardless of type
                        $parentLevel += $parentProp->getSticky()?4:0;

                        if ($parentLevel > $bestMatchParentLevel) {
                            $bestMatchParentId = $id;
                            $bestMatchParentType = $parentType;
                            $bestMatchParentLevel = $parentLevel;
                        } else if (($parentLevel === $bestMatchParentLevel) && ($bestMatchParentId !== null)) {
                            //lower parent id wins in case of ties
                            if (strcmp($id, $bestMatchParentId) < 0) {
                                $bestMatchParentId = $id;
                                $bestMatchParentType = $parentType;

                                //TODO mark as ambiguous!
                            }
                        }
                    }
                }
            } //end foreach ($gov->getParents() as $parentProp)

            ////////////////////////////////////////////////////////////////////
            //now check without stickies

            $bestMatchParentIdNonS = null;
            $bestMatchParentTypeNonS = null;
            $bestMatchParentLevelNonS = 0;

            /** @var GovProperty $parentProp */
            foreach ($gov->getParents() as $parentProp) {
                $id = $parentProp->getProp();
                $parent = $this->retrieveGovObject($id);

                $skipped = $parentProp->getSticky();

                //if interval overlaps and this is more relevant than best match, use this!
                //(note that due to how we built $intervals,
                //'overlaps' actually implies the respective prop's interval fully encloses $interval)
                if (!$skipped && ($parent !== null)) {
                    if ($parentProp->getInterval()->overlaps($filledInterval)) {

                        //determine best matching type in this interval

                        $typeData = $this->getRelevantTypeData($parent, $filledInterval, true);

                        //stickyness of type irrelevant from here on (unexpected here anyway)
                        $parentLevel = $typeData['typeLevel'] % 4;
                        $parentType = $typeData['type'];

                        //sticky parents invalidate non-sticky parents, regardless of type
                        //but we aren't sticky!

                        if ($parentLevel > $bestMatchParentLevelNonS) {
                            $bestMatchParentIdNonS = $id;
                            $bestMatchParentTypeNonS = $parentType;
                            $bestMatchParentLevelNonS = $parentLevel;
                        } else if (($parentLevel === $bestMatchParentLevelNonS) && ($bestMatchParentIdNonS !== null)) {
                            //lower parent id wins in case of ties
                            if (strcmp($id, $bestMatchParentIdNonS) < 0) {
                                $bestMatchParentIdNonS = $id;
                                $bestMatchParentTypeNonS = $parentType;

                                //TODO mark as ambiguous!
                            }
                        }
                    }
                }
            } //end foreach ($gov->getParents() as $parentProp)


            //do we have a parent with a usable level?
            //otherwise reset and return parentless interval
            if (($bestMatchParentLevel % 4) === 0) {
                $bestMatchParentId = null;
                $bestMatchParentType = null;
            } else {
                assert ($bestMatchParentId !== null);
                assert ($bestMatchParentType !== null);
            }

            $bestMatchParentHasLocalModifications =
                ($bestMatchParentId !== $bestMatchParentIdNonS) ||
                ($bestMatchParentType !== $bestMatchParentTypeNonS);

            $ret->push(new GovHierarchyRequest(
                $bestMatchParentId,
                $bestMatchParentType,
                $bestMatchParentHasLocalModifications,
                $filledInterval));
        }

        //join successive intervals with same id/type
        //(optimization, functionally not required as we have to repeat similar op in subsequent steps)
        $ret = GovHierarchyUtils::joinSuccessiveRequestIntervals($ret);

        /*
        error_log("parent requests for " . $gov->getId());
        error_log("parent requests for interval " . $interval->asGedcomDateInterval()->toGedcomString(2, true));

        foreach ($ret as $r) {
            error_log("----");
            error_log("parent request for " . $r->govId());
            error_log("parent request type " . $r->type());
            error_log("parent request hasLocalModifications " . $r->hasLocalModifications());
            error_log("parent request for interval " . $r->interval()->asGedcomDateInterval()->toGedcomString(2, true));
        }
        */

        return $ret;
    }

    /**
     *
     * @param Collection<GovHierarchyRequest> $govHierarchyRequests
     * @return Collection<GovHierarchyRequest>
     */
    public static function joinSuccessiveRequestIntervals(
        Collection $govHierarchyRequests): Collection {

        $curr = null;

        $ret = new Collection();

        /** @var GovHierarchyRequest $govHierarchyRequest */
        foreach ($govHierarchyRequests as $govHierarchyRequest) {

            if ($curr === null) {
                //initialize
                $curr = $govHierarchyRequest;

            } else if (
                ($curr->govId() === $govHierarchyRequest->govId()) &&
                ($curr->type() === $govHierarchyRequest->type()) &&
                ($curr->hasLocalModifications() === $govHierarchyRequest->hasLocalModifications())) {

                //merge
                $mergedInterval = $curr->interval()->append($govHierarchyRequest->interval());
                if ($mergedInterval === null) {
                    throw new Exception('unexpected: non-successive intervals');
                }

                $curr = new GovHierarchyRequest(
                    $curr->govId(),
                    $curr->type(),
                    $curr->hasLocalModifications(),
                    $mergedInterval);

            } else {
                //just move on
                $ret->push($curr);
                $curr = $govHierarchyRequest;
            }
        }

        if ($curr !== null) {
            $ret->push($curr);
        }

        return $ret;
    }

    /**
     *
     * @param Collection<GovHierarchy> $govHierarchies
     * @return Collection<GovHierarchy>
     */
    public static function joinSuccessiveIntervals(
        Collection $govHierarchies): Collection {

        $curr = null;

        $ret = new Collection();

        //error_log("joinSuccessiveIntervals");

        /** @var GovHierarchy $govHierarchy */
        foreach ($govHierarchies as $govHierarchy) {

            /*
            error_log("----");
            error_log($govHierarchy->govId());
            error_log($govHierarchy->labelsHtml());
            error_log($govHierarchy->typesHtml());
            error_log($govHierarchy->hasLocalModifications());
            */

            if ($curr === null) {
                //initialize
                $curr = $govHierarchy;

            } else if (
                ($curr->govId() === $govHierarchy->govId()) &&
                //($curr->adjustedLanguages() === $govHierarchy->adjustedLanguages()) &&
                ($curr->labelsHtml() === $govHierarchy->labelsHtml()) &&
                ($curr->typesHtml() === $govHierarchy->typesHtml()) &&
                ($curr->hasLocalModifications() === $govHierarchy->hasLocalModifications())) {

                //merge
                $mergedInterval = $curr->interval()->append($govHierarchy->interval());
                if ($mergedInterval === null) {
                    //throw new Exception('unexpected: non-successive intervals');
                    //no, this is possible for initial object in hierarchy
                    //if it doesn't exist for a while

                    //just move on
                    $ret->push($curr);
                    $curr = $govHierarchy;
                } else {
                    $curr = new GovHierarchy(
                        $curr->govId(),
                        $mergedInterval,
                        $curr->adjustedLanguages(),
                        $curr->labelsHtml(),
                        $curr->typesHtml(),
                        $curr->hasLocalModifications());
                }

            } else {
                //just move on
                $ret->push($curr);
                $curr = $govHierarchy;
            }
        }

        if ($curr !== null) {
            $ret->push($curr);
        }

        return $ret;
    }

    /**
     *
     * @param GovObject $gov
     * @param int $type has already been determined, no need to resolve via $gov, null only possible for initial object in hierarchy
     * @param GovHierarchy $parentHierarchy
     * @return Collection<GovHierarchy>
     */
    public function myHierarchies(
        GovObject $gov,
        bool $isHead,
        ?int $type,
        bool $hasLocalModifications,
        array $languages, //already adjusted
        GovHierarchy $parentHierarchy,
        GovHierarchyRequestArgs $args): Collection {

        if ($isHead) {
            $show = true;
        } else if (array_key_exists($type, FunctionsGov::admTypes())) {
            $show = true;
        } else if (array_key_exists($type, FunctionsGov::settlementTypes())) {
            $show = $args->showSettlements();
        } else if (array_key_exists($type, FunctionsGov::orgTypes())) {
            $show = $args->showOrganizational();
        } else {
            //including type === null
            $show = true;
        }

        if (!$show) {
            $hasLocalModifications =
                $hasLocalModifications ||
                $parentHierarchy->hasLocalModifications();

            $ret = new GovHierarchy(
                $gov->getId(),
                $parentHierarchy->interval(),
                $languages,
                $parentHierarchy->labelsHtml(),
                $parentHierarchy->typesHtml(),
                $hasLocalModifications);

            return new Collection([$ret]);
        }

        $interval = $parentHierarchy->interval();

        //includes labelless intervals!
        $intervals = GovHierarchyUtils::collectAndFillIntervals($gov->getLabels(), $interval);

        $ret = new Collection();

        foreach ($intervals as $interval) {

            //pick any (but consistently), per language
            //but prefer stickies
            $labelsAsResolvedProperties = [];

            //simpler than in case of parent rels wrt stickyness, because there are no invalidations here?

            /** @var GovProperty $labelProp */
            foreach ($gov->getLabels() as $labelProp) {

                //(note that due to how we built $intervals,
                //'overlaps' actually implies the respective prop's interval fully encloses $interval)

                if ($labelProp->getInterval()->overlaps($interval)) {

                    //this block cf FunctionsGov::retrieveLabels()

                    $label = $labelProp->getProp();
                    $language = $labelProp->getLanguage(); //may be null!
                    $sticky = $labelProp->getSticky();

                    if (!array_key_exists($language, $labelsAsResolvedProperties)) {
                        $replace = true;
                    } else {
                        $currLabel = $labelsAsResolvedProperties[$language]->getProp();
                        $currSticky = $labelsAsResolvedProperties[$language]->getSticky();

                        if ($sticky === $currSticky) {
                            if (strlen($label) === strlen($currLabel)) {
                                //replace if 'lower' (this is just in order to have deterministic outcome)
                                $replace = (strcmp($label, $currLabel) < 0);
                            } else {
                                //prefer shorter texts ('Germany' vs 'Federal Republic of Germany')
                                $replace = (strlen($label) < strlen($currLabel));
                            }
                        } else {
                            $replace = $sticky; //in which case !$currSticky
                        }
                    }

                    if ($replace) {
                        $labelsAsResolvedProperties[$language] = new ResolvedProperty($label, $sticky);
                    }
                }
            }

            $label = FunctionsGov::getResolvedLabel($labelsAsResolvedProperties, $languages);
            $resolvedLabel = $label->getProp();

            //assuming there is an actual change in this case,
            //strictly we should determine non-sticky label and compare
            $labelHasLocalModifications = $label->getSticky();

            //also occurs if types.owl isn't up-to-date unfortunately (misleading).
            $nullType = I18N::translate('this place does not exist at this point in time');

            $resolvedType = FunctionsGov::resolveTypeDescription(
                $this->module,
                $type,
                $args->languagesForTypes());

            ////////////////////////////////////////////////////////////////////
            //////// formatting

            if ($resolvedType === null) {
                $typeAndLabel = $resolvedLabel . ' (' . $nullType . ')';
                $displayedLabel = '<s>' . $resolvedLabel . '</s>'; //strikethrough
                $typesHtml = $nullType;
            } else {
                $typeAndLabel = $resolvedType . ' ' . $resolvedLabel;
                $displayedLabel = $resolvedLabel;
                $typesHtml = $resolvedType;
            }

            $labelsHtml = '';

            switch ($args->withInternalLinks()) {
                case 0: //classic
                    $labelsHtml .= '<a href="https://gov.genealogy.net/item/show/' . $gov->getId() . '" target="_blank" title="' . $typeAndLabel . '">';
                    $labelsHtml .= $displayedLabel;
                    $labelsHtml .= '</a>';
                    break;
                case 1: //classic plus place/shared place icons
                case 2: //names and main links to place, plus gov icons
                    $pre = '';

                    //Issue #13: is this a known webtrees place?
                    //(note: there may be more than one - we restrict to first found)
                    $ps = FunctionsPlaceUtils::gov2plac($this->module, new GovReference($gov->getId(), new Trace("")), $this->tree);
                    if ($ps !== null) {

                        //link to location? note: for now not indirectly = only if location defines the GOV!
                        $loc = $ps->getLoc();
                        if ($loc !== null) {
                            $locLink = FunctionsPlaceUtils::loc2linkIcon($this->module, new LocReference($loc, $this->tree, new Trace('')));
                            if ($locLink !== null) {
                                $pre = $locLink;
                            }
                        }
                    }

                    switch ($args->withInternalLinks()) {
                        case 1: //classic plus place/shared place icons
                            if (($ps !== null) && ($pre === '')) {
                                $pre = $this->plac2LinkIcon($ps);
                            }
                            $labelsHtml .= $pre;
                            $labelsHtml .= '<a href="https://gov.genealogy.net/item/show/' . $gov->getId() . '" target="_blank" title="' . $typeAndLabel . '">';
                            $labelsHtml .= $displayedLabel;
                            $labelsHtml .= '</a>';
                            break;
                        case 2: //names and main links to place, plus gov icons
                            if ($ps !== null) {
                                $labelsHtml .= '<a href="https://gov.genealogy.net/item/show/' . $gov->getId() . '" target="_blank" title="GOV: ' . $typeAndLabel . '">';
                                $labelsHtml .= '<span class="wt-icon-map-gov"><i class="fas fa-play fa-fw" aria-hidden="true"></i></span>';
                                $labelsHtml .= '&#8239;'; //meh (Narrow no-break space), should do this with css instead
                                $labelsHtml .= '</a>';
                                $labelsHtml .= $pre;
                                $labelsHtml .= '<a dir="auto" href="' . e($ps->getPlace()->url()) . '">' . $ps->getPlace()->placeName() . '</a>';
                            } else {
                                $labelsHtml .= '<a href="https://gov.genealogy.net/item/show/' . $gov->getId() . '" target="_blank" title="GOV: ' . $typeAndLabel . '">';
                                $labelsHtml .= '<span class="wt-icon-map-gov"><i class="fas fa-play fa-fw" aria-hidden="true"></i></span>';
                                $labelsHtml .= '&#8239;'; //meh (Narrow no-break space), should do this with css instead
                                $labelsHtml .= '</a>';
                                $labelsHtml .= '<i>' . $displayedLabel . '</i>';
                            }
                            break;
                        default:
                            break;
                    }
                    break;
                default:
                    break;
            }

            $labelsTail = $parentHierarchy->labelsHtml();
            if ($labelsTail !== '') {
                $labelsHtml .= ', ';
                $labelsHtml .= $labelsTail;
            }

            if (!$args->compactDisplay()) {
                $typesTail = $parentHierarchy->typesHtml();
                if ($typesTail !== '') {
                    $typesHtml .= ', ';
                    $typesHtml .= $typesTail;
                }
            }

            $hasLocalModifications =
                $hasLocalModifications ||
                $labelHasLocalModifications ||
                $parentHierarchy->hasLocalModifications();

            $ret->push(new GovHierarchy(
                $gov->getId(),
                $interval,
                $languages,
                $labelsHtml,
                $typesHtml,
                $hasLocalModifications));
        }

        return $ret;
    }

    /**
     * returned collection elements are non-overlapping, sorted, and gapless within $interval
     *
     * @param array $props
     * @param JulianDayInterval $interval
     * @return Collection<JulianDayInterval>
     */
    public static function collectAndFillIntervals(
        array $props,
        JulianDayInterval $interval
    ): Collection {

        $from = $interval->getFrom() ?? PHP_INT_MIN;
        $toExclusively = $interval->getToExclusively() ?? PHP_INT_MAX;

        //single day?
        if ($from === $toExclusively-1) {
            return new Collection([$interval]);
        }

        //every from starts an interval
        //every toExclusively starts an interval
        //(if within given interval)

        $arr = [];

        $arr[$from] = $from;
        $arr[$toExclusively] = $toExclusively;

        /** @var GovProperty $prop */
        foreach ($props as $prop) {
            $propFrom = $prop -> getFrom() ?? PHP_INT_MIN;
            $propToExclusively = $prop -> getTo() ?? PHP_INT_MAX;

            if (($propFrom > $from) && ($propFrom < $toExclusively)) {
                $arr[$propFrom] = $propFrom;
            }

            if (($propToExclusively < $toExclusively) && ($propToExclusively > $from)) {
                $arr[$propToExclusively] = $propToExclusively;
            }
        }

        //sort and re-index
        sort($arr);
        $arr2 = array_values($arr);

        $ret = new Collection();

        //single day handled above, therefore
        assert(sizeof($arr2) > 1);

        for ($i = 0; $i <= sizeof($arr2)-2; $i++) {
            $start = $arr2[$i];
            $nextStart = $arr2[$i+1];

            $from = ($start === PHP_INT_MIN)?null:$start;
            $toExclusively = ($nextStart === PHP_INT_MAX)?null:$nextStart;

            $ret->push(new JulianDayInterval($from, $toExclusively));
        }

        return $ret;
    }

    ////////////////////////////////////////////////////////////////////////////

    public function initArgs(): GovHierarchyRequestArgs {

        $locale = I18N::locale();

        $compactDisplay = boolval($this->module->getPreference('COMPACT_DISPLAY', '1'));
        $withInternalLinks = intval($this->module->getPreference('DISPLAY_INTERNAL_LINKS', '1'));
        $showSettlements = boolval($this->module->getPreference('ALLOW_SETTLEMENTS', '1'));
        $showOrganizational = boolval($this->module->getPreference('ALLOW_ORGANIZATIONAL', '1'));

        return new GovHierarchyRequestArgs(
            $locale,
            GovHierarchyUtils::getResolvedLanguages($this->module, $locale),
            GovHierarchyUtils::getResolvedLanguagesForTypes($this->module, $locale),
            $compactDisplay,
            $withInternalLinks,
            $showSettlements,
            $showOrganizational);
    }

    public static function getResolvedLanguages(
            Gov4WebtreesModule $module,
            LocaleInterface $locale,
            string $govId = '', //empty key = global
            bool $returnNullInCaseOfNoOverrides = false): ?array {

        $overridesFilename = $module->resourcesFolder() . 'gov/languages.csv';

        $languageOverrides = FunctionsGov::getGovObjectLanguageOverrides(
                        $overridesFilename,
                        $govId);

        $languages = [];

        //$lang is always first!
        $lang = FunctionsGov::toLang($locale->languageTag());
        $languages []= $lang;

        if (sizeof($languageOverrides) === 0) {
            if ($returnNullInCaseOfNoOverrides) {
                return null;
            }
        } else {
            $languages = array_merge($languages, $languageOverrides);
        }

        $fallbackPreferDeu = boolval($module->getPreference('FALLBACK_LANGUAGE_PREFER_DEU', '1'));

        if ($fallbackPreferDeu) {
            $languages []= 'deu';
        }

        return $languages;
    }

    public static function getResolvedLanguagesForTypes(
        Gov4WebtreesModule $module,
        LocaleInterface $locale): array {

        $overridesFilename = $module->resourcesFolder() . 'gov/languages.csv';

        $languageOverrides = FunctionsGov::getGovObjectLanguageOverrides(
                        $overridesFilename,
                        ''); //empty key = global

        $languagesForTypes = [];

        //$lang is always first!
        $lang = FunctionsGov::toLang($locale->languageTag());
        $languagesForTypes [] = $lang;

        if (sizeof($languageOverrides) === 0) {
            //cf fallback is old FunctionsGov::retrieveTypeDescription method
            $languagesForTypes [] = 'eng';
            $languagesForTypes [] = 'deu';
        } else {
            $languagesForTypes = array_merge($languagesForTypes, $languageOverrides);
        }

        return $languagesForTypes;
    }

    protected function plac2linkIcon(
        PlaceStructure $ps): string {

        return $this->linkIcon(
                        $this->module->name() . '::icons/place',
                        MoreI18N::xlate('Place'),
                        $ps->getPlace()->url());
    }

    protected function linkIcon(
        string $view,
        string $title,
        string $url): string {

        return '<a href="' . $url . '" rel="nofollow" title="' . $title . '">' .
            view($view) .
            '<span class="visually-hidden">' . $title . '</span>' .
            '</a>';
    }
}
