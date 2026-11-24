<?php
/*
 * Cost Per Receipe - Semi-Finished (SF) items
 *
 * Per SF item: its recipe components, qty per batch, mean cost per unit and cost.
 *   qtyType = 0 (Standard)   : qty per batch read from PackDt (recipe valid at To Date)
 *   qtyType = 1 (Production) : actual qty from ProductionSheetRM in the date range,
 *                              averaged per batch (total qty / number of mixings)
 * Cost = MeanCostCurByWH(item, To Date 23:59:59, base1)
 * Dry qty (return per batch) = qty - deduction  (same rule as the production sheet)
 * Cost/KG of the SF item     = total cost / dry qty
 *
 * Exp_CostPerReceipe.php sets $isExcel = 1 and includes this file.
 */

session_start();
if ($_SESSION['dbName'] == "") {
	header("location:../index.php");
	exit;
}

include("config.inc.php");
ini_set('max_execution_time', $config['ReportTimeExecution']);

$connection = mysql_connect($config['dbServer'], $config['dbUser'], $config['dbPass']) or die("Could not connect to DB");
mysql_select_db($_SESSION['dbName'], $connection) or die("Could not find DB");

if (!isset($isExcel)) $isExcel = 0;

// Parameters (GET first, fall back to register_globals)
$fromdate = isset($_GET['fromdate']) ? $_GET['fromdate'] : $fromdate;
$todate   = isset($_GET['todate'])   ? $_GET['todate']   : $todate;
$ItemCode = isset($_GET['ItemCode']) ? $_GET['ItemCode'] : $ItemCode;
$qtyType  = isset($_GET['qtyType'])  ? $_GET['qtyType']  : $qtyType;
$qtyType  = ($qtyType == 1) ? 1 : 0;
$ItemCode = trim($ItemCode);

$querylog = "SELECT print as printaccess from " . $_SESSION['dbLabel'] . ".login where user_account='" . $_SESSION['useraccount'] . "' and Upper(Login_Schema) = Upper('" . $_SESSION['dbName'] . "')";
$resultlog = mysql_query($querylog, $connection) or die(mysql_error());
while ($rows = mysql_fetch_array($resultlog)) {
	extract($rows);
}

$querysetup = "select DecimalBase, Base1, Base2, report_color1, report_color2, Company_Name As CompanyName from setup";
$resultsetup = mysql_query($querysetup, $connection) or die(mysql_error());
while ($rows = mysql_fetch_array($resultsetup)) {
	extract($rows);
}
if ($DecimalBase === '' || $DecimalBase === null) $DecimalBase = 2;

$sqlFrom   = toSqlDate($fromdate);
$sqlTo     = toSqlDate($todate);
if ($sqlTo == '') $sqlTo = date('Y-m-d');
$sqlItem   = mysql_real_escape_string($ItemCode, $connection);
$sqlBase1  = mysql_real_escape_string($Base1, $connection);
$costDate  = $sqlTo . " 23:59:59";

$dateFilter = " And Date_format(fg.productionSheet_date,'%Y-%m-%d') between " .
	($sqlFrom == '' ? "Date_format(fg.productionSheet_date,'%Y-%m-%d')" : "'$sqlFrom'") . " and '$sqlTo' ";

$qtyTypeLabel = ($qtyType == 1) ? 'Production' : 'Standard';

// ---------------------------------------------------------------- SF item list
if ($qtyType == 0 && $sqlItem != '') {
	// Standard with a selected item: show its recipe even if not produced in the period
	$queryItems = " Select Item_Code, Item_Description From Items Where Item_Code = '$sqlItem' ";
} else {
	$queryItems = " Select Distinct fg.Item_Code, Items.Item_Description " .
		" From ProductionSheetFG as fg " .
		" Inner Join Items on Items.Item_Code = fg.Item_Code " .
		" Where 1=1 " . $dateFilter;
	if ($sqlItem != '') $queryItems .= " And fg.Item_Code = '$sqlItem' ";
	$queryItems .= " Order By fg.Item_Code ";
}
$resultItems = mysql_query($queryItems, $connection) or die(mysql_error());

