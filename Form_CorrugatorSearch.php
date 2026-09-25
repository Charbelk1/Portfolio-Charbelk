<?php

session_start();
header("Content-type: text/html; charset=" . $_SESSION['encodingmode']);
if ($_SESSION['dbName'] == "") {
	header("location:../index.php");
}


include("config.inc.php");
include("function.inc.php");
include("Functions_Trans.php");
include("Functions_UserAccess.php");

$connection = mysqli_connect($config['dbServer'], $config['dbUser'], $config['dbPass']) or die("Could not connect to DB");
mysqli_select_db($connection, $_SESSION['dbName']) or die("Could not find DB");


if ($_SESSION['encodingmode'] == 'utf8' || $_SESSION['encodingmode'] == 'utf-8') {
	mysqli_query($connection, "SET NAMES 'utf8'");
	mysqli_query($connection, 'SET CHARACTER SET utf8');
	mysqli_set_charset($connection, 'utf8');
}
$fromdate   = $_POST["fromdate"] ?? '';
$todate     = $_POST["todate"] ?? '';
/*
$querydate = "select search_startingdate from setup";
$resultdate = mysqli_query($connection, $querydate) or die(mysqli_error($connection));
while ($rows = mysqli_fetch_array($resultdate)) {
	extract($rows);
}
	*/
$maxperpage = getSmodulevalue('maxperpage', 'string', $_SESSION['logincompany'], $_SESSION['dbLabel'], $connection);
$search_startingdate = getSmodulevalue('search_startingdate', 'string', $_SESSION['logincompany'], $_SESSION['dbLabel'], $connection);

$MainCompanyName = explode('_', $_SESSION['dbName']);

$QueryDir = " Select Date_Format(startdate,'%d/%m/%Y')
			From " . $_SESSION['dbLabel'] . ".accessschema " .
	" Where  Upper(accessschema_name) = Upper('" . $MainCompanyName[0] . "') " .
	" and Upper(Diryear) = Upper('" . $MainCompanyName[1] . "') and access_id = " . $_SESSION['accessid'] . " ";
//echo $QueryDir;
$ResultDir = mysqli_query($connection, $QueryDir) or die(mysqli_error($connection));
$RowsDir = mysqli_fetch_row($ResultDir);
$StartFinancialDate = $RowsDir[0] ?? '';


if ($fromdate == '') {
	if ($search_startingdate == -1) {
		$fromdate = $StartFinancialDate;
	} else if ($search_startingdate == 1) {
		$fromdate = date('01/m/Y');
	} else if ($search_startingdate == 0 || $search_startingdate == '') {
		$search_startingdate = '';
		$added_timestamp = strtotime('-' . $search_startingdate . ' month', time());
		$fromdate = date('01/m/Y', $added_timestamp);
	}
}
if ($todate == '')
	$todate =  date('d/m/Y');

$query_login = "select ifnull(access_belongstogrp,0) as FI_BelongsToGrp
			  from " . $_SESSION['dbLabel'] . ".access

			  where access_code='" . $_SESSION['useraccount'] . "' and access_id='" . $_SESSION['accessid'] . "' ";
$resultlogin = mysqli_query($connection, $query_login) or die(mysqli_error($connection));
while ($rows = mysqli_fetch_array($resultlogin)) {
	extract($rows);
}

/*
$querylog = " select ifNull(FI_PurchasesDel,0) As FI_PurchasesDel, ifNull(FI_PurchasesMod,0) As FI_PurchasesMod, ifNull(FI_PurchasesApp,0) As FI_PurchasesApp, ifNull(login_belongstogrp,0) As FI_BelongsToGrp, " .
	" ifNull(ShowTransaction,0) As ShowTransaction, ifNull(Login_Purchase_Pending,0) As Login_Purchase_Pending, ifnull(FI_PostPurchase,0) as FI_PostPurchase " .
	" from " . $_SESSION['dbLabel'] . ".login where user_account='" . $_SESSION['useraccount'] . "' and Upper(Login_Schema) = Upper('" . $_SESSION['dbName'] . "')  ";
$resultlog = mysqli_query($connection, $querylog) or die(mysqli_error($connection));
while ($rows = mysqli_fetch_array($resultlog)) {
	extract($rows);
}
	*/

// CJR form access (same getformdetails() row layout Form_DeliverySearch.php reads for 'Sales')
$sub_result = getformdetails('CJR', $_SESSION['accessschemaid'], $_SESSION['dbLabel']);
$row_login = mysqli_fetch_row($sub_result);
$Form_View = $row_login[7] ?? '';
$loginaccess = $Form_View;
$FI_CJRApp	= $row_login[1] ?? 0;
$FI_CJRMod  = $row_login[2] ?? 0;
$FI_CJRDel  = $row_login[3] ?? 0;
$FI_Print = $row_login[4] ?? 0;
$FI_Preview = $row_login[5] ?? 0;
$FI_Excel = $row_login[6] ?? 0;
if ($FI_Print == '') $FI_Print = 0;

//Read From Module Table
$querymodule = " Select ifNull(Module_Project,0) As Module_Project, ifNull(Module_Costcenter,0) As Module_CostCenter, " .
	" ifNull(Module_ProjectParent,0) As Module_ProjectParent, ifNull(Module_CostCenterParent,0) As Module_CostCenterParent, " .
	" ifNull(Module_PostToJournal,0) As Module_PostToJournal, ifNull(Module_PostToJournal,0) As Module_Purchase_Pending From Module";
$resultmodule = mysqli_query($connection, $querymodule) or die(mysqli_error($connection));
while ($rowmodule = mysqli_fetch_array($resultmodule)) {
	extract($rowmodule);
}


//Access Mode On Insert/Edit/Delete
$CjrDelMode  = 1;
$CjrEditMode = 1;
$CjrPrevMode = 1;

$PrevHint = '';
$EditHint = '';
$DelHint = '';

