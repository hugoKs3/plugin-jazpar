<?php
if (!isConnect('admin')) {
	throw new Exception('{{401 - Accès non autorisé}}');
}
$plugin = plugin::byId('jazpar');
sendVarToJS('eqType', $plugin->getId());
$eqLogics = eqLogic::byType($plugin->getId());
?>

<div class="row row-overflow">
   <div class="col-xs-12 eqLogicThumbnailDisplay">
  <legend><i class="fas fa-cog"></i>  {{Gestion}}</legend>
  <div class="eqLogicThumbnailContainer">
      <div class="cursor eqLogicAction logoPrimary" data-action="add">
        <i class="fas fa-plus-circle"></i>
        <br>
        <span>{{Ajouter}}</span>
    </div>
      <div class="cursor eqLogicAction logoSecondary" data-action="gotoPluginConf">
      <i class="fas fa-wrench"></i>
    <br>
    <span>{{Configuration}}</span>
  </div>
  </div>
  <legend><i class="fas fa-table"></i> {{Mes comptes GRDF}}</legend>
	   <input class="form-control" placeholder="{{Rechercher}}" id="in_searchEqlogic" />
<div class="eqLogicThumbnailContainer">
    <?php
// Affiche la liste des équipements
foreach ($eqLogics as $eqLogic) {
	$opacity = ($eqLogic->getIsEnable()) ? '' : 'disableCard';
	echo '<div class="eqLogicDisplayCard cursor '.$opacity.'" data-eqLogic_id="' . $eqLogic->getId() . '">';
	echo '<img src="' . $plugin->getPathImgIcon() . '"/>';
	echo '<br>';
	echo '<span class="name">' . $eqLogic->getHumanName(true, true) . '</span>';
	echo '</div>';
}
?>
</div>
</div>

