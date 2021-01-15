<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

/* * ***************************Includes********************************* */
require_once __DIR__  . '/../../../../core/php/core.inc.php';

class jazpar extends eqLogic {
    /*     * *************************Attributs****************************** */

  /*
   * Permet de définir les possibilités de personnalisation du widget (en cas d'utilisation de la fonction 'toHtml' par exemple)
   * Tableau multidimensionnel - exemple: array('custom' => true, 'custom::layout' => false)
	public static $_widgetPossibility = array();
   */

    /*     * ***********************Methode static*************************** */

  public static function cron()
  {
    $cronMinute = config::byKey('cronMinute', __CLASS__);
    if (!empty($cronMinute) && date('i') != $cronMinute) return;

    $eqLogics = self::byType(__CLASS__, true);

    foreach ($eqLogics as $eqLogic)
    {
      if (date('G') < 4 || date('G') >= 22)
      {
        if ($eqLogic->getCache('getJazparData') == 'done') {
          $eqLogic->setCache('getJazparData', null);
        }
        return;
      }

      if ($eqLogic->getCache('getJazparData') != 'done')
      {
        $eqLogic->pullJazpar(false);
      }
    }
  }

    /*     * *********************Méthodes d'instance************************* */

    public function pullJazpar($force)
    {
      $need_refresh = false;
        
      if ($force) {
          $need_refresh = true;
          log::add(__CLASS__, 'debug', 'Manual data refresh triggered');
      } else {
          foreach ($this->getCmd('info') as $eqLogicCmd)
          {
            if (strpos($eqLogicCmd->getLogicalId(), "local") === FALSE) {
                $eqLogicCmd->execCmd();
                if ($eqLogicCmd->getCollectDate() == date('Y-m-d 23:55:00', strtotime('-1 day'))) {
                    log::add(__CLASS__, 'debug', $this->getHumanName() . ' le ' . date('d/m/Y', strtotime('-1 day')) . ' : données déjà présentes pour la commande ' . $eqLogicCmd->getName());
                } else {
                    log::add(__CLASS__, 'debug', $this->getHumanName() . ' le ' . date('d/m/Y', strtotime('-1 day')) . ' : absence de données pour la commande ' . $eqLogicCmd->getName());
                    $need_refresh = true;
                }
                if ($this->getConfiguration('forceRefresh') != 1) {
                    log::add(__CLASS__, 'debug', 'Mode Force Refresh activé');
                    $need_refresh = true;
                }
            }
          }
      }

      if ($need_refresh == true)
      {
        sleep(rand(5,50));
        $cookies = $this->connectJazpar();

        if (!is_null($cookies)) {
            $consoDay = $this->getCmd(null, 'consod');
            if (is_object($consoDay))
            {
              $end = date('d/m/Y', strtotime('-1 day'));
              $start = date('d/m/Y', strtotime('-31 days'));
              $resource_id = 'jour';
              $resultDay = $this->getJazparData($cookies, $resource_id, $start, $end);
            }

            $consoMonth = $this->getCmd(null, 'consom');
            if (is_object($consoMonth))
            {
              $end = date('d/m/Y', strtotime('-1 day'));
              $start = date('d/m/Y', strtotime('-11 months'));
              $resource_id = 'mois';
              $resultMonth = $this->getJazparData($cookies, $resource_id, $start, $end);
            }
            
            if (!$resultDay || !resultMonth) {
                if (date('G') >= 21) {
                    log::add(__CLASS__, 'error', $this->getHumanName() . ' Impossible de récupérer les données aujourd\'hui - Prochain essai demain');
                }
            }
        }
        else {
          log::add(__CLASS__, 'warning', $this->getHumanName() . ' Erreur connexion - Abandon - Prochain essai dans 1 heure');
        }
      }
      else
      {
        if ($this->getCache('getJazparData') != 'done')
        {
          $this->setCache('getJazparData', 'done');
          log::add(__CLASS__, 'info', $this->getHumanName() . ' le ' . date('d/m/Y', strtotime('-1 day')) . ' : toutes les données sont à jour - désactivation de la vérification automatique pour aujourd\'hui');
        }
      }

    }

    public function connectJazpar()
		{
      log::add(__CLASS__, 'info', $this->getHumanName() . ' 1ère étape d\'authentification Jazpar');

      $login = $this->getConfiguration('login');
      $password = $this->getConfiguration('password');

      $curl = curl_init();
      curl_setopt_array($curl, array(
        CURLOPT_URL => "https://monespace.grdf.fr/monespace/connexion",
        CURLOPT_HEADER  => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => array(
          "Accept-Language: fr,fr-FR;q=0.8,en;q=0.6",
          "User-Agent: Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Mobile Safari/537.36",
          "Accept: application/xml, application/json, text/javascript, */*; q=0.01",
          "Accept-Encoding: gzip, deflate, br",
          "Faces-Request: partial/ajax",
          "Sec-Fetch-Mode: no-cors",
          "Sec-Fetch-Site: same-origin",
          "Origin: https://monespace.grdf.fr")
        ));
      $response = curl_exec($curl);
      $responseStatus = curl_getinfo($curl, CURLINFO_HTTP_CODE);
      curl_close($curl);
    
      preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $response, $matches);
      $cookies = array();
      foreach($matches[1] as $item) {
        parse_str($item, $cookie);
        $cookies = array_merge($cookies, $cookie);
      }
      $jsession = $cookies['JSESSIONID_EP'];

      log::add(__CLASS__, 'debug', $this->getHumanName() . ' Session: ' . $jsession);
        
