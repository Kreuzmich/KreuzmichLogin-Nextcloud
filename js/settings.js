/**
 *
 * (c) Copyright Ascensio System SIA 2020
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

(function ($, OC) {

    $(document).ready(function () {
        OCA.kreuzmich = _.extend({}, OCA.kreuzmich);
        if (!OCA.kreuzmich.AppName) {
            OCA.kreuzmich = {
                AppName: "kreuzmichlogin"
            };
        }

        $("#kreuzmichGroups").prop("checked", $("#kreuzmichLimitGroups").val() != "");

        var groupListToggle = function () {
            if ($("#kreuzmichGroups").prop("checked")) {
                OC.Settings.setupGroupsSelect($("#kreuzmichLimitGroups"));
            } else {
                $("#kreuzmichLimitGroups").select2("destroy");
            }
        };

        $("#kreuzmichGroups").click(groupListToggle);
        groupListToggle();

        $("#kreuzmichSave").click(function () {
			$(".section-kreuzmich").addClass("icon-loading");
			
			var kreuzmichCity = $("#kreuzmichCity").val().trim();
            var kreuzmichHTTPuser = ($("#kreuzmichHTTPuser").val() || "").trim();
            var kreuzmichHTTPpass = ($("#kreuzmichHTTPpass").val() || "").trim();
			
			
			if (($("#kreuzmichLimitGroups").select2('data')).length > 1) {
                $(".section-kreuzmich").removeClass("icon-loading");
				var message = "Bitte wähle nur eine Gruppe aus!";
				OC.Notification.show(message, {
                            type: "error",
                            timeout: 3
                        });
				return false;
			}
            
			var limitGroupsString = $("#kreuzmichGroups").prop("checked") ? $("#kreuzmichLimitGroups").val() : "";
            var limitGroups = limitGroupsString ? limitGroupsString.split("|") : [];

            var kmnew = $("#kreuzmichNew").is(":checked");
            var expired = $("#kreuzmichExpired").is(":checked");
			
            var objdata = {
                    httpuser: kreuzmichHTTPuser,
                    httppass: kreuzmichHTTPpass,
                    city: kreuzmichCity,
                    expired: expired,
                    new: kmnew,
                    groups: limitGroups[0]
                };
			console.log(objdata);
			$.ajax({
                method: "PUT",
				data: objdata,
                url: OC.generateUrl("apps/" + OCA.kreuzmich.AppName + "/ajax/settings/save"),
                success: function onSuccess(response) {
                    $(".section-kreuzmich").removeClass("icon-loading");
                    
					if (response) {
                        $("#kreuzmichCity").val(response.city);
                        $("#kreuzmichHTTPuser").val(response.httpuser);
                        $("#kreuzmichHTTPpass").val(response.httppass);
                        $("#kreuzmichLimitGroups").val(response.groups);

                        var message =
                            response.error
								? (t(OCA.kreuzmich.AppName, "Error when trying to connect") + " (" + response.error + ")")
								: t(OCA.kreuzmich.AppName, "Einstellungen erfolgreich geupdatet.");
                        OC.Notification.show(message, {
                            type: response.error ? "error" : null,
                            timeout: 3
                        });
                    }
                }
			});
        });

    });

})(jQuery, OC);