/*
$querysetup = "select DecimalBase, DecimalBase1, DecimalBase2, base1, base2, grid_color1, grid_color2, ifNull(Setup_MultiLangPrint,0) as Setup_MultiLangPrint from setup";
$resultsetup = mysqli_query($connection, $querysetup) or die(mysqli_error($connection));
while ($rowsetup = mysqli_fetch_array($resultsetup)) {
	extract($rowsetup);
}
	*/
$DecimalBase =  getSmodulevalue('DecimalBase', 'string', $_SESSION['logincompany'], $_SESSION['dbLabel'], $connection);
$DecimalBase1 =  getSmodulevalue('DecimalBase1', 'string', $_SESSION['logincompany'], $_SESSION['dbLabel'], $connection);
$DecimalBase2 =  getSmodulevalue('DecimalBase2', 'string', $_SESSION['logincompany'], $_SESSION['dbLabel'], $connection);
$base1 =  getSmodulevalue('base1', 'string', $_SESSION['logincompany'], $_SESSION['dbLabel'], $connection);
$base2 =  getSmodulevalue('base2', 'string', $_SESSION['logincompany'], $_SESSION['dbLabel'], $connection);
$grid_color1 =  getSmodulevalue('grid_color1', 'string', $_SESSION['logincompany'], $_SESSION['dbLabel'], $connection);
$grid_color2 =  getSmodulevalue('grid_color2', 'string', $_SESSION['logincompany'], $_SESSION['dbLabel'], $connection);
$Setup_MultiLangPrint =  getSmodulevalue('Setup_MultiLangPrint', 'string', $_SESSION['logincompany'], $_SESSION['dbLabel'], $connection);

$LockViewPeriod = getConditionsvalue('LockViewPeriod', $_SESSION['accessid'], $_SESSION['logincompany'], $_SESSION['dbLabel'], $connection);
$ViewPeriodDate = getConditionsvalue('ViewPeriodDate', $_SESSION['accessid'], $_SESSION['logincompany'], $_SESSION['dbLabel'], $connection);
$ViewLockDaily = getConditionsvalue('ViewLockDaily', $_SESSION['accessid'], $_SESSION['logincompany'], $_SESSION['dbLabel'], $connection);
$ViewNumberOfDays = getConditionsvalue('ViewNumberOfDays', $_SESSION['accessid'], $_SESSION['logincompany'], $_SESSION['dbLabel'], $connection);
$rowsperiod = 1;

$FieldArray   = array();
$ButtonArray   = array();

$FieldArray['cjrstitle']['FieldLabel'] = 'List of CJR';
$FieldArray['cjrsperpage']['FieldLabel'] = 'Per Page';
$FieldArray['cjrssearchby']['FieldLabel'] = 'Search By';
$FieldArray['cjrssearchoption']['FieldLabel'] = 'Search Option';
$FieldArray['cjrsfrom']['FieldLabel'] = 'From';
$FieldArray['cjrsto']['FieldLabel'] = 'To';

$FieldArray['cjrssearchdate']['FieldLabel'] = 'Date (yyyy-mm-dd)';
$FieldArray['cjrsreference']['FieldLabel'] = 'CJR Reference';
$FieldArray['cjrsdate']['FieldLabel'] = 'Date';
$FieldArray['cjrssofref']['FieldLabel'] = 'SOF Reference';
$FieldArray['cjrsmcref']['FieldLabel'] = 'MC Reference';
$FieldArray['cjrsclient']['FieldLabel'] = 'Client';
$FieldArray['cjrsitemdesc']['FieldLabel'] = 'Item Description';
$FieldArray['cjrsstatus']['FieldLabel'] = 'Status';

$FieldArray['cjrsprev']['FieldLabel'] = 'Preview';
$FieldArray['cjrseditrec']['FieldLabel'] = 'Edit Record';
$FieldArray['cjrsdelrec']['FieldLabel'] = 'Delete Record';
$FieldArray['cjrsexistsinpend']['FieldLabel'] = 'Exists in Pending';
$FieldArray['cjrsconfirm']['FieldLabel'] = 'Confirm CJR';
$FieldArray['cjrsapprove']['FieldLabel'] = 'Approve CJR';
$FieldArray['cjrsdisapprove']['FieldLabel'] = 'Disapprove CJR';

$FieldArray['cjrsfirstpage']['FieldLabel'] = 'First Page';
$FieldArray['cjrsprevpage']['FieldLabel'] = 'Previous';
$FieldArray['cjrsnextpage']['FieldLabel'] = 'Next';
$FieldArray['cjrslastpage']['FieldLabel'] = 'Last Page';

$FieldArray['cjrscardsearch']['FieldLabel'] = 'Search';
$FieldArray['cjrscarddaterange']['FieldLabel'] = 'Date Range';
$FieldArray['cjrslegend']['FieldLabel'] = 'Legend';
$FieldArray['cjrsstconf']['FieldLabel'] = 'Confirmed';
$FieldArray['cjrsstunconf']['FieldLabel'] = 'Unconfirmed';
$FieldArray['cjrsstapproved']['FieldLabel'] = 'Approved';

$FieldArray['cjrsfind']['FieldLabel'] = 'Find';
$FieldArray['cjrscancel']['FieldLabel'] = 'Cancel';
$FieldArray['btndatetoday']['FieldLabel'] = 'Today';
$FieldArray['btndateclear']['FieldLabel'] = 'Clear';

foreach ($FieldArray as $fieldKey => $fieldInfo) {
	if (!isset($FieldArray[$fieldKey]['IsHidden'])) $FieldArray[$fieldKey]['IsHidden'] = false;
	if (!isset($FieldArray[$fieldKey]['MandatoryLabel'])) $FieldArray[$fieldKey]['MandatoryLabel'] = '';
	if (!isset($FieldArray[$fieldKey]['FieldMod'])) $FieldArray[$fieldKey]['FieldMod'] = '';
}

