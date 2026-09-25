<?php

//Report Version For CJR
session_start();
if ($_SESSION['dbName'] == "") {
	header("location:../index.php");
}
include("config.inc.php");
include("Functions_UserAccess.php");

$connection = mysqli_connect($config['dbServer'], $config['dbUser'], $config['dbPass']) or die("Could not connect to DB");
mysqli_select_db($connection, $_SESSION['dbName']) or die("Could not find DB");

if ($_SESSION['encodingmode'] == 'utf8' || $_SESSION['encodingmode'] == 'utf-8') {
	mysqli_query($connection, "SET NAMES 'utf8'");
	mysqli_query($connection, 'SET CHARACTER SET utf8');
	mysqli_set_charset($connection, 'utf8');
}

// CJR form access (was the old login-table check)
$sub_result = getformdetails('CJR', $_SESSION['accessschemaid'], $_SESSION['dbLabel']);
$row_login = $sub_result ? mysqli_fetch_row($sub_result) : null;
$loginaccess = $row_login[7] ?? '';
if ($loginaccess == '' or $loginaccess == 0) {
	header("location:menu.php");
	exit;
}

print <<< HERE
<HTML>
<HEAD>
<meta http-equiv="Content-Type" content="text/html; charset={$_SESSION['encodingmode']}" />
<link rel="stylesheet" type="text/css" href="css/style.css">
<TITLE> CJR Report </TITLE>

<style type="text/css">

table.master th { font-size: 14px;
                  font-weight: bold;
                  font-family:Calibri;
                }
table.master td { font-size: 14px;
                  font-family:Calibri;
                 }
table.detail th { font-size: 14px;
                  font-weight: bold;
                  font-family:Calibri;
                }

table.detail td { font-size: 14px;
                  font-family:Calibri;
                 }

table.master td.labels { font-weight: bold;
                         background-color:#D8D8D8;
                         padding-inline-start:5px;
                       }
table.master td.values { padding-inline-start:5px; }

