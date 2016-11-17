"use strict";

var autoSaveTimer = 0;

parent.bindEvent(window, "load", function() {
	parent.loading(false, parent.frames.el.project);
});

function setLocation() {
	if (decodeURIComponent(parent.window.location.href).search(account.name) == -1) {
		var loc = parent.window.location;
		parent.window.history.replaceState({}, parent.document.title, loc.protocol + '//' + loc.host + loc.pathname + '?' + account.name);
		parent.document.title = "Sitecues INSPIRE: " + account.name;
	}	
}

function enableInputEvents() {
	var o = null;
	if (o = document.getElementById("ppage_project")) {
		parent.bindEvent(o, "change", function(e) {
			e = e || window.event;
			var t = e.target || e.srcElement;
			var projVal;
			switch (t.id) {
				case "url":
					projVal = (eval("proj." + t.id)).url;
					break;
				default:
					projVal = eval("proj." + t.id);
					break;
			}
			switch (t.tagName) {
				case "INPUT":
				case "TEXTAREA":
				case "SELECT":
					if (t.value != projVal) {
						var valText = (t.options) ? t.options[t.selectedIndex].text : t.value;
						var msg = parent.strings.IDS_CONFIRM_UPDATE.replace("%1", parent.ucwords(t.id)).replace("%2", valText ? valText : parent.strings.IDS_EMPTY)
						msg += "<input type='hidden' id='field' value='" + t.id + "'>";
						msg += "<input type='hidden' id='oldval' value='" + projVal + "'>";
						msg += "<input type='hidden' id='action' value='updateField'>";
						msg += "<input type='hidden' id='parms' value='pid=" + account.id + "&id=" + proj.id + "&field=" + t.id + "&val=" + t.value + "'>";
						var src = parent.getcache("confirmSrc");
						parent.dlg(parent.strings.IDS_CONFIRM, src.html.replace("{{msg}}", msg), t);
					}
					break;
				default:
					break;
			}
		}, true);
	}		
}

function getProject(id) {
	var retval = null;
	var p;
	for (p in account.projects) {
		var proj = account.projects[p];
		if (proj.id == id) {
			retval = proj;
			break;
		}
	}
	return retval;
}

function loadProject(id) {
	proj = getProject(id);
	var doc = parent.frames.dom.account;
	doc.getElementById("name").value = proj.name;
	doc.getElementById("url").value = proj.url.url;
	doc.getElementById("s_name").value = proj.s_name;
	doc.getElementById("s_email").value = proj.s_email;
	doc.getElementById("t_name").value = proj.t_name;
	doc.getElementById("t_email").value = proj.t_email;
	getSitecuesContacts(proj);
	getServiceTiers(proj);
	getStages(proj);
	getStatus(proj);
	getAttachments(proj);
	getValidation(proj)
	getIssues(proj);
	setLocation();	
	enableInputEvents();
	showTab("project");
}

function getServiceTiers(proj) {
	var cached = parent.getcache("getServiceTiers");
	var e;
	if (e = document.getElementById("tier")) {
		while (e.options.length) {
			e.remove(0);
		}
		var r;
		for (r in cached) {
			var opt = document.createElement("option");
			opt.value = cached[r].id;
			opt.text = cached[r].name;
			opt.selected = (cached[r].id == proj.tier);
			e.appendChild(opt);
		}
	}
}

function getStages(proj) {
	var cached = parent.getcache("getStages");
	var e;
	if (e = document.getElementById("stage")) {
		while (e.options.length) {
			e.remove(0);
		}
		var r;
		for (r in cached) {
			var opt = document.createElement("option");
			opt.value = cached[r].id;
			opt.text = cached[r].name;
			opt.selected = (cached[r].id == proj.stage);
			e.appendChild(opt);
		}
	}
}

function getStatus(proj) {
	var cached = parent.getcache("getStatus");
	var e;
	if (e = document.getElementById("status")) {
		while (e.options.length) {
			e.remove(0);
		}
		var r;
		for (r in cached) {
			var opt = document.createElement("option");
			opt.value = cached[r].id;
			opt.text = cached[r].name;
			opt.selected = (cached[r].id == proj.status);
			e.appendChild(opt);
		}
	}
}