$vMandatoryFields = '';
$sub_result = get_mandatory('CJR', $_SESSION['accessschemaid'], $_SESSION['dbLabel'], $connection);
while ($sub_result && ($sub_row = mysqli_fetch_row($sub_result))) {
	$vFieldLabel = '';
	$vFieldLabel = ((isset($_SESSION['languageid']) && $_SESSION['languageid'] != '') ? get_labelnames_bylanguage('CJR', $_SESSION['dbLabel'], $_SESSION['languageid'], $sub_row[2], $connection) : get_labelnames('CJR', $_SESSION['dbLabel'], $_SESSION['accessid'], $sub_row[2], $connection));

	$isHidden = ($sub_row[5] == '1');

	$vMandatoryLabel = '';

	if (!$isHidden && $sub_row[1] == '1') {
		$vMandatoryFields = $vMandatoryFields . $sub_row[2] . ',';
		$vMandatoryLabel = "<span class='style2'>*</span>&nbsp;";
	} else {
		$vMandatoryLabel = "";
	}

	$vReadOnly = '';
	if ($sub_row[0] == '0')
		$vReadOnly = 'readonly';

	if ($vFieldLabel != '') $FieldArray[$sub_row[2]]["FieldLabel"] = $vFieldLabel;
	$FieldArray[$sub_row[2]]["MandatoryLabel"] = $vMandatoryLabel;
	$FieldArray[$sub_row[2]]["FieldMod"]       = $vReadOnly;
	$FieldArray[$sub_row[2]]["IsHidden"]       = $isHidden;
}
$vMandatoryFields = substr($vMandatoryFields,  0, -1);

$sub_result = get_labelbuttons($_SESSION['dbLabel'], $_SESSION['accessid'], $connection);
while ($sub_row = mysqli_fetch_row($sub_result)) {
	$ButtonArray[$sub_row[0]]['ButtonLabel'] = $sub_row[1];
	$ButtonArray[$sub_row[0]]['ButtonTitle'] = $sub_row[2];
}
// Find / Cancel fall back to the captions above when the button labels aren't set up
if (empty($ButtonArray[6]['ButtonLabel'])) $ButtonArray[6] = array('ButtonLabel' => $FieldArray['cjrsfind']['FieldLabel'], 'ButtonTitle' => $FieldArray['cjrsfind']['FieldLabel']);
if (empty($ButtonArray[7]['ButtonLabel'])) $ButtonArray[7] = array('ButtonLabel' => $FieldArray['cjrscancel']['FieldLabel'], 'ButtonTitle' => $FieldArray['cjrscancel']['FieldLabel']);

$ui = initFormLanguageUI('CJR', $_SESSION['dbLabel'], $connection, $FieldArray, $ButtonArray);
$rtl_class = $ui['container_class'];
$alignment = $ui['align'];
$is_rtl = $ui['is_rtl'];
$body_dir = $ui['dir'];
$body_class = $ui['body_class'];
$Language = $ui['language_name'];

/*
$queryperiod = " Select ifNull(LockViewPeriod,0) As LockViewPeriod, ViewPeriodDate, ifNull(ViewLockDaily,0) As ViewLockDaily, ViewNumberOfDays, ... " .
	" From " . $_SESSION['dbLabel'] . ".ListUserDir  " . ...
	*/

$queryproject = " select distinct project.project_code, project_name " .
	" from project left join " . $_SESSION['dbLabel'] . ".loginstk on (($Module_ProjectParent=1 and project.project_code like concat(ifnull(loginstk.project_code,project.project_code),'%')) or ($Module_ProjectParent=0 and project.project_code = loginstk.project_code )) " .
	" where user_account='" . $_SESSION['useraccount'] . "' " .
	" and (loginstk.project_code is not null and loginstk.project_code != '') " .
	" order by project.project_code";
$projectresult = mysqli_query($connection, $queryproject) or die(mysqli_error($connection));
$projectexist  = mysqli_num_rows($projectresult);


$querycostcent = " select distinct costcent.costcent_code, costcent_name " .
	" from costcent left join " . $_SESSION['dbLabel'] . ".loginstk on (($Module_CostCenterParent=1 and costcent.costcent_code like concat(ifnull(loginstk.costcent_code,costcent.costcent_code),'%')) or ($Module_CostCenterParent=0 and costcent.costcent_code = loginstk.costcent_code )) " .
	" where user_account='" . $_SESSION['useraccount'] . "' " .
	" and (loginstk.costcent_code is not null and loginstk.costcent_code != '') " .
	" order by costcent.costcent_code";
$costcentresult = mysqli_query($connection, $querycostcent) or die(mysqli_error($connection));
$costcentexist  = mysqli_num_rows($costcentresult);

$queryledger = " select distinct ledger_number, ledger_include " .
	" from " . $_SESSION['dbLabel'] . ".ledgerstk  " .
	" where user_account='" . $_SESSION['useraccount'] . "' " .
	" and (ledger_number is not null and ledger_number != '') " .
	" order by ledger_number";
$ledgerresult = mysqli_query($connection, $queryledger) or die(mysqli_error($connection));
$ledgerexist  = mysqli_num_rows($ledgerresult);
$rows = mysqli_fetch_array($ledgerresult);
$ledgerinclude = $rows[1] ?? 0;
if (empty($ledgerinclude)) $ledgerinclude = 0;

//Get Default Project
$querydflproject = "select project_code as defaultproject from " . $_SESSION['dbLabel'] . ".loginstk where user_account='" . $_SESSION['useraccount'] . "' and default_project=1 ";
$resultdflproject = mysqli_query($connection, $querydflproject) or die(mysqli_error($connection));
while ($rows = mysqli_fetch_array($resultdflproject)) {
	extract($rows);
}

//Get Default Coscenter
$querydflcostcent = "select costcent_code as defaultcostcent from " . $_SESSION['dbLabel'] . ".loginstk where user_account='" . $_SESSION['useraccount'] . "' and default_costcenter=1 ";
$resultdflcostcent = mysqli_query($connection, $querydflcostcent) or die(mysqli_error($connection));
while ($rows = mysqli_fetch_array($resultdflcostcent)) {
	extract($rows);
}