<div class="col-xs-12 eqLogic" style="display: none;">
		<div class="input-group pull-right" style="display:inline-flex">
			<span class="input-group-btn">
				<a class="btn btn-default btn-sm eqLogicAction roundedLeft" data-action="configure"><i class="fa fa-cogs"></i> {{Configuration avancée}}</a><a class="btn btn-default btn-sm eqLogicAction" data-action="copy"><i class="fas fa-copy"></i> {{Dupliquer}}</a><a class="btn btn-sm btn-success eqLogicAction" data-action="save"><i class="fas fa-check-circle"></i> {{Sauvegarder}}</a><a class="btn btn-danger btn-sm eqLogicAction roundedRight" data-action="remove"><i class="fas fa-minus-circle"></i> {{Supprimer}}</a>
			</span>
		</div>
  <ul class="nav nav-tabs" role="tablist">
    <li role="presentation"><a href="#" class="eqLogicAction" aria-controls="home" role="tab" data-toggle="tab" data-action="returnToThumbnailDisplay"><i class="fa fa-arrow-circle-left"></i></a></li>
    <li role="presentation" class="active"><a href="#eqlogictab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-tachometer-alt"></i> {{Equipement}}</a></li>
    <li role="presentation"><a href="#commandtab" aria-controls="profile" role="tab" data-toggle="tab"><i class="fa fa-list-alt"></i> {{Commandes}}</a></li>
  </ul>
  <div class="tab-content" style="height:calc(100% - 50px);overflow:auto;overflow-x: hidden;">
    <div role="tabpanel" class="tab-pane active" id="eqlogictab">
      <br/>
     <div style="width: 50%; display:inline-block;">
    <form class="form-horizontal">
        <fieldset>
            <div class="form-group">
                <label class="col-sm-6 control-label">{{Nom de l'équipement GRDF}}</label>
                <div class="col-sm-6">
                    <input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display : none;" />
                    <input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom de l'équipement GRDF}}"/>
                </div>
            </div>
            <div class="form-group">
                <label class="col-sm-6 control-label" >{{Objet parent}}</label>
                <div class="col-sm-6">
                    <select id="sel_object" class="eqLogicAttr form-control" data-l1key="object_id">
                      <option value=""></option>
                      <?php
                      $options = '';
                      foreach ((jeeObject::buildTree (null, false)) as $object) {
                        $options .= '<option value="' . $object->getId() . '">' . str_repeat('&nbsp;&nbsp;', $object->getConfiguration('parentNumber')) . $object->getName() . '</option>';
                      }
                      echo $options;
                      ?>
                    </select>
               </div>
           </div>
	   <div class="form-group">
                <label class="col-sm-6 control-label">{{Catégorie}}</label>
                <div class="col-sm-6">
                 <?php
                    foreach (jeedom::getConfiguration('eqLogic:category') as $key => $value) {
                    echo '<label class="checkbox-inline">';
                    echo '<input type="checkbox" class="eqLogicAttr" data-l1key="category" data-l2key="' . $key . '" />' . $value['name'];
                    echo '</label>';
                    }
                  ?>
               </div>
           </div>
	<div class="form-group">
		<label class="col-sm-6 control-label">{{Options}}</label>
		<div class="col-sm-6">
			<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" checked/>{{Activer}}</label>
			<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isVisible" checked/>{{Visible}}</label>
		</div>
	</div>
	<br>
	<div class="form-group">
	 <label class="col-sm-6 control-label">{{Identifiant GRDF}}</label>
	 <div class="col-sm-6">
			 <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="login" placeholder="Identifiant du compte GRDF"/>
	 </div>
</div>
		<div class="form-group">
		 <label class="col-sm-6 control-label">{{Mot de passe GRDF}}</label>
		 <div class="col-sm-6">
				 <input type="password" autocomplete="new-password" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="password" placeholder="Mot de passe du compte GRDF"/>
		 </div>
 </div>
 <div class="form-group">
	 <label class="col-sm-6 control-label help" data-help="{{Indiquez le numéro PCE si vous possédez plusieurs compteurs (optionnel)}}">{{Numéro PCE}}</label>
	 <div class="col-sm-6">
			 <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="pce" placeholder="Identifiant du compteur (PCE)"/>
	 </div>
</div>
 <div class="form-group">
	 <label class="col-sm-6 control-label help" data-help="{{Cocher la case pour forcer la récupération des données même si déjà présentes}}">{{Forcer la récupération des données}}</label>
	 <div class="col-sm-6">
		<input type="checkbox" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="forceRefresh"/>
 	 </div>
	</div>
 <div class="form-group">
	 <label class="col-sm-6 control-label help" data-help="{{Sélectionnez le template de widget à utiliser}}">{{Template de widget}}</label>
	 <div class="col-sm-6">
         
        <select id="sel_object_template" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="widgetTemplate">
            <option value="none">{{Aucun}}</option>
            <option value="jazpar">Jazpar 1</option>
            <option value="jazpar2">Jazpar 2</option>
            <option value="jazpar3">Jazpar 3</option>
            <option value="jazpar4">Jazpar 4</option>
        </select>

 	 </div>
	</div>
 <div class="form-group">
	 <label class="col-sm-6 control-label help" data-help="{{Sélectionnez l'unité à utiliser par défaut (uniquement pour le widget Jazpar 2)}}">{{Unité préférée}}</label>
	 <div class="col-sm-6">
         
        <select id="sel_object_unit" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="defaultUnit">
            <option value="kwh">kWh</option>
            <option value="m3">m3</option>
        </select>

 	 </div>
	</div>
  <!--
 <div class="form-group">
	 <label class="col-sm-6 control-label help" data-help="{{Cocher la case pour utiliser des dates plutôt que les noms des commandes dans les widgets}}">{{Utiliser des dates dans les widgets}}</label>
	 <div class="col-sm-6">
		<input type="checkbox" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="useDates"/>
 	 </div>
	</div>
 <div class="form-group">
	 <label class="col-sm-6 control-label help" data-help="{{Cocher la case pour arrondir les valeurs dans les widgets}}">{{Arrondir les valeurs}}</label>
	 <div class="col-sm-6">
		<input type="checkbox" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="roundValues"/>
 	 </div>
	</div>
  -->
</fieldset>
</form>
</div>
<div style="width: 50%; float: right; text-align: center;">
    {{Exemple de rendu avec le template de widget sélectionné :}}
    <br/>
    <br/>
    <img id="screenshot_none" src="plugins/jazpar/data/images/screenshot_none.png" style="display: none;">
    <img id="screenshot_jazpar" src="plugins/jazpar/data/images/screenshot_jazpar.png" style="display: none;">
    <img id="screenshot_jazpar2" src="plugins/jazpar/data/images/screenshot_jazpar2.png" style="display: none;">
    <img id="screenshot_jazpar3" src="plugins/jazpar/data/images/screenshot_jazpar3.png" style="display: none;">
    <img id="screenshot_jazpar4" src="plugins/jazpar/data/images/screenshot_jazpar4.png" style="display: none;">
</div>
</div>
      <div role="tabpanel" class="tab-pane" id="commandtab">
<!--<a class="btn btn-success btn-sm cmdAction pull-right" data-action="add" style="margin-top:5px;">
<i class="fa fa-plus-circle"></i> {{Commandes}}</a><br/> -->
<br/>
<table id="table_cmd" class="table table-bordered table-condensed">
    <thead>
        <tr>
            <th style="width:50px;">{{Id}}</th>
            <th style="width:300px;">{{Nom}}</th>
						<th>{{Type}}</th>
						<th class="col-xs-3">{{Options}}</th>
						<th class="col-xs-2">{{Action}}</th>
        </tr>
    </thead>
    <tbody>
    </tbody>
</table>
</div>
</div>

</div>
    <script>           
        $('#sel_object_template').on('change', function() {
          $('#screenshot_none').hide();
          $('#screenshot_jazpar').hide();
          $('#screenshot_jazpar2').hide();
          $('#screenshot_jazpar3').hide();
          $('#screenshot_jazpar4').hide();
          $('#screenshot_' + this.value).show();
        });    
    </script> 
</div>

<!-- Inclusion du fichier javascript du plugin (dossier, nom_du_fichier, extension_du_fichier, nom_du_plugin) -->
<?php include_file('desktop', 'jazpar', 'js', 'jazpar');?>
<!-- Inclusion du fichier javascript du core - NE PAS MODIFIER NI SUPPRIMER -->
<?php include_file('core', 'plugin.template', 'js');?>