function getSitecuesContacts(proj) {
	var cached = parent.getcache("getSitecuesContacts");
	var e;
	if (e = document.getElementById("a_email")) {
		while (e.options.length) {
			e.remove(0);
		}
		var r;
		for (r in cached) {
			var opt = document.createElement("option");
			opt.value = cached[r].email;
			opt.text = cached[r].name;
			opt.selected = (opt.value == proj.a_email);
			e.appendChild(opt);
		}
	}
}

function getAttachments(proj) {
	var a = parent.frames.dom.account.getElementById("attachments_container");
	a.innerHTML = atob(proj.tables.attachments);
	var e;
	if (e = parent.frames.dom.account.getElementById("noattachments")) {
		e.innerHTML = parent.strings.IDS_NO_ATTACHMENTS;
	}
}

function getValidation(proj) {
	var e = parent.frames.dom.account.getElementById("validation_container");
	var validation = JSON.parse(atob(proj.url.validation));
	var result;
	for (result in validation) {
		var checks = validation[result].checks;
		var c;
		var found = 0;
		var errs = "";
		for (c in checks) {
			found++;
			errs += "<li>" + c + ": " + checks[c] + "</li>";
		}
		if (!found) {
			e.innerHTML = "<span class='green'>" + parent.strings.IDS_VALID + "</span>";
		} else {
			e.innerHTML = "<span class='red'>" + parent.strings.IDS_INVALID + "</span>";
			e.innerHTML += "<ul>" + errs + "</ul>";
		}
	}
}

function getIssues(proj) {
	var i = parent.frames.dom.account.getElementById("issues_container");
	i.innerHTML = atob(proj.tables.issues);
	var e;
	if (e = parent.frames.dom.account.getElementById("noissues")) {
		e.innerHTML = parent.strings.IDS_NO_ISSUES;
	}
}

function refreshProject() {
	parent.getRequest("inspire.php?" + btoa(proj.name) + "&action=refreshProject&progress=0", "refreshProject");
}

function showTab(id) {
	id = id.replace("tab_", "");
	var ppages = document.getElementsByClassName("ppage");
	for (var i = 0; i < ppages.length; i++) {
		var page = ppages[i];
		page.style.display = ("ppage_" + id == page.id) ? "flex" : "none";
		if (page.style.display == "flex") {
			//
		}
	}
}

function updateField(resultObj) {
	if (resultObj.success) {
		var e;
		if (e = document.getElementById(resultObj.field)) {
			e.value = resultObj.val;
			eval("proj." + resultObj.field + " = '" + resultObj.val + "'");
			// if (resultObj.field == "name") {
				// parent.frames.win.projects.refreshProjects();
				// setLocation();
			// }
		}
	} else {
		
	}
}

function getSite(id) {
	var result = null;
	for (var i = 0; i < proj.urls.length; i++) {
		var url = proj.urls[i];
		if (url.id == id) {
			result = url;
			break;
		}
	}
	return result;
}

function newSite() {
	parent.dlg(parent.strings.IDS_NEW_SITE, parent.getcache("newSiteSrc").html);
}

function newAttachment() {
	parent.dlg(parent.strings.IDS_NEW_ATTACHMENT, parent.getcache("newAttachmentSrc").html);
}

function addSite(dialog) {
	var url = dialog.querySelector("#url").value;
	var siteid = dialog.querySelector("#siteid").value;
	var created = dialog.querySelector("#created").value;
	var status = dialog.querySelector("#status").value;
	parent.getRequest("inspire.php?action=addSite&pid=" + proj.id
		+ "&url=" + url 
		+ "&siteid=" + siteid
		+ "&created=" + created
		+ "&status=" + status, "addSite");
}