$PMode    	= $_POST['PMode'] ?? '';
if ($PMode == "") $PMode = $_GET["PMode"] ?? '';
$keyword    = $_POST['keyword'] ?? '';
if ($keyword == "") $keyword = $_GET["keyword"] ?? '';
$searchby   = $_POST['searchby'] ?? '';
if ($searchby == "") $searchby = $_GET["searchby"] ?? '';
$orderby    = $_POST['orderby'] ?? '';
if ($orderby == "") $orderby = $_GET["orderby"] ?? '';
$perpage    = $_POST['perpage'] ?? '';
if ($perpage == "") $perpage = $_GET["perpage"] ?? '';
$startrange = $_POST['startrange'] ?? '';
if ($startrange == "") $startrange = $_GET["startrange"] ?? '';
$option  	= $_POST['option'] ?? '';
if ($option == "") $option = $_GET["option"] ?? '';
$status    	= $_POST['status'] ?? '';
if ($status == "") $status = $_GET["status"] ?? '';
$message    = $_POST['message'] ?? '';
if ($message == "") $message = $_GET["message"] ?? '';

$PurchaseId	= $_POST['PurchaseId'] ?? '';
if ($PurchaseId == "") $PurchaseId = $_GET["PurchaseId"] ?? '';
$PProjectCode 	= $_POST['PProjectCode'] ?? ($_GET["PProjectCode"] ?? null);
$PCostCentCode	= $_POST['PCostCentCode'] ?? ($_GET["PCostCentCode"] ?? null);

if ((!isset($PProjectCode)) && $Module_Project == 1)
	$PProjectCode = $defaultproject ?? '';
if ((!isset($PCostCentCode)) && $Module_CostCenter == 1)
	$PCostCentCode = $defaultcostcent ?? '';
$PProjectCode = "" . $PProjectCode;
$PCostCentCode = "" . $PCostCentCode;

if (empty($orderby))  $orderby = 1;
if (empty($searchby)) $searchby = 2;

if ($startrange == '')
	$startrange = 0;
if ($perpage == '')
	$perpage = $maxperpage;
if ($perpage == '' || $perpage == 0)
	$perpage = 20;

$cssStyleVersion = @filemtime(__DIR__ . '/css/style.css') ?: time();

print <<<HERE
<head>
<meta http-equiv="Content-Type" content="text/html; charset={$_SESSION['encodingmode']}" />
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{$FieldArray['cjrstitle']['FieldLabel']}</title>
<link rel="stylesheet" type="text/css" media="all" href="css/style.css?v=$cssStyleVersion">

<link rel="stylesheet" type="text/css" media="all" href="r-main-css.css">

<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script type="text/javascript" src="js/grid/jquery-1.9.1.js"></script>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<script type="text/javascript" language="javascript">

// Sets (or creates) a hidden field on the search form, same approach as Form_DeliverySearch.php's paging functions
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

function clearform()
{
  document.search.keyword.value='';
  document.search.fromdate.value = '';
  document.search.todate.value = '';

  document.search.action='Form_CorrugatorSearch.php';
  document.search.submit();

}

function LoadForm()
{
	x=document.search;
	x.keyword.focus();

	ChangeLocation();
}

function SetNextPage(startrange,perpage){
	setSearchInput('startrange', startrange);
	setSearchInput('perpage', perpage);
	document.search.action = 'Form_CorrugatorSearch.php';
	document.search.submit();
}

function SetPrvPage(startrange,perpage){
	setSearchInput('startrange', startrange);
	setSearchInput('perpage', perpage);
	document.search.action = 'Form_CorrugatorSearch.php';
	document.search.submit();
}

function SetFirstPage(startrange,perpage){
	setSearchInput('startrange', 0);
	setSearchInput('perpage', perpage);
	document.search.action = 'Form_CorrugatorSearch.php';
	document.search.submit();
}

function SetLastPage(perpage,totalrows){
	var result = totalrows % perpage;
	if (result == 0) result = perpage;
	var startrange = totalrows - result;
	if (startrange < 0) startrange = 0;
	setSearchInput('startrange', startrange);
	setSearchInput('perpage', perpage);
	document.search.action = 'Form_CorrugatorSearch.php';
	document.search.submit();
}


function DeleteRecord(PurId, PurRef){

	if (PurId != '') {
		if (confirm ( "Are you sure you want to Delete this record with reference = "+PurRef+" ?") )
		{
			document.search.action="Form_Corrugator_Actions.php?mainmode=delete&corrugator_id="+PurId;
			document.search.submit();
		}
	}

}


function UpdateRecord(PurId){

	opener.document.mastercard.txtCorrugatorId.value=PurId;
	opener.document.mastercard.mainaction.value='edit';
	opener.document.mastercard.actionmode.value='';

	opener.document.mastercard.action='Form_Corrugator_Actions.php?mainmode=search&txtCorrugatorId='+PurId;
	opener.document.mastercard.submit();

	self.close();
}


function PrintPreview(PurId)
{
	var scrWidth = screen.width;
	var scrHeight = screen.height;
	var left = (scrWidth / 2)-(scrWidth * 0.99/2);
	var top = (scrHeight / 2)-(scrHeight * 0.99/2);

	var str = 'Form_CJR_Preview.php?PurchaseId='+PurId;
	var newWin = window.open(str, 'Form_CJR_Preview','width=' + (scrWidth * 0.99) +',height=' + (scrHeight * 0.99) +',top=' + top +',left=' + left +',toolbar=no,location=no,directories=no,status=yes,menubar=no,scrollbars=yes,copyhistory=no,resizable=yes');
	if (newWin) newWin.focus();
}


function ChangeLocation()
{
	if (document.search.PMode.value == 1) {
		opener.document.mastercard.action='Form_Corrugator.php';
		opener.document.mastercard.submit();
		self.close();
	}
}

function SelectSearchBy()
{
	document.search.keyword.value='';
	setSearchInput('startrange', 0);
	document.search.action='Form_CorrugatorSearch.php';
	document.search.submit();
}

function SubmitForm()
{
	setSearchInput('startrange', 0);
	document.search.action='Form_CorrugatorSearch.php';
	document.search.submit();
}

function kd(e)
{
	x=document.search;
	var intKey = (window.Event) ? e.which : e.keyCode;

	if (intKey == 13) { //enter key
		x.btnSubmit.click();
		return false;
	}
	return true;
}

