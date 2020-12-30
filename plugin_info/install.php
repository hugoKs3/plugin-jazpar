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
  }

// Fonction exécutée automatiquement après la mise à jour du plugin
  function jazpar_update() {
    $cronMinute = config::byKey('cronMinute', 'jazpar');
    if (empty($cronMinute)) {
      $randMinute = rand(3, 59);
      config::save('cronMinute', $randMinute, 'jazpar');
    }
    foreach (eqLogic::byType('jazpar') as $eqLogic) {
        log::add('jazpar', 'info', '1');
        if (empty($eqLogic->getConfiguration('defaultUnit'))) {
            log::add('jazpar', 'info', '2');
            $eqLogic->setConfiguration('defaultUnit', 'kwh');
        }
        log::add('jazpar', 'info', $eqLogic->getConfiguration('widgetTemplate'));
        if ($eqLogic->getConfiguration('widgetTemplate') == 1) {
            log::add('jazpar', 'info', '3');
            $eqLogic->setConfiguration('widgetTemplate', 'jazpar');
        }
        if ($eqLogic->getConfiguration('widgetTemplate') == 0) {
            log::add('jazpar', 'info', '4');
            $eqLogic->setConfiguration('widgetTemplate', 'none');
        }
        $cmd = $eqLogic->getCmd(null, 'localmax');
        if ( ! is_object($cmd)) {
            log::add('jazpar', 'info', '5');
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
            log::add('jazpar', 'info', '6');
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
            log::add('jazpar', 'info', '7');
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
        log::add('jazpar', 'info', '8');
        $eqLogic->save();
    }
    
  }

// Fonction exécutée automatiquement après la suppression du plugin
  function jazpar_remove() {

  }

?>