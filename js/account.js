"use strict";

var autoSaveTimer = 0;
var accountEventsLoaded = false;
var inputEventsLoaded = false;

parent.bindEvent(window, "message", function(e) {
	switch (e.data.msg) {
		case "refreshAccount":
			loadAccount();
			break;
		case "refreshProject":
      updateAttachmentsTable(document.getElementById("pid").value);
			break;
		default:
			break;
	}
});

parent.bindEvent(window, "load", function() {
	parent.loading(false, parent.frames.el.account);
	loadAccount();
	selectAccount();
	setLocation();
});

function selectAccount() {
	var accounts = parent.frames.dom.accounts.getElementById("accountlist").getElementsByTagName("a");
	for (var i = 0; i < accounts.length; i++) {
		var a = accounts[i];
		var name = a.innerHTML;
		a.className = (name == account.name) ? "account_selected" : "account";
		if (a.className == "account_selected") {
			//a.scrollIntoView();
			setLocation();
		}
	}
}

function setLocation() {
	if (decodeURIComponent(parent.window.location.href).search(account.name) == -1) {
		var loc = parent.window.location;
		parent.window.history.replaceState({}, parent.document.title, loc.protocol + '//' + loc.host + loc.pathname + '?' + account.name);
		parent.document.title = "Sitecues INSPIRE: " + account.name;
	}	
}