function RefreshPage()
{
	setSearchInput('startrange', 0);
	document.search.action='Form_CorrugatorSearch.php';
	document.search.submit();
}


function SelectRecord(Obj, SelectedRec, SelectedRecColor, purchaseid){

	if (document.getElementById(SelectedRec).value=='')
	{
		document.getElementById(SelectedRec).value = Obj.id;
		document.getElementById(SelectedRecColor).value = Obj.style.backgroundColor;
	}
	else {

		var prevobj = document.getElementById(SelectedRec).value;
		if (document.getElementById(prevobj))
			document.getElementById(prevobj).style.backgroundColor = document.getElementById(SelectedRecColor).value;

		document.getElementById(SelectedRec).value = Obj.id;
		document.getElementById(SelectedRecColor).value = Obj.style.backgroundColor;
	}

	document.getElementById('PurchaseId').value = purchaseid;
	document.getElementById(Obj.id).style.backgroundColor ='#B8EDFF';

}

function confirmCorrugator(Corrugator_Id, mastercard_ref){
	if (Corrugator_Id != '') {
		if (confirm ( "Are you sure you want to Confirm this record with reference = "+mastercard_ref+" ?") )
		{
			document.search.action="Form_Corrugator_Actions.php?mainmode=confirmcorrugator&corrugator_id="+Corrugator_Id;
			document.search.submit();
		}
	}
}
function ApproveCorrugator(Corrugator_Id, mastercard_ref, option){
	if(option == 1){
		var mode = 'approveCorrugator';
		var action ='approve';
	}
	else {
		var mode = 'disapproveCorrugator';
		var action ='Disapprove';
	}
	if (Corrugator_Id != '') {
		if (confirm ( "Are you sure you want to "+action+" this record with reference = "+mastercard_ref+" ?") )
		{
			document.search.action="Form_Corrugator_Actions.php?mainmode="+mode+"&corrugator_id="+Corrugator_Id;
			document.search.submit();
		}
	}
}
</script>

</head>
<body onLoad='LoadForm();' dir='$body_dir' class='$body_class'>
<form name='search' method='post' action="" class='$rtl_class'>
<div class='$rtl_class form-rtl-page' dir='$body_dir'>
<input type='hidden' name='PurchaseId' id='PurchaseId' value=''>
<input type='hidden' name='ReportSelected' id='ReportSelected' value=''>
<input type='hidden' name='SelectedRec' id='SelectedRec' value=''>
<input type='hidden' name='SelectedRecColor' id='SelectedRecColor' value=''>
HERE;

$PRef = GetSQLValueString($keyword, 'text', $connection);

if (empty($ViewPeriodDate))
	$ViewPeriodDate = 'Null';
if (empty($ViewNumberOfDays))
	$ViewNumberOfDays = 1;

$querysearch = " SELECT corrugator_id as corrugator_id, jobcardid AS JobCard_Id, jobcardid2 AS JobCard_Id2, corrugator_reference as corrugator_reference,date_format(corrugator_date,'%d/%m/%Y') as corrugator_date,
related_salesorder as related_salesorder,related_mastercard as related_mastercard ,corrugator_confirm as corrugator_confirm , corrugator_approve as corrugator_approve
From corrugator1
left join jobcard    on corrugator1.jobcardid    = jobcard.jobcard_id
                     or corrugator1.jobcardid2   = jobcard.jobcard_id
left join mastercard on mastercard.mastercard_id = jobcard.related_mastercard
left join sorder     on sorder.sorder_id         = jobcard.related_salesorder
left join clients    on clients.Ledger_number    = jobcard.Clientcode ";

$querysearch = $querysearch .
	" Where (($searchby = 2 and corrugator_reference Like Concat('%',ifNull($PRef, corrugator_reference),'%') and date_format(corrugator_date,'%Y-%m-%d') >= '" . explodeword($fromdate) . "'  and  date_format(corrugator_date,'%Y-%m-%d') <= '" . explodeword($todate) . "')   Or " .
	"        ($searchby = 1 and date_format(corrugator_date,'%Y-%m-%d') = ifNull($PRef, date_format(corrugator_date,'%Y-%m-%d')) ) Or  " .
	"        ($searchby = 4 and mastercard.mastercard_reference Like Concat('%',ifNull($PRef, mastercard.mastercard_reference),'%') and date_format(corrugator_date,'%Y-%m-%d') >= '" . explodeword($fromdate) . "'  and  date_format(corrugator_date,'%Y-%m-%d') <= '" . explodeword($todate) . "') Or  " .
	"        ($searchby = 5 and sorder_reference Like Concat('%',ifNull($PRef, sorder_reference),'%') and date_format(corrugator_date,'%Y-%m-%d') >= '" . explodeword($fromdate) . "'  and  date_format(corrugator_date,'%Y-%m-%d') <= '" . explodeword($todate) . "') Or  " .
	"        ($searchby = 3 and (Clientcode Like Concat('%',ifNull($PRef, Clientcode),'%') or clients.Ledger_name Like Concat(ifNull($PRef,  clients.Ledger_name),'%')
			 ) and date_format(corrugator_date,'%Y-%m-%d') >= '" . explodeword($fromdate) . "'  and  date_format(corrugator_date,'%Y-%m-%d') <= '" . explodeword($todate) . "'))";


$querysearch = $querysearch . " group by corrugator_id";
$querysearch = $querysearch . " Order By corrugator_reference Desc ";


$resultsearch = mysqli_query($connection, $querysearch) or die(mysqli_error($connection));
$totalrows = mysqli_num_rows($resultsearch);


$querysearch = $querysearch . " limit " . $startrange . "," . $perpage;
//echo $querysearch;
$resultsearch = mysqli_query($connection, $querysearch) or die(mysqli_error($connection));


$FormTitle = $FieldArray['cjrstitle']['FieldLabel'];

echo "<div class='container-fluid'>";