      log::add(__CLASS__, 'info', $this->getHumanName() . ' 2ème étape d\'authentification Jazpar');
        
      $data = array(
        'javax.faces.partial.ajax' => 'true',
        'javax.faces.source' => '_EspacePerso_WAR_EPportlet_:seConnecterForm:meConnecter',
        'javax.faces.partial.execute' => '_EspacePerso_WAR_EPportlet_:seConnecterForm',
        'javax.faces.partial.render' => 'EspacePerso_WAR_EPportlet_:global _EspacePerso_WAR_EPportlet_:groupTitre',
        'javax.faces.behavior.event' => 'click',
        'javax.faces.partial.event' => 'click',
        '_EspacePerso_WAR_EPportlet_:seConnecterForm' => '_EspacePerso_WAR_EPportlet_:seConnecterForm',
        'javax.faces.encodedURL' => 'https://monespace.grdf.fr/web/guest/monespace?p_p_id=EspacePerso_WAR_EPportlet&amp;p_p_lifecycle=2&amp;p_p_state=normal&amp;p_p_mode=view&amp;p_p_cacheability=cacheLevelPage&amp;p_p_col_id=column-2&amp;p_p_col_count=1&amp;_EspacePerso_WAR_EPportlet__jsfBridgeAjax=true&amp;_EspacePerso_WAR_EPportlet__facesViewIdResource=%2Fviews%2FespacePerso%2FseconnecterEspaceViewMode.xhtml',
        '_EspacePerso_WAR_EPportlet_:seConnecterForm:email' => $login,
        '_EspacePerso_WAR_EPportlet_:seConnecterForm:passwordSecretSeConnecter' => $password
      );

      $curl = curl_init();
      curl_setopt_array($curl, array(
        CURLOPT_URL => "https://monespace.grdf.fr/web/guest/monespace?p_p_id=EspacePerso_WAR_EPportlet&p_p_lifecycle=2&p_p_state=normal&p_p_mode=view&p_p_cacheability=cacheLevelPage&p_p_col_id=column-2&p_p_col_count=1&_EspacePerso_WAR_EPportlet__jsfBridgeAjax=true&_EspacePerso_WAR_EPportlet__facesViewIdResource=%2Fviews%2FespacePerso%2FseconnecterEspaceViewMode.xhtml",
        CURLOPT_HEADER  => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => $data,
        CURLOPT_HTTPHEADER => array(
          "Accept-Language: fr,fr-FR;q=0.8,en;q=0.6",
          "User-Agent: Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Mobile Safari/537.36",
          "Accept: application/xml, application/json, text/javascript, */*; q=0.01",
          "Accept-Encoding: gzip, deflate, br",
          "Faces-Request: partial/ajax",
          "Sec-Fetch-Mode: no-cors",
          "Sec-Fetch-Site: same-origin",
          "Origin: https://monespace.grdf.fr",
          "Referer: https://monespace.grdf.fr/monespace/connexion",
          "Cookie: KPISavedRef=https://monespace.grdf.fr/monespace/connexion",
          "Cookie: COOKIE_SUPPORT=true",
          "Cookie: GUEST_LANGUAGE_ID=fr_FR",
          "Cookie: ROUTEID_EP=.1",
          "Cookie: JSESSIONID_EP=".$jsession
          )
        ));
      $response = curl_exec($curl);
      curl_close($curl);

      preg_match('/^.*CDATA\[(.*)\]]/mi', $response, $matches);
      $jvws = $matches[1];
        
      log::add(__CLASS__, 'debug', $this->getHumanName() . ' JVWS (authent): ' . $jvws);
        
      log::add(__CLASS__, 'info', $this->getHumanName() . ' 3ème étape d\'authentification Jazpar');
        
      $data['javax.faces.ViewState'] = $jvws;
        
      
      $curl = curl_init();
      curl_setopt_array($curl, array(
        CURLOPT_URL => "https://monespace.grdf.fr/web/guest/monespace?p_p_id=EspacePerso_WAR_EPportlet&p_p_lifecycle=2&p_p_state=normal&p_p_mode=view&p_p_cacheability=cacheLevelPage&p_p_col_id=column-2&p_p_col_count=1&_EspacePerso_WAR_EPportlet__jsfBridgeAjax=true&_EspacePerso_WAR_EPportlet__facesViewIdResource=%2Fviews%2FespacePerso%2FseconnecterEspaceViewMode.xhtml",
        CURLOPT_HEADER  => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => $data,
        CURLOPT_HTTPHEADER => array(
          "Accept-Language: fr,fr-FR;q=0.8,en;q=0.6",
          "User-Agent: Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Mobile Safari/537.36",
          "Accept: application/xml, application/json, text/javascript, */*; q=0.01",
          "Accept-Encoding: gzip, deflate, br",
          "Faces-Request: partial/ajax",
          "Sec-Fetch-Mode: no-cors",
          "Sec-Fetch-Site: same-origin",
          "Origin: https://monespace.grdf.fr",
          "Referer: https://monespace.grdf.fr/monespace/connexion",
          "Cookie: KPISavedRef=https://monespace.grdf.fr/monespace/connexion",
          "Cookie: COOKIE_SUPPORT=true",
          "Cookie: GUEST_LANGUAGE_ID=fr_FR",
          "Cookie: ROUTEID_EP=.1",
          "Cookie: JSESSIONID_EP=".$jsession   
          )
      ));
      $response = curl_exec($curl);
      curl_close($curl);
      
      preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $response, $matches);
      $cookies = array();
      foreach($matches[1] as $item) {
        parse_str($item, $cookie);
        $cookies = array_merge($cookies, $cookie);
      }

      log::add(__CLASS__, 'debug', $this->getHumanName() . ' Token: ' . $cookies['GRDF_EP']);    
        
      if (isset($cookies['GRDF_EP']) && $cookies['GRDF_EP'] <> '')
      {
        log::add(__CLASS__, 'info', $this->getHumanName() . ' Récupération des informations de session réussie');
        $token = $cookies['GRDF_EP'];
      }
      else
      {
        log::add(__CLASS__, 'warning', $this->getHumanName() . ' Erreur lors de la récupération des informations de session - Abandon - Prochain essai dans 1 heure');
        return null;
      }

      return array($jsession, $token);
   }

   public function getJazparData($cookies, $resource_id, $start, $end)
   {
     log::add(__CLASS__, 'info', $this->getHumanName() . ' Récupération des données ' . $resource_id . ' du ' . $start . ' au ' . $end . " - 1ère étape");

     $curl = curl_init();
     curl_setopt_array($curl, array(
        CURLOPT_URL => "https://monespace.grdf.fr/monespace/particulier/consommation/consommations",
        CURLOPT_HEADER  => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => array(
                "User-Agent: Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Mobile Safari/537.36",
                "Accept-Language: fr,fr-FR;q=0.8,en;q=0.6",
                "Accept-Encoding: gzip, deflate, br", 
                "Accept: application/xml, application/json, text/javascript, */*; q=0.01",
                "Faces-Request: partial/ajax",
				"Host: monespace.grdf.fr",
                "Origin: https://monespace.grdf.fr",
                "Referer: https://monespace.grdf.fr/monespace/particulier/consommation/consommation",
				"Sec-Fetch-Mode: cors",
				"Sec-Fetch-Site: same-origin",
                "X-Requested-With: XMLHttpRequest",
                "Cookie: connectedLUser=0; COOKIE_SUPPORT=true; GUEST_LANGUAGE_ID=fr_FR; ROUTEID_EP=.1; JSESSIONID_EP=".$cookies[0]."; GRDF_EP=".$cookies[1]."; KPISavedRef=https://monespace.grdf.fr/monespace/connexion;")
     ));
     $response = curl_exec($curl);
     curl_close($curl);

     libxml_use_internal_errors(true);
     $dom = new DOMDocument();
     $dom->loadHTML($response);
     $xpath = new DOMXpath($dom);
     $res = $xpath->query("//div[@id='_eConsoconsoDetaille_WAR_eConsoportlet_']/form[@id='_eConsoconsoDetaille_WAR_eConsoportlet_:idFormConsoDetaille']/input[@id='javax.faces.ViewState']/@value");
        
     $jvws = $res[0]->nodeValue;

     log::add(__CLASS__, 'debug', $this->getHumanName() . "JVWS=". $jvws);

     if ($jvws == '') {
        log::add(__CLASS__, 'warning', $this->getHumanName() . ' Erreur lors de la récupération des données (1/4) - Abandon - Prochain essai dans 1 heure');
	    log::add(__CLASS__, 'debug', $this->getHumanName() . ' Output data (1/4): ' . $response);
        return false;
     }
     
     preg_match_all('/.*process:\'(_eConsoconsoDetaille_WAR_eConsoportlet_:idFormConsoDetaille:.*)\'/miU', $response, $output_array);
     $formId = $output_array[1][0];
     log::add(__CLASS__, 'debug', $this->getHumanName() . ' Form : ' . $formId);   
       
     $formId = str_replace(":", "%3A", $formId);
       
     log::add(__CLASS__, 'info', $this->getHumanName() . ' Récupération des données ' . $resource_id . ' du ' . $start . ' au ' . $end . " - 2ème étape");
       
$postfields = "javax.faces.partial.ajax=true&javax.faces.source=".$formId."&javax.faces.partial.execute=".$formId."&javax.faces.partial.render=_eConsoconsoDetaille_WAR_eConsoportlet_%3AidFormConsoDetaille&javax.faces.behavior.event=click&javax.faces.partial.event=click&_eConsoconsoDetaille_WAR_eConsoportlet_%3AidFormConsoDetaille=_eConsoconsoDetaille_WAR_eConsoportlet_%3AidFormConsoDetaille&javax.faces.encodedURL=https%3A%2F%2Fmonespace.grdf.fr%2Fweb%2Fguest%2Fmonespace%2Fparticulier%2Fconsommation%2Fconsommations%3Fp_p_id%3DeConsoconsoDetaille_WAR_eConsoportlet%26p_p_lifecycle%3D2%26p_p_state%3Dnormal%26p_p_mode%3Dview%26p_p_cacheability%3DcacheLevelPage%26p_p_col_id%3Dcolumn-3%26p_p_col_count%3D5%26p_p_col_pos%3D3%26_eConsoconsoDetaille_WAR_eConsoportlet__jsfBridgeAjax%3Dtrue%26_eConsoconsoDetaille_WAR_eConsoportlet__facesViewIdResource%3D%252Fviews%252Fconso%252Fdetaille%252FconsoDetailleViewMode.xhtml&javax.faces.ViewState=".$jvws;
    
     $curl = curl_init();
     curl_setopt_array($curl, array(
        CURLOPT_URL => "https://monespace.grdf.fr/monespace/particulier/consommation/consommations?p_p_id=eConsoconsoDetaille_WAR_eConsoportlet&p_p_lifecycle=2&p_p_state=normal&p_p_mode=view&p_p_cacheability=cacheLevelPage&p_p_col_id=column-3&p_p_col_count=5&p_p_col_pos=3&_eConsoconsoDetaille_WAR_eConsoportlet__jsfBridgeAjax=true&_eConsoconsoDetaille_WAR_eConsoportlet__facesViewIdResource=/views/conso/detaille/consoDetailleViewMode.xhtml",
        CURLOPT_HEADER  => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => $postfields,
        CURLOPT_HTTPHEADER => array(
                "User-Agent: Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Mobile Safari/537.36",
                "Accept-Language: fr,fr-FR;q=0.8,en;q=0.6",
                "Accept-Encoding: gzip, deflate, br", 
                "Accept: application/xml, application/json, text/javascript, */*; q=0.01",
                "Faces-Request: partial/ajax",
				"Host: monespace.grdf.fr",
                "Origin: https://monespace.grdf.fr",
                "Referer: https://monespace.grdf.fr/monespace/particulier/consommation/consommations",
				"Sec-Fetch-Mode: cors",
				"Sec-Fetch-Site: same-origin",
                "X-Requested-With: XMLHttpRequest",
          "Cookie: connectedLUser=0; COOKIE_SUPPORT=true; GUEST_LANGUAGE_ID=fr_FR; ROUTEID_EP=.1; JSESSIONID_EP=".$cookies[0]."; GRDF_EP=".$cookies[1]."; KPISavedRef=https://monespace.grdf.fr/monespace/particulier/consommation/consommations;")
     ));
     $response = curl_exec($curl);
     curl_close($curl);  
	   
     log::add(__CLASS__, 'debug', $this->getHumanName() . ' Output data (2/3): ' . $response);

     log::add(__CLASS__, 'info', $this->getHumanName() . ' Récupération des données ' . $resource_id . ' du ' . $start . ' au ' . $end . " (kwh) - 3ème étape");
     
$postfields = "javax.faces.partial.ajax=true&javax.faces.source=_eConsoconsoDetaille_WAR_eConsoportlet_%3AidFormConsoDetaille%3ApanelTypeGranularite1%3A2&javax.faces.partial.execute=_eConsoconsoDetaille_WAR_eConsoportlet_%3AidFormConsoDetaille%3ApanelTypeGranularite1&javax.faces.partial.render=_eConsoconsoDetaille_WAR_eConsoportlet_%3AidFormConsoDetaille%3ArefreshHighchart+_eConsoconsoDetaille_WAR_eConsoportlet_%3AidFormConsoDetaille%3AupdateDatesBean+_eConsoconsoDetaille_WAR_eConsoportlet_%3AidFormConsoDetaille%3AboutonTelechargerDonnees+_eConsoconsoDetaille_WAR_eConsoportlet_%3AidFormConsoDetaille%3ApanelTypeGranularite+_eConsoconsoDetaille_WAR_eConsoportlet_%3AidFormConsoDetaille%3AidBlocSeuilParametrage&javax.faces.behavior.event=valueChange&javax.faces.partial.event=change&eConsoconsoDetaille_WAR_eConsoportlet_%3AidFormConsoDetaille=_eConsoconsoDetaille_WAR_eConsoportlet_%3AidFormConsoDetaille&javax.faces.encodedURL=https%3A%2F%2Fmonespace.grdf.fr%2Fweb%2Fguest%2Fmonespace%2Fparticulier%2Fconsommation%2Fconsommations%3Fp_p_id%3DeConsoconsoDetaille_WAR_eConsoportlet%26p_p_lifecycle%3D2%26p_p_state%3Dnormal%26p_p_mode%3Dview%26p_p_cacheability%3DcacheLevelPage%26p_p_col_id%3Dcolumn-3%26p_p_col_count%3D5%26p_p_col_pos%3D3%26_eConsoconsoDetaille_WAR_eConsoportlet__jsfBridgeAjax%3Dtrue%26_eConsoconsoDetaille_WAR_eConsoportlet__facesViewIdResource%3D%252Fviews%252Fconso%252Fdetaille%252FconsoDetailleViewMode.xhtml&_eConsoconsoDetaille_WAR_eConsoportlet_%3AidFormConsoDetaille%3AidDateDebutConsoDetaille=".str_replace("/", "%2F", $start)."&_eConsoconsoDetaille_WAR_eConsoportlet_%3AidFormConsoDetaille%3AidDateFinConsoDetaille=".str_replace("/", "%2F", $end)."&_eConsoconsoDetaille_WAR_eConsoportlet_%3AidFormConsoDetaille%3ApanelTypeGranularite1=".$resource_id."&_eConsoconsoDetaille_WAR_eConsoportlet_%3AidFormConsoDetaille%3ApanelTypeGranularite3=".$resource_id."&_eConsoconsoDetaille_WAR_eConsoportlet_%3AidFormConsoDetaille%3AselecteurVolumeType2=kwh&_eConsoconsoDetaille_WAR_eConsoportlet_%3AidFormConsoDetaille%3AselecteurVolumeType4=kwh&javax.faces.ViewState=".$jvws;

     $curl = curl_init();
     curl_setopt_array($curl, array(
        CURLOPT_URL => "https://monespace.grdf.fr/monespace/particulier/consommation/consommations?p_p_id=eConsoconsoDetaille_WAR_eConsoportlet&p_p_lifecycle=2&p_p_state=normal&p_p_mode=view&p_p_cacheability=cacheLevelPage&p_p_col_id=column-3&p_p_col_count=5&p_p_col_pos=3&_eConsoconsoDetaille_WAR_eConsoportlet__jsfBridgeAjax=true&_eConsoconsoDetaille_WAR_eConsoportlet__facesViewIdResource=/views/conso/detaille/consoDetailleViewMode.xhtml",
        CURLOPT_HEADER  => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => $postfields,
        CURLOPT_HTTPHEADER => array(
                "User-Agent: Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Mobile Safari/537.36",
                "Accept-Language: fr,fr-FR;q=0.8,en;q=0.6",
                "Accept-Encoding: gzip, deflate, br", 
                "Accept: application/xml, application/json, text/javascript, */*; q=0.01",
                "Faces-Request: partial/ajax",
				"Host: monespace.grdf.fr",
                "Origin: https://monespace.grdf.fr",
                "Referer: https://monespace.grdf.fr/monespace/particulier/consommation/consommations",
				"Sec-Fetch-Mode: cors",
				"Sec-Fetch-Site: same-origin",
                "X-Requested-With: XMLHttpRequest",
          "Cookie: connectedLUser=0; COOKIE_SUPPORT=true; GUEST_LANGUAGE_ID=fr_FR; ROUTEID_EP=.1; JSESSIONID_EP=".$cookies[0]."; GRDF_EP=".$cookies[1]."; KPISavedRef=https://monespace.grdf.fr/monespace/particulier/consommation/consommations;")
     ));
     $response = curl_exec($curl);
     curl_close($curl);
	   
     log::add(__CLASS__, 'debug', $this->getHumanName() . ' Output data (3/3): ' . $response);
     
     preg_match_all('/^.*donneesCourante = \"(.*?)\"/mi', $response, $matches);
     log::add(__CLASS__, 'debug', $this->getHumanName() . ' Mesures : ' . $matches[1][0]);
     $measures = explode(",", $matches[1][0]);
     preg_match_all('/^.*tooltipDatesInfo = \"(.*?)\"/mi', $response, $matches);
     log::add(__CLASS__, 'debug', $this->getHumanName() . ' Periodes : ' . $matches[1][0]);   
     $periods = explode(",", $matches[1][0]);
       
     $this->recordData($measures, $periods, $resource_id, ''); 
       
       
     log::add(__CLASS__, 'info', $this->getHumanName() . ' Récupération des données ' . $resource_id . ' du ' . $start . ' au ' . $end . " (m3) - 4ème étape");
     
     $postfields = "javax.faces.partial.ajax=true&javax.faces.source=_eConsoconsoDetaille_WAR_eConsoportlet_%3AidFormConsoDetaille%3AselecteurVolumeType2%3A1&javax.faces.partial.execute=_eConsoconsoDetaille_WAR_eConsoportlet_%3AidFormConsoDetaille%3AselecteurVolumeType2&javax.faces.partial.render=_eConsoconsoDetaille_WAR_eConsoportlet_%3AidFormConsoDetaille%3ArefreshHighchart+_eConsoconsoDetaille_WAR_eConsoportlet_%3AidFormConsoDetaille%3AupdateDatesBean+_eConsoconsoDetaille_WAR_eConsoportlet_%3AidFormConsoDetaille%3AboutonTelechargerDonnees+_eConsoconsoDetaille_WAR_eConsoportlet_%3AidFormConsoDetaille%3AselecteurVolumeType&javax.faces.behavior.event=valueChange&javax.faces.partial.event=change&eConsoconsoDetaille_WAR_eConsoportlet_%3AidFormConsoDetaille=_eConsoconsoDetaille_WAR_eConsoportlet_%3AidFormConsoDetaille&javax.faces.encodedURL=https%3A%2F%2Fmonespace.grdf.fr%2Fweb%2Fguest%2Fmonespace%2Fparticulier%2Fconsommation%2Fconsommations%3Fp_p_id%3DeConsoconsoDetaille_WAR_eConsoportlet%26p_p_lifecycle%3D2%26p_p_state%3Dnormal%26p_p_mode%3Dview%26p_p_cacheability%3DcacheLevelPage%26p_p_col_id%3Dcolumn-3%26p_p_col_count%3D5%26p_p_col_pos%3D3%26_eConsoconsoDetaille_WAR_eConsoportlet__jsfBridgeAjax%3Dtrue%26_eConsoconsoDetaille_WAR_eConsoportlet__facesViewIdResource%3D%252Fviews%252Fconso%252Fdetaille%252FconsoDetailleViewMode.xhtml&_eConsoconsoDetaille_WAR_eConsoportlet_%3AidFormConsoDetaille%3AidDateDebutConsoDetaille=".str_replace("/", "%2F", $start)."&_eConsoconsoDetaille_WAR_eConsoportlet_%3AidFormConsoDetaille%3AidDateFinConsoDetaille=".str_replace("/", "%2F", $end)."&_eConsoconsoDetaille_WAR_eConsoportlet_%3AidFormConsoDetaille%3ApanelTypeGranularite1=".$resource_id."&_eConsoconsoDetaille_WAR_eConsoportlet_%3AidFormConsoDetaille%3ApanelTypeGranularite3=".$resource_id."&_eConsoconsoDetaille_WAR_eConsoportlet_%3AidFormConsoDetaille%3AselecteurVolumeType2=mcube&_eConsoconsoDetaille_WAR_eConsoportlet_%3AidFormConsoDetaille%3AselecteurVolumeType4=kwh&javax.faces.ViewState=".$jvws;

     $curl = curl_init();
     curl_setopt_array($curl, array(
        CURLOPT_URL => "https://monespace.grdf.fr/monespace/particulier/consommation/consommations?p_p_id=eConsoconsoDetaille_WAR_eConsoportlet&p_p_lifecycle=2&p_p_state=normal&p_p_mode=view&p_p_cacheability=cacheLevelPage&p_p_col_id=column-3&p_p_col_count=5&p_p_col_pos=3&_eConsoconsoDetaille_WAR_eConsoportlet__jsfBridgeAjax=true&_eConsoconsoDetaille_WAR_eConsoportlet__facesViewIdResource=/views/conso/detaille/consoDetailleViewMode.xhtml",
        CURLOPT_HEADER  => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => $postfields,
        CURLOPT_HTTPHEADER => array(
                "User-Agent: Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Mobile Safari/537.36",
                "Accept-Language: fr,fr-FR;q=0.8,en;q=0.6",
                "Accept-Encoding: gzip, deflate, br", 
                "Accept: application/xml, application/json, text/javascript, */*; q=0.01",
                "Faces-Request: partial/ajax",
				"Host: monespace.grdf.fr",
                "Origin: https://monespace.grdf.fr",
                "Referer: https://monespace.grdf.fr/monespace/particulier/consommation/consommations",
				"Sec-Fetch-Mode: cors",
				"Sec-Fetch-Site: same-origin",
                "X-Requested-With: XMLHttpRequest",
          "Cookie: connectedLUser=0; COOKIE_SUPPORT=true; GUEST_LANGUAGE_ID=fr_FR; ROUTEID_EP=.1; JSESSIONID_EP=".$cookies[0]."; GRDF_EP=".$cookies[1]."; KPISavedRef=https://monespace.grdf.fr/monespace/particulier/consommation/consommations;")
     ));
     $response = curl_exec($curl);
     curl_close($curl);
     
     preg_match_all('/^.*donneesCourante = \"(.*?)\"/mi', $response, $matches);
     log::add(__CLASS__, 'debug', $this->getHumanName() . ' Mesures : ' . $matches[1][0]);
     $measures = explode(",", $matches[1][0]);
     preg_match_all('/^.*tooltipDatesInfo = \"(.*?)\"/mi', $response, $matches);
     log::add(__CLASS__, 'debug', $this->getHumanName() . ' Periodes : ' . $matches[1][0]);   
     $periods = explode(",", $matches[1][0]);
       
     $this->recordData($measures, $periods, $resource_id, '3'); 
     
     if ($resource_id == 'mois') {
         
         log::add(__CLASS__, 'info', $this->getHumanName() . ' Récupération des données de comparaison');

         $curl = curl_init();
         curl_setopt_array($curl, array(
            CURLOPT_URL => "https://monespace.grdf.fr/monespace/particulier/consommation/comparaison",
            CURLOPT_HEADER  => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                    "User-Agent: Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Mobile Safari/537.36",
                    "Accept-Language: fr,fr-FR;q=0.8,en;q=0.6",
                    "Accept-Encoding: gzip, deflate, br", 
                    "Accept: application/xml, application/json, text/javascript, */*; q=0.01",
                    "Faces-Request: partial/ajax",
                    "Host: monespace.grdf.fr",
                    "Origin: https://monespace.grdf.fr",
                    "Referer: https://monespace.grdf.fr/monespace/particulier/consommation/consommation",
                    "Sec-Fetch-Mode: cors",
                    "Sec-Fetch-Site: same-origin",
                    "X-Requested-With: XMLHttpRequest",
                    "Cookie: connectedLUser=0; COOKIE_SUPPORT=true; GUEST_LANGUAGE_ID=fr_FR; ROUTEID_EP=.1; JSESSIONID_EP=".$cookies[0]."; GRDF_EP=".$cookies[1]."; KPISavedRef=https://monespace.grdf.fr/monespace/connexion;")
         ));
         $response = curl_exec($curl);
         curl_close($curl);

         log::add(__CLASS__, 'debug', $this->getHumanName() . ' Output data (comparison): ' . $response);

         preg_match_all('/^.*dateDebut=new Date\(\"(.*?)\".*?/mi', $response, $matches);
         log::add(__CLASS__, 'debug', $this->getHumanName() . ' Date debut comparison : ' . $matches[1][0]);
         $dateDebutStr = $matches[1][0];
         if ($dateDebutStr == '') {
             log::add(__CLASS__, 'warning', $this->getHumanName() . ' Aucune donnée de comparaison');
         } else {
             $dateDebutStr = substr($dateDebutStr, 0, 10);
             preg_match_all('/^.*conso_median:parseData\(\"(.*?)\".*?/mi', $response, $matches);   
             log::add(__CLASS__, 'debug', $this->getHumanName() . ' Local data median : ' . $matches[1][0]);
             $averages = explode(",", $matches[1][0]);
             preg_match_all('/^.*conso_haute:parseData\(\"(.*?)\".*?/mi', $response, $matches);
             log::add(__CLASS__, 'debug', $this->getHumanName() . ' Local data max : ' . $matches[1][0]);
             $maximums = explode(",", $matches[1][0]);
             preg_match_all('/^.*conso_basse:parseData\(\"(.*?)\".*?/mi', $response, $matches);
             log::add(__CLASS__, 'debug', $this->getHumanName() . ' Local data min : ' . $matches[1][0]);
             $minimums = explode(",", $matches[1][0]);
             $this->recordComparison(DateTime::createFromFormat('Y-m-d', $dateDebutStr), $averages, $this->getCmd(null, 'localavg'));
             $this->recordComparison(DateTime::createFromFormat('Y-m-d', $dateDebutStr), $maximums, $this->getCmd(null, 'localmax'));
             $this->recordComparison(DateTime::createFromFormat('Y-m-d', $dateDebutStr), $minimums, $this->getCmd(null, 'localmin'));
         }

     }
     
     return true;
       
   }
   
   public function recordComparison($startDate, $values, $cmdComp) {
       $cmdId = $cmdComp->getId();
       foreach ($values as $value) {
           $period = $startDate->format('Y-m-t 23:55:00'); 
           $cmdHistory = history::byCmdIdDatetime($cmdId, $period);
            if (is_object($cmdHistory) && $cmdHistory->getValue() == $value) {
                log::add(__CLASS__, 'debug', $this->getHumanName() . ' Mesure de comparaison en historique - Aucune action : ' . ' Date = ' . $period . ' => Mesure = ' . $value);
            }
            else {
                log::add(__CLASS__, 'info', $this->getHumanName() . ' Enregistrement mesure : ' . ' Date = ' . $period . ' => Mesure = ' . $value);
                $cmdComp->event($value, $period);
            }
           $startDate->modify('+1 month');
       }
   }
     
     
   public function recordData($measures, $periods, $timeframe, $suffix) {
     
     foreach($periods as $key=>$period) {
        if (is_null($period) || $period == 'null') {
          log::add(__CLASS__, 'debug', $this->getHumanName() . ' NULL period, skipping');   
        }
        else {
            $measure = $measures[$key];
            switch($timeframe)
            {
                case 'jour':
                    $cmd = $this->getCmd(null, 'consod' . $suffix);
                    $dt = DateTime::createFromFormat('d/m/Y', str_replace("Le ", "", $period));
                    if (is_bool($dt)) {
                        return;
                    }
                    $dateReal = $dt->format('Y-m-d 23:55:00'); 
                    break;
                case 'mois':
                    $cmd = $this->getCmd(null, 'consom' . $suffix);
                    $dt = DateTime::createFromFormat('d/m/Y', "01/" . $period);
                    if (is_bool($dt)) {
                        return;
                    }
                    if ($key == count($periods) - 1) {
                        $dateReal = date('Y-m-d 23:55:00', strtotime('-1 day'));
                        //$dateReal = $dt->format('Y-m-' . $dayNum . '23:55:00'); 
                    } else {
                        $dateReal = $dt->format('Y-m-t 23:55:00'); 
                    }
                    break;
            }
            $cmdId = $cmd->getId();
            $cmdHistory = history::byCmdIdDatetime($cmdId, $dateReal);
            if (is_object($cmdHistory) && $cmdHistory->getValue() == $measure) {
                log::add(__CLASS__, 'debug', $this->getHumanName() . ' Mesure en historique - Aucune action : ' . ' Date = ' . $dateReal . ' => Mesure = ' . $measure);
            }
            else {      
                if ($timeframe == 'mois') {
                    log::add(__CLASS__, 'debug', $this->getHumanName() . ' Clean history from ' . $dt->format('Y-m-01') . ' to ' . $dateReal);
                    history::removes($cmdId, $dt->format('Y-m-d'), $dateReal);
                }
                log::add(__CLASS__, 'info', $this->getHumanName() . ' Enregistrement mesure : ' . ' Date = ' . $dateReal . ' => Mesure = ' . $measure);
                $cmd->event($measure, $dateReal);
            }
        }
     }
   }

 // Fonction exécutée automatiquement avant la création de l'équipement
    public function preInsert() {
      $this->setDisplay('height','332px');
      $this->setDisplay('width', '192px');
      $this->setConfiguration('forceRefresh', 0);
      $this->setConfiguration('defaultUnit', 'kwh');
      $this->setConfiguration('widgetTemplate', 'jazpar2');
      $this->setCategory('energy', 1);
      $this->setIsEnable(1);
      $this->setIsVisible(1);
    }

 // Fonction exécutée automatiquement avant la mise à jour de l'équipement
    public function preUpdate() {
      if (empty($this->getConfiguration('login'))) {
        throw new Exception(__('L\'identifiant du compte GRDF doit être renseigné',__FILE__));
      }
      if (empty($this->getConfiguration('password'))) {
        throw new Exception(__('Le mot de passe du compte GRDF doit être renseigné',__FILE__));
      }
    }

 // Fonction exécutée automatiquement après la mise à jour de l'équipement
    public function postUpdate() {
      $cmdInfos = [
    		'consod' => 'Conso veille',
    		'consom' => 'Conso mois en cours',
            'consod3' => 'Conso veille (m3)',
            'consom3' => 'Conso mois en cours (m3)'
    	];

      foreach ($cmdInfos as $logicalId => $name)
      {
        $cmd = $this->getCmd(null, $logicalId);
        if (!is_object($cmd))
        {
          log::add(__CLASS__, 'debug', $this->getHumanName() . ' Création commande :'.$logicalId.'/'.$name);
  				$cmd = new jazparCmd();
					$cmd->setLogicalId($logicalId);
          $cmd->setEqLogic_id($this->getId());
          $cmd->setGeneric_type('CONSUMPTION');
          $cmd->setIsHistorized(1);
          $cmd->setDisplay('showStatsOndashboard', 0);
          $cmd->setDisplay('showStatsOnmobile', 0);
          $cmd->setTemplate('dashboard','tile');
          $cmd->setTemplate('mobile','tile');
        }
        $cmd->setName($name);
        if (substr($logicalId, -1) == '3') {
          $cmd->setUnite('m3');
        } else {
          $cmd->setUnite('kWh');
        }
        $cmd->setType('info');
        $cmd->setSubType('numeric');
        $cmd->save();
      }
      
        $cmd = $this->getCmd(null, 'localmax');
        if ( ! is_object($cmd)) {
            $cmd = new jazparCmd();
            $cmd->setName('Conso max locale');
            $cmd->setEqLogic_id($this->getId());
            $cmd->setLogicalId('localmax');
            $cmd->setType('info');
            $cmd->setSubType('numeric');
            $cmd->setIsHistorized(1);
            $cmd->setIsVisible(0);
            $cmd->setTemplate('dashboard','tile');
            $cmd->setTemplate('mobile','tile');
            $cmd->setUnite('kWh');
            $cmd->setGeneric_type('CONSUMPTION');
            $cmd->save();
        }
        $cmd = $this->getCmd(null, 'localmin');
        if ( ! is_object($cmd)) {
            $cmd = new jazparCmd();
            $cmd->setName('Conso min locale');
            $cmd->setEqLogic_id($this->getId());
            $cmd->setLogicalId('localmin');
            $cmd->setType('info');
            $cmd->setSubType('numeric');
            $cmd->setIsHistorized(1);
            $cmd->setIsVisible(0);
            $cmd->setTemplate('dashboard','tile');
            $cmd->setTemplate('mobile','tile');
            $cmd->setUnite('kWh');
            $cmd->setGeneric_type('CONSUMPTION');
            $cmd->save();
        }
        $cmd = $this->getCmd(null, 'localavg');
        if ( ! is_object($cmd)) {
            $cmd = new jazparCmd();
            $cmd->setName('Conso moyenne locale');
            $cmd->setEqLogic_id($this->getId());
            $cmd->setLogicalId('localavg');
            $cmd->setType('info');
            $cmd->setSubType('numeric');
            $cmd->setIsHistorized(1);
            $cmd->setIsVisible(0);
            $cmd->setTemplate('dashboard','tile');
            $cmd->setTemplate('mobile','tile');
            $cmd->setUnite('kWh');
            $cmd->setGeneric_type('CONSUMPTION');
            $cmd->save();
        }
    }
    
    public function toHtml($_version = 'dashboard') {
      $template = $this->getConfiguration('widgetTemplate');
      if ($template == "none")
    	{
    		return parent::toHtml($_version);
    	}

      $replace = $this->preToHtml($_version);
      if (!is_array($replace)) {
        return $replace;
      }
      $version = jeedom::versionAlias($_version);

      foreach ($this->getCmd('info') as $cmd) {
        $replace['#' . $cmd->getLogicalId() . '_id#'] = $cmd->getId();
        $value = $cmd->execCmd();
        if (strpos($cmd->getLogicalId(), "local") == 0) {
            $value = round($value, 0);
        }
        $replace['#' . $cmd->getLogicalId() . '#'] = $value;
        $replace['#' . $cmd->getLogicalId() . '_collect#'] = $cmd->getCollectDate();
      }
      $replace['#default_unit#'] = $this->getConfiguration('defaultUnit', 'kwh');
      
      if ($template != "jazpar") {
          $cmd = $this->getCmd(null, 'localavg');
          $min = 0;
          $max = 0;
          $avg = 0;
          $month = "?";
          $year = "?";
          $value = "?";
          $padding = 45;
          if (is_object($cmd)) {
            $avg = round($cmd->execCmd(), 0);
            if ($avg > 0) {
                $dateCompare = $cmd->getCollectDate();
                $month = date_fr(date('F', strtotime($dateCompare)));
                $year = strftime("%Y", strtotime($dateCompare));
                $cmdMonth =  $this->getCmd(null, 'consom');
                $cmdHistory = history::byCmdIdDatetime($cmdMonth->getId(), $dateCompare);
                if (is_object($cmdHistory)) {
                    $value = round($cmdHistory->getValue(), 0);
                    $cmd = $this->getCmd(null, 'localmin');
                    if (is_object($cmd)) {
                        $min = round($cmd->execCmd(), 0);
                    }
                    $cmd = $this->getCmd(null, 'localmax');
                    if (is_object($cmd)) {
                        $max = round($cmd->execCmd(), 0);
                    }
                    log::add(__CLASS__, 'debug', $this->getHumanName() . ' values (min/max/avg): '.$min.' '.$max.' '.$avg);
                    if ($value == $avg) {
                        $padding = 45;
                    }
                    if ($value > $avg) {
                        $padding = 45 - round((($value - $avg) * 45) / ($max - $avg), 0);
                    }
                    if ($value < $avg) {
                        $padding = 45 + round((($avg - $value) * 45) / ($avg - $min), 0);
                    }
                    log::add(__CLASS__, 'debug', $this->getHumanName() . ' Calculated padding : '.$padding);
                    if ($padding > 90) {
                       $padding = 90;
                    }
                    if ($padding < 0) {
                       $padding = 0;
                    }
                }
            }
          }
          $replace['#past_month#'] = __($month,__FILE__);
          $replace['#past_year#'] = $year;
          $replace['#past_month_conso#'] = $value;
          $replace['#cursor_compare#'] = $padding;
      }

      $html = template_replace($replace, getTemplate('core', $version, $template.'.template', __CLASS__));
      cache::set('widgetHtml' . $_version . $this->getId(), $html, 0);
      return $html;
    }

}

class jazparCmd extends cmd {
    
    public function dontRemoveCmd() {
		return true;
	}
    
	public function execute($_options = null) {
        $eqLogic = $this->getEqLogic();
        if (!is_object($eqLogic) || $eqLogic->getIsEnable() != 1) {
            throw new Exception(__('Equipement desactivé impossible d\éxecuter la commande : ' . $this->getHumanName(), __FILE__));
        }
        log::add('jazpar', 'debug', 'Execution de la commande ' . $this->getLogicalId());
        switch ($this->getLogicalId()) {
            case "refresh":
                $eqLogic->pullJazpar(true);
                break;
        }
    }
