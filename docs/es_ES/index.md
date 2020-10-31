# Complemento Enedis Linky

Plugin que permite recuperar consumos del contador comunicante *linky* consultando la cuenta del cliente *Enedis*. Como los datos no están disponibles en tiempo real, el complemento recupera los datos de consumo de electricidad del día anterior a cada día.

4 tipos de datos de consumo accesibles :
- el **poder dibujado** por media hora *(en kW)*.
> La curva de consumo *(o poder extraído)* restaura la energía demandada por todos sus dispositivos eléctricos en promedio durante media hora.

- el **consumo diario** *(en kWh)*.
- el **consumo mensual** *(en kWh)*.
- el **consumo anual** *(en kWh)*.

>**Importante**      
>Debes tener una cuenta de cliente de Enedis. El complemento recupera información del juego *profesionales* [del sitio de Enedis](https://espace-client-connexion.enedis.fr/auth/XUI/#login/&realm=particuliers&goto=https://espace-client-particuliers.enedis.fr%2Fgroup%2Fespace-particuliers%2Faccueil){:target = "\_ blank"}, por lo tanto, debe verificar que tiene acceso a él con sus identificadores habituales y que los datos están visibles allí. De lo contrario, el complemento no funcionará.

# Configuration

## Configuración del plugin

El complemento **Enedis Linky** no requiere ninguna configuración específica y solo debe activarse después de la instalación.

Los datos se verifican cada hora entre las 4 a.m. y las 10 p.m. y se actualizan solo si no están disponibles en Jeedom.

## Configuración del equipo

Para acceder a los diferentes equipos **Enedis Linky**, ir al menú **Complementos → Energía → Enedis Linky**.

> **A saber**    
> El botón **+ Agregar** le permite agregar una nueva cuenta **Enedis Linky**.

En la página del equipo, complete el'**Identificador** así como el **Contraseña** de su cuenta de cliente *Enedis* luego haga clic en el botón **Guardar**.

El complemento luego verificará la conexión correcta al sitio *Enedis* y recuperar e insertar en el historial :
- **poder dibujado** : los 48 valores del día anterior *(1 valor por media hora)*,
- **consumo diario** : los últimos 30 días,
- **consumo mensual** : los últimos 12 meses,
- **consumo anual** : los últimos 3 años.

# Plantilla de widget

El complemento ofrece la posibilidad de mostrar datos de consumo en una plantilla de widget que imita la apariencia de un medidor *Linky*. Tienes la posibilidad de seleccionar o no esta plantilla marcando o desmarcando la casilla **Plantilla de widget** en la página general del equipo en cuestión.

La plantilla se mostrará tanto en la versión de escritorio como en la versión móvil.

>**CONSEJO**     
>En la versión de escritorio, la información que se muestra en el widget se adapta en tamaño al cambiar el tamaño del mosaico.