/* ===================== TOP BAR (title + paging + Find / Cancel) ===================== */
echo "<div class='topbar'>";
echo "<div class='topbar-title'><div class='icon-wrap'><i class='fa fa-layer-group'></i></div><span>$FormTitle</span></div>";
echo "<div class='topbar-center'>";
echo "<span class='topbar-perpage-label'>" . $FieldArray['cjrsperpage']['FieldLabel'] . "</span>";
echo "<select class='topbar-select' name='perpage' id='perpage' value='$perpage' onchange='RefreshPage();'>";
$perpages = (isset($config['trowlimit']) && is_array($config['trowlimit'])) ? $config['trowlimit'] : array(20, 30, 40, 50);
foreach ($perpages as $val) {
	echo " <option value='$val' ";
	if ($perpage == $val) echo "selected";
	echo ">$val</option> ";
}
echo "</select>";
echo "<div class='topbar-pager'>";
echo "<img class='topbar-nav-prev' src='img/previous.png' title='" . $FieldArray['cjrsfirstpage']['FieldLabel'] . "' onclick=SetFirstPage(0,'" . $perpage . "')>";
$prvstartrange = $startrange - $perpage;
if ($prvstartrange < 0) $prvstartrange = 0;
echo "<img class='topbar-nav-prev' src='img/back.png' title='" . $FieldArray['cjrsprevpage']['FieldLabel'] . "' onclick=SetPrvPage('" . $prvstartrange . "','" . $perpage . "')>";
$startrange1 = $startrange + $perpage;
if ($startrange1 >= $totalrows) $startrange1 = $startrange;
echo "<img class='topbar-nav-next' src='img/next.png' title='" . $FieldArray['cjrsnextpage']['FieldLabel'] . "' onclick=SetNextPage('" . $startrange1 . "','" . $perpage . "')>";
echo "<img class='topbar-nav-next' src='img/next2.png' title='" . $FieldArray['cjrslastpage']['FieldLabel'] . "' onclick=SetLastPage('" . $perpage . "','" . $totalrows . "')>";
echo "</div>";
echo "</div>";
echo "<div class='topbar-actions'>";
echo "<button type='button' class='btn-top btn-top-success' id='btnSubmit' name='btnSubmit' onclick='SubmitForm(); return false;' title='" . $ButtonArray[6]['ButtonTitle'] . "'><i class='fa fa-search'></i><span>" . $ButtonArray[6]['ButtonLabel'] . "</span></button>";
echo "<button type='button' class='btn-top btn-top-danger' id='btnCancel' name='btnCancel' onclick='clearform();return false;' title='" . $ButtonArray[7]['ButtonTitle'] . "'><i class='fa fa-eraser'></i><span>" . $ButtonArray[7]['ButtonLabel'] . "</span></button>";
echo "</div>";
echo "</div>";

/* ===================== FILTER CARDS ===================== */
echo "<div class='cards-row'>";
echo "  <div class='mini-card mini-card-compact'>";
echo "    <div class='mini-card-head'>" . $FieldArray['cjrscardsearch']['FieldLabel'] . "</div>";
echo "    <div class='mini-card-body'>";
$fieldHtml = array();
$fieldHtml['keyword'] = "<input class='inputBox' type='text' name='keyword' id='keyword' value='" . htmlspecialchars($keyword, ENT_QUOTES) . "' size='15' maxlength='100' onkeydown=\"return kd(event)\" /><input type='hidden' name='PMode' id='PMode' value='$PMode' />";

$s21 = $s22 = $s23 = $s24 = $s25 = '';
if ($searchby == 1)		$s21 = 'selected';
else if ($searchby == 2)	$s22 = 'selected';
else if ($searchby == 3)	$s23 = 'selected';
else if ($searchby == 4)	$s24 = 'selected';
else if ($searchby == 5)	$s25 = 'selected';

$fieldHtml['searchby'] = "<select class='inputBox' name='searchby' id='searchby' value='$searchby' onchange='SelectSearchBy();'>
	<option " . $s21 . " value='1'>" . $FieldArray['cjrssearchdate']['FieldLabel'] . "</option>
	<option " . $s22 . " value='2'>" . $FieldArray['cjrsreference']['FieldLabel'] . "</option>
	<option " . $s24 . " value='4'>" . $FieldArray['cjrsmcref']['FieldLabel'] . "</option>
	<option " . $s25 . " value='5'>" . $FieldArray['cjrssofref']['FieldLabel'] . "</option>
	<option " . $s23 . " value='3'>" . $FieldArray['cjrsclient']['FieldLabel'] . "</option>
</select>";
$searchFields = array(
	array($FieldArray['cjrssearchby']['FieldLabel'], 'keyword'),
	array($FieldArray['cjrssearchoption']['FieldLabel'], 'searchby')
);
foreach ($searchFields as $field) {
	echo "<div class='field-row'><span class='style1'>" . $field[0] . "</span><div class='field-control-wrap'>" . $fieldHtml[$field[1]] . "</div></div>";
}
echo "    </div>";
echo "  </div>";

echo "  <div class='mini-card mini-card-date'>";
echo "    <div class='mini-card-head'>" . $FieldArray['cjrscarddaterange']['FieldLabel'] . "</div>";
echo "    <div class='mini-card-body'>";
$fieldHtml = array();
$fieldHtml['fromdate'] = "<input type='text' class='inputBox mydate' name='fromdate' id='fromdate' value='$fromdate'>";
$fieldHtml['todate'] = "<input type='text' class='inputBox mydate' name='todate' id='todate' value='$todate'>";
$dateFields = array(
	array($FieldArray['cjrsfrom']['FieldLabel'], 'fromdate'),
	array($FieldArray['cjrsto']['FieldLabel'], 'todate')
);
foreach ($dateFields as $field) {
	echo "<div class='field-row'><span class='style1'>" . $field[0] . "</span><div class='field-control-wrap'>" . $fieldHtml[$field[1]] . "</div></div>";
}
echo "    </div>";
echo "  </div>";
echo "</div>"; // close cards-row


if ($message != '') {
	echo "<div class='list-grid-section'><div class='text-center searchmessage'>" . htmlspecialchars($message, ENT_QUOTES) . "</div></div>";
}

