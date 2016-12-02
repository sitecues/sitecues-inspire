"use strict";

// Global vars
var http = null;
var resultObj = null;
var searchTimer = null;
var lastFocus = null;
var animationInProgress = false;

var baseUrl = "https://localhost/inspire/";

var frames = {
	"initialized" : false,
	"loadedCount" : 0, 
	"el" : {
		"accounts" : null,
		"account" : null
	},
	"win" : {
		"accounts" : null,
		"account" : null
	},
	"dom" : {
		"accounts" : null,
		"account" : null
	}
};

var bindEvent = function (element, type, handler, useCapture) {
	useCapture = (typeof useCapture === "undefined") ? false : useCapture;
    if (element.addEventListener) {
        element.addEventListener(type, handler, useCapture);
    } else {
        element.attachEvent("on" + type, handler);
    }
}

bindEvent(window, "message", function(e) {
	switch (e.data.msg) {		
		case "loaded":
			frames.loadedCount++;
			if (frames.loadedCount == Object.keys(frames.dom).length && !frames.initialized) {
				main();
			}
			break;
		default:
			break;
	}
});

function main() {
	// Store our iframes for easy reference
	var accounts = document.getElementById("accounts");
	frames.el.accounts = accounts;
	frames.win.accounts = accounts.contentWindow;
	frames.dom.accounts = accounts.contentDocument || accounts.contentWindow.document;
	
	var account = document.getElementById("account");
	frames.el.account = account;
	frames.win.account = account.contentWindow;
	frames.dom.account = account.contentDocument || account.contentWindow.document;

	frames.initialized = true;
	
	// Load the accounts
	frames.win.accounts.loadAccounts(null);
	enableSearchEvents();

	// Pre-load templates and static data
	getRequest("inspire.php?action=newAccountSrc", "newAccountSrc");
	getRequest("inspire.php?action=newProjectSrc", "newProjectSrc");
	getRequest("inspire.php?action=newAttachmentSrc", "newAttachmentSrc");
	getRequest("inspire.php?action=confirmSrc", "confirmSrc");
	getRequest("inspire.php?action=getStages", "getStages");
	getRequest("inspire.php?action=getStatus", "getStatus");
	getRequest("inspire.php?action=getServiceTiers", "getServiceTiers");
	getRequest("inspire.php?action=getSitecuesContacts", "getSitecuesContacts");
	getRequest("inspire.php?action=getUrlStatus", "getUrlStatus");
}

function enableSearchEvents() {
	var o = null;
	if (o = document.getElementById("search")) {
		bindEvent(o, "keyup", function(evt) {
			switch (evt.keyCode) {
				case 13: //enter
          loading(true, frames.dom.accounts.getElementById("accountlist"));
          parent.getRequest("inspire.php?&action=deepSearch&q=" + document.getElementById("search").value, "deepSearch");
					break;
				case 27: //escape
					clearSearch();
					document.body.focus();
					break;
				default:
					if (o && o.value.length > 0 && !document.getElementById("closeSearch")) {
						// Add a clear button
						var e = document.createElement("div");
						e.setAttribute("id", "closeSearch");
						var pos = getAbsPos(o);
						e.style.left = (pos.right - 20) + "px";
						e.style.top = (pos.top + 5) + "px";
						o.parentNode.appendChild(e);
						bindEvent(e, "click", function() {
							clearSearch();
						});
					} else {
						if (o && o.value.length < 1) {
							clearSearch();
						}
					}
					if ((evt.keyCode >= 32 && evt.keyCode <= 126) || evt.keyCode == 8 /* backspace */) {
						if (searchTimer) {
							clearTimeout(searchTimer);
							searchTimer = null;
						}
						searchTimer = setTimeout(function() {
							searchAccounts(o.value);
						}, 10);
					}
				break;
			}
		});
	}	
}

function getRequest(url, id) {
    try {
        var http = window.XMLHttpRequest ? new XMLHttpRequest() : new ActiveXObject("Microsoft.XMLHTTP");
    } catch(e) {
        //xmlhttp not supported
    }

    if (http) {
        http.onreadystatechange = function() { httpResult(http, id); };
        http.open("GET", url, true);
        http.send(null);
    }
}

