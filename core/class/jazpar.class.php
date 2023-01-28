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
   */
  public static $_widgetPossibility = array('custom' => true);

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
        $eqLogic->pullJazpar();
      }
    }
  }

    /*     * *********************Méthodes d'instance************************* */

    public function pullJazpar()
    {
      $need_refresh = false;

      $consoDay = $this->getCmd(null, 'consod');
      $consoDay->execCmd();
      $daydelay = config::byKey('daysdelay','jazpar','',true);
      if ($consoDay->getCollectDate() == date('Y-m-d 00:00:00', strtotime('-'.$daydelay.' day'))) {
        log::add(__CLASS__, 'debug', $this->getHumanName() . ' le ' . date('d/m/Y', strtotime('-'.$daydelay.' day')) . ' : données déjà présentes');
      } else {
        log::add(__CLASS__, 'debug', $this->getHumanName() . ' le ' . date('d/m/Y', strtotime('-'.$daydelay.' day')) . ' : absence de données');
        $need_refresh = true;
      }

      if ($this->getConfiguration('forceRefresh') == 1) {
        log::add(__CLASS__, 'info', 'Mode Force Refresh activé');
        $need_refresh = true;
      }

      if ($need_refresh == true)
      {
        sleep(rand(5,50));
        $start = date('Y-m-01', strtotime('-1 year'));
        $end = date('Y-m-d', strtotime('-'.$daydelay.' day'));
        $data = $this->connectJazpar($start, $end);

        if (!is_null($data)) {
          $consoDay = $this->getCmd(null, 'consod');
          $consoDay3 = $this->getCmd(null, 'consod3');
          $consoMonth = $this->getCmd(null, 'consom');
          $consoMonth3 = $this->getCmd(null, 'consom3');
          $thresholdCmd = $this->getCmd(null, 'threshold');

          $thePce = $data[0];
          $conso = $data[1];
          $compare = $data[2];
          $thresholds = $data[3];

          $monthValues = array();
          $monthValues3 = array();

          if (!empty($conso->$thePce->releves)) {
            foreach ($conso->$thePce->releves as $measure) {
              $dt = DateTime::createFromFormat('Y-m-d', $measure->journeeGaziere);
              $dateDay = $dt->format('Y-m-d 00:00:00'); 
              $dateMonth = $dt->format('Y-m-t 00:00:00'); 
              if (new DateTime($measure->journeeGaziere) < new DateTime($start)) {
                continue;
              }
              $this->recordDay($consoDay3, $dateDay, $measure->volumeBrutConsomme);
              $this->recordDay($consoDay, $dateDay, $measure->energieConsomme);
              $month = 0;
              $month3 = 0;
              if (array_key_exists($dateMonth, $monthValues)) {
                $month = $monthValues[$dateMonth];
              }
              if (array_key_exists($dateMonth, $monthValues3)) {
                $month3 = $monthValues3[$dateMonth];
              }
              if (!is_null($measure->energieConsomme)) {
                $monthValues[$dateMonth] = $month + $measure->energieConsomme;
              }
              if (!is_null($measure->volumeBrutConsomme)) {
                $monthValues3[$dateMonth] = $month3 + $measure->volumeBrutConsomme;
              }
            }

            $this->recordMonths($consoMonth3, $monthValues3, end($conso->$thePce->releves)->journeeGaziere . ' 00:00:00');
            $this->recordMonths($consoMonth, $monthValues, end($conso->$thePce->releves)->journeeGaziere . ' 00:00:00');
  
            $this->recordIndex(end($conso->$thePce->releves));

          } else {
            log::add(__CLASS__, 'warning', $this->getHumanName() . ' Aucune information de consommation trouvée');
          }
          
          if (!is_null($compare)) {
            foreach ($compare as $measure) {
              $cmd = null;
              switch($measure->consommationType)
              {
                case 'conso_median':
                  $cmd = $this->getCmd(null, 'localavg');
                  break;
                case 'conso_P10':
                  $cmd = $this->getCmd(null, 'localmin');
                  break;
                case 'conso_P90':
                  $cmd = $this->getCmd(null, 'localmax');
                  break;
              }
              $this->recordComparison($cmd, $measure);
            }
          }

          if (!is_null($thresholds)) {
            foreach ($thresholds->seuils as $seuil) {
              $this->recordThreshold($thresholdCmd, $seuil);
            }
          }

        }
        else {
          if (date('G') >= 21) {
            log::add(__CLASS__, 'error', $this->getHumanName() . ' Error - Next try tomorrow');
          } else {
            log::add(__CLASS__, 'warning', $this->getHumanName() . ' Error - Next try in one hour');
          }
        }
      }
      else
      {
        if ($this->getCache('getJazparData') != 'done')
        {
          $this->setCache('getJazparData', 'done');
          log::add(__CLASS__, 'info', $this->getHumanName() . ' le ' . date('d/m/Y', strtotime('-'.$daydelay.' day')) . ' : toutes les données sont à jour - désactivation de la vérification automatique pour aujourd\'hui');
        }
      }

    }

    public function recordComparison($cmd, $measure) {
      $cmdId = $cmd->getId();
      $date = new DateTime();
      $date->setTimestamp(strtotime('-1 year'));
      for ($i = 1; $i <= 13; $i++) {
        $month = "mois".$date->format('n');
        $pieces = explode(",", $measure->$month);
        $theValue = $pieces[0];
        $fullDate = $date->format('Y-m-t 00:00:00');
        $cmdHistory = history::byCmdIdDatetime($cmdId, $fullDate);
        if (is_object($cmdHistory) && $cmdHistory->getValue() == $theValue) {
            log::add(__CLASS__, 'debug', $this->getHumanName() . ' Comparaison ('.$measure->consommationType.') déjà en historique - Aucune action : ' . ' Date = ' . $fullDate . ' => Mesure = ' . $theValue);
        }
        else {     
            log::add(__CLASS__, 'debug', $this->getHumanName() . ' Clean comparison history ('.$measure->consommationType.') from ' . $date->format('Y-m-01') . ' to ' . $fullDate);
            history::removes($cmdId, $date->format('Y-m-01'), $fullDate); 
            log::add(__CLASS__, 'info', $this->getHumanName() . ' Enregistrement comparaison ('.$measure->consommationType.') : ' . ' Date = ' . $fullDate . ' => Mesure = ' . $theValue);
            $cmd->event($theValue, $fullDate);
        }
        $date->modify('+1 month');
      }
    }

    public function recordThreshold($cmd, $threshold) {
      $cmdId = $cmd->getId();
      if (!is_null($threshold)) {
        $themonth = "";
        if (strlen($threshold->mois) == 2) {
          $themonth = $threshold->mois;
        } else {
          $themonth = "0".$threshold->mois;
        }
        $theDate = $threshold->annee."-".$themonth."-01";
        $dt = DateTime::createFromFormat('Y-m-d', $theDate);
        $dateToRecord = $dt->format('Y-m-t 00:00:00'); 
        $cmdHistory = history::byCmdIdDatetime($cmdId, $dateToRecord);
        if (is_object($cmdHistory) && $cmdHistory->getValue() == $threshold->valeur) {
          log::add(__CLASS__, 'debug', $this->getHumanName() . ' Seuil mensuel déjà en historique - Aucune action : ' . ' Date = ' . $dateToRecord . ' => Mesure = ' . $threshold->valeur);
        } else {
          $dt = DateTime::createFromFormat('Y-m-d H:i:s', $dateToRecord);
          log::add(__CLASS__, 'debug', $this->getHumanName() . ' Clean threshold history from ' . $dt->format('Y-m-01') . ' to ' . $dateToRecord);
          history::removes($cmdId, $dt->format('Y-m-01'), $dateToRecord);
          log::add(__CLASS__, 'info', $this->getHumanName() . ' Enregistrement seuil mensuel : Date = ' . $dateToRecord . ' => Valeur = ' . $threshold->valeur);
          $cmd->event($threshold->valeur, $dateToRecord);
        }
      }
    }

    public function recordIndex($measure)
    {
      if (!is_null($measure)) {
        $dt = DateTime::createFromFormat('Y-m-d', $measure->journeeGaziere);
        $theDate = $dt->format('Y-m-d 00:00:00'); 
        $theValue = $measure->indexFin;
        $cmd = $this->getCmd(null, 'index');
        $cmdId = $cmd->getId();
        $cmdHistory = history::byCmdIdDatetime($cmdId, $theDate);
        if (is_object($cmdHistory) && $cmdHistory->getValue() == $theValue) {
            log::add(__CLASS__, 'debug', $this->getHumanName() . ' Index déjà en historique - Aucune action : ' . ' Date = ' . $theDate . ' => Mesure = ' . $theValue);
        }
        else {      
            log::add(__CLASS__, 'info', $this->getHumanName() . ' Enregistrement index : ' . ' Date = ' . $theDate . ' => Mesure = ' . $theValue);
            $cmd->event($theValue, $theDate);
        }
      } else {
        log::add(__CLASS__, 'warning', $this->getHumanName() . ' Mesure est null pour index, Date = ' . $theDate);
      }
    }

    public function recordDay($cmd, $theDate, $theValue)
    {
      if (!is_null($theValue)) {
        $cmdId = $cmd->getId();
        $cmdHistory = history::byCmdIdDatetime($cmdId, $theDate);
        if (is_object($cmdHistory) && $cmdHistory->getValue() == $theValue) {
            log::add(__CLASS__, 'debug', $this->getHumanName() . ' Mesure (jour '. $cmd->getUnite() . ') déjà en historique - Aucune action : ' . ' Date = ' . $theDate . ' => Mesure = ' . $theValue);
        }
        else {      
            log::add(__CLASS__, 'info', $this->getHumanName() . ' Enregistrement mesure (jour '. $cmd->getUnite() . ') : ' . ' Date = ' . $theDate . ' => Mesure = ' . $theValue);
            $cmd->event($theValue, $theDate);
        }
      } else {
        log::add(__CLASS__, 'warning', $this->getHumanName() . ' Mesure est null pour jour ('. $cmd->getUnite() . '), Date = ' . $theDate);
      }
    }

    public function recordMonths($cmd, $records, $lastDate) 
    {
      $thresholdsType = config::byKey('thresholds-site','jazpar','',true);
      $cmdId = $cmd->getId();
      foreach (array_keys($records) as $array_key) {
        $theDate = $array_key;
        $theValue = $records[$theDate];
        if (new DateTime($theDate) > new DateTime($lastDate)) {
          $theDate = $lastDate;
        }
        $cmdHistory = history::byCmdIdDatetime($cmdId, $theDate);
        if (is_object($cmdHistory) && $cmdHistory->getValue() == $theValue) {
          log::add(__CLASS__, 'debug', $this->getHumanName() . ' Mesure (mois '. $cmd->getUnite() . ') déjà en historique - Aucune action : ' . ' Date = ' . $theDate . ' => Mesure = ' . $theValue);
        } else {
          $dt = DateTime::createFromFormat('Y-m-d H:i:s', $theDate);
          log::add(__CLASS__, 'debug', $this->getHumanName() . ' Clean history from ' . $dt->format('Y-m-01') . ' to ' . $theDate);
          history::removes($cmdId, $dt->format('Y-m-01'), $theDate);
          log::add(__CLASS__, 'info', $this->getHumanName() . ' Enregistrement mesure (mois '. $cmd->getUnite() . ') : ' . ' Date = ' . $theDate . ' => Mesure = ' . $theValue);
          $cmd->event($theValue, $theDate);
        }
        if ($thresholdsType == 0 && $cmd->getUnite() == 'kWh') {
          $thresholdCmd = $this->getCmd(null, 'threshold');
          $dt = new DateTime($theDate);
          $dtf = $dt->format('Y-m-t H:i:s');
          $dt->modify('-1 year');
          $dtfOld = $dt->format('Y-m-t H:i:s');
          $dt->modify('+1 year');
          $cmdHistory = history::byCmdIdDatetime($cmdId, $dtfOld);
          if (is_object($cmdHistory)) {
            log::add(__CLASS__, 'info', $this->getHumanName() . ' Computing monthly threshold data from last year consumption...');
            $lastYearValue = round($cmdHistory->getValue(), 0);
            $cmdThresholdHistory = history::byCmdIdDatetime($thresholdCmd->getId(), $dtf);
            if (is_object($cmdThresholdHistory) && $cmdThresholdHistory->getValue() == $lastYearValue) {
              log::add(__CLASS__, 'debug', $this->getHumanName() . ' Seuil déjà en historique - Aucune action : ' . ' Date = ' . $dtf . ' => Mesure = ' . $lastYearValue);
            } else {
              log::add(__CLASS__, 'debug', $this->getHumanName() . ' Clean threshold history from ' . $dt->format('Y-m-01') . ' to ' . $dtf);
              history::removes($thresholdCmd->getId(), $dt->format('Y-m-01'), $dtf);
              log::add(__CLASS__, 'info', $this->getHumanName() . ' Enregistrement seuil : ' . ' Date = ' . $dtf . ' => Mesure = ' . $lastYearValue);
              $thresholdCmd->event($lastYearValue, $dtf);
            }
          }
        }
      }
    }

    public function connectJazpar($start, $end)
		{
      $login = $this->getConfiguration('login');
      $password = $this->getConfiguration('password');
      $mypce = null;
      $conso = null;
      $comparison = null;
      $thresholds = null;

      log::add(__CLASS__, 'info', $this->getHumanName() . ' Authentication in progress..');

      
      $postdata = array(
        'password' => $password,
        'email' => $login,
        'capp' => 'meg',
        'goto' => 'https://sofa-connexion.grdf.fr:443/openam/oauth2/externeGrdf/authorize?response_type=code&scope=openid%20profile%20email%20infotravaux%20%2Fv1%2Faccreditation%20%2Fv1%2Faccreditations%20%2Fdigiconso%2Fv1%20%2Fdigiconso%2Fv1%2Fconsommations%20new_meg%20%2FDemande.read%20%2FDemande.write&client_id=prod_espaceclient&state=0&redirect_uri=https%3A%2F%2Fmonespace.grdf.fr%2F_codexch&nonce=7cV89oGyWnw28DYdI-702Gjy9f&by_pass_okta=1&capp=meg'
      );

      $curl = curl_init();
      curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://login.monespace.grdf.fr/sofit-account-api/api/v1/auth',
        CURLOPT_RETURNTRANSFER => true,
        //CURLOPT_HEADER  => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_COOKIEFILE => '',
        CURLOPT_POSTFIELDS => http_build_query($postdata),
        CURLOPT_HTTPHEADER => array(
          'domain: grdf.fr',
          'Content-type: application/x-www-form-urlencoded; charset=UTF-8',
          'Referer: https://login.monespace.grdf.fr/mire/connexion?goto=https:%2F%2Fsofa-connexion.grdf.fr:443%2Fopenam%2Foauth2%2FexterneGrdf%2Fauthorize%3Fresponse_type%3Dcode%26scope%3Dopenid%2520profile%2520email%2520infotravaux%2520%252Fv1%252Faccreditation%2520%252Fv1%252Faccreditations%2520%252Fdigiconso%252Fv1%2520%252Fdigiconso%252Fv1%252Fconsommations%2520new_meg%2520%252FDemande.read%2520%252FDemande.write%26client_id%3Dprod_espaceclient%26state%3D0%26redirect_uri%3Dhttps%253A%252F%252Fmonespace.grdf.fr%252F_codexch%26nonce%3D7cV89oGyWnw28DYdI-702Gjy9f'
      ),));

      $response = curl_exec($curl);
      $responseStatus = curl_getinfo($curl, CURLINFO_HTTP_CODE);

      log::add(__CLASS__, 'debug', $this->getHumanName() . ' Authent1: ' . $response);

      if ($responseStatus != "200") {
        log::add(__CLASS__, 'error', $this->getHumanName() . ' Authentification error');
        return null;
      } else {
        $obj = json_decode($response);
        if ($obj->state != "SUCCESS") {
          log::add(__CLASS__, 'error', $this->getHumanName() . ' Authentification error, state = ' . $obj->state . ', captcha = ' . $obj->displayCaptcha);
          if ($obj->displayCaptcha == true) {
            log::add(__CLASS__, 'error', $this->getHumanName() . ' Authentification error, a captcha needs to be entered on the website');
            if (config::byKey('captcha-warning','jazpar','',true) == 1) {
              message::add($this->getHumanName(), __('Un captcha a été détecté. Connectez-vous à votre espace pour le résoudre.',__FILE__), '', $this->getId());
            }
            if (config::byKey('captcha-disable','jazpar','',true) == 1) {
              $this->setIsEnable(0);
              log::add(__CLASS__, 'info', $this->getHumanName() . ' Equipment disabled (as per plugin config)');
            }
          }
          return null;
        }
      }

      curl_setopt($curl, CURLOPT_URL, "https://monespace.grdf.fr/");
      curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
      curl_setopt($curl, CURLOPT_HTTPHEADER, array('Referer: https://sofa-connexion.grdf.fr:443/openam/oauth2/externeGrdf/authorize?response_type=code&scope=openid profile email infotravaux /v1/accreditation /v1/accreditations /digiconso/v1 /digiconso/v1/consommations new_meg /Demande.read /Demande.write&client_id=prod_espaceclient&state=0&redirect_uri=https://monespace.grdf.fr/_codexch&nonce=7cV89oGyWnw28DYdI-702Gjy9f5XdIJ_4dKE_hbsvag&by_pass_okta=1&capp=meg'));

      $response = curl_exec($curl);
      $responseStatus = curl_getinfo($curl, CURLINFO_HTTP_CODE);

      log::add(__CLASS__, 'debug', $this->getHumanName() . ' Authent2: ' . $response);

      if ($responseStatus != "200") {
        log::add(__CLASS__, 'error', $this->getHumanName() . ' Authentification error');
        return null;
      } else {
        log::add(__CLASS__, 'info', $this->getHumanName() . ' ...authenticated!');
      }

      if (empty($this->getConfiguration('pce'))) {
        log::add(__CLASS__, 'info', $this->getHumanName() . ' Retrieve PCE...');
        curl_setopt($curl, CURLOPT_URL, "https://monespace.grdf.fr/api/e-connexion/users/pce/historique-consultation");
        $response = curl_exec($curl);
        log::add(__CLASS__, 'debug', $this->getHumanName() . ' PCE: ' . $response);
        $responseStatus = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if ($responseStatus != "200") {
          log::add(__CLASS__, 'error', $this->getHumanName() . ' Unable to retrieve PCE');
          log::add(__CLASS__, 'debug', $this->getHumanName() . ' error: ' . $response);
          return null;
        } else {
          $obj = json_decode($response);
          foreach ($obj as $pce) {
            $mypce = $pce->numPce;
          }
          log::add(__CLASS__, 'info', $this->getHumanName() . ' ...PCE retrieved: '.$mypce);
        }
      } else {
        log::add(__CLASS__, 'info', $this->getHumanName() . ' PCE provided as input: ' . $this->getConfiguration('pce'));
        $mypce = $this->getConfiguration('pce');
      }

      log::add(__CLASS__, 'info', $this->getHumanName() . ' Get consumption data...');
      curl_setopt($curl, CURLOPT_URL, "https://monespace.grdf.fr/api/e-conso/pce/consommation/informatives?dateDebut=".$start."&dateFin=".$end."&pceList%5B%5D=". $mypce);
      $response = curl_exec($curl);
      log::add(__CLASS__, 'debug', $this->getHumanName() . ' conso: ' . $response);
      $responseStatus = curl_getinfo($curl, CURLINFO_HTTP_CODE);

      if ($responseStatus != "200") {
        log::add(__CLASS__, 'error', $this->getHumanName() . ' Unable to retrieve consumption data');
        log::add(__CLASS__, 'debug', $this->getHumanName() . ' error: ' . $response);
        return null;
      } else {
        $conso = json_decode($response);
        log::add(__CLASS__, 'info', $this->getHumanName() . ' ...consumption data retrieved!');
      }

      log::add(__CLASS__, 'info', $this->getHumanName() . ' Get comparison data...');
      curl_setopt($curl, CURLOPT_URL, "https://monespace.grdf.fr/api/e-conso/pce/".$mypce."/consommation-anuelle-reference");
      $response = curl_exec($curl);
      log::add(__CLASS__, 'debug', $this->getHumanName() . ' comparison: ' . $response);
      $responseStatus = curl_getinfo($curl, CURLINFO_HTTP_CODE);

      if ($responseStatus != "200") {
        log::add(__CLASS__, 'warning', $this->getHumanName() . ' Unable to retrieve comparison data');
        log::add(__CLASS__, 'debug', $this->getHumanName() . ' error: ' . $response);
      } else {
        $comparison = json_decode($response);
        log::add(__CLASS__, 'info', $this->getHumanName() . ' ...comparison data retrieved!');
      }

      $thresholdsType = config::byKey('thresholds-site','jazpar','',true);
      if ($thresholdsType == 1) {
        log::add(__CLASS__, 'info', $this->getHumanName() . ' Get monthly threshold datas...');
        curl_setopt($curl, CURLOPT_URL, "https://monespace.grdf.fr/api/e-conso/pce/".$mypce."/seuils?frequence=Mensuel");
        $response = curl_exec($curl);
        log::add(__CLASS__, 'debug', $this->getHumanName() . ' thresholds: ' . $response);
        $responseStatus = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if ($responseStatus != "200") {
          log::add(__CLASS__, 'warning', $this->getHumanName() . ' Unable to retrieve monthly thresholds data');
          log::add(__CLASS__, 'debug', $this->getHumanName() . ' error: ' . $response);
        } else {
          $thresholds = json_decode($response);
          log::add(__CLASS__, 'info', $this->getHumanName() . ' ...monthly thresholds data retrieved!');
        }
      }

      curl_close($curl);
      return array($mypce, $conso, $comparison, $thresholds);
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

    
    public static function preConfig_daysdelay($value) {
      $options = array('options' => array('min_range' => 0));
      if (filter_var($value, FILTER_VALIDATE_INT, $options) === false) {
        throw new Exception(__('Le nombre de jours doit être un entier positif ou 0',__FILE__));
      }
      return $value;
    }
    

 // Fonction exécutée automatiquement après la mise à jour de l'équipement
    public function postUpdate() {
      $cmdInfos = [
    		'consod' => 'Conso jour',
    		'consom' => 'Conso mois',
            'consod3' => 'Conso jour (m3)',
            'consom3' => 'Conso mois (m3)'
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
        $cmd = $this->getCmd(null, 'refresh');
        if (!is_object($cmd)) {
            $cmd = new jazparCmd();
            $cmd->setLogicalId('refresh');
            $cmd->setEqLogic_id($this->getId());
            $cmd->setName('Rafraichir');
            $cmd->setType('action');
            $cmd->setSubType('other');
            $cmd->save();
        }
        $cmd = $this->getCmd(null, 'index');
        if ( ! is_object($cmd)) {
            $cmd = new jazparCmd();
            $cmd->setName('Index');
            $cmd->setEqLogic_id($this->getId());
            $cmd->setLogicalId('index');
            $cmd->setType('info');
            $cmd->setSubType('numeric');
            $cmd->setIsHistorized(1);
            $cmd->setIsVisible(0);
            $cmd->setTemplate('dashboard','tile');
            $cmd->setTemplate('mobile','tile');
            $cmd->setUnite('m3');
            $cmd->setGeneric_type('CONSUMPTION');
            $cmd->save();
        }
        $cmd = $this->getCmd(null, 'threshold');
        if ( ! is_object($cmd)) {
            $cmd = new jazparCmd();
            $cmd->setName('Seuil du mois en cours');
            $cmd->setEqLogic_id($this->getId());
            $cmd->setLogicalId('threshold');
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
        if (substr($cmd->getLogicalId(), 0, 5) === "local") {
            $value = round($value, 0);
        }
        $replace['#' . $cmd->getLogicalId() . '#'] = $value;
        $replace['#' . $cmd->getLogicalId() . '_collect#'] = $cmd->getCollectDate();
        if (substr($cmd->getLogicalId(), 0, 6) === "consom") {
          $month = date_fr(date('F', strtotime($cmd->getCollectDate())));
          $replace['#' . $cmd->getLogicalId() . '_name#'] = $month;
        } 
        if (substr($cmd->getLogicalId(), 0, 6) === "consod") {
          $month = date_fr(date('F', strtotime($cmd->getCollectDate())));
          $day = date('j', strtotime($cmd->getCollectDate()));
          $replace['#' . $cmd->getLogicalId() . '_name#'] = $day . ' ' . $month;
        } 
      }
        
      $replace['#default_unit#'] = $this->getConfiguration('defaultUnit', 'kwh');
      
      if ($template != "jazpar") {
        for ($i = 0; $i <= 12; $i++) { 
          $replace = array_merge($replace, $this->computePadding($i));
        }
      }

      $html = template_replace($replace, getTemplate('core', $version, $template.'.template', __CLASS__));
      cache::set('widgetHtml' . $_version . $this->getId(), $html, 0);
      return $html;
    }

    public function computePadding($theMonth) {
      $cmdAvg = $this->getCmd(null, 'localavg');
      $cmdMin = $this->getCmd(null, 'localmin');
      $cmdMax = $this->getCmd(null, 'localmax');
      $cmdMonth = $this->getCmd(null, 'consom');
      $date = new DateTime();
      $date->setTimestamp(strtotime('-'.$theMonth.' month'));
      $startDate = $date->format('Y-m-01 00:00:00');
      $endDate = $date->format('Y-m-t 00:00:00');
      $cmdAvgHistory = history::byCmdIdDatetime($cmdAvg->getId(), $startDate, $endDate);
      $cmdMinHistory = history::byCmdIdDatetime($cmdMin->getId(), $startDate, $endDate);
      $cmdMaxHistory = history::byCmdIdDatetime($cmdMax->getId(), $startDate, $endDate);
      $cmdMonthHistory = history::byCmdIdDatetime($cmdMonth->getId(), $startDate, $endDate);
      $min = "?";
      $min_collect = "?";
      $min_id = "?";
      $max = "?";
      $max_collect = "?";
      $max_id = "?";
      $avg = "?";
      $avg_collect = "?";
      $avg_id = "?";
      $month = "?";
      $year = "?";
      $conso = "?";
      $conso_collect = "?";
      $conso_id = "?";
      $padding = 45;
      $compareValues = [];

      if (is_object($cmdAvgHistory) && is_object($cmdMinHistory) && is_object($cmdMaxHistory)) {
        $month = date_fr($date->format('F'));
        $year = $date->format('Y');
        $avg = round($cmdAvgHistory->getValue(), 0);
        $avg_collect = $cmdAvgHistory->getDatetime();
        $avg_id = $cmdAvgHistory->getCmd_id();
        $min = round($cmdMinHistory->getValue(), 0);
        $min_collect = $cmdMinHistory->getDatetime();
        $min_id = $cmdMinHistory->getCmd_id();
        $max = round($cmdMaxHistory->getValue(), 0);
        $max_collect = $cmdMaxHistory->getDatetime();
        $max_id = $cmdMaxHistory->getCmd_id();
        log::add(__CLASS__, 'debug', $this->getHumanName() . ' values (min/max/avg): '.$min.' '.$max.' '.$avg);
        if (is_object($cmdMonthHistory)) {
          $conso = round($cmdMonthHistory->getValue(), 0);
          $conso_collect = $cmdMonthHistory->getDatetime();
          $conso_id = $cmdMonthHistory->getCmd_id();
          if ($conso == $avg) {
            $padding = 45;
          }
          if ($conso > $avg) {
              $padding = 45 - round((($conso - $avg) * 45) / ($max - $avg), 0);
          }
          if ($conso < $avg) {
              $padding = 45 + round((($avg - $conso) * 45) / ($avg - $min), 0);
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
      
      $compareValues['#'.$theMonth.'_comp_month#'] = __($month,__FILE__);
      $compareValues['#'.$theMonth.'_comp_year#'] = $year;
      $compareValues['#'.$theMonth.'_comp_conso#'] = $conso;
      $compareValues['#'.$theMonth.'_comp_conso_collect#'] = $conso_collect;
      $compareValues['#'.$theMonth.'_comp_conso_id#'] = $conso_id;
      $compareValues['#'.$theMonth.'_comp_avg#'] = $avg;
      $compareValues['#'.$theMonth.'_comp_avg_collect#'] = $avg_collect;
      $compareValues['#'.$theMonth.'_comp_avg_id#'] = $avg_id;
      $compareValues['#'.$theMonth.'_comp_min#'] = $min;
      $compareValues['#'.$theMonth.'_comp_min_collect#'] = $min_collect;
      $compareValues['#'.$theMonth.'_comp_min_id#'] = $min_id;
      $compareValues['#'.$theMonth.'_comp_max#'] = $max;
      $compareValues['#'.$theMonth.'_comp_max_collect#'] = $max_collect;
      $compareValues['#'.$theMonth.'_comp_max_id#'] = $max_id;
      $compareValues['#'.$theMonth.'_cursor_compare#'] = $padding;

      return $compareValues;
    }

}

class jazparCmd extends cmd {
    
    /*
    public function dontRemoveCmd() {
		return true;
	}
    */
    
	public function execute($_options = null) {
        $eqLogic = $this->getEqLogic();
        if (!is_object($eqLogic) || $eqLogic->getIsEnable() != 1) {
            throw new Exception(__('Equipement desactivé impossible d\éxecuter la commande : ' . $this->getHumanName(), __FILE__));
        }
        log::add('jazpar', 'debug', 'Execution de la commande ' . $this->getLogicalId());
        switch ($this->getLogicalId()) {
            case "refresh":
                $eqLogic->pullJazpar();
                break;
        }
    }
}