/* ===================== LEGEND ===================== */
echo "<div class='list-grid-section'>";
echo "<div class='pa-legend-box'>";
echo "<span class='pa-legend-title'>" . htmlspecialchars($FieldArray['cjrslegend']['FieldLabel']) . "</span>";
echo "<div class='d-flex flex-wrap align-items-center pa-legend-row'>";
echo "<div class='d-flex'><div class='pa-legend-swatch--pill pa-pill--info'></div>&nbsp;<span class='pa-legend-label'>" . $FieldArray['cjrsstconf']['FieldLabel'] . "</span></div>";
echo "<div class='d-flex'><div class='pa-legend-swatch--pill pa-pill--mute'></div>&nbsp;<span class='pa-legend-label'>" . $FieldArray['cjrsstunconf']['FieldLabel'] . "</span></div>";
echo "<div class='d-flex'><div class='pa-legend-swatch--pill pa-pill--ok'></div>&nbsp;<span class='pa-legend-label'>" . $FieldArray['cjrsstapproved']['FieldLabel'] . "</span></div>";
echo "</div>";
echo "</div>";
echo "</div>";

/* ===================== GRID ===================== */
echo "<div class='list-grid-section'>
		<div id='listDiv' class='table-responsive list-grid-wrap'>
			<table class='table table-bordered mb-0'>";

echo "<thead><tr class='Search-Label-bg'>";
echo "<th class='text-alignment pa-actions'><span class='style1'>&nbsp;</span></th>";
if (!$FieldArray['cjrsreference']['IsHidden'])
	echo "<th class='text-alignment'><span class='style1'>" . $FieldArray['cjrsreference']['FieldLabel'] . "</span></th>";
if (!$FieldArray['cjrsdate']['IsHidden'])
	echo "<th class='text-alignment col-nowrap'><span class='style1'>" . $FieldArray['cjrsdate']['FieldLabel'] . "</span></th>";
if (!$FieldArray['cjrssofref']['IsHidden'])
	echo "<th class='text-alignment'><span class='style1'>" . $FieldArray['cjrssofref']['FieldLabel'] . "</span></th>";
if (!$FieldArray['cjrsmcref']['IsHidden'])
	echo "<th class='text-alignment'><span class='style1'>" . $FieldArray['cjrsmcref']['FieldLabel'] . "</span></th>";
if (!$FieldArray['cjrsitemdesc']['IsHidden'])
	echo "<th class='text-alignment'><span class='style1'>" . $FieldArray['cjrsitemdesc']['FieldLabel'] . "</span></th>";
echo "<th class='text-alignment'><span class='style1'>" . $FieldArray['cjrsstatus']['FieldLabel'] . "</span></th>";
echo "</tr></thead><tbody>";


