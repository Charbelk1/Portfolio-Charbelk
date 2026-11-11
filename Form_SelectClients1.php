<?php

session_start();
if (!empty($_SESSION['encodingmode'])) {
	header("Content-type: text/html; charset=" . $_SESSION['encodingmode']);
}
if (empty($_SESSION['dbName'])) {
	header("location:../index.php");
	exit;
}


include("config.inc.php");
include("function.inc.php");
include("Functions_Trans.php");
include("Functions_UserAccess.php");
set_time_limit(0);

$connection = mysqli_connect($config['dbServer'], $config['dbUser'], $config['dbPass']) or die("Could not connect to DB");
mysqli_select_db($connection, $_SESSION['dbName']) or die("Could not find DB");


if ($_SESSION['encodingmode'] == 'utf8' || $_SESSION['encodingmode'] == 'utf-8') {
	mysqli_query($connection, "SET NAMES 'utf8'");
	mysqli_query($connection, 'SET CHARACTER SET utf8');
	mysqli_set_charset($connection, 'utf8');
}

// Escapes text for HTML in the page encoding (same as Form_LoadKeyline.php)
function smcH($value)
{
	$charset = (isset($_SESSION['encodingmode']) && in_array(strtolower($_SESSION['encodingmode']), array('utf8', 'utf-8'))) ? 'UTF-8' : 'ISO-8859-1';
	return htmlspecialchars((string)$value, ENT_QUOTES, $charset);
}
// Request value: POST first, then GET (the PHP 5 page read POST then GET by hand)
function smcReq($name, $default = '')
{
	if (isset($_POST[$name]) && !is_array($_POST[$name]) && $_POST[$name] !== '') return $_POST[$name];
	if (isset($_GET[$name]) && !is_array($_GET[$name]) && $_GET[$name] !== '') return $_GET[$name];
	return $default;
}

$fromdate   = smcReq('fromdate');
$todate     = smcReq('todate');

$maxperpage = getSmodulevalue('maxperpage', 'string', $_SESSION['logincompany'], $_SESSION['dbLabel'], $connection);

/* ---------- Default dates (same rule as PHP 5) ----------
   From: setup.search_startingdate  -1 = start of the financial year, -2 = 1st January, 0 = 1st of the month,
   anything else = no lower bound.  To: today. */
if ($fromdate == '') {
	$search_startingdate = '';
	$resultdate = mysqli_query($connection, "select search_startingdate from setup") or die(mysqli_error($connection));
	if ($rowdate = mysqli_fetch_row($resultdate)) $search_startingdate = (string)$rowdate[0];

	if ($search_startingdate == '-1') {
		$MainCompanyName = explode('_', $_SESSION['dbName']);
		$QueryDir = " Select Date_Format(StartFinancialDate,'%d/%m/%Y')
				From " . $_SESSION['dbLabel'] . ".Directories " .
			" Where Upper(CompanyName) = Upper('" . mysqli_real_escape_string($connection, $MainCompanyName[0] ?? '') . "') " .
			" and Upper(Diryear) = Upper('" . mysqli_real_escape_string($connection, $MainCompanyName[1] ?? '') . "') ";
		$ResultDir = mysqli_query($connection, $QueryDir) or die(mysqli_error($connection));
		$RowsDir = mysqli_fetch_row($ResultDir);
		$fromdate = (string)($RowsDir[0] ?? '');
	} else if ($search_startingdate == '-2') {
		$fromdate = date('01/01/Y');
	} else if ($search_startingdate == '0') {
		$fromdate = date('01/m/Y');
	}
}
if ($todate == '')
	$todate = date('d/m/Y');

$grid_color1 =  getSmodulevalue('grid_color1', 'string', $_SESSION['logincompany'], $_SESSION['dbLabel'], $connection);
$grid_color2 =  getSmodulevalue('grid_color2', 'string', $_SESSION['logincompany'], $_SESSION['dbLabel'], $connection);

$FieldArray   = array();
$ButtonArray   = array();

$FieldArray['smctitle']['FieldLabel'] = 'List of MasterCard';
$FieldArray['smcperpage']['FieldLabel'] = 'Per Page';
$FieldArray['smcsearchby']['FieldLabel'] = 'Search By';
$FieldArray['smcsearchoption']['FieldLabel'] = 'Search Option';
$FieldArray['smcfrom']['FieldLabel'] = 'From';
$FieldArray['smcto']['FieldLabel'] = 'To';

