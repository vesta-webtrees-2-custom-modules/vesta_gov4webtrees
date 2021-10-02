<?php

declare(strict_types=1);

namespace Cissee\Webtrees\Module\Gov4Webtrees\Http\RequestHandlers;

use Cissee\Webtrees\Module\Gov4Webtrees\FunctionsGov;
use Cissee\Webtrees\Module\Gov4Webtrees\GovProperty;
use DateTime;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use function redirect;
use function route;

/**
 * Controller for maintaining GOV data.
 */
class GovDataSave implements RequestHandlerInterface
{
    /**
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $params = (array) $request->getParsedBody();

        //error_log(print_r($params, true));
        
        $key = (int)$params['key'];
        $type = $params['type'];
        $sticky = (bool)$params['sticky'];
        
        $govId = $params['govId'];
        $from = $params['from'];
        $to = $params['to'];
        
        $fromJD = null;
        if ($from != null) {
          //$fromJD = (Carbon::parse($from))->julianDay(); is off by one wtf (timezone issue?)
          $dateTime = DateTime::createFromFormat('Y-m-d', $from);          
          $fromJD = gregoriantojd(
                  (int)$dateTime->format('m'), 
                  (int)$dateTime->format('d'), 
                  (int)$dateTime->format('Y'));
        }
        
        $toJD = null;
        if ($to != null) {
          $dateTime = DateTime::createFromFormat('Y-m-d', $to);          
          $toJD = gregoriantojd(
                  (int)$dateTime->format('m'), 
                  (int)$dateTime->format('d'), 
                  (int)$dateTime->format('Y'));
        }
        
        $prop = null;
        $language = null;
        
        if ($type == 'label') {
          $prop = $params['prop'];
          
          $text = $params['text'];
          if (($text !== null) && (is_array($text))) {
            $language = reset($text);
            
            //meh (why do we use text[] anyway? cf gov-language-edit-control)
          }
          
        } else if ($type == 'type') {
          $text = $params['text'];
          if (($text !== null) && (is_array($text))) {
            $prop = (int)reset($text);
            
            //meh (why do we use text[] anyway? cf gov-type-id-edit-control)
            if ($prop === 0) {
              $prop = null;
            }
          }
        } else if ($type == 'parent') {
          $prop = $params['prop'];
        }
        
        /*
        error_log("key: " . $key);
        error_log("type: " . $type);
        error_log("sticky: " . $sticky);
        
        error_log("prop: " . $prop);
        error_log("language: " . $language);
        error_log("fromJD: " . $fromJD);
        error_log("toJD: " . $toJD);
        */
        
        if (($key !== null) && ($govId !== null) && ($prop !== null)) {
          $govProperty = new GovProperty(
                $key, 
                $govId, 
                $prop, 
                $language, 
                $fromJD, 
                $toJD, 
                $sticky);
          
          if ($type == 'label') {
            FunctionsGov::setPropLabel($govProperty);
          } else if ($type == 'type') {
            FunctionsGov::setPropType($govProperty);
          } else if ($type == 'parent') {
            FunctionsGov::setPropParent($govProperty);
          }
          
          //$message = MoreI18N::xlate('The details for “%s” have been updated.', e($key));
          //FlashMessages::addMessage($message, 'success');
        }

        $url = route(GovData::class, ['gov_id' => $govId]);

        return redirect($url);
    }
}