function enableAccountInputEvents() {
	accountEventsLoaded = true;
	var o = null;
	if (o = document.getElementById("ppage_account")) {
		parent.bindEvent(o, "change", function(e) {
			e = e || window.event;
			var t = e.target || e.srcElement;
			var accVal = eval("account." + t.id);
			switch (t.tagName) {
				case "INPUT":
				case "TEXTAREA":
				case "SELECT":
					if (t.value != accVal) {
						var valText = (t.options) ? t.options[t.selectedIndex].text : t.value;
						var msg = parent.strings.IDS_CONFIRM_UPDATE.replace("%1", getFieldName(t.id)).replace("%2", valText ? valText : parent.strings.IDS_EMPTY)
						msg += "<input type='hidden' id='field' value='" + t.id + "'>";
						msg += "<input type='hidden' id='oldval' value='" + accVal + "'>";
						msg += "<input type='hidden' id='action' value='updateAccount'>";
						msg += "<input type='hidden' id='parms' value='id=" + account.id + "&field=" + t.id + "&val=" + t.value + "'>";
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

function enableProjectInputEvents() {
	inputEventsLoaded = true;
	var o = null;
	if (o = document.getElementById("ppage_project")) {
		parent.bindEvent(o, "change", function(e) {
			e = e || window.event;
			var t = e.target || e.srcElement;
			var projVal;
			var proj = parent.frames.win.account.account.proj;
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
						var msg = parent.strings.IDS_CONFIRM_UPDATE.replace("%1", getFieldName(t.id)).replace("%2", valText ? valText : parent.strings.IDS_EMPTY)
						msg += "<input type='hidden' id='field' value='" + t.id + "'>";
						msg += "<input type='hidden' id='oldval' value='" + projVal + "'>";
						msg += "<input type='hidden' id='action' value='updateProject'>";
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

function loadAccount() {
	var doc = parent.frames.dom.account;
	doc.getElementById("name").value = prettify(account.name);
	doc.getElementById("description").value = prettify(account.description);
	doc.getElementById("created").value = account.created;
	doc.getElementById("updated").value = account.updated;
	getServiceTiers();
	getSitecuesContacts();
	if (!accountEventsLoaded) {
		enableAccountInputEvents();
	}
}

function loadProject(id) {
	var proj = getProject(id);
	account.proj = proj;
	var doc = parent.frames.dom.account;
	doc.getElementById("pid").value = proj.id;
	doc.getElementById("p_created").value = proj.created;
	doc.getElementById("p_updated").value = proj.updated;
	doc.getElementById("p_url").value = proj.url.url;
	doc.getElementById("p_siteid").value = proj.siteid;
	doc.getElementById("p_description").value = prettify(proj.description);
	doc.getElementById("p_s_name").value = prettify(proj.s_name);
	doc.getElementById("p_s_email").value = proj.s_email;
	addEmailLink("p_s_", proj);
	doc.getElementById("p_t_name").value = prettify(proj.t_name);
	doc.getElementById("p_t_email").value = proj.t_email;
	addEmailLink("p_t_", proj);
	getStages(proj);
	getStatus(proj);
	getAttachments(proj);
	getValidation(proj)
	getIssues(proj);
	setLocation();
	showTab("project");
	if (!inputEventsLoaded) {
		enableProjectInputEvents();
	}
}

function getServiceTiers() {
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
			opt.selected = (cached[r].id == parent.frames.win.account.account.tier);
			e.appendChild(opt);
		}
	}
}

function getStages(proj) {
	var cached = parent.getcache("getStages");
	var e;
	if (e = document.getElementById("p_stage")) {
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
	if (e = document.getElementById("p_status")) {
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

function getSitecuesContacts() {
	var cached = parent.getcache("getSitecuesContacts");
	var e;
	if (e = document.getElementById("sales_id")) {
		while (e.options.length) {
			e.remove(0);
		}
		var r;
		for (r in cached) {
			var opt = document.createElement("option");
			opt.value = cached[r].email;
			opt.text = cached[r].name;
			opt.selected = (opt.value == account.sales_id);
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
		var results = JSON.parse(atob(JSON.parse(atob(proj.url.validation)).result));
		var c, r;
		var found = 0;
		var errs = "";
		for (r in results) {
      var checks = results[r].checks;
      for (c in checks) {
        found++;
        errs += "<li>" + c + "<ul><li>" + checks[c] + "</li></ul></li>";
      }
		}
    if (!found) {
      e.innerHTML = "<span class='green'>" + parent.strings.IDS_VALID + "</span>";
    } else {
      e.innerHTML = "<span class='red'>" + parent.strings.IDS_INVALID + "</span>";
      e.innerHTML += "<ul>" + errs + "</ul>";
    }
	// }
}

function getIssues(proj) {
	var i = parent.frames.dom.account.getElementById("issues_container");
	i.innerHTML = atob(proj.tables.issues);
	var e;
	if (e = parent.frames.dom.account.getElementById("noissues")) {
		e.innerHTML = parent.strings.IDS_NO_ISSUES;
	}
}

function refreshAccount() {
	parent.getRequest("inspire.php?" + btoa(account.name) + "&action=refreshAccount&progress=0", "refreshAccount");
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

function updateProject(resultObj) {
	if (resultObj.success) {
		var e;
		if (e = document.getElementById(resultObj.field)) {
			e.value = resultObj.val;
			eval("parent.frames.win.account.account.proj." + resultObj.field + " = '" + resultObj.val + "'");
		}
	} else {
		
	}
}

function updateAccount(resultObj) {
	account = resultObj;
	loadAccount();
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

function newProject() {
	parent.dlg(parent.strings.IDS_NEW_PROJECT, parent.getcache("newProjectSrc").html);
}

function newAttachment() {
	parent.dlg(parent.strings.IDS_NEW_ATTACHMENT, parent.getcache("newAttachmentSrc").html);
}

function addProject(dialog) {
	parent.getRequest("inspire.php?action=addProject&pid=" + account.id
		+ "&url=" + dialog.querySelector("#url").value
		+ "&siteid=" + dialog.querySelector("#site_id").value
		+ "&created=" + dialog.querySelector("#created").value
		+ "&stage=" + dialog.querySelector("#stage").value
		+ "&status=" + dialog.querySelector("#status").value
		+ "&s_name=" + dialog.querySelector("#s_name").value
		+ "&s_email=" + dialog.querySelector("#s_email").value
		+ "&t_name=" + dialog.querySelector("#t_name").value
		+ "&t_email=" + dialog.querySelector("#t_email").value, "addProject");
}

function refreshSites() {
	var sitetable = document.getElementById("sitetable");
	sitetable.parentNode.removeChild(sitetable);
	parent.loading(true, document.getElementById("projects_container"));
	parent.getRequest("inspire.php?" + btoa(account.name) + "&action=getSitesTable&progress=0", "updateSitesTable");
}

function updateSiteTable(resultObj) {
	document.getElementById("projects_container").innerHTML = resultObj.html;
	parent.loading(false, document.getElementById("projects_container"));	
}

function refreshAttachments() {
	var attachmentstable = document.getElementById("attachmentstable");
	attachmentstable.parentNode.removeChild(attachmentstable);
	parent.loading(true, document.getElementById("projects_container"));
	parent.getRequest("inspire.php?" + btoa(account.name) + "&pid=&action=getAttachmentsTable&progress=0", "updateAttachmentsTable");
}

function updateAttachmentsTable(id) {
	document.getElementById("attachments_container").innerHTML = atob(getProject(id).tables.attachments);
  var e;
	if (e = parent.frames.dom.account.getElementById("noattachments")) {
		e.innerHTML = parent.strings.IDS_NO_ATTACHMENTS;
	}
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
			editor.on('keyup', function(e) {
				if (autoSaveTimer) {
					clearTimeout(autoSaveTimer);
				}
				autoSaveTimer = setTimeout(function() {
					var t = e.target || e.srcElement;
					var content = encodeURIComponent(btoa(tinymce.get(id).getContent()));
					var parms = "id=" + account.id + "&field=" + id + "&val=" + content;
					parent.postRequest("inspire.php?action=updateAccount", parms, "updateAccount");
					parent.popIn(parent.strings.IDS_AUTO_SAVED);
				}, 1100)
			})
		}
	});
}

function addEmailLink(id, proj) {
	var a = document.createElement("a");
	a.setAttribute("href", "mailto:" + eval("proj." + id + "email"));
	var i = document.createElement("img");
	i.setAttribute("src", "../img/email.png");
	i.setAttribute("aria-label", eval("proj." + id + "name") + " " + eval("proj." + id + "email"));
	a.appendChild(i);
}

function getFieldName(id) {
	var label = document.querySelector("label[for='" + id + "']");
	var legend = null;
	if (label) {
		var p = label.parentNode;
		do {
			if (p.tagName == "FIELDSET") {
				legend = p.getElementsByTagName("legend")[0].innerHTML.trim();
			}
			p = p.parentNode;
		} while (p || !legend);
	}
	return legend + " / " + label.innerHTML.replace(":", "").trim();
}

function deleteAttachment(id, pid) {
	var msg = parent.strings.IDS_CONFIRM_DELETE_ATTACHMENT;
	msg += "<input type='hidden' id='action' value='deleteAttachment'>";
	msg += "<input type='hidden' id='parms' value='id=" + id + "&pid=" + pid + "'>";
	var src = parent.getcache("confirmSrc");
	parent.dlg(parent.strings.IDS_CONFIRM, src.html.replace("{{msg}}", msg));
}

function cloneSales(c) {
	var dlg = parent.document.getElementById("dlgDialog");
	if (c) {
		dlg.querySelector("#t_name").value = dlg.querySelector("#s_name").value;
		dlg.querySelector("#t_email").value = dlg.querySelector("#s_email").value;
		dlg.querySelector("#t_name").readonly = dlg.querySelector("#t_email").readonly = true;
	} else {
		dlg.querySelector("#t_name").value = dlg.querySelector("#t_email").value = "";
		dlg.querySelector("#t_name").readonly = dlg.querySelector("#t_email").readonly = false;
	}
}

function prettify(str) {
  return str ? decodeURIComponent(str).replace(/\+/, " ") : "";
}