$FieldArray['smcsearchdate']['FieldLabel'] = 'Date (yyyy-mm-dd)';
$FieldArray['smcsearchref']['FieldLabel'] = 'Reference';
$FieldArray['smcsearchclient']['FieldLabel'] = 'Client';

$FieldArray['smcreference']['FieldLabel'] = 'Reference';
$FieldArray['smcdate']['FieldLabel'] = 'Date';
$FieldArray['smcclientcode']['FieldLabel'] = 'Client Code';
$FieldArray['smcclientname']['FieldLabel'] = 'Name';
$FieldArray['smcproddesc']['FieldLabel'] = 'Product Desc.';

$FieldArray['smcedit']['FieldLabel'] = 'Edit Record';
$FieldArray['smcfirstpage']['FieldLabel'] = 'First Page';
$FieldArray['smcprevpage']['FieldLabel'] = 'Previous';
$FieldArray['smcnextpage']['FieldLabel'] = 'Next';
$FieldArray['smclastpage']['FieldLabel'] = 'Last Page';

$FieldArray['smccardsearch']['FieldLabel'] = 'Search';
$FieldArray['smccarddaterange']['FieldLabel'] = 'Date Range';
$FieldArray['smcnorecords']['FieldLabel'] = 'No approved master card found.';

$FieldArray['smcfind']['FieldLabel'] = 'Find';
$FieldArray['smccancel']['FieldLabel'] = 'Cancel';
$FieldArray['btndatetoday']['FieldLabel'] = 'Today';
$FieldArray['btndateclear']['FieldLabel'] = 'Clear';

foreach ($FieldArray as $fieldKey => $fieldInfo) {
	if (!isset($FieldArray[$fieldKey]['IsHidden'])) $FieldArray[$fieldKey]['IsHidden'] = false;
	if (!isset($FieldArray[$fieldKey]['MandatoryLabel'])) $FieldArray[$fieldKey]['MandatoryLabel'] = '';
	if (!isset($FieldArray[$fieldKey]['FieldMod'])) $FieldArray[$fieldKey]['FieldMod'] = '';
}

$sub_result = get_mandatory('MasterCard', $_SESSION['accessschemaid'], $_SESSION['dbLabel'], $connection);
while ($sub_result && ($sub_row = mysqli_fetch_row($sub_result))) {
	$vFieldLabel = ((isset($_SESSION['languageid']) && $_SESSION['languageid'] != '') ? get_labelnames_bylanguage('MasterCard', $_SESSION['dbLabel'], $_SESSION['languageid'], $sub_row[2], $connection) : get_labelnames('MasterCard', $_SESSION['dbLabel'], $_SESSION['accessid'], $sub_row[2], $connection));

	if ($vFieldLabel != '') $FieldArray[$sub_row[2]]["FieldLabel"] = $vFieldLabel;
	$FieldArray[$sub_row[2]]["MandatoryLabel"] = '';
	$FieldArray[$sub_row[2]]["FieldMod"]       = ($sub_row[0] == '0') ? 'readonly' : '';
	$FieldArray[$sub_row[2]]["IsHidden"]       = ($sub_row[5] == '1');
}

$sub_result = get_labelbuttons($_SESSION['dbLabel'], $_SESSION['accessid'], $connection);
while ($sub_result && ($sub_row = mysqli_fetch_row($sub_result))) {
	$ButtonArray[$sub_row[0]]['ButtonLabel'] = $sub_row[1];
	$ButtonArray[$sub_row[0]]['ButtonTitle'] = $sub_row[2];
}
// Find / Cancel fall back to the captions above when the button labels aren't set up
if (empty($ButtonArray[6]['ButtonLabel'])) $ButtonArray[6] = array('ButtonLabel' => $FieldArray['smcfind']['FieldLabel'], 'ButtonTitle' => $FieldArray['smcfind']['FieldLabel']);
if (empty($ButtonArray[7]['ButtonLabel'])) $ButtonArray[7] = array('ButtonLabel' => $FieldArray['smccancel']['FieldLabel'], 'ButtonTitle' => $FieldArray['smccancel']['FieldLabel']);

