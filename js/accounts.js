"use strict";
var progressEvent = null;

parent.bindEvent(window, "message", function(e) {
	switch (e.data) {	
		case "loadAccounts":
			loadAccounts(null, null);
			break;
		default:
			break;
	}
});

function clearAccounts() {
  var accountlist = document.getElementById("accountlist");
  while (accountlist.firstChild) {
    accountlist.removeChild(accountlist.firstChild);
  }
}

function loadAccounts(search, resultObj) {
	if (!resultObj) {
		parent.loading(true, parent.frames.el.accounts);
		parent.getRequest("inspire.php?action=getAccounts" + ((search) ? "&search=" + search : ""), "getAccounts");
	} else {
		parent.loading(false, parent.frames.el.accounts);
		// Clear any existing accounts
    clearAccounts();
		if (resultObj.length) {
			for (var i = 0; i < resultObj.length; i++) {
				var p = resultObj[i];
				insertAccount(p.name, p.id);
				if (decodeURIComponent(parent.window.location.href).search(p.name) != -1) {
					getAccount(p.id);
				}
			}
		} else {
			insertAccount(strings.IDS_NO_MATCH, "-1");
		}
		parent.document.getElementById("search").focus();
	}
}

function insertAccount(name, id) {
	var a = document.createElement("a");
	var cn = "account";
	a.className = cn;
	a.setAttribute("href", "#" + name);
	parent.bindEvent(a, "click", function() {
		getAccount(id);
	});
	a.innerHTML = name;
	document.getElementById("accountlist").appendChild(a);
}

function getAccount(id) {
	parent.loading(true, parent.frames.el.account);
	progressEvent = new EventSource("https://localhost/inspire/account.php?id=" + id  + "&action=getAccount&progress=1");
	progressEvent.onmessage = function(e) {
		var msg = JSON.parse(e.data);
		switch (parseInt(msg.msg)) {
			case 0:
				progressEvent.close();
				var win = parent.frames.win.account;
				win.document.open();
				win.document.write(atob(msg.value));
				win.document.close();			
				parent.loading(false, parent.frames.el.account);
				break;
			default:
				var str = eval("parent.strings.IDS_PROGRESS_" + msg.msg);
				parent.updateProgressText(str.replace("%s", atob(msg.value)));
				break;
		}
	};
}

function refreshAccounts() {
	loadAccounts(null);
}