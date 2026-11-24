<?php
/*
 * Cost Per Receipe - Semi-Finished (SF) and Finished Goods (FG)
 *
 * SF section (Production Sheet):
 *   qtyType = 0 (Standard)   : qty per batch from PackDt (recipe valid at To Date)
 *   qtyType = 1 (Production) : actual qty from ProductionSheetRM in the date range,
 *                              averaged per batch (total qty / number of mixings)
 *   Dry qty (return per batch) = qty - deduction  (same rule as the production sheet)
 *   Cost/KG of the SF item     = total cost / dry qty
 *
 * FG section (Assemble):
 *   qtyType = 0 (Standard)   : PackDt_Quantity / Item_QtyToBeProduced  (qty per 1 FG unit)
 *   qtyType = 1 (Production) : sum(ProductionDt_Quantity) / sum(Production_Quantity)
 *   Unit cost of a component:
 *     - SF item (made on a production sheet)   -> SF Cost/KG computed in the SF section logic
 *     - Production mode, FG-Overhead/non-stock -> cost recorded on the assemble (ProductionDt_CostBase2)
 *     - otherwise                              -> MeanCostCurByWH at To Date
 *
 * All costs in Base2: MeanCostCurByWH(item, To Date 23:59:59, 2, Base2, Base2, warehouse)
 *
 * Item filter: SF section -> that SF item.
 *              FG section -> that FG item, or every FG whose recipe uses that item.
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
$sqlBase2  = mysql_real_escape_string($Base2, $connection);
$costDate  = $sqlTo . " 23:59:59";

// Date filters (SF: production sheet date, FG: assemble date)
$dateFilter = " And Date_format(fg.productionSheet_date,'%Y-%m-%d') between " .
	($sqlFrom == '' ? "Date_format(fg.productionSheet_date,'%Y-%m-%d')" : "'$sqlFrom'") . " and '$sqlTo' ";
$dateFilterPrd = " And Date_format(prd.Production_Date,'%Y-%m-%d') between " .
	($sqlFrom == '' ? "Date_format(prd.Production_Date,'%Y-%m-%d')" : "'$sqlFrom'") . " and '$sqlTo' ";
$packdtValid = " And (p.PackDt_FromDate is null or Date_format(p.PackDt_FromDate,'%Y-%m-%d') <= '$sqlTo') " .
	" And (p.PackDt_ToDate is null or Date_format(p.PackDt_ToDate,'%Y-%m-%d') >= '$sqlTo') ";

$qtyTypeLabel = ($qtyType == 1) ? 'Production' : 'Standard';
$ReportTitle = 'Cost Per Receipe - Semi-Finished & Finished Goods';

$sfCache    = array();  // SF code => computeSF() result
$isSFCache  = array();  // item code => bool

// ---------------------------------------------------------------- output start
if ($isExcel == 1) {
	header("Content-Type: application/vnd.ms-excel; charset=windows-1256");
	header("Content-Disposition: attachment; filename=CostPerReceipe.xls");
	header("Pragma: no-cache");
	header("Expires: 0");
}

echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.0 Transitional//EN\">\n";
echo "<html><head><meta http-equiv='Content-Type' content='text/html; charset=windows-1256' />";
echo "<title>$ReportTitle</title>";
if ($isExcel == 0) {
	echo "<style type='text/css'>
	table.params td { font-size: 12px; font-family:Times-New-Roman; }
	table.master th { font-size: 13px; font-weight: bold; font-family:Times-New-Roman; border-bottom:1px solid black; }
	table.master td { font-size: 12px; font-family:Times-New-Roman; }
	tr.total td { font-weight: bold; border-top:1px solid black; }
	tr.section td { font-size: 15px; font-weight: bold; padding-top:10px; }
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
	  <td width='45%' align='center'><font size=4><b>$ReportTitle</b></font></td>
	  <td width='20%'><font size=1><b>Printed:</b> $now</font></td>
	</tr>
	<tr height='5'><td colspan=3><h5></td></tr>
	</table><br clear='all'>";
	echo "<table class='params' width='1000' border='0' align='left' style='table-layout:fixed'>
	<tr>
	  <td width='12%'><b>Date From/To:</b></td><td width='20%'>" . htmlspecialchars($fromdate) . " - " . htmlspecialchars($todate) . "</td>
	  <td width='8%'><b>Currency:</b></td><td width='10%'>" . htmlspecialchars($Base2) . "</td>
	  <td width='8%'><b>Qty:</b></td><td width='12%'>$qtyTypeLabel</td>
	  <td width='30%'><b>Cost:</b> Mean cost at " . htmlspecialchars($todate) . "</td>
	</tr>
	<tr height='4'><td colspan=7><h4></td></tr>
	</table><br clear='all'>";
} else {
	echo "<table border='0'>
	<tr><td colspan='8'><b>" . htmlspecialchars($CompanyName) . "</b></td></tr>
	<tr><td colspan='8'><b>$ReportTitle</b></td></tr>
	<tr><td colspan='8'>Date From/To: " . htmlspecialchars($fromdate) . " - " . htmlspecialchars($todate) .
		"  |  Currency: " . htmlspecialchars($Base2) . "  |  Qty: $qtyTypeLabel  |  Mean cost at " . htmlspecialchars($todate) . "</td></tr>
	<tr><td colspan='8'>&nbsp;</td></tr>
	</table>";
}

$border = ($isExcel == 1) ? " border='1'" : " border='0'";
echo "<table width='1000' $border align='left' cellpadding='2' cellspacing='0' class='master' style='table-layout:fixed'>
	<col width='70'><col width='170'><col width='70'><col width='230'>
	<col width='100'><col width='100'><col width='110'><col width='110'>";

// ================================================================ SF SECTION
{
	echo "<tr class='section'><td colspan='8'>Semi-Finished</td></tr>";
	echo "<tr>
		<th align='left'>SF Code</th>
		<th align='left'>SF Name</th>
		<th align='left'>Item</th>
		<th align='left'>Item Name</th>
		<th align='right'>Return / Batch</th>
		<th align='right'>Qty in Batch</th>
		<th align='right'>Cost / KG</th>
		<th align='right'>Cost</th>
	</tr>";

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

	$nbItems = 0;
	while ($rowItem = mysql_fetch_row($resultItems)) {
		$sfCode = $rowItem[0];
		$sfName = $rowItem[1];
		$sf = getSF($sfCode);
		if (count($sf['lines']) == 0) continue;
		$nbItems++;

		$rowspan = count($sf['lines']);
		$i = 0;
		foreach ($sf['lines'] as $l) {
			$bg = ($i % 2 == 0) ? $report_color1 : $report_color2;
			echo "<tr style='background-color:$bg'>";
			if ($i == 0) {
				echo "<td rowspan='$rowspan' valign='top'><b>" . htmlspecialchars($sfCode) . "</b></td>";
				echo "<td rowspan='$rowspan' valign='top'><b>" . htmlspecialchars($sfName) . "</b>" .
					(($qtyType == 1 && $isExcel == 0) ? "<br><font size=1><i>Batches: " . fmt($sf['batches'], 0, $isExcel) . "</i></font>" : "") . "</td>";
			}
			echo "<td>" . htmlspecialchars($l[0]) . "</td>";
			echo "<td>" . htmlspecialchars($l[1]) . "</td>";
			if ($i == 0) echo "<td rowspan='$rowspan' align='right' valign='top'>" . fmt($sf['totDry'], 4, $isExcel) . "</td>";
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
		echo "<td align='right'><b>" . fmt($sf['totDry'], 4, $isExcel) . "</b></td>";
		echo "<td align='right'><b>" . fmt($sf['totQty'], 4, $isExcel) . "</b></td>";
		echo "<td align='right'><b>" . fmt($sf['costPerKg'], 6, $isExcel) . "</b></td>";
		echo "<td align='right'><b>" . fmt($sf['totCost'], $DecimalBase + 2, $isExcel) . "</b></td>";
		echo "</tr>";
		echo "<tr><td colspan='8'>&nbsp;</td></tr>";
	}

	if ($nbItems == 0)
		echo "<tr><td colspan='8' align='center'><i>No semi-finished items found for the selected criteria.</i></td></tr>";
}

// ================================================================ FG SECTION
{
	echo "<tr><td colspan='8'>&nbsp;</td></tr><tr class='section'><td colspan='8'>Finished Goods</td></tr>";
	echo "<tr>
		<th align='left'>FG Code</th>
		<th align='left'>FG Name</th>
		<th align='left'>Item</th>
		<th align='left'>Item Name</th>
		<th align='right'>" . ($qtyType == 1 && $isExcel == 0 ? "Qty Produced" : "&nbsp;") . "</th>
		<th align='right'>Qty in Ctn</th>
		<th align='right'>Cost / KG</th>
		<th align='right'>Cost</th>
	</tr>";

	// FG items = items assembled (Production table)
	$itemFilter = '';
	if ($sqlItem != '')
		$itemFilter = " And (prd.Item_code = '$sqlItem' Or prd.Item_code In (Select p.Item_Code From PackDt as p Where p.Item_CodePackDt = '$sqlItem' $packdtValid)) ";

	if ($qtyType == 0 && $sqlItem != '') {
		// Standard with a selected item: no date restriction, the item must just be an assembled FG
		$queryFG = " Select Distinct prd.Item_code, Items.Item_Description, ifNull(Items.Item_QtyToBeProduced,1) " .
			" From Production as prd Inner Join Items on Items.Item_Code = prd.Item_code " .
			" Where 1=1 " . $itemFilter . " Order By prd.Item_code ";
	} else {
		$queryFG = " Select Distinct prd.Item_code, Items.Item_Description, ifNull(Items.Item_QtyToBeProduced,1) " .
			" From Production as prd Inner Join Items on Items.Item_Code = prd.Item_code " .
			" Where 1=1 " . $dateFilterPrd . $itemFilter . " Order By prd.Item_code ";
	}
	$resultFG = mysql_query($queryFG, $connection) or die(mysql_error());

	$nbFG = 0;
	while ($rowFG = mysql_fetch_row($resultFG)) {
		$fgCode    = $rowFG[0];
		$fgName    = $rowFG[1];
		$qtyToProd = ($rowFG[2] > 0) ? (float)$rowFG[2] : 1;
		$sqlFgCode = mysql_real_escape_string($fgCode, $connection);
		$prodQty   = 0;

		if ($qtyType == 0) {
			$queryLines = " Select p.Item_CodePackDt, Items.Item_Description, " .
				" ifNull(p.PackDt_Quantity,0) / $qtyToProd As Qty, " .
				" ifNull(MeanCostCurByWH(p.Item_CodePackDt, '$costDate', 2, '$sqlBase2', '$sqlBase2', p.warehouse_code),0) As MeanCost, " .
				" 0 As RecordedCost, Items.Group_Code, ifNull(Items.Item_NonStock,0), ifNull(p.iscalculated,0) " .
				" From PackDt as p " .
				" Left Join Items on Items.Item_Code = p.Item_CodePackDt " .
				" Where p.Item_Code = '$sqlFgCode' " . $packdtValid .
				" Order By p.Item_CodePackDt ";
		} else {
			$queryPrdQty = " Select ifNull(Sum(ifNull(prd.Production_Quantity,0)),0) From Production as prd " .
				" Where prd.Item_code = '$sqlFgCode' " . $dateFilterPrd;
			$resPrdQty = mysql_query($queryPrdQty, $connection) or die(mysql_error());
			$rowPrdQty = mysql_fetch_row($resPrdQty);
			$prodQty   = (float)$rowPrdQty[0];
			$div       = ($prodQty > 0) ? $prodQty : 1;

			$queryLines = " Select Q.Item_code, Q.Item_Description, Q.Qty / $div As Qty, " .
				" ifNull(MeanCostCurByWH(Q.Item_code, '$costDate', 2, '$sqlBase2', '$sqlBase2', Q.Warehouse_Code),0) As MeanCost, " .
				" Q.RecordedCost, Q.Group_Code, Q.NonStock, 0 " .
				" From ( " .
				"   Select dt.Item_code, Items.Item_Description, " .
				"   Sum(ifNull(dt.ProductionDt_Quantity,0)) As Qty, " .
				"   ifNull(Sum(ifNull(dt.ProductionDt_Quantity,0) * ifNull(dt.ProductionDt_CostBase2,0)) / NullIf(Sum(ifNull(dt.ProductionDt_Quantity,0)),0), Max(ifNull(dt.ProductionDt_CostBase2,0))) As RecordedCost, " .
				"   Max(dt.Warehouse_Code) As Warehouse_Code, Items.Group_Code, ifNull(Items.Item_NonStock,0) As NonStock " .
				"   From ProductionDt as dt " .
				"   Inner Join Production as prd on prd.Production_ID = dt.Production_ID " .
				"   Left Join Items on Items.Item_Code = dt.Item_code " .
				"   Where prd.Item_code = '$sqlFgCode' " . $dateFilterPrd .
				"   Group By dt.Item_code, Items.Item_Description, Items.Group_Code, Items.Item_NonStock " .
				" ) as Q Order By Q.Item_code ";
		}

		$resLines = mysql_query($queryLines, $connection) or die(mysql_error());
		$lines   = array();
		$totQty  = 0;
		$totCost = 0;
		while ($rowLine = mysql_fetch_row($resLines)) {
			$code       = $rowLine[0];
			$qty        = (float)$rowLine[2];
			$isOverhead = (strtoupper($rowLine[5]) == 'FG-OVERHEAD' || $rowLine[6] == 1);

			if ($rowLine[7] > 0 || isSFItem($code)) {
				// Semi-finished component: cost per KG from its recipe
				$sf = getSF($code);
				$unitCost = (count($sf['lines']) > 0) ? $sf['costPerKg'] : (float)$rowLine[3];
			} else if ($qtyType == 1 && $isOverhead) {
				$unitCost = (float)$rowLine[4];
			} else {
				$unitCost = (float)$rowLine[3];
			}
			$cost     = $qty * $unitCost;
			$lines[]  = array($code, $rowLine[1], $qty, $unitCost, $cost);
			$totQty  += $qty;
			$totCost += $cost;
		}
		if (count($lines) == 0) continue;
		$nbFG++;

		$rowspan = count($lines);
		$i = 0;
		foreach ($lines as $l) {
			$bg = ($i % 2 == 0) ? $report_color1 : $report_color2;
			echo "<tr style='background-color:$bg'>";
			if ($i == 0) {
				echo "<td rowspan='$rowspan' valign='top'><b>" . htmlspecialchars($fgCode) . "</b></td>";
				echo "<td rowspan='$rowspan' valign='top'><b>" . htmlspecialchars($fgName) . "</b></td>";
			}
			echo "<td>" . htmlspecialchars($l[0]) . "</td>";
			echo "<td>" . htmlspecialchars($l[1]) . "</td>";
			if ($i == 0) echo "<td rowspan='$rowspan' align='right' valign='top'>" . ($qtyType == 1 && $isExcel == 0 ? fmt($prodQty, 2, $isExcel) : "") . "</td>";
			echo "<td align='right'>" . fmt($l[2], 4, $isExcel) . "</td>";
			echo "<td align='right'>" . fmt($l[3], 6, $isExcel) . "</td>";
			echo "<td align='right'>" . fmt($l[4], $DecimalBase + 2, $isExcel) . "</td>";
			echo "</tr>";
			$i++;
		}

		echo "<tr class='total'>";
		echo "<td></td><td></td>";
		echo "<td><b>" . htmlspecialchars($fgCode) . "</b></td>";
		echo "<td><b>Total " . htmlspecialchars($fgName) . "</b></td>";
		echo "<td></td><td></td><td></td>";
		echo "<td align='right'><b>" . fmt($totCost, $DecimalBase + 2, $isExcel) . "</b></td>";
		echo "</tr>";
		echo "<tr><td colspan='8'>&nbsp;</td></tr>";
	}

	if ($nbFG == 0)
		echo "<tr><td colspan='8' align='center'><i>No finished goods found for the selected criteria.</i></td></tr>";
}

echo "</table>";

if ($isExcel == 0 && $printaccess == 1)
	echo "<script language='javascript'>if (typeof(window.print) != 'undefined') window.print();</script>";

echo "</body></html>";


// ---------------------------------------------------------------- functions

// SF recipe with costs (cached). Returns lines [code, name, qty, unitCost, cost], totals, cost/KG, batches.
function getSF($sfCode)
{
	global $sfCache;
	if (!isset($sfCache[$sfCode])) $sfCache[$sfCode] = computeSF($sfCode);
	return $sfCache[$sfCode];
}

function computeSF($sfCode)
{
	global $connection, $qtyType, $costDate, $sqlBase2, $dateFilter, $packdtValid;

	$sqlSfCode = mysql_real_escape_string($sfCode, $connection);
	$batches   = 1;
	$lines     = array();

	if ($qtyType == 1) {
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
			" ifNull(MeanCostCurByWH(Q.Item_Code, '$costDate', 2, '$sqlBase2', '$sqlBase2', Q.Warehouse_Code),0) As UnitCost " .
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
		$resLines = mysql_query($queryLines, $connection) or die(mysql_error());
		while ($r = mysql_fetch_row($resLines)) $lines[] = $r;
	}

	if (count($lines) == 0) {
		// Standard (or SF not produced in the period): recipe from PackDt valid at To Date
		$batches = 1;
		$queryLines = " Select p.Item_CodePackDt, Items.Item_Description, " .
			" ifNull(p.PackDt_Quantity,0) As Qty, " .
			" if(ifNull(p.deduction_isper,0) = 1, ifNull(p.PackDt_Quantity,0) * ifNull(p.deduction_amount,0) / 100, ifNull(p.deduction_amount,0)) As Deduction, " .
			" ifNull(MeanCostCurByWH(p.Item_CodePackDt, '$costDate', 2, '$sqlBase2', '$sqlBase2', p.warehouse_code),0) As UnitCost " .
			" From PackDt as p " .
			" Left Join Items on Items.Item_Code = p.Item_CodePackDt " .
			" Where p.Item_Code = '$sqlSfCode' " . $packdtValid .
			" Order By p.Item_CodePackDt ";
		$resLines = mysql_query($queryLines, $connection) or die(mysql_error());
		while ($r = mysql_fetch_row($resLines)) $lines[] = $r;
	}

	$out = array('lines' => array(), 'totQty' => 0, 'totDry' => 0, 'totCost' => 0, 'costPerKg' => 0, 'batches' => $batches);
	foreach ($lines as $r) {
		$qty      = (float)$r[2];
		$unitCost = (float)$r[4];
		$cost     = $qty * $unitCost;
		$out['lines'][] = array($r[0], $r[1], $qty, $unitCost, $cost);
		$out['totQty']  += $qty;
		$out['totDry']  += $qty - (float)$r[3];
		$out['totCost'] += $cost;
	}
	if ($out['totDry'] != 0) $out['costPerKg'] = $out['totCost'] / $out['totDry'];
	return $out;
}

// True when the item is a semi-finished made on a production sheet
function isSFItem($code)
{
	global $connection, $isSFCache;
	if (!isset($isSFCache[$code])) {
		$q = "Select 1 From ProductionSheetFG Where Item_Code = '" . mysql_real_escape_string($code, $connection) . "' Limit 1";
		$r = mysql_query($q, $connection) or die(mysql_error());
		$isSFCache[$code] = (mysql_num_rows($r) > 0);
	}
	return $isSFCache[$code];
}

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