$ui = initFormLanguageUI('MasterCard', $_SESSION['dbLabel'], $connection, $FieldArray, $ButtonArray);
$rtl_class = $ui['container_class'];
$body_dir = $ui['dir'];
$body_class = $ui['body_class'] ?? '';

/* ---------- Request ---------- */
$PMode      = smcReq('PMode');
$keyword    = smcReq('keyword');
$searchby   = smcReq('searchby');
$perpage    = smcReq('perpage');
$startrange = smcReq('startrange');
$message    = smcReq('message');

// Linked / replaced wizard state, sent by Form_MasterCard.php in the URL (PHP 5) and kept as hidden fields here
$numoflinked   = smcReq('numoflinked');
$numofreplaced = smcReq('numofreplaced');

if (empty($searchby)) $searchby = 2;
$searchby = (int)$searchby;

if ($startrange == '')
	$startrange = 0;
if ($perpage == '')
	$perpage = $maxperpage;
if ($perpage == '' || $perpage == 0)
	$perpage = 20;
$startrange = (int)$startrange;
$perpage = (int)$perpage;

$cssStyleVersion = @filemtime(__DIR__ . '/css/style.css') ?: time();

$h_title = smcH($FieldArray['smctitle']['FieldLabel']);

print <<<HERE
<head>
<meta http-equiv="Content-Type" content="text/html; charset={$_SESSION['encodingmode']}" />
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>$h_title</title>
<link rel="stylesheet" type="text/css" media="all" href="css/style.css?v=$cssStyleVersion">

<link rel="stylesheet" type="text/css" media="all" href="r-main-css.css">

<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script type="text/javascript" src="js/grid/jquery-1.9.1.js"></script>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<script type="text/javascript" language="javascript">

// Sets (or creates) a hidden field on the search form, same approach as Form_LoadKeyline.php
function setSearchInput(name, value)
{
	let input = document.search.querySelector('input[name="' + name + '"]');
	if (!input) {
		input = document.createElement('input');
		input.type = 'hidden';
		input.name = name;
		document.search.appendChild(input);
	}
	input.value = value;
}

// numoflinked / numofreplaced are hidden fields, so every post keeps them
function submitSearch(startrange)
{
	if (startrange !== undefined) setSearchInput('startrange', startrange);
	document.search.action = 'Form_SelectClients1.php';
	document.search.submit();
}

function RefreshPage()
{
	submitSearch(0);
}

function clearform()
{
	document.search.keyword.value = '';
	if (document.search.fromdate) document.search.fromdate.value = '';
	if (document.search.todate) document.search.todate.value = '';
	submitSearch(0);
}

function LoadForm()
{
	x = document.search;
	if (x.keyword) x.keyword.focus();

	ChangeLocation();
}

function SetNextPage(startrange, perpage){
	setSearchInput('perpage', perpage);
	submitSearch(startrange);
}

function SetPrvPage(startrange, perpage){
	setSearchInput('perpage', perpage);
	submitSearch(startrange);
}

function SetFirstPage(startrange, perpage){
	setSearchInput('perpage', perpage);
	submitSearch(0);
}

function SetLastPage(perpage, totalrows){
	var result = totalrows % perpage;
	if (result == 0) result = perpage;
	var startrange = totalrows - result;
	if (startrange < 0) startrange = 0;
	setSearchInput('perpage', perpage);
	submitSearch(startrange);
}

// Hands the chosen master card back to the Master Card form (same fields as the PHP 5 page)
function UpdateRecord(PurId, MasterCardRef) {
	if (!window.opener || window.opener.closed || !opener.document.mastercard) {
		alert('Please open this list from the Master Card form.');
		return;
	}
	var numoflinked = document.search.numoflinked.value;

	var f = opener.document.mastercard;
	f.actionmode.value = '';
	f.linkedtext.value = numoflinked;
	f.replacedMasterCardId.value = PurId;
	f.MasterCardRef.value = MasterCardRef;
	f.count.value = 1;
	f.action = "Form_MasterCard.php";
	f.submit();
	self.close();
}

// PMode = 1 (after an action): reload the Master Card form and close this list
function ChangeLocation()
{
	if (document.search.PMode.value == 1) {
		if (window.opener && !window.opener.closed && opener.document.mastercard) {
			opener.document.mastercard.action = 'Form_MasterCard.php';
			opener.document.mastercard.submit();
		}
		self.close();
	}
}

