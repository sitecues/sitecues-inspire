function createIssueTable(resultObj) {
	var tableID = 'issue_table';
	// If the table already exists, remove it so we don't get duplicates.
	if (tmp = document.getElementById(tableID)) {
		tmp.parentNode.removeChild(tmp);
	}
	// Create the table
	var table = document.createElement('table');
	table.setAttribute('id', tableID);
	table.setAttribute('cellpadding', '8');
	table.setAttribute('cellspacing', '0');
	// Create thead
	var thead = document.createElement('thead');
	table.appendChild(thead);
	// Create header row
	var tr = document.createElement('tr');
	thead.appendChild(tr);
	// Create headers
	var headers = ['Priority', 'Status', 'Issue', 'Created'];
	for (var i = 0; i < headers.length; i++) {
		var th = document.createElement('th');
		th.innerHTML = headers[i];
		tr.appendChild(th);
	}
	// Create tbody
	var tbody = document.createElement('tbody');
	table.appendChild(tbody);
	// Create issue rows
	for (var i = 0; i < resultObj.issues.length; i++) {
		var issue = resultObj.issues[i];
		var pIconUrl = issue.fields.priority.iconUrl;
		var sIconUrl = issue.fields.status.iconUrl
		var pName = issue.fields.priority.name;
		var sName = issue.fields.status.name;
		var items = [
			createImageItem(pIconUrl, 'smallIcon', pName),
			createImageItem(sIconUrl, 'smallIcon', sName),
			createIssueItem(issue.key + ' ' + issue.fields.summary),
			createDateItem(issue.fields.created)
		];
		var row = document.createElement('tr');
		row.setAttribute('class', 'data');
		tbody.appendChild(row);
		for (item in items) {
			var item = items[item];
			var cell = document.createElement('td');
			switch (item.tagName) {
				case 'IMG':
					cell.setAttribute('align', 'center');
					break;
				default:
					break;
			}
			cell.appendChild(item);
			row.appendChild(cell);
		}
	}
	document.getElementById('content').appendChild(table);
}
