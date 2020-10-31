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

class enedis extends eqLogic {
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
        if ($eqLogic->getCache('getEnedisData') == 'done')
    		{
          $eqLogic->setCache('getEnedisData', null);
        }
        return;
      }

      if ($eqLogic->getCache('getEnedisData') != 'done')
      {
        $eqLogic->pullEnedis();
      }
    }
  }

    /*     * *********************Méthodes d'instance************************* */

    public function pullEnedis()
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
        $cookies = $this->connectEnedis();

        $charge = $this->getCmd(null, 'charge');
        if (is_object($charge))
        {
          $end = date('d/m/Y');
          $start = date('d/m/Y', strtotime('-1 day'));
          $resource_id = 'urlCdcHeure';
          $this->getEnedisData($cookies, $resource_id, $start, $end);
        }

        $consoDay = $this->getCmd(null, 'consod');
        if (is_object($consoDay))
        {
          $end = date('d/m/Y', strtotime('-1 day'));
          $start = date('d/m/Y', strtotime('-31 days'));
          $resource_id = 'urlCdcJour';
          $this->getEnedisData($cookies, $resource_id, $start, $end);
        }

        $consoMonth = $this->getCmd(null, 'consom');
        if (is_object($consoMonth))
        {
          $end = date('d/m/Y', strtotime('-1 day'));
          $start = date('d/m/Y', strtotime('first day of this month -11 months'));
          $resource_id = 'urlCdcMois';
          $this->getEnedisData($cookies, $resource_id, $start, $end);
        }

        $consoYear = $this->getCmd(null, 'consoy');
        if (is_object($consoYear))
        {
          $end = date('d/m/Y', strtotime('-1 day'));
          $start = date('d/m/Y', strtotime('first day of january this year -3 years'));
          $resource_id = 'urlCdcAn';
          $this->getEnedisData($cookies, $resource_id, $start, $end);
        }
      }
      else
      {
        if ($this->getCache('getEnedisData') != 'done')
        {
          $this->setCache('getEnedisData', 'done');
          log::add(__CLASS__, 'info', $this->getHumanName() . ' le ' . date('d/m/Y', strtotime('-1 day')) . ' : toutes les données sont à jour - désactivation de la vérification automatique pour aujourd\'hui');
        }
      }

    }

    public function connectEnedis()
		{
      log::add(__CLASS__, 'info', $this->getHumanName() . ' 1ère étape d\'authentification Enedis - Récupération du token');

      $login = $this->getConfiguration('login');
      $password = $this->getConfiguration('password');

      $curl = curl_init();
      curl_setopt_array($curl, array(
        CURLOPT_URL => "https://espace-client-connexion.enedis.fr/auth/json/authenticate?realm=particuliers&realm=particuliers",
        CURLOPT_HEADER  => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS =>"{\"template\":\"\",\"stage\":\"LDAP1\",\"header\":\"Ce serveur utilise l'authentification LDAP.\",\"callbacks\":[{\"type\":\"NameCallback\",\"output\":[{\"name\":\"prompt\",\"value\":\"Nom d'utilisateur :\"}],\"input\":[{\"name\":\"IDToken1\",\"value\":\"".$login."\"}]},{\"type\":\"PasswordCallback\",\"output\":[{\"name\":\"prompt\",\"value\":\"Mot de passe :\"}],\"input\":[{\"name\":\"IDToken2\",\"value\":\"".$password."\"}]}]}",
        CURLOPT_HTTPHEADER => array(
          "Connection: keep-alive",
          "Accept-API-Version: protocol=1.0,resource=2.0",
          "X-Password: anonymous",
          "Accept-Language: fr-FR",
          "X-Username: anonymous",
          "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/83.0.4103.116 Safari/537.36 OPR/69.0.3686.95",
          "Content-Type: application/json",
          "Accept: application/json, text/javascript, */*; q=0.01",
          "Cache-Control: no-cache",
          "X-Requested-With: XMLHttpRequest",
          "X-NoSession: true",
          "Origin: https://espace-client-connexion.enedis.fr",
          "Sec-Fetch-Site: same-origin",
          "Sec-Fetch-Mode: cors",
          "Sec-Fetch-Dest: empty",
          "Referer: https://espace-client-connexion.enedis.fr/auth/XUI/"),
        ));
      $response = curl_exec($curl);
      $responseStatus = curl_getinfo($curl, CURLINFO_HTTP_CODE);
      curl_close($curl);

      if ($responseStatus == '401') {
        log::add(__CLASS__, 'debug', $this->getHumanName() . ' Echec d\'authentification - identifiant ou mot de passe erroné');
        throw new Exception(__('Erreur d\'authentification : vérifier la validité de l\'identifiant et du mot de passe',__FILE__));
      }

      preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $response, $matches);
      $cookies = array();
      foreach($matches[1] as $item) {
        parse_str($item, $cookie);
        $cookies = array_merge($cookies, $cookie);
      }

      if (isset($cookies['iPlanetDirectoryPro']))
      {
        log::add(__CLASS__, 'info', $this->getHumanName() . ' Récupération du token réussie');
        $iPlanetDirectoryPro = $cookies['iPlanetDirectoryPro'];
        log::add(__CLASS__, 'debug', $this->getHumanName() . ' Token : ' . $iPlanetDirectoryPro);
      }
      else
      {
        log::add(__CLASS__, 'error', $this->getHumanName() . ' Erreur lors de la récupération du token - Abandon');
        return;
      }

      log::add(__CLASS__, 'info', $this->getHumanName() . ' 2ème étape d\'authentification Enedis - Récupération des informations de session');

      $curl = curl_init();
      curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://espace-client-particuliers.enedis.fr/group/espace-particuliers/accueil',
        CURLOPT_HEADER  => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => array(
          "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/83.0.4103.116 Safari/537.36 OPR/69.0.3686.95",
          "Cookie: iPlanetDirectoryPro=".$iPlanetDirectoryPro.";"
        ),
        ));
      $response = curl_exec($curl);
      curl_close($curl);

      preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $response, $matches);
      $cookies = array();
      foreach($matches[1] as $item) {
        parse_str($item, $cookie);
        $cookies = array_merge($cookies, $cookie);
      }

      if (isset($cookies['JSESSIONID']))
      {
        log::add(__CLASS__, 'info', $this->getHumanName() . ' Récupération des informations de session réussie');
        $JSESSIONID = $cookies['JSESSIONID'];
        log::add(__CLASS__, 'debug', $this->getHumanName() . ' JSESSIONID : ' . $JSESSIONID);
      }
      else
      {
        log::add(__CLASS__, 'error', $this->getHumanName() . ' Erreur lors de la récupération des informations de session - Abandon');
        return;
      }

      return array($iPlanetDirectoryPro, $JSESSIONID);
   }

   public function getEnedisData($cookies, $resource_id, $start, $end)
   {
     log::add(__CLASS__, 'info', $this->getHumanName() . ' Récupération des données ' . $resource_id . ' du ' . $start . ' au ' . $end);

     $curl = curl_init();
     curl_setopt_array($curl, array(
       CURLOPT_URL => "https://espace-client-particuliers.enedis.fr/group/espace-particuliers/suivi-de-consommation?p_p_id=lincspartdisplaycdc_WAR_lincspartcdcportlet&p_p_lifecycle=2&p_p_state=normal&p_p_mode=view&p_p_resource_id=".$resource_id."&p_p_cacheability=cacheLevelPage&p_p_col_id=column-1&p_p_col_pos=1&p_p_col_count=3",
       CURLOPT_RETURNTRANSFER => true,
       CURLOPT_ENCODING => "",
       CURLOPT_MAXREDIRS => 10,
       CURLOPT_TIMEOUT => 0,
       CURLOPT_FOLLOWLOCATION => true,
       CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
       CURLOPT_CUSTOMREQUEST => "POST",
       CURLOPT_POSTFIELDS =>"_lincspartdisplaycdc_WAR_lincspartcdcportlet_dateDebut=".$start."&_lincspartdisplaycdc_WAR_lincspartcdcportlet_dateFin=".$end,
       CURLOPT_HTTPHEADER => array(
         "Connection: keep-alive",
         "Accept: application/json, text/javascript, */*; q=0.01",
         "X-Requested-With: XMLHttpRequest",
         "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/83.0.4103.116 Safari/537.36 OPR/69.0.3686.95",
         "Content-Type: application/x-www-form-urlencoded; charset=UTF-8",
         "Origin: https://espace-client-particuliers.enedis.fr",
         "Sec-Fetch-Site: same-origin",
         "Sec-Fetch-Mode: cors",
         "Sec-Fetch-Dest: empty",
         "Referer: https://espace-client-particuliers.enedis.fr/group/espace-particuliers/suivi-de-consommation",
         "Accept-Language: fr-FR,fr;q=0.9,en-US;q=0.8,en;q=0.7",
         "Cookie: iPlanetDirectoryPro=".$cookies[0]."; JSESSIONID=".$cookies[1].";;"
       ),
     ));
     $response = curl_exec($curl);
     curl_close($curl);
     log::add(__CLASS__, 'debug', print_r($response, true));

     $data = json_decode($response, true);

     if ($data['etat']['valeur'] == 'termine')
     {
       switch($resource_id)
       {
         case 'urlCdcHeure':
            $cmd = $this->getCmd(null, 'charge');
            $date_end = date('Y-m-d 23:55:00', strtotime('-1 day'));
            $date = date('Y-m-d 00:30:00', strtotime('-1 day'));
            $next = ' +30 minutes';
            break;
         case 'urlCdcJour':
            $cmd = $this->getCmd(null, 'consod');
            $date_end = date('Y-m-d 23:55:00', strtotime('-1 day'));
            $date = date('Y-m-d 23:55:00', strtotime('-31 days'));
            $next = ' +1 day';
            break;
         case 'urlCdcMois':
            $cmd = $this->getCmd(null, 'consom');
            $date_end = date('Y-m-d 23:55:00', strtotime('-1 day'));
            $date = date("Y-m-t 23:55:00", strtotime('first day of this month -11 months'));
            $next = ' last day of next month';
            break;
         case 'urlCdcAn':
            $cmd = $this->getCmd(null, 'consoy');
            $date_end = date('Y-m-d 23:55:00', strtotime('-1 day'));
            $date = date('Y-12-31 23:55:00', strtotime('-3 years'));
            $next = ' +1 year';
            break;
        }

       foreach ($data['graphe']['data'] as $measure) {
         if ($date > $date_end)
         {
           $date = $date_end;
         }
         if ($measure['valeur'] != "-1" && $measure['valeur'] != "-2") {
           $cmdId = $cmd->getId();
           $cmdHistory = history::byCmdIdDatetime($cmdId, $date);
           $roundMeasure = round($measure['valeur'], 2);
           if (is_object($cmdHistory) && $cmdHistory->getValue() == $roundMeasure)
           {
             log::add(__CLASS__, 'debug', $this->getHumanName() . ' Mesure en historique - Aucune action : ' . ' Date = ' . $date . ' => Mesure = ' . $roundMeasure);
           }
           else
           {
             log::add(__CLASS__, 'debug', $this->getHumanName() . ' Enregistrement mesure : ' . ' Date = ' . $date . ' => Mesure = ' . $roundMeasure);
             $cmd->event($roundMeasure, $date);
           }
         }
         $date = date('Y-m-d H:i:s', strtotime($date . $next));
       }
     }
     else if ($data['etat']['valeur'] == 'nonActive')
     {
       log::add(__CLASS__, 'info', $this->getHumanName() . ' La collecte et l\'enregistrement de la consommation horaire doivent être activés dans votre compte Enedis');
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
        throw new Exception(__('L\'identifiant du compte Enedis doit être renseigné',__FILE__));
      }
      if (empty($this->getConfiguration('password'))) {
        throw new Exception(__('Le mot de passe du compte Enedis doit être renseigné',__FILE__));
      }
    }

 // Fonction exécutée automatiquement après la mise à jour de l'équipement
    public function postUpdate() {
      $cmdInfos = [
    		'charge' => 'Puissance Soutirée',
    		'consod' => 'Conso Jour',
    		'consom' => 'Conso Mois',
    		'consoy' => 'Conso Année'
    	];

      foreach ($cmdInfos as $logicalId => $name)
      {
        $cmd = $this->getCmd(null, $logicalId);
        if (!is_object($cmd))
        {
          log::add(__CLASS__, 'debug', $this->getHumanName() . ' Création commande :'.$logicalId.'/'.$name);
  				$cmd = new enedisCmd();
					$cmd->setLogicalId($logicalId);
          $cmd->setEqLogic_id($this->getId());
					$cmd->setName($name);
          ($logicalId == 'charge') ? $cmd->setGeneric_type('POWER') : $cmd->setGeneric_type('CONSUMPTION');
          ($logicalId == 'charge') ? $cmd->setUnite('kW') : $cmd->setUnite('kWh');
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
        $this->pullEnedis();
      }

    }

    // Non obligatoire : permet de modifier l'affichage du widget (également utilisable par les commandes)
    public function toHtml($_version = 'dashboard') {
      if ($this->getConfiguration('widgetTemplate') != 1)
    	{
        log::add(__CLASS__, 'debug', $this->getHumanName() . ' Utilisation du template Jeedom (' . $_version . ')');
        return parent::toHtml($_version);
    	}

      log::add(__CLASS__, 'debug', $this->getHumanName() . ' Utilisation du template Linky (' . $_version . ')');
      $replace = $this->preToHtml($_version);
      if (!is_array($replace)) {
        return $replace;
      }
      $version = jeedom::versionAlias($_version);

      foreach ($this->getCmd('info') as $cmd) {
        $replace['#' . $cmd->getLogicalId() . '_id#'] = $cmd->getId();
        $replace['#' . $cmd->getLogicalId() . '#'] = $cmd->execCmd();
        $replace['#' . $cmd->getLogicalId() . '_collect#'] = $cmd->getCollectDate();
      }

      $html = template_replace($replace, getTemplate('core', $version, 'enedis.template', __CLASS__));
      cache::set('widgetHtml' . $_version . $this->getId(), $html, 0);
      return $html;
    }

    /*     * **********************Getteur Setteur*************************** */
}

class enedisCmd extends cmd {
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