function postRequest(url, parms, id) {
    try {
        var http = window.XMLHttpRequest ? new XMLHttpRequest() : new ActiveXObject("Microsoft.XMLHTTP");
    } catch(e) {
        //xmlhttp not supported
    }

    if (http) {
        http.onreadystatechange = function() { httpResult(http, id); };
        http.open("POST", url, true);
		if (typeof parms == "string") {
			http.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
		}
        http.send(parms);
    }
}

function httpResult(http, id) {
    if (http.readyState == 4) {
        if (http.status == 200) {
			resultObj = JSON.parse(http.responseText);
			switch(id) {
				case "getAccounts":
					frames.win.accounts.loadAccounts(null, resultObj);
					break;
				case "addAccount":
					if (resultObj.success == 1) {
						closeDlg();
						frames.win.accounts.loadAccounts(null, null);
						frames.win.account.loadAccount(resultObj.id);
					} else {
						alert("oops: " + resultObj.success);
					}
					break;
				case "addProject":
					if (resultObj.success == 1) {
						closeDlg();
						frames.win.account.refreshAccount();
						frames.win.account.refreshSites();
					} else {
						alert("oops: " + resultObj.success);
					}
					break;
				case "updateSitesTable":
					frames.win.account.updateSiteTable(resultObj);
					//frames.win.account.refreshAccount();
					break;
				case "getAccount":
					frames.win.accounts.loadAccount(resultObj.id);
					break;
				case "getSitecuesContacts":
				case "getServiceTiers":
				case "getStages":
				case "getStatus":
				case "newAccountSrc":
				case "newProjectSrc":
				case "newAttachmentSrc":
				case "confirmSrc":
				case "getUrlStatus":
					setcache(id, resultObj);
					break;
				case "updateProject":
					frames.win.account.updateProject(resultObj);
					frames.win.account.refreshAccount();
					break;
				case "updateAccount":
					frames.win.account.updateAccount(resultObj);
					break;
				case "refreshAccount":
					frames.win.account.account = resultObj;
          if (frames.dom.account.getElementById("pid").value !== "") {
            frames.win.account.postMessage({"msg":"refreshProject"}, "*");
          }
					break;
				case "addAttachment":
				case "deleteAttachment":
					if (resultObj.success) {
						frames.win.account.refreshAccount();
					}
					break;
				case "getProject":
					frames.win.account.loadProject(resultObj);
					break;
        case "deepSearch":
          loading(false, frames.dom.accounts.getElementById("accountlist"));
          frames.win.accounts.clearAccounts();
          var r;
          for (r in resultObj) {
            frames.win.accounts.insertAccount(resultObj[r], r);
          }          
          break;
				default:
					break;
			}
		}
	}
}

function loading(enabled, e) {
	if (enabled) {
		var w = e.offsetWidth;
		var h = e.offsetHeight;
		var r = e.getBoundingClientRect();
		var b = document.createElement("div");
		b.className = "loading_background";
		b.style.width = w;
		b.style.height = h;
		b.style.zIndex = 1000;
		b.style.position = 'absolute';
		b.style.top = r.top;
		b.style.left = r.left;
		
		var l = document.createElement("div");
		l.className = "loading";
		l.style.width = "64px";
		l.style.height = "64px";
		
		var p = document.createElement("div");
		p.className = "loading_progress";
		p.id = "progress";
		p.innerHTML = "Loading...";

		l.appendChild(p);
		b.appendChild(l);
		document.body.appendChild(b);		
	} else {
		var t
		if (t = document.querySelector(".loading_background")) {
			document.body.removeChild(t);
		}
	}
}

function updateProgressText(str) {
	document.getElementById("progress").innerHTML = str;
}

function getAbsPos(e) {
	var r = e.getBoundingClientRect();
	return { "top" : Math.floor(r.top), "left" : Math.floor(r.left), "right" : Math.floor(r.right), "bottom" : Math.floor(r.bottom)};
}

function searchAccounts(search) {
	if (searchTimer) {
		clearTimeout(searchTimer);
		searchTimer = null;
	}
	var accounts = frames.dom.accounts.getElementById("accountlist").getElementsByClassName("account");
	var re = new RegExp(search, "i");
	for (var i = 0; i < accounts.length; i++) {
		var name = accounts[i].innerHTML;
		accounts[i].style.display = (String(name).match(re)) ? "block" : "none";
	}
}

