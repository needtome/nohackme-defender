function nohackme_defender_addRow() {
  var table = dqs('.table__pdxglobal');
  var rowCount = table.rows.length;
  var row = table.insertRow(rowCount);
  var cell1 = row.insertCell(0);
  var cell2 = row.insertCell(1);
  cell1.innerHTML = rowCount;
  cell2.innerHTML = '<input type=\"text\" name=\"hacks[' + (rowCount - 1) + ']\" value=\"\" style=\"width:100%;\"/>';
}

function nohackmeDefenderAppendIpToWhitelist(ip) {
  var textarea = document.getElementById('pdxmeta_options_exclude_ip');
  var preparedIp = (ip || '').trim();

  if (!textarea || !preparedIp) {
    return;
  }

  var currentValue = textarea.value.replace(/\r\n/g, '\n');
  var lines = currentValue
    .split('\n')
    .map(function(line) { return line.trim(); })
    .filter(function(line) { return line !== ''; });

  if (lines.indexOf(preparedIp) !== -1) {
    textarea.focus();
    if (typeof showMsg === 'function') {
      showMsg('IP уже есть в белом списке');
    }
    return;
  }

  textarea.value = currentValue.trim() === '' ? preparedIp : currentValue.replace(/\s*$/, '') + '\n' + preparedIp;
  textarea.focus();
  textarea.setSelectionRange(textarea.value.length, textarea.value.length);

  if (typeof showMsg === 'function') {
    showMsg('IP добавлен в белый список');
  }
}
