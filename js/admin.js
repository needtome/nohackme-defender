function pdxnohackme_addRow() {
  var table = dqs('.table__pdxglobal');
  var rowCount = table.rows.length;
  var row = table.insertRow(rowCount);
  var cell1 = row.insertCell(0);
  var cell2 = row.insertCell(1);
  cell1.innerHTML = rowCount;
  cell2.innerHTML = '<input type=\"text\" name=\"hacks[' + (rowCount - 1) + ']\" value=\"\" style=\"width:100%;\"/>';
}