.preview-cell-pad {
    padding-inline-start: 5px;
}
.cjr-warn { background-color:#ff4d4d !important; }

h4 { border-top-style: solid;
	 border-top-color: black;
	 border-top-width: 1px;
   }

@media print{
    body{
        -webkit-print-color-adjust:exact;
        print-color-adjust:exact;
    }
}
</style>


</HEAD>

<BODY>
HERE;

$FieldArray = array();
$ButtonArray = array();
$cjrPreviewDefaults = array(
	'cjrreport' => 'CJR',
	'cjrreference' => 'CJR Reference#',
	'date' => 'Date',
	'printeddatetime' => 'Printed Date & Time',
	'printedby' => 'Printed By',
	'sof' => 'SOF#',
	'customer' => 'Customer',
	'itemdescription' => 'Item Description',
	'qtyrequest' => 'Qty Request',
	'mastercard' => 'MasterCard#',
	'flutetype' => 'Flute Type',
	'scoringtype' => 'Scoring Type',
	'boardneed' => 'BoardNeed',
	'jobcard' => 'JobCard#',
	'insideliner' => 'Inside Liner',
	'outsideliner' => 'Outside Liner',
	'plannedqty' => 'Planned Qty',
	'boxtype' => 'Box Type',
	'stdgsm' => 'STD GSM',
	'producedqty' => 'Produced Qty',
	'boxsize' => 'Box Size ED',
	'stdpaper' => 'STD.Paper',
	'remainingqty' => 'Remaining Qty',
	'corrid' => 'CorrID',
	'salesref' => 'Sales Ref',
	'length' => 'Length',
	'width' => 'Width',
	'flaptop' => 'Flap Top',
	'height' => 'Height',
	'flapbot' => 'Flap Bot',
	'outs' => '# Outs',
	'twidth' => 'T-Width',
	'cuts' => '# Cuts',
	'acuts' => 'A cuts',
	'lm' => 'LM',
	'sq' => 'SQ',
	'reeldeckle' => 'Reel Deckle',
	'totwidth' => 'Tot Width',
	'trimming' => 'Trimming',
	'layer' => 'Layer',
	'item' => 'Item',
	'gsm' => 'GSM',
	'papergrade' => 'Paper Grade',
	'sizemm' => 'Size MM',
	'papermill' => 'Paper Mill',
	'warehouse' => 'WareHouse',
	'supplier' => 'Supplier',
	'urequired' => 'U Required',
	'lrequired' => 'L Required',
	'trequired' => 'T Required',
	'wtpcu' => 'WTPC(U)',
	'wtpcl' => 'WTPC(L)',
	'totgsm' => 'TOT GSM',
	'trimwaste' => 'TrimWaste%',
	'totalreq' => 'Total.Req',
	'req' => 'Req.',
	'stk' => 'Stk.',
	'booking' => 'Booking.',
	'reqtrim' => 'ReqTrim.(KG)'
);
foreach ($cjrPreviewDefaults as $cjrPreviewCode => $cjrPreviewLabel) {
	$FieldArray[$cjrPreviewCode]['FieldLabel'] = $cjrPreviewLabel;
}
$cjrPreviewUi = initFormLanguageUI('CJR', $_SESSION['dbLabel'], $connection, $FieldArray, $ButtonArray);
$body_dir = $cjrPreviewUi['dir'];
$body_class = $cjrPreviewUi['body_class'];
$is_rtl = $cjrPreviewUi['is_rtl'];
$previewAlign = ($is_rtl) ? 'right' : 'left';
$previewOppositeAlign = ($is_rtl) ? 'left' : 'right';
$previewFieldSettings = array();
$previewRenderResult = get_fields_for_render('CJR', $_SESSION['accessschemaid'], $_SESSION['dbLabel'], $connection, 'preview');
while ($previewRenderResult && ($previewField = mysqli_fetch_assoc($previewRenderResult))) {
	$previewCode = strtolower($previewField['fieldcode']);
	$previewFieldSettings[$previewCode] = $previewField;
	if (isset($FieldArray[$previewCode]) && trim((string)$previewField['accessfield_fieldname']) != '') {
		$FieldArray[$previewCode]['FieldLabel'] = $previewField['accessfield_fieldname'];
	}
}
if (!function_exists('cjr_preview_h')) {
	function cjr_preview_h($value)
	{
		return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
	}
}
if (!function_exists('cjr_preview_label')) {
	function cjr_preview_label($fields, $code, $fallback = '')
	{
		$code = strtolower($code);
		if (isset($fields[$code]['FieldLabel']) && trim((string)$fields[$code]['FieldLabel']) != '') return cjr_preview_h($fields[$code]['FieldLabel']);
		return cjr_preview_h($fallback);
	}
}
echo "<script>document.documentElement.dir='" . cjr_preview_h($body_dir) . "'; if(document.body){document.body.dir='" . cjr_preview_h($body_dir) . "'; document.body.className += ' " . cjr_preview_h($body_class) . "';}</script>";
echo "<style>";
foreach ($previewFieldSettings as $previewFieldCode => $previewFieldData) {
	if (intval($previewFieldData['is_hidden']) === 1) echo "[data-preview-field='" . cjr_preview_h(strtolower($previewFieldCode)) . "']{display:none !important;}";
}
echo "</style>";

$DecimalBase =  getSmodulevalue('DecimalBase', 'string', $_SESSION['logincompany'], $_SESSION['dbLabel'], $connection);
$CompanyName =   getSmodulevalue('Company_Name', 'string', $_SESSION['logincompany'], $_SESSION['dbLabel'], $connection);

$txtCJRId = $_POST["PurchaseId"] ?? '';
if ($txtCJRId == "") {
	$txtCJRId = $_GET["PurchaseId"] ?? '';
}
$txtCJRId = (int)$txtCJRId;

// Job card table (same one the CJR pages join)
$jobcardTable = "jobcard";

$now = current_server_time($connection);
$printedDate = $now[0] ?? date("d/m/Y H:i:s");

$bright = 'border-inline-end:black solid 1px;';
$brleft = 'border-inline-start:black solid 1px;';
$brtop = 'border-top:black solid 1px;';
$brbottom = 'border-bottom:black solid 1px;';
$cellBorder = "$brleft$bright$brtop$brbottom";

if ($txtCJRId > 0) {

	$query = "SELECT corrugator_reference, date_format(corrugator_date,'%d/%m/%Y') as corrugator_date, corrugator_confirm, corrugator_approve, adjustment_id,
				ifnull(reeldeckle,'') as reelDeckle, ifnull(totwidth,'') as totWidth, ifnull(trimming,'') as trimming,
				upperpaperrequiredT, lowerpaperrequiredT, wtpcupper, wtpclower, totalgsm, trimwasteper
			From corrugator1
			where corrugator_id = $txtCJRId ";
	$result = mysqli_query($connection, $query) or die(mysqli_error($connection));
	$rows = mysqli_fetch_array($result);
	if (is_array($rows)) extract($rows);

	/* ===================== REPORT HEADER (company block + title) ===================== */
	echo "<table border=0 width=100% cellspacing=0 cellpadding=0>";
	echo "<tr><td><table width=800 dir='$body_dir'>
		<tr>
		<td width=500 style='vertical-align: top;'>";
	echo "<div class='rpt-header-left'>";
	if (function_exists('atp_render_company_block')) atp_render_company_block($connection);
	else echo "<b style='font-size:20px;'>" . nl2br(cjr_preview_h($CompanyName)) . "</b>";
	echo "</div>";
	echo "</td>
		<td width=300 style='vertical-align: top; text-align: center; font-family: Arial; font-size: 30px; color: #a6a6a6;'>
		" . cjr_preview_label($FieldArray, 'cjrreport', 'CJR') . "
		</td>
		</tr>
	</table></td></tr>";

	/* ===================== CJR MASTER INFO ===================== */
	echo "<tr><td align='left'>";
	echo "<table border=0 width='800' cellspacing=0 cellpadding=2 class='master' style='table-layout:fixed;'>
		<col width=150><col width=250><col width=150><col width=250>";
	echo "<tr>
		<td class='labels' data-preview-field='cjrreference' style='$cellBorder'>" . cjr_preview_label($FieldArray, 'cjrreference', 'CJR Reference#') . "</td>
		<td class='values' data-preview-field='cjrreference' style='$cellBorder'><b>" . cjr_preview_h($corrugator_reference ?? '') . "</b></td>
		<td class='labels' data-preview-field='date' style='$cellBorder'>" . cjr_preview_label($FieldArray, 'date', 'Date') . "</td>
		<td class='values' data-preview-field='date' style='$cellBorder'>" . cjr_preview_h($corrugator_date ?? '') . "</td>
	</tr>";
	echo "<tr>
		<td class='labels' data-preview-field='printeddatetime' style='$cellBorder'>" . cjr_preview_label($FieldArray, 'printeddatetime', 'Printed Date & Time') . "</td>
		<td class='values' data-preview-field='printeddatetime' style='$cellBorder'>" . cjr_preview_h($printedDate) . "</td>
		<td class='labels' data-preview-field='printedby' style='$cellBorder'>" . cjr_preview_label($FieldArray, 'printedby', 'Printed By') . "</td>
		<td class='values' data-preview-field='printedby' style='$cellBorder'>" . cjr_preview_h($_SESSION['useraccount'] ?? '') . "</td>
	</tr>";
	echo "</table></td></tr>";
	echo "<tr><td>&nbsp;</td></tr>";

	/* ===================== JOB CARDS ===================== */
	$query1 = "Select
		related_salesorder, related_mastercard, jobcard_id, related_keylineId, mastercard.mastercard_BoxtypeId, mastercard.product_desc as itemdesc,
		upperlowerstacker.scoringtype as scoringtype, mastercard.outerlinercolor as outsideliner, mastercard.innerlinercolor as insideliner, papercombinations.temp_flutting2, jobcard.jobcard_gsm,
		clients.ledger_number as txtClientCode, clients.Ledger_name as customer, mastercard.mastercard_FluteTypeId,
		mastercard_reference as masterref, Sorder_reference as salesref, JobCard_Reference as jobCardRef, ifNull(plannedqty,0) as plannedqty,
		ifNull(producedqty,0) as producedqty, ifNull(remainingqty,0) as remainingqty,
		flutetype.FluteType_code as flutetype_code, flutetype.FluteType_ply as flutetype_ply, boxtype.boxtype_code as BoxType_code,
		upperlowerstacker.boardneed as boardneed, ifnull(BorderNeededQty,0) as BorderNeededQty,
		stdgsm, mastercard.external_length, mastercard.external_width, mastercard.external_height,
		papercombinations.temp_TLiner1, papercombinations.temp_Liner1, papercombinations.temp_TFlutting1, papercombinations.temp_Flutting1,
		papercombinations.temp_Tliner2, papercombinations.temp_Liner2, papercombinations.temp_TFlutting2, papercombinations.temp_Flutting2,
		papercombinations.temp_TOuterLine, papercombinations.temp_OuterLine,
		upperlowerstacker.flutetype, mastercard.scoringtype as MCscoringtype
	from upperlowerstacker
	left join $jobcardTable as jobcard ON jobcard.jobcard_Id = upperlowerstacker.jobcardid
	left join mastercard on mastercard.mastercard_id = jobcard.related_mastercard
	left join papercombinations on papercombinations.mastercard_id = mastercard.mastercard_id
	left join clients on clients.ledger_number = mastercard.ledger_number
	left join sorder on sorder.Sorder_id = jobcard.related_salesorder
	left join boxtype on mastercard.mastercard_BoxTypeId = boxtype.boxtype_id
	left join flutetype on flutetype.FluteType_ID = upperlowerstacker.flutetype
	where corrugatorid = " . $txtCJRId . "
	group by upperlowerstacker.ulstacker_id
	order by upperlowerstacker.ulstacker_id ";
	// (the PHP 5 query also joined sorderdt, which repeated each job card once per sales order line)

	$result1 = mysqli_query($connection, $query1) or die(mysqli_error($connection));

	$JobCardIdArr = array();
	$scoringTypeArr = array();
	$outsideLinerArr = array();
	$fluteTypeArr = array();
	$flutetype_code = '';
	$i = 0;

	while ($rows1 = mysqli_fetch_array($result1)) {
		extract($rows1);
		$JobCardIdArr[$i] = $jobcard_id;

		$masterref = (string)$masterref;
		if ($masterref != '') {
			if (strlen($masterref) < 4) {
				if (strlen($masterref) == 1)
					$masterref = "MC0000" . $masterref;
				else if (strlen($masterref) == 2)
					$masterref = "MC000" . $masterref;
				else
					$masterref = "MC00" . $masterref;
			} else
				$masterref = "MC0" . $masterref;
		}

		// Highlight values that differ between the two job cards / from the master card (same rules as Form_Corrugator.php)
		$warnScoring = '';
		foreach ($scoringTypeArr as $prevScoring) if ($prevScoring != $scoringtype) $warnScoring = 'cjr-warn';
		$warnFlute = '';
		foreach ($fluteTypeArr as $prevFlute) if (strtoupper($prevFlute) != strtoupper($flutetype)) $warnFlute = 'cjr-warn';
		$warnOutside = '';
		foreach ($outsideLinerArr as $prevOutside) if (strtoupper($prevOutside) != strtoupper($outsideliner)) $warnOutside = 'cjr-warn';
		$warnScoringMC = ($MCscoringtype != $scoringtype) ? "color:red;" : '';

		$scoringTypeArr[$i] = $scoringtype;
		$fluteTypeArr[$i] = $flutetype;
		$outsideLinerArr[$i] = $outsideliner;

		$fluteText = trim($flutetype_code . ($flutetype_ply != '' ? "   -   " . $flutetype_ply . " ply" : ''));
		$stdPaper = trim("$temp_TLiner1 $temp_Liner1 $temp_TFlutting1 $temp_Flutting1 $temp_Tliner2 $temp_Liner2 $temp_TFlutting2 $temp_Flutting2 $temp_TOuterLine $temp_OuterLine");

		echo "<tr><td align='left'>";
		echo "<table border=0 width='800' cellspacing=0 cellpadding=2 class='master' style='table-layout:fixed;'>
			<col width=110><col width=160><col width=110><col width=160><col width=110><col width=150>";
		echo "<tr>
			<td class='labels' data-preview-field='sof' style='$cellBorder'>" . cjr_preview_label($FieldArray, 'sof', 'SOF#') . "</td>
			<td class='values' data-preview-field='sof' style='$cellBorder'>" . cjr_preview_h($salesref) . "</td>
			<td class='labels' data-preview-field='customer' style='$cellBorder'>" . cjr_preview_label($FieldArray, 'customer', 'Customer') . "</td>
			<td class='values' data-preview-field='customer' style='$cellBorder'>" . cjr_preview_h($customer) . "</td>
			<td class='labels' data-preview-field='qtyrequest' style='$cellBorder'>" . cjr_preview_label($FieldArray, 'qtyrequest', 'Qty Request') . "</td>
			<td class='values' data-preview-field='qtyrequest' style='$cellBorder'>" . cjr_preview_h($boardneed) . "</td>
		</tr>";
		echo "<tr>
			<td class='labels' data-preview-field='itemdescription' style='$cellBorder'>" . cjr_preview_label($FieldArray, 'itemdescription', 'Item Description') . "</td>
			<td class='values' data-preview-field='itemdescription' style='$cellBorder' colspan=5>" . cjr_preview_h($itemdesc) . "</td>
		</tr>";
		echo "<tr>
			<td class='labels' data-preview-field='mastercard' style='$cellBorder'>" . cjr_preview_label($FieldArray, 'mastercard', 'MasterCard#') . "</td>
			<td class='values' data-preview-field='mastercard' style='$cellBorder'>" . cjr_preview_h($masterref) . "</td>
			<td class='labels $warnFlute' data-preview-field='flutetype' style='$cellBorder'>" . cjr_preview_label($FieldArray, 'flutetype', 'Flute Type') . "</td>
			<td class='values' data-preview-field='flutetype' style='$cellBorder'>" . cjr_preview_h($fluteText) . "</td>
			<td class='labels $warnScoring' data-preview-field='scoringtype' style='$cellBorder'>" . cjr_preview_label($FieldArray, 'scoringtype', 'Scoring Type') . "</td>
			<td class='values' data-preview-field='scoringtype' style='$cellBorder$warnScoringMC'>" . cjr_preview_h($scoringtype) . "</td>
		</tr>";
		echo "<tr>
			<td class='labels' data-preview-field='jobcard' style='$cellBorder'>" . cjr_preview_label($FieldArray, 'jobcard', 'JobCard#') . "</td>
			<td class='values' data-preview-field='jobcard' style='$cellBorder'>" . cjr_preview_h($jobCardRef) . "</td>
			<td class='labels' data-preview-field='insideliner' style='$cellBorder'>" . cjr_preview_label($FieldArray, 'insideliner', 'Inside Liner') . "</td>
			<td class='values' data-preview-field='insideliner' style='$cellBorder'>" . cjr_preview_h($insideliner) . "</td>
			<td class='labels $warnOutside' data-preview-field='outsideliner' style='$cellBorder'>" . cjr_preview_label($FieldArray, 'outsideliner', 'Outside Liner') . "</td>
			<td class='values' data-preview-field='outsideliner' style='$cellBorder'>" . cjr_preview_h($outsideliner) . "</td>
		</tr>";
		echo "<tr>
			<td class='labels' data-preview-field='boxtype' style='$cellBorder'>" . cjr_preview_label($FieldArray, 'boxtype', 'Box Type') . "</td>
			<td class='values' data-preview-field='boxtype' style='$cellBorder'>" . cjr_preview_h($BoxType_code) . "</td>
			<td class='labels' data-preview-field='stdgsm' style='$cellBorder'>" . cjr_preview_label($FieldArray, 'stdgsm', 'STD GSM') . "</td>
			<td class='values' data-preview-field='stdgsm' style='$cellBorder'>" . cjr_preview_h($stdgsm) . "</td>
			<td class='labels' data-preview-field='boardneed' style='$cellBorder'>" . cjr_preview_label($FieldArray, 'boardneed', 'BoardNeed') . "</td>
			<td class='values' data-preview-field='boardneed' style='$cellBorder'>" . cjr_preview_h($BorderNeededQty) . "</td>
		</tr>";
		echo "<tr>
			<td class='labels' data-preview-field='boxsize' style='$cellBorder'>" . cjr_preview_label($FieldArray, 'boxsize', 'Box Size ED') . "</td>
			<td class='values' data-preview-field='boxsize' style='$cellBorder'>" . cjr_preview_h(trim("$external_length  $external_width  $external_height")) . "</td>
			<td class='labels' data-preview-field='stdpaper' style='$cellBorder'>" . cjr_preview_label($FieldArray, 'stdpaper', 'STD.Paper') . "</td>
			<td class='values' data-preview-field='stdpaper' style='$cellBorder' colspan=3>" . cjr_preview_h($stdPaper) . "</td>
		</tr>";
		echo "<tr>
			<td class='labels' data-preview-field='plannedqty' style='$cellBorder'>" . cjr_preview_label($FieldArray, 'plannedqty', 'Planned Qty') . "</td>
			<td class='values' data-preview-field='plannedqty' style='$cellBorder'>" . cjr_preview_h($plannedqty) . "</td>
			<td class='labels' data-preview-field='producedqty' style='$cellBorder'>" . cjr_preview_label($FieldArray, 'producedqty', 'Produced Qty') . "</td>
			<td class='values' data-preview-field='producedqty' style='$cellBorder'>" . cjr_preview_h($producedqty) . "</td>
			<td class='labels' data-preview-field='remainingqty' style='$cellBorder'>" . cjr_preview_label($FieldArray, 'remainingqty', 'Remaining Qty') . "</td>
			<td class='values' data-preview-field='remainingqty' style='$cellBorder'>" . cjr_preview_h($remainingqty) . "</td>
		</tr>";
		echo "</table></td></tr>";
		echo "<tr><td>&nbsp;</td></tr>";

		$i = $i + 1;
	}

	/* ===================== CORRUGATOR GRID ===================== */
	echo "<tr><td align='left'>";
	echo "<table border=0 width='800' cellspacing=0 class='detail' style='table-layout:fixed;'>
		<col width=55><col width=80><col width=60><col width=60><col width=60><col width=60><col width=60><col width=55><col width=60><col width=55><col width=55><col width=70><col width=70>";
	echo "<tr height='20' bgcolor='#D8D8D8'>";
	foreach (array('corrid' => 'CorrID', 'salesref' => 'Sales Ref', 'length' => 'Length', 'width' => 'Width', 'flaptop' => 'Flap Top', 'height' => 'Height', 'flapbot' => 'Flap Bot', 'outs' => '# Outs', 'twidth' => 'T-Width', 'cuts' => '# Cuts', 'acuts' => 'A cuts', 'lm' => 'LM', 'sq' => 'SQ') as $gridCode => $gridLabel) {
		echo "<td data-preview-field='$gridCode' style='font-weight: bold;$cellBorder' align='center'>" . cjr_preview_label($FieldArray, $gridCode, $gridLabel) . "</td>";
	}
	echo "</tr>";

	for ($i = 0; $i < count($JobCardIdArr); $i++) {
		$query = " SELECT corgrid_id, corrugatorid, ifNull(length,0) as length, ifNull(width,0) as width,
			ifNull(flaptop,0) as flaptop, ifNull(height,0) as height, ifNull(flapbot,0) as flapbot, ifNull(numouts,0) as numouts, ifNull(twidth,0) as twidth,
			ifNull(numcuts,0) as numcuts, ifNull(actualcuts,0) as actualcuts, ifNull(lm,0) as lm, ifNull(sq,0) as sq, sorder_reference
			from $jobcardTable as jobcard
			left join corgrid on jobcard.jobcard_Id = corgrid.jobcardid
			left join sorder  on sorder.sorder_id = jobcard.related_salesorder
			where corrugatorid = " . $txtCJRId . " and jobcard.jobcard_id = '" . $JobCardIdArr[$i] . "'";
		$result = mysqli_query($connection, $query) or die(mysqli_error($connection));
		$rows = mysqli_fetch_array($result);
		if (!is_array($rows)) continue;
		extract($rows);

		$getwidth = "select scoringflaptop + scorinhheight + scorinhbottom, scoringflaptop, scorinhheight, scorinhbottom
			from mastercard
			inner join $jobcardTable as jobcard on related_mastercard = mastercard_id
			where jobcard_id = '" . $JobCardIdArr[$i] . "'";
		$resultwidth = mysqli_query($connection, $getwidth);
		$rowwidth = $resultwidth ? mysqli_fetch_array($resultwidth) : null;
		$warn1 = (($rowwidth[0] ?? '') != $width) ? 'cjr-warn' : '';
		$warn2 = (($rowwidth[1] ?? '') != $flaptop) ? 'cjr-warn' : '';
		$warn3 = (($rowwidth[2] ?? '') != $height) ? 'cjr-warn' : '';
		$warn4 = (($rowwidth[3] ?? '') != $flapbot) ? 'cjr-warn' : '';

		echo "<tr>
			<td data-preview-field='corrid' style='$cellBorder' align='center'>" . cjr_preview_h($corrugatorid) . "</td>
			<td data-preview-field='salesref' style='$cellBorder' align='center'>" . cjr_preview_h($sorder_reference) . "</td>
			<td data-preview-field='length' style='$cellBorder' align='center'>" . cjr_preview_h($length) . "</td>
			<td data-preview-field='width' class='$warn1' style='$cellBorder' align='center'>" . cjr_preview_h($width) . "</td>
			<td data-preview-field='flaptop' class='$warn2' style='$cellBorder' align='center'>" . cjr_preview_h($flaptop) . "</td>
			<td data-preview-field='height' class='$warn3' style='$cellBorder' align='center'>" . cjr_preview_h($height) . "</td>
			<td data-preview-field='flapbot' class='$warn4' style='$cellBorder' align='center'>" . cjr_preview_h($flapbot) . "</td>
			<td data-preview-field='outs' style='$cellBorder' align='center'>" . cjr_preview_h($numouts) . "</td>
			<td data-preview-field='twidth' style='$cellBorder' align='center'>" . cjr_preview_h($twidth) . "</td>
			<td data-preview-field='cuts' style='$cellBorder' align='center'>" . cjr_preview_h($numcuts) . "</td>
			<td data-preview-field='acuts' style='$cellBorder' align='center'>" . cjr_preview_h($actualcuts) . "</td>
			<td data-preview-field='lm' style='$cellBorder' align='center'>" . cjr_preview_h($lm) . "</td>
			<td data-preview-field='sq' style='$cellBorder' align='center'>" . cjr_preview_h($sq) . "</td>
		</tr>";
	}
	echo "</table></td></tr>";

	echo "<tr><td align='left'>";
	echo "<table border=0 width='800' cellspacing=0 cellpadding=2 class='master' style='table-layout:fixed;margin-top:6px;'>
		<col width=120><col width=120><col width=120><col width=120><col width=120><col width=120><col width=80>";
	echo "<tr>
		<td class='labels' data-preview-field='reeldeckle' style='$cellBorder'>" . cjr_preview_label($FieldArray, 'reeldeckle', 'Reel Deckle') . "</td>
		<td class='values' data-preview-field='reeldeckle' style='$cellBorder'>" . cjr_preview_h($reelDeckle ?? '') . "</td>
		<td class='labels' data-preview-field='totwidth' style='$cellBorder'>" . cjr_preview_label($FieldArray, 'totwidth', 'Tot Width') . "</td>
		<td class='values' data-preview-field='totwidth' style='$cellBorder'>" . cjr_preview_h($totWidth ?? '') . "</td>
		<td class='labels' data-preview-field='trimming' style='$cellBorder'>" . cjr_preview_label($FieldArray, 'trimming', 'Trimming') . "</td>
		<td class='values' data-preview-field='trimming' style='$cellBorder'>" . cjr_preview_h($trimming ?? '') . "</td>
		<td></td>
	</tr>";
	echo "</table></td></tr>";
	echo "<tr><td>&nbsp;</td></tr>";

	/* ===================== LOCATION (paper layers) ===================== */
	$queryLoc = "select itemType, itemCode, relldeckle, gsm, papergradle, trimsize, papermill, warehouse_code, supplier,
			upperpaperrequired, lowerpaperrequired
		From locationTable
		where corrugatorid='$txtCJRId' or corrugatorid2='$txtCJRId'
		order by loc_id";
	$resultLoc = mysqli_query($connection, $queryLoc);

	echo "<tr><td align='left'>";
	echo "<table border=0 width='800' cellspacing=0 class='detail' style='table-layout:fixed;'>
		<col width=95><col width=90><col width=65><col width=55><col width=75><col width=60><col width=90><col width=75><col width=85><col width=55><col width=55>";
	echo "<tr height='20' bgcolor='#D8D8D8'>";
	foreach (array('layer' => 'Layer', 'item' => 'Item', 'reeldeckle' => 'Reel Deckle', 'gsm' => 'GSM', 'papergrade' => 'Paper Grade', 'sizemm' => 'Size MM', 'papermill' => 'Paper Mill', 'warehouse' => 'WareHouse', 'supplier' => 'Supplier', 'urequired' => 'U Required', 'lrequired' => 'L Required') as $locCode => $locLabel) {
		echo "<td data-preview-field='$locCode' style='font-weight: bold;$cellBorder' align='center'>" . cjr_preview_label($FieldArray, $locCode, $locLabel) . "</td>";
	}
	echo "</tr>";
	while ($resultLoc && ($rowsLoc = mysqli_fetch_array($resultLoc))) {
		echo "<tr>
			<td data-preview-field='layer' style='$cellBorder' align='center'><b>" . cjr_preview_h($rowsLoc['itemType']) . "</b></td>
			<td data-preview-field='item' style='$cellBorder' align='center'>" . cjr_preview_h($rowsLoc['itemCode']) . "</td>
			<td data-preview-field='reeldeckle' style='$cellBorder' align='center'>" . cjr_preview_h($rowsLoc['relldeckle']) . "</td>
			<td data-preview-field='gsm' style='$cellBorder' align='center'>" . cjr_preview_h($rowsLoc['gsm']) . "</td>
			<td data-preview-field='papergrade' style='$cellBorder' align='center'>" . cjr_preview_h($rowsLoc['papergradle']) . "</td>
			<td data-preview-field='sizemm' style='$cellBorder' align='center'>" . cjr_preview_h($rowsLoc['trimsize']) . "</td>
			<td data-preview-field='papermill' style='$cellBorder' align='center'>" . cjr_preview_h($rowsLoc['papermill']) . "</td>
			<td data-preview-field='warehouse' style='$cellBorder' align='center'>" . cjr_preview_h($rowsLoc['warehouse_code']) . "</td>
			<td data-preview-field='supplier' style='$cellBorder' align='center'>" . cjr_preview_h($rowsLoc['supplier']) . "</td>
			<td data-preview-field='urequired' style='$cellBorder' align='center'>" . cjr_preview_h($rowsLoc['upperpaperrequired']) . "</td>
			<td data-preview-field='lrequired' style='$cellBorder' align='center'>" . cjr_preview_h($rowsLoc['lowerpaperrequired']) . "</td>
		</tr>";
	}
	echo "</table></td></tr>";
	echo "<tr><td>&nbsp;</td></tr>";

	/* ===================== PAPER REQUIREMENT SUMMARY ===================== */
	// 3-ply = INNER LINER / FLUTE / OUTER LINER, 5-ply = LINER-1 / FLUTE-1 / LINER-2 / FLUTE-2 / OUTER LINER
	$rowsNb_ = (strlen((string)$flutetype_code) == 2) ? 5 : 3;

	$cjrLayer = function ($itemType, $bookingCjrOnly) use ($connection, $txtCJRId) {
		$layer = array('required' => '', 'stock' => '', 'booking' => '', 'trim' => '');
		$query = "select totURequired, stockqty, itemCode, trimrequired from locationtable where corrugatorid='$txtCJRId' and itemtype='$itemType'";
		$result = mysqli_query($connection, $query);
		$rows = $result ? mysqli_fetch_array($result) : null;
		$layer['required'] = $rows[0] ?? '';
		$layer['stock'] = $rows[1] ?? '';
		$layer['trim'] = $rows[3] ?? '';
		$q = "Select Sum(bookingqty_qty - bookingqty_usedqty) from bookingqty where item_code ='" . ($rows[2] ?? '') . "'" . ($bookingCjrOnly ? " and ifnull(cjr_id,0) <> 0 " : "");
		$res = mysqli_query($connection, $q);
		$ress = $res ? mysqli_fetch_array($res) : null;
		$layer['booking'] = $ress[0] ?? '';
		return $layer;
	};
	$sumLabel = function ($text, $warn = '') use ($cellBorder) {
		return "<td class='labels $warn' style='$cellBorder'>" . cjr_preview_h($text) . "</td>";
	};
	$sumValue = function ($value) use ($cellBorder) {
		return "<td class='values' style='$cellBorder'>" . cjr_preview_h($value) . "</td>";
	};
	$layerCells = function ($prefix, $layer, $warnWhenShort) use ($sumLabel, $sumValue, $FieldArray) {
		$warn = ($warnWhenShort && (float)$layer['required'] > (float)$layer['stock']) ? 'cjr-warn' : '';
		return $sumLabel($prefix . ' ' . $FieldArray['req']['FieldLabel'], $warn) . $sumValue($layer['required'])
			. $sumLabel($prefix . ' ' . $FieldArray['stk']['FieldLabel']) . $sumValue($layer['stock'])
			. $sumLabel($prefix . ' ' . $FieldArray['booking']['FieldLabel']) . $sumValue($layer['booking']);
	};

	$UReqTot = $upperpaperrequiredT ?? '';
	$LReqTot = $lowerpaperrequiredT ?? '';
	$TReqTot = (float)$UReqTot + (float)$LReqTot;
	$totalReq = 0;

	if ($rowsNb_ == 3) {
		$layers = array(
			array('I', 'INNER LINER', true),
			array('F', 'FLUTE', false),
			array('O', 'OUTER LINER', false)
		);
	} else {
		$layers = array(
			array('LINER-1', 'LINER-1', true),
			array('FLUTE-1', 'FLUTE-1', false),
			array('LINER-2', 'LINER-2', false)
		);
	}
	$firstCells = array(
		array(cjr_preview_label($FieldArray, 'urequired', 'U Required'), $UReqTot, cjr_preview_label($FieldArray, 'wtpcu', 'WTPC(U)'), $wtpcupper ?? ''),
		array(cjr_preview_label($FieldArray, 'lrequired', 'L Required'), $LReqTot, cjr_preview_label($FieldArray, 'wtpcl', 'WTPC(L)'), $wtpclower ?? ''),
		array(cjr_preview_label($FieldArray, 'trequired', 'T Required'), $TReqTot, cjr_preview_label($FieldArray, 'totgsm', 'TOT GSM'), $totalgsm ?? '')
	);

	echo "<tr><td align='left'>";
	echo "<table border=0 width='1000' cellspacing=0 cellpadding=2 class='master' style='table-layout:fixed;'>
		<col width=110><col width=70><col width=110><col width=70><col width=110><col width=70><col width=120><col width=70><col width=90><col width=70><col width=140><col width=70>";
	foreach ($layers as $idx => $layerDef) {
		// booking filter on cjr_id kept per layer exactly as the PHP 5 preview had it
		$layer = $cjrLayer($layerDef[1], $layerDef[2]);
		$totalReq += (float)$layer['required'];
		echo "<tr>";
		echo "<td class='labels' style='$cellBorder'>" . $firstCells[$idx][0] . "</td>" . $sumValue($firstCells[$idx][1]);
		echo $layerCells($layerDef[0], $layer, $rowsNb_ == 3 || $idx == 0);
		echo "<td class='labels' style='$cellBorder'>" . $firstCells[$idx][2] . "</td>" . $sumValue($firstCells[$idx][3]);
		echo $sumLabel($layerDef[0] . ' ' . $FieldArray['reqtrim']['FieldLabel']) . $sumValue($layer['trim']);
		echo "</tr>";
	}

	echo "<tr>";
	echo "<td class='labels' style='$cellBorder'>" . cjr_preview_label($FieldArray, 'trimwaste', 'TrimWaste%') . "</td>" . $sumValue($trimwasteper ?? '');
	if ($rowsNb_ == 5) {
		$layer = $cjrLayer('FLUTE-2', true);
		echo $layerCells('FLUTE-2', $layer, false);
		echo "<td></td><td></td>";
		echo $sumLabel('FLUTE-2 ' . $FieldArray['reqtrim']['FieldLabel']) . $sumValue($layer['trim']);
		echo "</tr><tr><td></td><td></td>";
		$layer = $cjrLayer('OUTER LINER', true);
		echo $layerCells('O LINER', $layer, false);
		echo "<td></td><td></td>";
		echo $sumLabel('O.Liner ' . $FieldArray['reqtrim']['FieldLabel']) . $sumValue($layer['trim']);
		echo "</tr><tr><td></td><td></td>";
	}
	echo "<td class='labels' style='$cellBorder'>" . cjr_preview_label($FieldArray, 'totalreq', 'Total.Req') . "</td>" . $sumValue(round($totalReq));
	echo "</tr>";
	echo "</table></td></tr>";

	echo "</table>";
}


function current_server_time($connection)
{

	$string = "Select date_format(NOW(),'%d/%m/%Y %h:%i %p') ";
	$result = mysqli_query($connection, $string) or die(mysqli_error($connection));
	$now = mysqli_fetch_row($result);

	return $now;
}


print <<< HERE

<script language=javascript>
if (typeof(window.print) != 'undefined')
{
    window.print();
}
</script>
</BODY>
</HTML>
HERE;