// ---------------------------------------------------------------- output start
if ($isExcel == 1) {
	header("Content-Type: application/vnd.ms-excel; charset=windows-1256");
	header("Content-Disposition: attachment; filename=CostPerReceipe_SF.xls");
	header("Pragma: no-cache");
	header("Expires: 0");
}

echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.0 Transitional//EN\">\n";
echo "<html><head><meta http-equiv='Content-Type' content='text/html; charset=windows-1256' />";
echo "<title>Cost Per Receipe - Semi-Finished</title>";
if ($isExcel == 0) {
	echo "<style type='text/css'>
	table.params td { font-size: 12px; font-family:Times-New-Roman; }
	table.master th { font-size: 13px; font-weight: bold; font-family:Times-New-Roman; border-bottom:1px solid black; }
	table.master td { font-size: 12px; font-family:Times-New-Roman; }
	tr.total td { font-weight: bold; border-top:1px solid black; }
	h4 { border-top-style: solid; border-top-color: black; border-top-width: 2px; }
	h5 { border-top-style: solid; border-top-color: black; border-top-width: 1px; }
	</style>";
}
echo "</head>";

if ($isExcel == 0 && ($printaccess == 0 or $printaccess == ''))
	echo '<body oncontextmenu="return false;">';
else
	echo '<body>';

$now = date('d/m/Y h:i A');

if ($isExcel == 0) {
	echo "<table width='1000' border='0' align='left' style='table-layout:fixed'>
	<tr>
	  <td width='35%'><b>" . htmlspecialchars($CompanyName) . "</b></td>
	  <td width='45%' align='center'><font size=4><b>Cost Per Receipe - Semi-Finished</b></font></td>
	  <td width='20%'><font size=1><b>Printed:</b> $now</font></td>
	</tr>
	<tr height='5'><td colspan=3><h5></td></tr>
	</table><br clear='all'>";
	echo "<table class='params' width='1000' border='0' align='left' style='table-layout:fixed'>
	<tr>
	  <td width='12%'><b>Date From/To:</b></td><td width='20%'>" . htmlspecialchars($fromdate) . " - " . htmlspecialchars($todate) . "</td>
	  <td width='8%'><b>Currency:</b></td><td width='10%'>" . htmlspecialchars($Base1) . "</td>
	  <td width='8%'><b>Qty:</b></td><td width='12%'>$qtyTypeLabel</td>
	  <td width='30%'><b>Cost:</b> Mean cost at " . htmlspecialchars($todate) . "</td>
	</tr>
	<tr height='4'><td colspan=7><h4></td></tr>
	</table><br clear='all'>";
} else {
	echo "<table border='0'>
	<tr><td colspan='8'><b>" . htmlspecialchars($CompanyName) . "</b></td></tr>
	<tr><td colspan='8'><b>Cost Per Receipe - Semi-Finished</b></td></tr>
	<tr><td colspan='8'>Date From/To: " . htmlspecialchars($fromdate) . " - " . htmlspecialchars($todate) .
		"  |  Currency: " . htmlspecialchars($Base1) . "  |  Qty: $qtyTypeLabel  |  Mean cost at " . htmlspecialchars($todate) . "</td></tr>
	<tr><td colspan='8'>&nbsp;</td></tr>
	</table>";
}

$border = ($isExcel == 1) ? " border='1'" : " border='0'";
echo "<table width='1000' $border align='left' cellpadding='2' cellspacing='0' class='master' style='table-layout:fixed'>
	<col width='70'><col width='170'><col width='70'><col width='230'>
	<col width='100'><col width='100'><col width='110'><col width='110'>";