function SelectSearchBy()
{
	submitSearch(0);
}

function SubmitForm()
{
	submitSearch(0);
}

function kd(e)
{
	x = document.search;
	var intKey = (window.Event) ? e.which : e.keyCode;

	if (intKey == 13) { //enter key
		x.btnSubmit.click();
		return false;
	}
	return true;
}

// Row highlight (SelectRecord() in the PHP 5 page), same as Form_LoadKeyline.php
function HighlightRow(Obj, purchaseid){
	var prev = document.getElementById('SelectedRec').value;
	if (prev != '' && document.getElementById(prev))
		document.getElementById(prev).style.backgroundColor = document.getElementById('SelectedRecColor').value;
	document.getElementById('SelectedRec').value = Obj.id;
	document.getElementById('SelectedRecColor').value = Obj.style.backgroundColor;
	document.getElementById('PurchaseId').value = purchaseid;
	Obj.style.backgroundColor = '#B8EDFF';
}
</script>

</head>
<body onLoad='LoadForm();' dir='$body_dir' class='$body_class'>
<div class='$rtl_class search-loader' id='loader'><img src='img/Loading_icon.gif' width=100% /></div>
<form name='search' method='post' action="" class='$rtl_class'>
<div class='$rtl_class form-rtl-page' dir='$body_dir'>
<input type='hidden' name='PurchaseId' id='PurchaseId' value=''>
<input type='hidden' name='SelectedRec' id='SelectedRec' value=''>
<input type='hidden' name='SelectedRecColor' id='SelectedRecColor' value=''>
HERE;

// Hidden state (names unchanged from the PHP 5 page)
echo "<input type='hidden' name='numoflinked' id='numoflinked' value='" . smcH($numoflinked) . "'>
<input type='hidden' name='numofreplaced' id='numofreplaced' value='" . smcH($numofreplaced) . "'>
<input type='hidden' name='message' value='" . smcH($message) . "'>";

$FormTitle = smcH($FieldArray['smctitle']['FieldLabel']);
$totalrows = 0;
$resultsearch = null;

// PMode = 1: nothing to search, ChangeLocation() closes the window
if ($PMode != '1') {

	$PRef = GetSQLValueString($keyword, 'text', $connection);
	$vKeyword = trim((string)$keyword);

	// Master cards that are confirmed and approved (same rule as PHP 5)
	$querywhere = " ifnull(mastercard_approve,0) = 1 and ifnull(mastercard_confirm,0) = 1 ";

	// Only the active search option is added to the WHERE (as in Form_LoadKeyline.php)
	if ($vKeyword != '') {
		if ($searchby == 1) {
			$vDay = GetSQLValueString(explodeword($vKeyword), 'text', $connection);
			$querywhere .= " and Mastercard.MasterCard_Date >= $vDay and Mastercard.MasterCard_Date < DATE_ADD($vDay, INTERVAL 1 DAY) ";
		} else if ($searchby == 3) {
			$querywhere .= " and (Mastercard.ledger_number Like Concat($PRef, '%') or clients.ledger_name Like Concat('%', $PRef, '%')) ";
		} else {
			// A reference can be typed as shown on the screen (MC00012 or MC00012_03): the number is what is stored
			$refNo = $vKeyword;
			$refLinked = '';
			if (preg_match('/^(?:MC)?0*(\d+)(?:_(\d+))?$/i', $vKeyword, $m)) {
				$refNo = $m[1];
				$refLinked = $m[2] ?? '';
			}
			// PHP 5 matched the start of the reference
			$querywhere .= " and Mastercard.MasterCard_Reference Like Concat(" . GetSQLValueString($refNo, 'text', $connection) . ", '%') ";
			if ($refLinked != '') $querywhere .= " and ifnull(Mastercard.mastercard_linked,0) = 1 and Mastercard.mastercard_linked_count = " . (int)$refLinked;
		}
	}

	// Date range (not with the Date search option), on the raw column so an index on MasterCard_Date can be used
	if (!($searchby == 1 && $vKeyword != '')) {
		if ($fromdate != '')
			$querywhere .= " and Mastercard.MasterCard_Date >= '" . mysqli_real_escape_string($connection, explodeword($fromdate)) . " 00:00:00' ";
		if ($todate != '')
			$querywhere .= " and Mastercard.MasterCard_Date <= '" . mysqli_real_escape_string($connection, explodeword($todate)) . " 23:59:59' ";
	}

	$queryfrom = " From Mastercard
		Left join clients on clients.ledger_number = Mastercard.ledger_number ";

	$querycount = " SELECT count(*) " . $queryfrom . " Where " . $querywhere;
	$resultcount = mysqli_query($connection, $querycount) or die(mysqli_error($connection));
	$rowcount = mysqli_fetch_row($resultcount);
	$totalrows = (int)($rowcount[0] ?? 0);

	if ($startrange >= $totalrows) $startrange = 0;

	$querysearch = " SELECT MasterCard_Id, MasterCard_Reference, date_format(MasterCard_Date, '%d/%m/%Y') as MasterCard_Date,
			Mastercard.ledger_number as Client_code, clients.ledger_name as ledger_name, product_desc,
			ifNull(mastercard_linked,0) as mastercard_linked, mastercard_linked_count " .
		$queryfrom .
		" Where " . $querywhere .
		" Order By Mastercard.MasterCard_Date Desc, MasterCard_Id Desc " .   // newest first, as PHP 5
		" limit " . (int)$startrange . "," . (int)$perpage;
	$resultsearch = mysqli_query($connection, $querysearch) or die(mysqli_error($connection));
}


