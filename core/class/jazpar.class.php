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
        if ($eqLogic->getCache('getJazparData') == 'done')
    		{
          $eqLogic->setCache('getJazparData', null);
        }
        return;
      }

      if ($eqLogic->getCache('getJazparData') != 'done')
      {
        $eqLogic->pullJazpar();
      }
    }
  }

    /*     * *********************Méthodes d'instance************************* */

    public function pullJazpar()
    {
      $need_refresh = false;

      foreach ($this->getCmd('info') as $eqLogicCmd)
      {
        $eqLogicCmd->execCmd();
        if ($eqLogicCmd->getCollectDate() == date('Y-m-d 23:55:00', strtotime('-1 day')))
        {
          log::add(__CLASS__, 'debug', $this->getHumanName() . ' le ' . date('d/m/Y', strtotime('-1 day')) . ' : données déjà présentes pour la commande ' . $eqLogicCmd->getName());
        }
        else
        {
          $need_refresh = true;
          log::add(__CLASS__, 'debug', $this->getHumanName() . ' le ' . date('d/m/Y', strtotime('-1 day')) . ' : absence de données pour la commande ' . $eqLogicCmd->getName());
        }
      }

      if ($need_refresh == true)
      {
        sleep(rand(5,50));
        $cookies = $this->connectJazpar();

        $consoDay = $this->getCmd(null, 'consod');
        if (is_object($consoDay))
        {
          $end = date('d/m/Y', strtotime('-2 day'));
          $start = date('d/m/Y', strtotime('-31 days'));
          $resource_id = 'jour';
          $this->getJazparData($cookies, $resource_id, $start, $end);
        }

        $consoMonth = $this->getCmd(null, 'consom');
        if (is_object($consoMonth))
        {
          $end = date('d/m/Y', strtotime('-1 day'));
          $start = date('d/m/Y', strtotime('-11 months'));
          $resource_id = 'mois';
          $this->getJazparData($cookies, $resource_id, $start, $end);
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

      if (isset($cookies['GRDF_EP']) && $cookies['GRDF_EP'] <> '')
      {
        log::add(__CLASS__, 'info', $this->getHumanName() . ' Récupération des informations de session réussie');
        $token = $cookies['GRDF_EP'];
      }
      else
      {
        log::add(__CLASS__, 'error', $this->getHumanName() . ' Erreur lors de la récupération des informations de session - Abandon');
        return;
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
     log::add(__CLASS__, 'debug', print_r($response, true));

     libxml_use_internal_errors(true);
     $dom = new DOMDocument();
     $dom->loadHTML($response);
     $xpath = new DOMXpath($dom);
     $res = $xpath->query("//div[@id='_eConsoconsoDetaille_WAR_eConsoportlet_']/form[@id='_eConsoconsoDetaille_WAR_eConsoportlet_:idFormConsoDetaille']/input[@id='javax.faces.ViewState']/@value");
        
     $jvws = $res[0]->nodeValue;

     log::add(__CLASS__, 'debug', $this->getHumanName() . "JVWS=". $jvws);

     if ($jvws == '') {
        log::add(__CLASS__, 'error', $this->getHumanName() . ' Erreur lors de la récupération des données (1/3) - Abandon');
        return;
     }
       
     log::add(__CLASS__, 'info', $this->getHumanName() . ' Récupération des données ' . $resource_id . ' du ' . $start . ' au ' . $end . " - 2ème étape");
       
     $postfields = "javax.faces.partial.ajax=true&javax.faces.source=_eConsoconsoDetaille_WAR_eConsoportlet_:idFormConsoDetaille:j_idt140&javax.faces.partial.execute=_eConsoconsoDetaille_WAR_eConsoportlet_:idFormConsoDetaille:j_idt140&javax.faces.partial.render=_eConsoconsoDetaille_WAR_eConsoportlet_:idFormConsoDetaille&javax.faces.behavior.event=click&javax.faces.partial.event=click&_eConsoconsoDetaille_WAR_eConsoportlet_:idFormConsoDetaille=_eConsoconsoDetaille_WAR_eConsoportlet_:idFormConsoDetaille&javax.faces.encodedURL=https://monespace.grdf.fr/web/guest/monespace/particulier/consommation/consommations?p_p_id=eConsoconsoDetaille_WAR_eConsoportlet&amp;p_p_lifecycle=2&amp;p_p_state=normal&amp;p_p_mode=view&amp;p_p_cacheability=cacheLevelPage&amp;p_p_col_id=column-3&amp;p_p_col_count=5&amp;p_p_col_pos=3&amp;_eConsoconsoDetaille_WAR_eConsoportlet__jsfBridgeAjax=true&amp;_eConsoconsoDetaille_WAR_eConsoportlet__facesViewIdResource=%2Fviews%2Fconso%2Fdetaille%2FconsoDetailleViewMode.xhtml&javax.faces.ViewState=".$jvws;
    
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

     log::add(__CLASS__, 'info', $this->getHumanName() . ' Récupération des données ' . $resource_id . ' du ' . $start . ' au ' . $end . " - 3ème étape");
     
     $postfields = "
        javax.faces.partial.ajax=true
        &javax.faces.source=_eConsoconsoDetaille_WAR_eConsoportlet_:idFormConsoDetaille:panelTypeGranularite1:2
        &javax.faces.partial.execute=_eConsoconsoDetaille_WAR_eConsoportlet_:idFormConsoDetaille:panelTypeGranularite1 &javax.faces.partial.render=_eConsoconsoDetaille_WAR_eConsoportlet_:idFormConsoDetaille:refreshHighchart+_eConsoconsoDetaille_WAR_eConsoportlet_:idFormConsoDetaille:updateDatesBean+_eConsoconsoDetaille_WAR_eConsoportlet_:idFormConsoDetaille:boutonTelechargerDonnees+_eConsoconsoDetaille_WAR_eConsoportlet_:idFormConsoDetaille:panelTypeGranularite+_eConsoconsoDetaille_WAR_eConsoportlet_:idFormConsoDetaille:idBlocSeuilParametrage
        &javax.faces.behavior.event=valueChange
        &javax.faces.partial.event=change
        &eConsoconsoDetaille_WAR_eConsoportlet_:idFormConsoDetaille=_eConsoconsoDetaille_WAR_eConsoportlet_:idFormConsoDetaille
        &javax.faces.encodedURL=https://monespace.grdf.fr/web/guest/monespace/particulier/consommation/consommations?p_p_id=eConsoconsoDetaille_WAR_eConsoportlet&amp;p_p_lifecycle=2&amp;p_p_state=normal&amp;p_p_mode=view&amp;p_p_cacheability=cacheLevelPage&amp;p_p_col_id=column-3&amp;p_p_col_count=5&amp;p_p_col_pos=3&amp;_eConsoconsoDetaille_WAR_eConsoportlet__jsfBridgeAjax=true&amp;_eConsoconsoDetaille_WAR_eConsoportlet__facesViewIdResource=%2Fviews%2Fconso%2Fdetaille%2FconsoDetailleViewMode.xhtml
        &_eConsoconsoDetaille_WAR_eConsoportlet_:idFormConsoDetaille:idDateDebutConsoDetaille=".$start."
        &_eConsoconsoDetaille_WAR_eConsoportlet_:idFormConsoDetaille:idDateFinConsoDetaille=".$end."
        &_eConsoconsoDetaille_WAR_eConsoportlet_:idFormConsoDetaille:panelTypeGranularite1=".$resource_id."
        &_eConsoconsoDetaille_WAR_eConsoportlet_:idFormConsoDetaille:panelTypeGranularite3=".$resource_id."
        &_eConsoconsoDetaille_WAR_eConsoportlet_:idFormConsoDetaille:selecteurVolumeType2=kwh
        &_eConsoconsoDetaille_WAR_eConsoportlet_:idFormConsoDetaille:selecteurVolumeType4=kwh
        &javax.faces.ViewState=".$jvws;

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
     log::add(__CLASS__, 'debug', $this->getHumanName() . ' Mesures : ' . $matches[1]);
     $measures = explode(",", $matches[1][0]);
     preg_match_all('/^.*tooltipDatesInfo = \"(.*?)\"/mi', $response, $matches);
     log::add(__CLASS__, 'debug', $this->getHumanName() . ' Periodes : ' . $matches[1]);   
     $periods = explode(",", $matches[1][0]);
    
     foreach($periods as $key=>$period) {
        $measure = $measures[$key];
        switch($resource_id)
        {
            case 'jour':
                $cmd = $this->getCmd(null, 'consod');
                $dt = DateTime::createFromFormat('d/m/Y', str_replace("Le ", "", $period));
                $date = $dt->format('Y-m-d 23:55:00'); 
                break;
            case 'mois':
                $cmd = $this->getCmd(null, 'consom');
                $dt = DateTime::createFromFormat('d/m/Y', "01/" . $period);
                $date = $dt->format('Y-m-t 23:55:00'); 
                break;
        }
        $cmdId = $cmd->getId();
        $cmdHistory = history::byCmdIdDatetime($cmdId, $date);
        if (is_object($cmdHistory) && $cmdHistory->getValue() == $measure) {
            log::add(__CLASS__, 'debug', $this->getHumanName() . ' Mesure en historique - Aucune action : ' . ' Date = ' . $date . ' => Mesure = ' . $measure);
        }
        else {
            log::add(__CLASS__, 'debug', $this->getHumanName() . ' Enregistrement mesure : ' . ' Date = ' . $date . ' => Mesure = ' . $measure);
            $cmd->event($measure, $date);
        }
     }
   }

 // Fonction exécutée automatiquement avant la création de l'équipement
    public function preInsert() {
      $this->setDisplay('height','332px');
      $this->setDisplay('width', '192px');
      $this->setConfiguration('widgetTemplate', 1);
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
    		'consod' => 'Conso Jour',
    		'consom' => 'Conso Mois'
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
					$cmd->setName($name);
          $cmd->setGeneric_type('CONSUMPTION');
          $cmd->setUnite('kWh');
          $cmd->setIsHistorized(1);
          $cmd->setDisplay('showStatsOndashboard', 0);
          $cmd->setDisplay('showStatsOnmobile', 0);
          $cmd->setTemplate('dashboard','tile');
          $cmd->setTemplate('mobile','tile');
        }
        $cmd->setType('info');
        $cmd->setSubType('numeric');
        $cmd->save();
      }

		  if ($this->getIsEnable() == 1) {
        $this->pullJazpar();
      }

    }

    // Non obligatoire : permet de modifier l'affichage du widget (également utilisable par les commandes)
    public function toHtml($_version = 'dashboard') {
        return parent::toHtml($_version);
    }

    /*     * **********************Getteur Setteur*************************** */
}

class jazparCmd extends cmd {
    /*     * *************************Attributs****************************** */

    /*
      public static $_widgetPossibility = array();
    */

    /*     * ***********************Methode static*************************** */


    /*     * *********************Methode d'instance************************* */

  // Exécution d'une commande
     public function execute($_options = array()) {

     }

    /*     * **********************Getteur Setteur*************************** */
}