$ct = 0;
while ($rows = mysqli_fetch_array($resultsearch)) {

	extract($rows);
	$ct += 1;

	$isConfirmed = ($corrugator_confirm == 1);
	$isApproved = ($corrugator_approve == 1);
	$rowStatusClass = $isConfirmed ? 'pa-rail pa-rail--confirmed' : 'pa-rail pa-rail--awaiting';

	$pillConfirmed   = "<span class='pa-pill pa-pill--info'><span class='pa-pill-dot'></span>" . htmlspecialchars($FieldArray['cjrsstconf']['FieldLabel']) . "</span>";
	$pillUnconfirmed = "<span class='pa-pill pa-pill--ghost'>" . htmlspecialchars($FieldArray['cjrsstunconf']['FieldLabel']) . "</span>";
	$pillApproved    = "<span class='pa-pill pa-pill--ok'><span class='pa-pill-dot'></span>" . htmlspecialchars($FieldArray['cjrsstapproved']['FieldLabel']) . "</span>";

	$PrevHint = $FieldArray['cjrsprev']['FieldLabel'];
	$EditHint = $FieldArray['cjrseditrec']['FieldLabel'];
	$DelHint = $FieldArray['cjrsdelrec']['FieldLabel'];

	$txtMC  = '';
	$txtSOF = '';
	$txtItemDesc = '';

	$queryRef = " SELECT mastercard.mastercard_id,sorder.sorder_id,mastercard.product_desc
	From corrugator1
	left join jobcard on corrugator1.jobcardid = jobcard.jobcard_id
					or corrugator1.jobcardid2 = jobcard.jobcard_id
	left join mastercard on mastercard.mastercard_id = jobcard.related_mastercard
	left join sorder     on sorder.sorder_id     = jobcard.related_salesorder
	where  corrugator_id='$corrugator_id'";
	//echo$queryRef;exit;
	$resultRef = mysqli_query($connection, $queryRef) or die(mysqli_error($connection));
	while ($rowsRef = mysqli_fetch_array($resultRef)) {
		extract($rowsRef);

		$MasterCard_Reference = '';
		$mastercard_linked = 0;
		$mastercard_linked_count = 0;
		$queryMC_Detail = "select MasterCard_Reference,ifNull(mastercard_linked,0) as mastercard_linked,ifNull(mastercard_linked_count,0) as mastercard_linked_count from mastercard where mastercard_id='$mastercard_id'";
		$resultMC_Detail = mysqli_query($connection, $queryMC_Detail) or die(mysqli_error($connection));
		$rowsMC_Detail = mysqli_fetch_array($resultMC_Detail);
		if (is_array($rowsMC_Detail)) extract($rowsMC_Detail);
		if (strlen($MasterCard_Reference) < 4) {
			if (strlen($MasterCard_Reference) == 1)
				$MasterCard_Reference = "MC0000" . $MasterCard_Reference;
			else if (strlen($MasterCard_Reference) == 2)  $MasterCard_Reference = "MC000" . $MasterCard_Reference;
			else  $MasterCard_Reference = "MC00" . $MasterCard_Reference;
		} else  $MasterCard_Reference = "MC0" . $MasterCard_Reference;

		if ($mastercard_linked == 1) {
			if ($mastercard_linked_count < 10) {
				$MasterCard_Reference = $MasterCard_Reference . "_0" . $mastercard_linked_count;
			} else {
				$MasterCard_Reference = $MasterCard_Reference . "_" . $mastercard_linked_count;
			}
		}
		$txtMC .= $MasterCard_Reference . " ";


		$sorder_reference = '';
		$querySOF_Detail = "select sorder_reference from sorder where sorder_id='$sorder_id'";
		$resultSOF_Detail = mysqli_query($connection, $querySOF_Detail) or die(mysqli_error($connection));
		$rowsSOF_Detail = mysqli_fetch_array($resultSOF_Detail);
		if (is_array($rowsSOF_Detail)) extract($rowsSOF_Detail);
		if (strlen($sorder_reference) < 4) {
			if (strlen($sorder_reference) == 1)
				$sorder_reference = "SOF0000" . $sorder_reference;
			else if (strlen($sorder_reference) == 2)  $sorder_reference = "SOF000" . $sorder_reference;
			else  $sorder_reference = "SOF00" . $sorder_reference;
		} else  $sorder_reference = "SOF0" . $sorder_reference;

		$txtSOF .= $sorder_reference . " ";

		$txtItemDesc .= "- " . htmlspecialchars((string)$product_desc, ENT_QUOTES) . "<br>";
	}

	// A CJR still open in the temp tables (being edited) can't be edited/deleted from here
	$queryTemp  = "select count(*) from temp_corrugator1 where corrugator_id='$corrugator_id'";
	$resultTemp = mysqli_query($connection, $queryTemp);
	$rowsTemp   = mysqli_fetch_array($resultTemp);
	$countTemp  = $rowsTemp[0] ?? 0;

	if ($countTemp > 0) {
		$EditHint = $DelHint = $FieldArray['cjrsexistsinpend']['FieldLabel'];
	}

	$CjrEditMode = ($FI_CJRMod == 1 && $countTemp == 0) ? 1 : 0;
	$CjrDelMode = ($FI_CJRDel == 1 && $countTemp == 0) ? 1 : 0;

	$refJs = htmlspecialchars(addslashes($corrugator_reference), ENT_QUOTES);
	$mcJs = htmlspecialchars(addslashes($related_mastercard), ENT_QUOTES);

	echo "<tr class='$rowStatusClass' onMouseOver=\"style.cursor='pointer'\" id='row$ct' onclick=\"SelectRecord(this,'SelectedRec','SelectedRecColor','" . $corrugator_id . "');\">";

	// --- Grouped row-action icons ---
	echo "<td class='text-alignment pa-actions'><div class='pa-actions-grid'>";
	echo "<i class='fa fa-search pa-row-ico' onclick=\"PrintPreview($corrugator_id);\" title='" . htmlspecialchars($PrevHint, ENT_QUOTES) . "'></i>";
	if ($countTemp > 0)
		echo "<span class='style1' title='" . htmlspecialchars($EditHint, ENT_QUOTES) . "'>*</span>";
	else if ($CjrEditMode == 1)
		echo "<i class='fa fa-pen pa-row-ico' onclick='UpdateRecord($corrugator_id);' title='" . htmlspecialchars($EditHint, ENT_QUOTES) . "'></i>";
	if ($CjrDelMode == 1)
		echo "<i class='fa fa-trash pa-row-ico' onclick=\"DeleteRecord($corrugator_id, '$refJs');\" title='" . htmlspecialchars($DelHint, ENT_QUOTES) . "'></i>";
	if ($corrugator_confirm == 0)
		echo "<i class='fa fa-check pa-row-ico' onclick=\"confirmCorrugator($corrugator_id, '$mcJs');\" title='" . htmlspecialchars($FieldArray['cjrsconfirm']['FieldLabel'], ENT_QUOTES) . "'></i>";
	if ($corrugator_approve == 0)
		echo "<i class='fa fa-thumbs-up pa-row-ico' onclick=\"ApproveCorrugator($corrugator_id, '$mcJs', 1);\" title='" . htmlspecialchars($FieldArray['cjrsapprove']['FieldLabel'], ENT_QUOTES) . "'></i>";
	else if ($corrugator_approve == 1)
		echo "<i class='fa fa-thumbs-down pa-row-ico' onclick=\"ApproveCorrugator($corrugator_id, '$mcJs', 2);\" title='" . htmlspecialchars($FieldArray['cjrsdisapprove']['FieldLabel'], ENT_QUOTES) . "'></i>";
	echo "</div></td>";

	// --- Data cells (each under the same gate as its <th>) ---
	if (!$FieldArray['cjrsreference']['IsHidden'])
		echo "<td class='text-alignment'><b class='pa-refnum'>" . htmlspecialchars($corrugator_reference, ENT_QUOTES) . "</b></td>";
	if (!$FieldArray['cjrsdate']['IsHidden'])
		echo "<td class='text-alignment col-nowrap'><b>" . $corrugator_date . "</b></td>";
	if (!$FieldArray['cjrssofref']['IsHidden'])
		echo "<td class='text-alignment'><span class='style5'>" . $txtSOF . "</span></td>";
	if (!$FieldArray['cjrsmcref']['IsHidden'])
		echo "<td class='text-alignment'><span class='style5'>" . $txtMC . "</span></td>";
	if (!$FieldArray['cjrsitemdesc']['IsHidden'])
		echo "<td class='text-alignment'><span class='style5'>" . $txtItemDesc . "</span></td>";
	echo "<td class='text-alignment'><div class='pa-status-stack'>";
	echo $isConfirmed ? $pillConfirmed : $pillUnconfirmed;
	if ($isApproved) echo $pillApproved;
	echo "</div></td>";

	echo '</tr>';
}


echo "</tbody></table></div></div>";


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
		todayBtn.innerHTML = "{$FieldArray['btndatetoday']['FieldLabel']}";
		todayBtn.onclick = () => instance.setDate(new Date());
		const clearBtn = document.createElement("button");
		clearBtn.type = "button";
		clearBtn.innerHTML = "{$FieldArray['btndateclear']['FieldLabel']}";
		clearBtn.onclick = () => instance.clear();
		footer.appendChild(todayBtn);
		footer.appendChild(clearBtn);
		instance.calendarContainer.appendChild(footer);
	}
});
</script>
</body>

HERE;
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