echo "<div class='container-fluid'>";

/* ===================== TOP BAR (title + paging + Find / Cancel) ===================== */
echo "<div class='topbar'>";
echo "<div class='topbar-title'><div class='icon-wrap'><i class='fa fa-id-card'></i></div><span>$FormTitle</span></div>";
echo "<div class='topbar-center'>";
echo "<span class='topbar-perpage-label'>" . smcH($FieldArray['smcperpage']['FieldLabel']) . "</span>";
echo "<select class='topbar-select' name='perpage' id='perpage' onchange='RefreshPage();'>";
$perpages = (isset($config['trowlimit']) && is_array($config['trowlimit'])) ? $config['trowlimit'] : array(20, 30, 40, 50);
if (!in_array($perpage, $perpages)) $perpages[] = $perpage;
foreach ($perpages as $val) {
	echo " <option value='" . smcH($val) . "' ";
	if ($perpage == $val) echo "selected";
	echo ">" . smcH($val) . "</option> ";
}
echo "</select>";
echo "<div class='topbar-pager'>";
echo "<img class='topbar-nav-prev' src='img/previous.png' title='" . smcH($FieldArray['smcfirstpage']['FieldLabel']) . "' onclick=SetFirstPage(0,'" . $perpage . "')>";
$prvstartrange = $startrange - $perpage;
if ($prvstartrange < 0) $prvstartrange = 0;
echo "<img class='topbar-nav-prev' src='img/back.png' title='" . smcH($FieldArray['smcprevpage']['FieldLabel']) . "' onclick=SetPrvPage('" . $prvstartrange . "','" . $perpage . "')>";
$startrange1 = $startrange + $perpage;
if ($startrange1 >= $totalrows) $startrange1 = $startrange;
echo "<img class='topbar-nav-next' src='img/next.png' title='" . smcH($FieldArray['smcnextpage']['FieldLabel']) . "' onclick=SetNextPage('" . $startrange1 . "','" . $perpage . "')>";
echo "<img class='topbar-nav-next' src='img/next2.png' title='" . smcH($FieldArray['smclastpage']['FieldLabel']) . "' onclick=SetLastPage('" . $perpage . "','" . $totalrows . "')>";
echo "</div>";
echo "</div>";
echo "<div class='topbar-actions'>";
echo "<button type='button' class='btn-top btn-top-success' id='btnSubmit' name='btnSubmit' onclick='SubmitForm(); return false;' title='" . smcH($ButtonArray[6]['ButtonTitle']) . "'><i class='fa fa-search'></i><span>" . smcH($ButtonArray[6]['ButtonLabel']) . "</span></button>";
echo "<button type='button' class='btn-top btn-top-danger' id='btnCancel' name='btnCancel' onclick='clearform();return false;' title='" . smcH($ButtonArray[7]['ButtonTitle']) . "'><i class='fa fa-eraser'></i><span>" . smcH($ButtonArray[7]['ButtonLabel']) . "</span></button>";
echo "</div>";
echo "</div>";