echo "<tr>
	<th align='left'>SF Code</th>
	<th align='left'>SF Name</th>
	<th align='left'>Item</th>
	<th align='left'>Item Name</th>
	<th align='right'>Return / Batch<br>Dry Qty</th>
	<th align='right'>Qty in Batch</th>
	<th align='right'>Cost / KG</th>
	<th align='right'>Cost</th>
</tr>";

$nbItems = 0;
while ($rowItem = mysql_fetch_row($resultItems)) {
	$sfCode    = $rowItem[0];
	$sfName    = $rowItem[1];
	$sqlSfCode = mysql_real_escape_string($sfCode, $connection);
	$batches   = 1;

	if ($qtyType == 0) {
		// Standard: recipe from PackDt valid at To Date
		$queryLines = " Select p.Item_CodePackDt, Items.Item_Description, " .
			" ifNull(p.PackDt_Quantity,0) As Qty, " .
			" if(ifNull(p.deduction_isper,0) = 1, ifNull(p.PackDt_Quantity,0) * ifNull(p.deduction_amount,0) / 100, ifNull(p.deduction_amount,0)) As Deduction, " .
			" ifNull(MeanCostCurByWH(p.Item_CodePackDt, '$costDate', 1, '$sqlBase1', '$sqlBase1', p.warehouse_code),0) As UnitCost " .
			" From PackDt as p " .
			" Left Join Items on Items.Item_Code = p.Item_CodePackDt " .
			" Where p.Item_Code = '$sqlSfCode' " .
			" And (p.PackDt_FromDate is null or Date_format(p.PackDt_FromDate,'%Y-%m-%d') <= '$sqlTo') " .
			" And (p.PackDt_ToDate is null or Date_format(p.PackDt_ToDate,'%Y-%m-%d') >= '$sqlTo') " .
			" Order By p.Item_CodePackDt ";
	} else {
		// Production: actual qty from ProductionSheetRM, averaged per batch
		$queryBatch = " Select ifNull(Sum(ifNull(Scmixer_NbOfMixing,0)),0) From Scmixer " .
			" Where ItemCode = '$sqlSfCode' And Related_ProductionSheetId In ( " .
			"   Select Distinct fg.ProductionSheet_id From ProductionSheetFG as fg " .
			"   Where fg.Item_Code = '$sqlSfCode' " . $dateFilter . " ) ";
		$resBatch = mysql_query($queryBatch, $connection) or die(mysql_error());
		$rowBatch = mysql_fetch_row($resBatch);
		$batches  = ($rowBatch[0] > 0) ? $rowBatch[0] : 1;

		$queryLines = " Select Q.Item_Code, Q.Item_Description, Q.QtyBefore / $batches As Qty, " .
			" (Q.QtyBefore - Q.QtyAfter) / $batches As Deduction, " .
			" ifNull(MeanCostCurByWH(Q.Item_Code, '$costDate', 1, '$sqlBase1', '$sqlBase1', Q.Warehouse_Code),0) As UnitCost " .
			" From ( " .
			"   Select rm.Item_Code, Items.Item_Description, " .
			"   Sum(if(ifNull(rm.ProductionSheetRM_quantityBefore,0) = 0, ifNull(rm.ProductionSheetRM_quantity,0), rm.ProductionSheetRM_quantityBefore)) As QtyBefore, " .
			"   Sum(ifNull(rm.ProductionSheetRM_quantity,0)) As QtyAfter, " .
			"   Max(rm.Warehouse_Code) As Warehouse_Code " .
			"   From ProductionSheetRM as rm " .
			"   Inner Join ProductionSheetFG as fg on fg.ProductionSheetFG_ID = rm.ProductionSheetFG_ID " .
			"   Left Join Items on Items.Item_Code = rm.Item_Code " .
			"   Where fg.Item_Code = '$sqlSfCode' " . $dateFilter .
			"   Group By rm.Item_Code, Items.Item_Description " .
			" ) as Q Order By Q.Item_Code ";
	}

	$resLines = mysql_query($queryLines, $connection) or die(mysql_error());
	$lines = array();
	$totQty = 0;
	$totDry = 0;
	$totCost = 0;
	while ($rowLine = mysql_fetch_row($resLines)) {
		$qty      = (float)$rowLine[2];
		$dry      = $qty - (float)$rowLine[3];
		$unitCost = (float)$rowLine[4];
		$cost     = $qty * $unitCost;
		$lines[]  = array($rowLine[0], $rowLine[1], $qty, $unitCost, $cost);
		$totQty  += $qty;
		$totDry  += $dry;
		$totCost += $cost;
	}
	if (count($lines) == 0) continue;
	$nbItems++;

	$costPerKg = ($totDry != 0) ? $totCost / $totDry : 0;
	$rowspan   = count($lines);

	$i = 0;
	foreach ($lines as $l) {
		$bg = ($i % 2 == 0) ? $report_color1 : $report_color2;
		echo "<tr style='background-color:$bg'>";
		if ($i == 0) {
			echo "<td rowspan='$rowspan' valign='top'><b>" . htmlspecialchars($sfCode) . "</b></td>";
			echo "<td rowspan='$rowspan' valign='top'><b>" . htmlspecialchars($sfName) . "</b>" .
				(($qtyType == 1 && $isExcel == 0) ? "<br><font size=1><i>Batches: " . fmt($batches, 0, $isExcel) . "</i></font>" : "") . "</td>";
		}
		echo "<td>" . htmlspecialchars($l[0]) . "</td>";
		echo "<td>" . htmlspecialchars($l[1]) . "</td>";
		if ($i == 0) echo "<td rowspan='$rowspan' align='right' valign='top'>" . fmt($totDry, 4, $isExcel) . "</td>";
		echo "<td align='right'>" . fmt($l[2], 4, $isExcel) . "</td>";
		echo "<td align='right'>" . fmt($l[3], 6, $isExcel) . "</td>";
		echo "<td align='right'>" . fmt($l[4], $DecimalBase + 2, $isExcel) . "</td>";
		echo "</tr>";
		$i++;
	}

	echo "<tr class='total'>";
	echo "<td></td><td></td>";
	echo "<td><b>" . htmlspecialchars($sfCode) . "</b></td>";
	echo "<td><b>Total " . htmlspecialchars($sfName) . "</b></td>";
	echo "<td align='right'><b>" . fmt($totDry, 4, $isExcel) . "</b></td>";
	echo "<td align='right'><b>" . fmt($totQty, 4, $isExcel) . "</b></td>";
	echo "<td align='right'><b>" . fmt($costPerKg, 6, $isExcel) . "</b></td>";
	echo "<td align='right'><b>" . fmt($totCost, $DecimalBase + 2, $isExcel) . "</b></td>";
	echo "</tr>";
	echo "<tr><td colspan='8'>&nbsp;</td></tr>";
}

if ($nbItems == 0)
	echo "<tr><td colspan='8' align='center'><i>No semi-finished items found for the selected criteria.</i></td></tr>";

echo "</table>";

if ($isExcel == 0 && $printaccess == 1)
	echo "<script language='javascript'>if (typeof(window.print) != 'undefined') window.print();</script>";

echo "</body></html>";


// dd/mm/yyyy -> yyyy-mm-dd ('' when invalid)
function toSqlDate($date)
{
	$p = explode('/', trim($date));
	if (count($p) != 3 || !checkdate((int)$p[1], (int)$p[0], (int)$p[2])) return '';
	return sprintf('%04d-%02d-%02d', $p[2], $p[1], $p[0]);
}

// Screen: formatted with separators. Excel: plain rounded number so cells stay numeric.
function fmt($value, $dec, $isExcel)
{
	if ($isExcel == 1) return round($value, $dec);
	return number_format($value, $dec);
}
?>