function clearSearch() {
	var accounts = frames.dom.accounts.getElementById("accountlist").getElementsByClassName("account");
	for (var i = 0; i < accounts.length; i++) {
		accounts[i].style.display = "block";
	}
	document.getElementById("search").value = "";
	document.getElementById("search").placeholder = strings.IDS_SEARCH_PROJECTS;
	var f;
	if (f = document.getElementById("closeSearch")) {
		f.parentNode.removeChild(f);
		f.focus();
	}
  frames.win.accounts.loadAccounts();
}

function escape(str) {
    return str.replace(/[-[\]{}()*+!<=:?.\/\\^$|#\s,]/g, "\\$&");
}

function dlg(title, content, e = document.activeElement) {
	lastFocus = e;
	var background = document.createElement("div");
	background.setAttribute("id", "dlgBackground");
	background.setAttribute("tabindex", "-1");
	document.body.appendChild(background);
	
	var dialog = document.createElement("div");
	dialog.setAttribute("id", "dlgDialog");
	dialog.setAttribute("role", "dialog");
	dialog.innerHTML = "<div id='dlgTitle'><h3>" + title + "</h3></div><div id='dlgContent'>" + content + "</div>";
	bindEvent(dialog, "keypress", function(e) {
		e = e || event;
		switch (e.keyCode) {
			case 9: // Tab 
				var ctrls = dialog.querySelectorAll(".tabable");
				if (e.shiftKey) {
					if (e.target.isEqualNode(ctrls[0])) {
						ctrls[ctrls.length - 1].focus();
						e.preventDefault();
					}
				} else {
					if (e.target.isEqualNode(ctrls[ctrls.length -1])) {
						ctrls[0].focus();
						e.preventDefault();
					}
				}
				enableAddBtn(dialog);
				break;
			case 27: // Escape
				closeDlg();
				break;
			default:
				break;
		}
	});
	background.appendChild(dialog);
	switch (title) {
		case strings.IDS_NEW_PROJECT:
			var addBtn = dialog.querySelector("#btnAdd");
			addBtn.innerHTML = strings.IDS_ADD;
			bindEvent(addBtn, "click", function() {
				frames.win.account.addProject(dialog)
			});
			populateStatus(document.getElementById("status"));
			populateSitecuesContacts(document.getElementById("sales_id"));
			populateStages(document.getElementById("stage"));
			document.getElementById("created").value = (new Date()).toLocaleDateString("en-US");
			break;
		case strings.IDS_NEW_ACCOUNT:
			populateServiceTiers(document.getElementById("n_tier"));
			populateSitecuesContacts(document.getElementById("n_sales_id"));
			break;
		case strings.IDS_NEW_ATTACHMENT:
			break;
		case strings.IDS_CONFIRM:
			var y;
			if (y = dialog.querySelector("button[id='btnYes']")) {
				y.onclick = function() {
					var action = dialog.querySelector("input[id='action']").value;
					var parms = dialog.querySelector("input[id='parms']").value;
					postRequest("inspire.php?action=" + action, parms, action);
					closeDlg();
					popIn(parent.strings.IDS_AUTO_SAVED);
				};
			}
			var n;
			if (y = dialog.querySelector("button[id='btnNo']")) {
				y.onclick = function() {
					var old;
					if (old = dialog.querySelector("input[id='oldval']")) {
						var oldval = decodeURIComponent(old.value);
						var field = dialog.querySelector("input[id='field']").value;
						switch (field) {
							case "history":
							case "notes":
								frames.win.account.tinymce.get(field).setContent(oldval);
								break;
							default:
								e.value = oldval;
								break;
							
						}					
					}
					closeDlg();
				};
			}
			break;
		default:
			break;
	}
	// Figure out where to put focus
	switch (title) {
		case strings.IDS_CONFIRM:
			var btns = dialog.getElementsByTagName("button");
			if (btns && btns.length > 0) {
				btns[0].focus();
			}
			break;
		default:
			var ctrls = dialog.getElementsByTagName("input");
			if (ctrls && ctrls.length > 0) {
				ctrls[0].focus();
			}
			break;
	}
	document.body.setAttribute("aria-hidden", true);
}

function enableAddBtn(dialog) {
	var b;
	if (b = dialog.querySelector("button[id='btnAdd']")) {
		b.disabled = (dialog.querySelectorAll("input")[0].value == 0);
	}
}

function closeDlg() {
	document.body.setAttribute("aria-hidden", false);
	document.getElementById("dlgBackground").removeChild(document.getElementById("dlgDialog"));
	document.body.removeChild(document.getElementById("dlgBackground"));
	if (lastFocus) {
		lastFocus.focus();
	}
}

function copySalesContact() {
	document.getElementById("a_t_name").value = document.getElementById("a_s_name").value;
	document.getElementById("a_t_email").value = document.getElementById("a_s_email").value;
}

function newAccount() {
	dlg(strings.IDS_NEW_ACCOUNT, getcache("newAccountSrc").html);
}

function addAccount() {
	getRequest("inspire.php?action=addAccount&name=" + document.getElementById("n_name").value + 
	"&description=" + document.getElementById("n_description").value +
	"&tier=" + document.getElementById("n_tier").value +
	"&sales_id=" + document.getElementById("n_sales_id").value, "addAccount");
}

function setcache(id, obj) {
	sessionStorage.setItem(id, (typeof(obj) == "object") ? JSON.stringify(obj) : obj);
}

function getcache(id) {
	return JSON.parse(sessionStorage.getItem(id));
}

function ucwords (str) {
    return (str + '').replace(/^([a-z])|\s+([a-z])/g, function ($1) {
        return $1.toUpperCase();
    });
}

function strtolower (str) {
    return (str+'').toLowerCase();
}

function populateStatus(e) {
	if (e) {
		clearOptions(e);
		var currentStatus = document.getElementById("currentStatus");
		var statusi = getcache("getUrlStatus");
		for (var i = 0; i < statusi.length; i++) {
			var status = statusi[i];
			var opt = document.createElement("option");
			opt.value = status.id;
			opt.text = status.name;
			//opt.selected = (currentStatus && currentStatus.value == status.id);
			e.appendChild(opt);
		}
	}
}

function popIn(str) {
	if (!animationInProgress) {
		animationInProgress = true;
		var e = document.createElement("div");
		e.id = "popup";
		e.innerHTML = str;
		bindEvent(e, "animationend", function(evt) {
			document.body.removeChild(evt.target);
			animationInProgress = false;
		});
		document.body.appendChild(e);
	}
}

function addAttachment(f, d) {
  // Add a temporary row to the table to indicate progress
  var t
  if (t = frames.dom.account.getElementById("attachmentstable")) {
    var r = t.insertRow(-1);
    var c = r.insertCell(-1)
    c.id = "attachment_loading";
    c.colSpan = 6;
    c.align = "center";
    c.innerHTML = "<div class='progress'></div>";
  } else {
    var e = document.createElement("div");
    e.setAttribute("class", "progress");
    frames.dom.account.getElementById("noattachments").appendChild(e);
  }
	var fd = new FormData(f);
	fd.append("description", d);
	fd.append("pid", frames.dom.account.getElementById("pid").value);
	postRequest("inspire.php?action=addAttachment", fd, "addAttachment");
	closeDlg();
}

function populateServiceTiers(e) {
	if (e) {
		clearOptions(e);
		var tiers = getcache("getServiceTiers");
		for (var i = 0; i < tiers.length; i++) {
			var tier = tiers[i];
			var opt = document.createElement("option");
			opt.value = tier.id;
			opt.text = tier.name;
			e.appendChild(opt);
		}
	}
}

function populateStages(e) {
	if (e) {
		clearOptions(e);
		var tiers = getcache("getStages");
		for (var i = 0; i < tiers.length; i++) {
			var tier = tiers[i];
			var opt = document.createElement("option");
			opt.value = tier.id;
			opt.text = tier.name;
			e.appendChild(opt);
		}
	}
}

function populateSitecuesContacts(e) {
	if (e) {
		clearOptions(e)
		var contacts = getcache("getSitecuesContacts");
		for (var i = 0; i < contacts.length; i++) {
			var contact = contacts[i];
			var opt = document.createElement("option");
			opt.value = contact.email;
			opt.text = contact.name;
			// If the email matches the gloabl login email var, select it.
			opt.selected = (opt.value == login);
			e.appendChild(opt);
		}
	}
}

function clearOptions(e) {
	if (e) {
		while (e.options.length) {
			e.remove(0);
		}
	}
}