/* ===================== FILTER CARDS ===================== */
echo "<div class='cards-row'>";
echo "  <div class='mini-card mini-card-compact'>";
echo "    <div class='mini-card-head'>" . smcH($FieldArray['smccardsearch']['FieldLabel']) . "</div>";
echo "    <div class='mini-card-body'>";
$fieldHtml = array();
$fieldHtml['keyword'] = "<input class='inputBox' type='text' name='keyword' id='keyword' value='" . smcH($keyword) . "' size='15' maxlength='100' onkeydown=\"return kd(event)\" /><input type='hidden' name='PMode' id='PMode' value='" . smcH($PMode) . "' />";

$s21 = $s22 = $s23 = '';
if ($searchby == 1)		$s21 = 'selected';
else if ($searchby == 3)	$s23 = 'selected';
else					$s22 = 'selected';

$fieldHtml['searchby'] = "<select class='inputBox' name='searchby' id='searchby' onchange='SelectSearchBy();'>
	<option " . $s21 . " value='1'>" . smcH($FieldArray['smcsearchdate']['FieldLabel']) . "</option>
	<option " . $s22 . " value='2'>" . smcH($FieldArray['smcsearchref']['FieldLabel']) . "</option>
	<option " . $s23 . " value='3'>" . smcH($FieldArray['smcsearchclient']['FieldLabel']) . "</option>
</select>";
$searchFields = array(
	array($FieldArray['smcsearchby']['FieldLabel'], 'keyword'),
	array($FieldArray['smcsearchoption']['FieldLabel'], 'searchby')
);
foreach ($searchFields as $field) {
	echo "<div class='field-row'><span class='style1'>" . smcH($field[0]) . "</span><div class='field-control-wrap'>" . $fieldHtml[$field[1]] . "</div></div>";
}
echo "    </div>";
echo "  </div>";

echo "  <div class='mini-card mini-card-date'>";
echo "    <div class='mini-card-head'>" . smcH($FieldArray['smccarddaterange']['FieldLabel']) . "</div>";
echo "    <div class='mini-card-body'>";
$fieldHtml = array();
$fieldHtml['fromdate'] = "<input type='text' class='inputBox mydate' name='fromdate' id='fromdate' value='" . smcH($fromdate) . "' onkeydown=\"return kd(event);\">";
$fieldHtml['todate'] = "<input type='text' class='inputBox mydate' name='todate' id='todate' value='" . smcH($todate) . "' onkeydown=\"return kd(event);\">";
$dateFields = array(
	array($FieldArray['smcfrom']['FieldLabel'], 'fromdate'),
	array($FieldArray['smcto']['FieldLabel'], 'todate')
);
foreach ($dateFields as $field) {
	echo "<div class='field-row'><span class='style1'>" . smcH($field[0]) . "</span><div class='field-control-wrap'>" . $fieldHtml[$field[1]] . "</div></div>";
}
echo "    </div>";
echo "  </div>";
echo "</div>"; // close cards-row


if ($message != '') {
	echo "<div class='list-grid-section'><div class='text-center searchmessage'>" . smcH($message) . "</div></div>";
}

/* ===================== GRID ===================== */
echo "<div class='list-grid-section'>
		<div id='listDiv' class='table-responsive list-grid-wrap'>
			<table class='table table-bordered mb-0'>";

$columns = array(
	'smcreference'  => '',
	'smcdate'       => 'col-nowrap',
	'smcclientcode' => '',
	'smcclientname' => '',
	'smcproddesc'   => ''
);

echo "<thead><tr class='Search-Label-bg'>";
echo "<th class='text-alignment pa-actions'><span class='style1'>&nbsp;</span></th>";
foreach ($columns as $code => $cls) {
	if (!$FieldArray[$code]['IsHidden'])
		echo "<th class='text-alignment $cls'><span class='style1'>" . smcH($FieldArray[$code]['FieldLabel']) . "</span></th>";
}
echo "</tr></thead><tbody>";


$ct = 0;
$editHint = smcH($FieldArray['smcedit']['FieldLabel']);

