<?php
/**
 *
 *
 * This program is a free software product.
 * You can redistribute it and/or modify it under the terms of the GNU Affero General Public License
 * (AGPL) version 3 as published by the Free Software Foundation.
 * In accordance with Section 7(a) of the GNU AGPL its Section 15 shall be amended to the effect
 * that Ascensio System SIA expressly excludes the warranty of non-infringement of any third-party rights.
 *
 * This program is distributed WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * For details, see the GNU AGPL at: http://www.gnu.org/licenses/agpl-3.0.html
 *
 * You can contact Ascensio System SIA at 20A-12 Ernesta Birznieka-Upisha street, Riga, Latvia, EU, LV-1050.
 *
 * The interactive user interfaces in modified source and object code versions of the Program
 * must display Appropriate Legal Notices, as required under Section 5 of the GNU AGPL version 3.
 *
 * Pursuant to Section 7(b) of the License you must retain the original Product logo when distributing the program.
 * Pursuant to Section 7(e) we decline to grant you any rights under trademark law for use of our trademarks.
 *
 * All the Product's GUI elements, including illustrations and icon sets, as well as technical
 * writing content are licensed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International.
 * See the License terms at http://creativecommons.org/licenses/by-sa/4.0/legalcode
 *
 */

    // style("kreuzmichlogin", "settings");
    script("kreuzmichlogin", "settings");

    if ($_["tagsEnabled"]) {
        if (\version_compare(\implode(".", \OCP\Util::getVersion()), "16", "<")) {
            script("core", [
                "oc-backbone-webdav"
            ]);
        }
        script("core", [
            "systemtags/systemtags",
            "systemtags/systemtagmodel",
            "systemtags/systemtagsmappingcollection",
            "systemtags/systemtagscollection",
            "systemtags/systemtagsinputfield",
        ]);
    }
?>
<div class="section section-kreuzmich">
    <h2>
        Kreuzmich Login
        <a target="_blank" class="icon-info svg" title="" href="https://wiki.fsmed.de/lesen/Kreuzmich-Login" data-original-title="<?php p($l->t("Mehr Info")) ?>"></a>
    </h2>
	<p><?php p($l->t("Achtung, diese Einstellungen überschreiben alle Konfigurationen, die in der /config/config.php Datei von Nextcloud angelegt wurden. Sollten diese Einstellungen hier den Kreuzmich-Login unmöglich machen, z.B. durch einen Tippfehler, müssen über einen Datenbankzugriff die EInstellungen gelöscht oder die App „Kreuzmich-Login“ deaktiviert werden.")) ?></p>

    <h3 style="margin-top: 2em;"><?php p($l->t("Allgemeine Einstellungen")) ?></h3>

    <div id="kreuzmichAddrSettings">
        <p class="settings-hint"><?php p($l->t("Hier kannst du allgemeine Einstellungen für den Kreumich-Login vornehmen. Nicht alle Einstellungen sind für einen normalen Betrieb notwendig.")) ?></p>
        <p style="margin-top: 1em;" class="kreuzmich-header"><?php p($l->t("Kreuzmich Stadt/Subdomäne")) ?></p>
		<p><input id="kreuzmichCity" value="<?php p($_["city"]) ?>" placeholder="duesseldorf" type="text"></p>
		<p style="margin-top: 0em;" class="settings-hint"><?php p($l->t("Deine Stadt, so wie sie in der URL deines Kreuzmichs steht, z.B. \"https://duesseldorf.kreuzmich.de\".")) ?></p>
		<p style="margin-top: 1em;" class="settings-hint"><?php p($l->t("Die folgenden Einstellungen werden nur für besondere Testzwecke benutzt. Für den normalen Betrieb einfach freilassen.")) ?></p>
        <p class="kreuzmich-header"><?php p($l->t("HTTP User")) ?></p>
        <p><input id="kreuzmichHTTPuser" value="<?php p($_["httpuser"]) ?>" placeholder="Benutzer" type="text"></p>
        <p class="kreuzmich-header"><?php p($l->t("HTTP Passwort")) ?></p>
        <p><input id="kreuzmichHTTPpass" value="<?php p($_["httppass"]) ?>" placeholder="Passwort" type="text"></p>
    </div>

</div>

<div class="section section-kreuzmich section-kreuzmich-common">
    <h3><?php p($l->t("Benutzereinstellungen")) ?></h3>
	<p class="settings-hint"><?php p($l->t("Hier kannst du festlegen, was mit den Kreuzmich-Benutzer*innen geschieht, wenn sie sich einloggen.")) ?></p>

    <p>
        <input type="checkbox" class="checkbox" id="kreuzmichGroups"
            <?php if ($_["groups"]) { ?>checked="checked"<?php } ?> />
        <label for="kreuzmichGroups"><?php p($l->t("Allen Benutzer*innen eine Standardgruppe zuweisen")) ?></label>
        <input type="hidden" id="kreuzmichLimitGroups" value="<?php p($_["groups"]) ?>" placeholder="Gruppen" style="display: block" />
    </p>

    <p>
        <input type="checkbox" class="checkbox" id="kreuzmichExpired"
            <?php if ($_["expired"]) { ?>checked="checked"<?php } ?> />
        <label for="kreuzmichExpired"><?php p($l->t("Auch abgelaufene Benutzer*innen zulassen")) ?></label>
    </p>
	<p>
        <input type="checkbox" class="checkbox" id="kreuzmichNew"
            <?php if ($_["new"]) { ?>checked="checked"<?php } ?> />
        <label for="kreuzmichNew"><?php p($l->t("Auch neue Benutzer*innen zulassen")) ?></label>
    </p>
	<p style="margin-top: 0em;" class="settings-hint"><?php p($l->t("Ist diese Option deaktiviert, können sich nur Benutzer*innen einloggen, die schon mal in der Cloud eingeloggt waren oder die durch eine/n Admin manuell zur Cloud hinzugefügt wurden.")) ?></p>

    <br />

</div>

<div class="section">
    <p><button id="kreuzmichSave" class="button"><?php p($l->t("Speichern")) ?></button></p>
</div>