function editSite(id) {
	var site = getSite(id); 
	var d = document.createElement('div');
	d.innerHTML = parent.getcache("newSiteSrc").html;
	var inputs = d.getElementsByTagName('input');
	for (var i in inputs) {
		var input = inputs[i];
		switch (input.id) {
			case "url":
				input.setAttribute("value", site.url);
				break;
			case "siteid":
				input.setAttribute("value", site.siteid);
				break;
			case "created":
				input.setAttribute("value", site.created);
				break;
			default:
				break
		}
	}
	var ide = document.createElement("input");
	ide.setAttribute("type", "hidden");
	ide.setAttribute("id", "id");
	ide.setAttribute("value", site.id);
	d.appendChild(ide);
	var ide = document.createElement("input");
	ide.setAttribute("type", "hidden");
	ide.setAttribute("id", "currentStatus");
	ide.setAttribute("value", site.status);
	d.appendChild(ide);
	parent.dlg(parent.strings.IDS_EDIT_SITE, d.innerHTML, document.activeElement);
}

function updateSite(id, url, siteid, created, status) {
	parent.getRequest("inspire.php?action=updateSite&id=" + id + "&pid=" + proj.id + "&url=" + url + "&siteid=" + siteid + "&created=" + created + "&status=" + status, "updateSite");
}

function refreshSites() {
	var sitetable = document.getElementById("sitetable");
	sitetable.parentNode.removeChild(sitetable);
	parent.loading(true, parent.frames.el.project, "ppage_sites");
	parent.getRequest("inspire.php?" + proj.name + "&action=getSitesTable", "updateSitesTable");
}

function updateSiteTable(resultObj) {
	document.getElementById("sites_container").innerHTML = resultObj.html;
	parent.loading(false, parent.frames.el.project, "ppage_sites");	
}

function updateAttachmentsTable(resultObj) {
	// document.getElementById("sites_container").innerHTML = resultObj.html;
	// parent.loading(false, parent.frames.el.project, "ppage_sites");	
}

function loadTiny(id) {
	tinymce.init({
		selector:'#' + id,
		width: '100%',
		height: '80%',
		menu: {
			edit: {title: 'Edit', items: 'undo redo | cut copy paste pastetext | selectall'},
			format: {title: 'Format', items: 'bold italic underline strikethrough superscript subscript | formats | removeformat'},
			table: {title: 'Table', items: 'inserttable tableprops deletetable | cell row column'},
			tools: {title: 'Tools', items: 'spellchecker code'}
		},
		content_css: "../css/tiny.css",
		init_instance_callback: function (editor) {
			// editor.on('blur', function (e) { 
				// var t = e.target || e.srcElement;
				// var projVal = encodeURIComponent(eval("proj." + t.id))
				// var content = encodeURIComponent(btoa(tinymce.get(id).getContent()));
				// if (projVal != content) {
					// var msg = eval("parent.strings.IDS_UPDATE_" + id.toUpperCase());
					// msg += "<input type='hidden' id='field' value='" + t.id + "'>";
					// msg += "<input type='hidden' id='oldval' value='" + projVal + "'>";
					// msg += "<input type='hidden' id='action' value='updateField'>";
					// msg += "<input type='hidden' id='parms' value='pid=" + proj.id + "&field=" + t.id + "&val=" + content + "'>";
					// var src = parent.getcache("confirmSrc");
					// parent.dlg(parent.strings.IDS_CONFIRM, src.html.replace("{{msg}}", msg), t);
				// }
			// }),
			editor.on('keyup', function(e) {
				if (autoSaveTimer) {
					clearTimeout(autoSaveTimer);
				}
				autoSaveTimer = setTimeout(function() {
					var t = e.target || e.srcElement;
					var content = encodeURIComponent(btoa(tinymce.get(id).getContent()));
					var parms = "pid=" + proj.id + "&field=" + id + "&val=" + content;
					parent.postRequest("inspire.php?action=updateField", parms, "updateField");
					parent.popIn(parent.strings.IDS_AUTO_SAVED);
				}, 1100)
			})
		}
	});
}