while ($resultsearch && ($rows = mysqli_fetch_array($resultsearch))) {

	$ct += 1;

	$MasterCard_Id = (int)$rows['MasterCard_Id'];
	$masterCardRef = (string)$rows['MasterCard_Reference'];   // raw number, handed to the Master Card form

	// Reference as shown on the screen: MC + 5-digit number (+ _NN linked count), same as PHP 5
	if (strlen($masterCardRef) <= 4) {
		$MasterCard_Reference = "MC" . str_pad($masterCardRef, 5, '0', STR_PAD_LEFT);
	} else {
		$MasterCard_Reference = "MC0" . $masterCardRef;
	}
	if ($rows['mastercard_linked'] == 1) {
		$linkedCount = (string)$rows['mastercard_linked_count'];
		$MasterCard_Reference .= ((int)$linkedCount < 10) ? "_0" . $linkedCount : "_" . $linkedCount;
	}
	$refJs = smcH(json_encode($masterCardRef));

	echo "<tr class='pa-rail pa-rail--confirmed' onMouseOver=\"style.cursor='pointer'\" id='row$ct' onclick='HighlightRow(this, $MasterCard_Id);' ondblclick='UpdateRecord($MasterCard_Id, $refJs);'>";

	// --- Row action: load this master card into the Master Card form ---
	echo "<td class='text-alignment pa-actions'><div class='pa-actions-grid'>";
	echo "<i class='fa fa-pen-to-square pa-row-ico' onclick='event.stopPropagation(); UpdateRecord($MasterCard_Id, $refJs);' title='$editHint'></i>";
	echo "</div></td>";

	// --- Data cells (each under the same gate as its <th>) ---
	if (!$FieldArray['smcreference']['IsHidden'])
		echo "<td class='text-alignment'><b class='pa-refnum'>" . smcH($MasterCard_Reference) . "</b></td>";
	if (!$FieldArray['smcdate']['IsHidden'])
		echo "<td class='text-alignment col-nowrap'><b>" . smcH($rows['MasterCard_Date']) . "</b></td>";
	if (!$FieldArray['smcclientcode']['IsHidden'])
		echo "<td class='text-alignment'><span class='pa-client-code'>" . smcH($rows['Client_code']) . "</span></td>";
	if (!$FieldArray['smcclientname']['IsHidden'])
		echo "<td class='text-alignment'><span class='pa-client-name'>" . smcH($rows['ledger_name']) . "</span></td>";
	if (!$FieldArray['smcproddesc']['IsHidden'])
		echo "<td class='text-alignment'><span class='style5'>" . smcH($rows['product_desc']) . "</span></td>";

	echo '</tr>';
}

if ($PMode != '1' && $ct == 0) {
	echo "<tr><td colspan='20' class='text-center'><span class='style5'>" . smcH($FieldArray['smcnorecords']['FieldLabel']) . "</span></td></tr>";
}

echo "</tbody></table></div></div>";


$h_today = smcH($FieldArray['btndatetoday']['FieldLabel']);
$h_clear = smcH($FieldArray['btndateclear']['FieldLabel']);

print <<<HERE

</div>
</div>
</form>
<script>
flatpickr(".mydate", {
	dateFormat: "d/m/Y",
	disableMobile: "true",
	animate: true,
	onReady: function(selectedDates, dateStr, instance) {
		const footer = document.createElement("div");
		footer.className = "fp-cal-footer";
		const todayBtn = document.createElement("button");
		todayBtn.type = "button";
		todayBtn.innerHTML = "$h_today";
		todayBtn.onclick = () => instance.setDate(new Date());
		const clearBtn = document.createElement("button");
		clearBtn.type = "button";
		clearBtn.innerHTML = "$h_clear";
		clearBtn.onclick = () => instance.clear();
		footer.appendChild(todayBtn);
		footer.appendChild(clearBtn);
		instance.calendarContainer.appendChild(footer);
	}
});
</script>
</body>

HERE;
mysqli_close($connection);

function explodeword($date)
{
	if (empty($date)) {
		return $date;
	}

	if (strpos($date, '-') !== false && strpos($date, '/') === false) {
		return $date;
	}

	$trim = explode('/', $date);
	if (count($trim) === 3) {
		return $trim[2] . "-" . $trim[1] . "-" . $trim[0];
	}

	return $date;
}
