# Enedis Linky Plugin

Plugin zur Wiederherstellung des Verbrauchs des kommunizierenden Messgeräts *Linky* durch Abfragen des Kundenkontos *Enedis*. Da die Daten nicht in Echtzeit verfügbar sind, ruft das Plugin die Stromverbrauchsdaten vom Vortag ab.

Es stehen 4 Arten von Verbrauchsdaten zur Verfügung :
- die **gezogene Kraft** pro halbe Stunde *(in kW)*.
> Die Verbrauchskurve *(oder gezogene Kraft)* stellt die von all Ihren elektrischen Geräten benötigte Leistung im Durchschnitt über eine halbe Stunde wieder her.

- die **Täglicher Verbrauch** *(in kWh)*.
- die **monatlicher Verbrauch** *(in kWh)*.
- die **Jahresverbrauch** *(in kWh)*.

>**Wichtig**      
>Sie müssen über ein Enedis-Kundenkonto verfügen. Das Plugin ruft Informationen aus dem Spiel ab *Profis* [der Enedis-Site](https://espace-client-connexion.enedis.fr/auth/XUI/#login/&realm=particuliers&goto=https://espace-client-particuliers.enedis.fr%2Fgroup%2Fespace-particuliers%2Faccueil){:target = "\_ blank"}, müssen Sie daher überprüfen, ob Sie mit Ihren üblichen Kennungen darauf zugreifen können und ob die Daten dort sichtbar sind. Andernfalls funktioniert das Plugin nicht.

# Configuration

## Plugin Konfiguration

Das Plugin **Enedis Linky** erfordert keine spezielle Konfiguration und sollte erst nach der Installation aktiviert werden.

Die Daten werden stündlich zwischen 16 und 22 Uhr überprüft und nur aktualisiert, wenn sie in Jeedom nicht verfügbar sind.

## Gerätekonfiguration

Zugriff auf die verschiedenen Geräte **Enedis Linky**, Gehe zum Menü **Plugins → Energie → Enedis Linky**.

> **Wissen**    
> Die Schaltfläche **+ Hinzufügen** ermöglicht es Ihnen, ein neues Konto hinzuzufügen **Enedis Linky**.

Füllen Sie auf der Ausrüstungsseite das Feld aus'**Login** ebenso wie **Passwort** Ihres Kundenkontos *Enedis* Klicken Sie dann auf die Schaltfläche **Speichern**.

Das Plugin überprüft dann die korrekte Verbindung zur Site *Enedis* und abrufen und in den Verlauf einfügen :
- **gezogene Kraft** : die 48 Werte des Vortages *(1 Wert pro halbe Stunde)*,
- **Täglicher Verbrauch** : die letzten 30 Tage,
- **monatlicher Verbrauch** : die letzten 12 Monate,
- **Jahresverbrauch** : die letzten 3 Jahre.

# Widget-Vorlage

Das Plugin bietet die Möglichkeit, Verbrauchsdaten in einer Widget-Vorlage anzuzeigen, die das Erscheinungsbild eines Zählers imitiert *Linky*. Sie haben die Möglichkeit, diese Vorlage auszuwählen oder nicht, indem Sie das Kontrollkästchen aktivieren oder deaktivieren **Widget-Vorlage** auf der allgemeinen Seite der betreffenden Ausrüstung.

Die Vorlage wird sowohl auf Desktop- als auch auf Mobilversionen angezeigt.

>**TIPP**     
>In der Desktop-Version passen sich die im Widget angezeigten Informationen an die Größe an, wenn die Größe der Kachel geändert wird.
