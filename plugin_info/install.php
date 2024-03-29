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

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';

// Fonction exécutée automatiquement après l'installation du plugin
  function jazpar_install() {
    $cronMinute = config::byKey('cronMinute', 'jazpar');
    if (empty($cronMinute)) {
      $randMinute = rand(3, 59);
      config::save('cronMinute', $randMinute, 'jazpar');
    }
    config::save('captcha-warning', 1, 'jazpar');
  }

// Fonction exécutée automatiquement après la mise à jour du plugin
  function jazpar_update() {
    $cronMinute = config::byKey('cronMinute', 'jazpar');
    if (empty($cronMinute)) {
      $randMinute = rand(3, 59);
      config::save('cronMinute', $randMinute, 'jazpar');
    }

    $captcha = config::byKey('captcha-warning','jazpar','unset',true);
    if ($captcha == 'unset') {
      config::save('captcha-warning', 1, 'jazpar');
    }

    $daysdelay = config::byKey('daysdelay','jazpar','unset',true);
    if ($daysdelay == 'unset') {
      config::save('daysdelay', 2, 'jazpar');
    }

    $thresholds = config::byKey('thresholds-site','jazpar','unset',true);
    if ($thresholds == 'unset') {
      config::save('thresholds-site', 1, 'jazpar');
    }

    foreach (eqLogic::byType('jazpar') as $eqLogic) {
        if (empty($eqLogic->getConfiguration('defaultUnit'))) {
            $eqLogic->setConfiguration('defaultUnit', 'kwh');
        }
        if ($eqLogic->getConfiguration('useDates', 'unset') === 'unset') {
            $eqLogic->setConfiguration('useDates', 0);
        }
        if ($eqLogic->getConfiguration('roundValues', 'unset') === 'unset') {
            $eqLogic->setConfiguration('roundValues', 1);
        }
        
        $template = $eqLogic->getConfiguration('widgetTemplate');
        log::add('jazpar', 'debug', 'update template : ' . $template);
        if (is_int($template)) {
            if ($template == 1) {
                $eqLogic->setConfiguration('widgetTemplate', 'jazpar');
            } else {
                $eqLogic->setConfiguration('widgetTemplate', 'none');
            }                
        }
        $cmd = $eqLogic->getCmd(null, 'localmax');
        if ( ! is_object($cmd)) {
            $cmd = new jazparCmd();
            $cmd->setName('Conso max locale');
            $cmd->setEqLogic_id($eqLogic->getId());
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
        $cmd = $eqLogic->getCmd(null, 'localmin');
        if ( ! is_object($cmd)) {
            $cmd = new jazparCmd();
            $cmd->setName('Conso min locale');
            $cmd->setEqLogic_id($eqLogic->getId());
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
        $cmd = $eqLogic->getCmd(null, 'localavg');
        if ( ! is_object($cmd)) {
            $cmd = new jazparCmd();
            $cmd->setName('Conso moyenne locale');
            $cmd->setEqLogic_id($eqLogic->getId());
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
        $cmd = $eqLogic->getCmd(null, 'refresh');
        if (!is_object($cmd)) {
            $cmd = new jazparCmd();
            $cmd->setLogicalId('refresh');
            $cmd->setEqLogic_id($eqLogic->getId());
            $cmd->setName('Rafraichir');
            $cmd->setType('action');
            $cmd->setSubType('other');
            $cmd->save();
        }
        $cmd = $eqLogic->getCmd(null, 'index');
        if ( ! is_object($cmd)) {
            $cmd = new jazparCmd();
            $cmd->setName('Index');
            $cmd->setEqLogic_id($eqLogic->getId());
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
        $cmd = $eqLogic->getCmd(null, 'threshold');
        if ( ! is_object($cmd)) {
            $cmd = new jazparCmd();
            $cmd->setName('Seuil du mois en cours');
            $cmd->setEqLogic_id($eqLogic->getId());
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
        } else {
          $cmd->setName('Seuil mensuel');
          $cmd->save();
        }

        $cmd = $eqLogic->getCmd(null, 'consod');
        if (is_object($cmd)) {
          $cmd->setName('Conso jour');
          $cmd->save();
        }

        $cmd = $eqLogic->getCmd(null, 'consod3');
        if (is_object($cmd)) {
          $cmd->setName('Conso jour (m3)');
          $cmd->save();
        }

        $cmd = $eqLogic->getCmd(null, 'consom');
        if (is_object($cmd)) {
          $cmd->setName('Conso mois');
          $cmd->save();
        }

        $cmd = $eqLogic->getCmd(null, 'consom3');
        if (is_object($cmd)) {
          $cmd->setName('Conso mois (m3)');
          $cmd->save();
        }

        $eqLogic->save();
    }
    
  }

// Fonction exécutée automatiquement après la suppression du plugin
  function jazpar_remove() {

  }

?>