<?php

session_start();
header("Content-type: text/html; charset=".$_SESSION['encodingmode']);
if($_SESSION['dbName']==""){
header("location:../index.php");
}


include("config.inc.php");
include("function.inc.php");
include("Functions_Trans.php");
include("Functions_UserAccess.php");

$connection = mysqli_connect($config['dbServer'],$config['dbUser'],$config['dbPass']) or die("Could not connect to DB");
mysqli_select_db($connection,$_SESSION['dbName']) or die("Could not find DB");

if(	$_SESSION['encodingmode'] == 'utf8' || $_SESSION['encodingmode'] == 'utf-8'){
		mysqli_query($connection,"SET NAMES 'utf8'");
		mysqli_query($connection,'SET CHARACTER SET utf8');
		mysqli_set_charset( $connection,'utf8');
}
	/*
$querysetup="select DecimalBase, DecimalBase1, DecimalBase2, base1, base2, grid_color1, grid_color2, ifNull(Setup_VatPercentage,0) As Setup_VatPercentage from setup";
$resultsetup=mysqli_query($connection,$querysetup) or die(mysqli_error($connection));
while($rowsetup=mysqli_fetch_array($resultsetup)){
	extract($rowsetup);
}
*/
$maxperpage =  getSmodulevalue('maxperpage', 'string', $_SESSION['logincompany'], $_SESSION['dbLabel'], $connection);
$DecimalBase =  getSmodulevalue('DecimalBase', 'string', $_SESSION['logincompany'], $_SESSION['dbLabel'], $connection);
$DecimalBase1 =  getSmodulevalue('DecimalBase1', 'string', $_SESSION['logincompany'], $_SESSION['dbLabel'], $connection);
$DecimalBase2 =  getSmodulevalue('DecimalBase2', 'string', $_SESSION['logincompany'], $_SESSION['dbLabel'], $connection);
$base1 =  getSmodulevalue('base1', 'string', $_SESSION['logincompany'], $_SESSION['dbLabel'], $connection);
$base2 =  getSmodulevalue('base2', 'string', $_SESSION['logincompany'], $_SESSION['dbLabel'], $connection);

//Read From Module Table
$querymodule=" Select ifNull(Module_ProjectParent,0) As Module_ProjectParent, ifNull(Module_CostCenterParent,0) As Module_CostCenterParent ".
			 " From Module";
$resultmodule=mysqli_query($connection,$querymodule) or die(mysqli_error($connection));
while($rowmodule=mysqli_fetch_array($resultmodule)){
	extract($rowmodule);
}

//Read From Login Table
/*
$querylog=" select ifNull(Login_ModifyAllPending,0) As Login_ModifyAllPending, ifNull(login_belongstogrp,0) As FI_BelongsToGrp ".
          " from ".$_SESSION['dbLabel'].".login where user_account='".$_SESSION['useraccount']."' and Upper(Login_Schema) = Upper('".$_SESSION['dbName']."')  ";
$resultlog=mysqli_query($connection,$querylog) or die(mysqli_error($connection));
while($rows=mysqli_fetch_array($resultlog)){
	extract($rows);
}
*/
$MainCompanyName=explode('_',$_SESSION['dbName']);

$Login_ModifyAllPending = getConditionsvalue('Login_ModifyAllPending', $_SESSION['accessid'], $_SESSION['logincompany'], $_SESSION['dbLabel'], $connection);

$LockViewPeriod = getConditionsvalue('LockViewPeriod', $_SESSION['accessid'], $_SESSION['logincompany'], $_SESSION['dbLabel'], $connection);
$ViewPeriodDate = getConditionsvalue('ViewPeriodDate', $_SESSION['accessid'], $_SESSION['logincompany'], $_SESSION['dbLabel'], $connection);
$ViewLockDaily = getConditionsvalue('ViewLockDaily', $_SESSION['accessid'], $_SESSION['logincompany'], $_SESSION['dbLabel'], $connection);
$ViewNumberOfDays = getConditionsvalue('ViewNumberOfDays', $_SESSION['accessid'], $_SESSION['logincompany'], $_SESSION['dbLabel'], $connection);

$rowsperiod = 1;

$FieldArray   = Array();
$ButtonArray   = Array();

$FieldArray['cjrpendingtitle']['FieldLabel']='List of Pending CJR';
$FieldArray['cjrpperpage']['FieldLabel']='Per Page';

$FieldArray['cjrpsearchby']['FieldLabel']='Search By';
$FieldArray['cjrpsearchin']['FieldLabel']='Search In';

$FieldArray['cjrpsearchdate']['FieldLabel']='Date (yyyy-mm-dd)';
$FieldArray['cjrpreff']['FieldLabel']='CJR Reference';
$FieldArray['cjrpmcref']['FieldLabel']='MC Reference';
$FieldArray['cjrpsofref']['FieldLabel']='SOF Reference';
$FieldArray['cjrpclient']['FieldLabel']='Client';
$FieldArray['cjrpdate']['FieldLabel']='Date';

$FieldArray['cjrpeditrec']['FieldLabel']='Edit Record';
$FieldArray['cjrpdelrec']['FieldLabel']='Delete Record';

$FieldArray['cjrpfind']['FieldLabel']='Find';
$FieldArray['cjrpcancel']['FieldLabel']='Cancel';

$FieldArray['cjrpfirstpage']['FieldLabel']='First Page';
$FieldArray['cjrpprev']['FieldLabel']='Previous';
$FieldArray['cjrpnext']['FieldLabel']='Next';
$FieldArray['cjrplastpage']['FieldLabel']='Last Page';

$FieldArray['cjrpcardsearch']['FieldLabel']='Search';
$FieldArray['cjrpcardpagination']['FieldLabel']='Pagination';

// Pending-search localization strings (translation-only).
$FieldArray['btndatetoday']['FieldLabel']='Today';
$FieldArray['btndateclear']['FieldLabel']='Clear';
$FieldArray['cjrpmsgdeleteconfirm']['FieldLabel']='Are you sure you want to Delete this record with reference = ';


// Search AccessFields: keep PHP defaults visible when there is no matching
// search row, then overlay only the current schema's scoped search settings.
foreach ($FieldArray as $fieldKey => $fieldInfo) {
    if (!isset($FieldArray[$fieldKey]['IsHidden'])) $FieldArray[$fieldKey]['IsHidden'] = false;
    if (!isset($FieldArray[$fieldKey]['MandatoryLabel'])) $FieldArray[$fieldKey]['MandatoryLabel'] = '';
    if (!isset($FieldArray[$fieldKey]['FieldMod'])) $FieldArray[$fieldKey]['FieldMod'] = '';
}

$search_render_result = get_fields_for_render(
    'CJR',
    $_SESSION['accessschemaid'],
    $_SESSION['dbLabel'],
    $connection,
    'search'
);
while ($search_render_result && ($search_render_row = mysqli_fetch_assoc($search_render_result))) {
    $fieldCode = strtolower(trim((string)$search_render_row['fieldcode']));
    if (isset($FieldArray[$fieldCode])) {
        $FieldArray[$fieldCode]['IsHidden'] = (intval($search_render_row['is_hidden']) == 1);
        $FieldArray[$fieldCode]['FieldMod'] = (intval($search_render_row['can_edit']) == 1) ? '' : 'readonly';
        $FieldArray[$fieldCode]['MandatoryLabel'] = (!$FieldArray[$fieldCode]['IsHidden'] && intval($search_render_row['is_mandatory']) == 1)
            ? "<span class='style2'>*</span>&nbsp;"
            : '';
    }
}
$vMandatoryFields = '';

$sub_result=get_labelbuttons($_SESSION['dbLabel'],$_SESSION['accessid'],$connection);
while($sub_row=mysqli_fetch_row($sub_result)){
    $ButtonArray[$sub_row[0]]['ButtonLabel']= $sub_row[1];
    $ButtonArray[$sub_row[0]]['ButtonTitle']= $sub_row[2];
}
// Find / Cancel fall back to the captions above when the button labels aren't set up
if (empty($ButtonArray[6]['ButtonLabel'])) $ButtonArray[6] = array('ButtonLabel' => $FieldArray['cjrpfind']['FieldLabel'], 'ButtonTitle' => $FieldArray['cjrpfind']['FieldLabel']);
if (empty($ButtonArray[7]['ButtonLabel'])) $ButtonArray[7] = array('ButtonLabel' => $FieldArray['cjrpcancel']['FieldLabel'], 'ButtonTitle' => $FieldArray['cjrpcancel']['FieldLabel']);

$ui = initFormLanguageUI('CJR', $_SESSION['dbLabel'], $connection, $FieldArray, $ButtonArray);
$rtl_class = $ui['container_class'];
$alignment = $ui['align'];
$is_rtl = $ui['is_rtl'];
$body_dir = $ui['dir'];
$body_class = $ui['body_class'];
$Language = $ui['language_name'];
$jsMsgDeleteConfirm = json_encode($FieldArray['cjrpmsgdeleteconfirm']['FieldLabel'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$jsDateToday = json_encode($FieldArray['btndatetoday']['FieldLabel'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$jsDateClear = json_encode($FieldArray['btndateclear']['FieldLabel'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
/*
$queryperiod= " Select ifNull(LockViewPeriod,0) As LockViewPeriod, ViewPeriodDate, ifNull(ViewLockDaily,0) As ViewLockDaily, ViewNumberOfDays ".
              " From ".$_SESSION['dbLabel'].".ListUserDir  ".
			  " Where Usercode='".$_SESSION['useraccount']."' ".
			  " And Upper(CompanyName) = Upper('".$MainCompanyName[0]."') ".
			  " And Upper(Diryear) = Upper('".$MainCompanyName[1]."') ";

$resultperiod = mysqli_query($connection,$queryperiod) or die(mysqli_error($connection));
$rowsperiod   = mysqli_num_rows($resultperiod);

while($rows=mysqli_fetch_array($resultperiod)){
	extract($rows);
}
	*/

//echo $queryperiod;

$queryproject=" select distinct project.project_code, project_name ".
              " from project left join ".$_SESSION['dbLabel'].".loginstk on (($Module_ProjectParent=1 and project.project_code like concat(ifnull(loginstk.project_code,project.project_code),'%')) or ($Module_ProjectParent=0 and project.project_code = loginstk.project_code )) ".
			  " where user_account='".$_SESSION['useraccount']."' ".
			  " and (loginstk.project_code is not null and loginstk.project_code != '') ".
			  " order by project.project_code";
$projectresult = mysqli_query($connection,$queryproject) or die(mysqli_error($connection));
$projectexist  = mysqli_num_rows($projectresult);

$querycostcent=" select distinct costcent.costcent_code, costcent_name ".
               " from costcent left join ".$_SESSION['dbLabel'].".loginstk on (($Module_CostCenterParent=1 and costcent.costcent_code like concat(ifnull(loginstk.costcent_code,costcent.costcent_code),'%')) or ($Module_CostCenterParent=0 and costcent.costcent_code = loginstk.costcent_code )) ".
			   " where user_account='".$_SESSION['useraccount']."' ".
			   " and (loginstk.costcent_code is not null and loginstk.costcent_code != '') ".
			   " order by costcent.costcent_code";
$costcentresult = mysqli_query($connection,$querycostcent) or die(mysqli_error($connection));
$costcentexist  = mysqli_num_rows($costcentresult);

$queryledger= " select distinct ledger_number, ledger_include ".
              " from ".$_SESSION['dbLabel'].".ledgerstk  ".
			  " where user_account='".$_SESSION['useraccount']."' ".
			  " and (ledger_number is not null and ledger_number != '') ".
			  " order by ledger_number";
$ledgerresult = mysqli_query($connection,$queryledger) or die(mysqli_error($connection));
$ledgerexist  = mysqli_num_rows($ledgerresult);
$rows=mysqli_fetch_array($ledgerresult) ;
$ledgerinclude = $rows[1] ?? 0;

$keyword    = $_POST['keyword'] ?? '';
if ($keyword == "") $keyword = $_GET["keyword"] ?? '';
$searchby   = $_POST['searchby'] ?? '';
if ($searchby == "") $searchby = $_GET["searchby"] ?? '';
$perpage    = $_POST['perpage'] ?? '';
if ($perpage == "") $perpage = $_GET["perpage"] ?? '';
$startrange = $_POST['startrange'] ?? '';
if ($startrange == "") $startrange = $_GET["startrange"] ?? '';
$message    = $_POST['message'] ?? '';
if ($message == "") $message = $_GET["message"] ?? '';

if (empty($searchby)) $searchby = 2;

if ($startrange=='')
	$startrange = 0;
if ($perpage=='')
	$perpage = $maxperpage;
if ($perpage=='' || $perpage == 0)
	$perpage = 20;

// Job card table joined for the MC / SOF / client columns (same table Form_Corrugator.php uses)
$jobcardTable = "jobcard";

$cssStyleVersion = @filemtime(__DIR__ . '/css/style.css') ?: time();

print <<<HERE
<head>
<meta http-equiv="Content-Type" content="text/html; charset={$_SESSION['encodingmode']}" />
<link rel="stylesheet" type="text/css" media="all" href="css/style.css?v=$cssStyleVersion">
<link rel="stylesheet" type="text/css" media="all" href="r-main-css.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
<title>{$FieldArray['cjrpendingtitle']['FieldLabel']}</title>
<script type="text/javascript" language="javascript">

function clearform()
{
  document.search.keyword.value='';

  document.search.action='Form_CorrugatorSearchPending.php';
  document.search.submit();

}

function LoadForm()
{
	x=document.search;
	x.keyword.focus();
}

function SetNextPage(startrange,perpage){
	let keywordInput = document.search.querySelector('input[name="keyword"]');
	if (!keywordInput) {
    	keywordInput = document.createElement('input');
    	keywordInput.type = 'hidden';
    	keywordInput.name = 'keyword';
    	document.search.appendChild(keywordInput);
	}
	keywordInput.value = '';
	let startrangeInput = document.search.querySelector('input[name="startrange"]');
	if (!startrangeInput) {
    	startrangeInput = document.createElement('input');
    	startrangeInput.type = 'hidden';
    	startrangeInput.name = 'startrange';
    	document.search.appendChild(startrangeInput);
	}
	startrangeInput.value = startrange;
	let perpageInput = document.search.querySelector('input[name="perpage"]');
	if (!perpageInput) {
    	perpageInput = document.createElement('input');
    	perpageInput.type = 'hidden';
    	perpageInput.name = 'perpage';
    	document.search.appendChild(perpageInput);
	}
	perpageInput.value = perpage;

	//document.search.action='Form_CorrugatorSearchPending.php?keyword=&startrange='+startrange+'&perpage='+perpage;
	document.search.action='Form_CorrugatorSearchPending.php';
	document.search.submit();
}

function SetPrvPage(startrange,perpage){
 	let keywordInput = document.search.querySelector('input[name="keyword"]');
	if (!keywordInput) {
    	keywordInput = document.createElement('input');
    	keywordInput.type = 'hidden';
    	keywordInput.name = 'keyword';
    	document.search.appendChild(keywordInput);
	}
	keywordInput.value = '';
	let startrangeInput = document.search.querySelector('input[name="startrange"]');
	if (!startrangeInput) {
    	startrangeInput = document.createElement('input');
    	startrangeInput.type = 'hidden';
    	startrangeInput.name = 'startrange';
    	document.search.appendChild(startrangeInput);
	}
	startrangeInput.value = startrange;
	let perpageInput = document.search.querySelector('input[name="perpage"]');
	if (!perpageInput) {
    	perpageInput = document.createElement('input');
    	perpageInput.type = 'hidden';
    	perpageInput.name = 'perpage';
    	document.search.appendChild(perpageInput);
	}
	perpageInput.value = perpage;

	//document.search.action='Form_CorrugatorSearchPending.php?keyword=&startrange='+startrange+'&perpage='+perpage;
	document.search.action='Form_CorrugatorSearchPending.php';
	document.search.submit();
}


function DeleteRecord(txtCorrugatorId, CorrugatorRef){

	if (txtCorrugatorId != '') {
		if (confirm ( $jsMsgDeleteConfirm+CorrugatorRef+" ?") )
		{
			let mainmodeInput = document.search.querySelector('input[name="mainmode"]');
			if (!mainmodeInput) {
    			mainmodeInput = document.createElement('input');
    			mainmodeInput.type = 'hidden';
    			mainmodeInput.name = 'mainmode';
    			document.search.appendChild(mainmodeInput);
			}
			mainmodeInput.value = 'deletepending';
			let CorrugatorIdInput = document.search.querySelector('input[name="txtCorrugatorId"]');
			if (!CorrugatorIdInput) {
    			CorrugatorIdInput = document.createElement('input');
    			CorrugatorIdInput.type = 'hidden';
    			CorrugatorIdInput.name = 'txtCorrugatorId';
    			document.search.appendChild(CorrugatorIdInput);
			}
			CorrugatorIdInput.value = txtCorrugatorId;
			//document.search.action="Form_Corrugator_Actions.php?mainmode=deletepending&txtCorrugatorId="+txtCorrugatorId;
			document.search.action="Form_Corrugator_Actions.php";
			document.search.submit();
		}
	}

}

function UpdateRecord(txtCorrugatorId){

	opener.document.mastercard.txtCorrugatorId.value=txtCorrugatorId;
	opener.document.mastercard.mainaction.value='edit';
	opener.document.mastercard.actionmode.value='';
	opener.document.mastercard.action='Form_Corrugator.php?txtCorrugatorId='+txtCorrugatorId;
	opener.document.mastercard.submit();
	self.close();

}

function SelectSearchBy()
{

  //document.search.keyword.value='';
  //document.search.action='Form_CorrugatorSearchPending.php';
  document.search.submit();

}

function SubmitForm()
{

  //document.search.keyword.value='';
  	let startrangeInput = document.search.querySelector('input[name="startrange"]');
	if (!startrangeInput) {
    	startrangeInput = document.createElement('input');
    	startrangeInput.type = 'hidden';
    	startrangeInput.name = 'startrange';
    	document.search.appendChild(startrangeInput);
	}
	startrangeInput.value = 0;
  //document.search.action='Form_CorrugatorSearchPending.php?startrange=0';
  document.search.action='Form_CorrugatorSearchPending.php';
  document.search.submit();

}


function RefreshPage()
{
  	let startrangeInput = document.search.querySelector('input[name="startrange"]');
	if (!startrangeInput) {
    	startrangeInput = document.createElement('input');
    	startrangeInput.type = 'hidden';
    	startrangeInput.name = 'startrange';
    	document.search.appendChild(startrangeInput);
	}
	startrangeInput.value = 0;
   //document.search.action='Form_CorrugatorSearchPending.php?startrange=0';
   document.search.action='Form_CorrugatorSearchPending.php';
   document.search.submit();

}


function SetFirstPage(startrange,perpage){
  	let startrangeInput = document.search.querySelector('input[name="startrange"]');
	if (!startrangeInput) {
    	startrangeInput = document.createElement('input');
    	startrangeInput.type = 'hidden';
    	startrangeInput.name = 'startrange';
    	document.search.appendChild(startrangeInput);
	}
	startrangeInput.value = 0;
	  	let perpageInput = document.search.querySelector('input[name="perpage"]');
	if (!perpageInput) {
    	perpageInput = document.createElement('input');
    	perpageInput.type = 'hidden';
    	perpageInput.name = 'perpage';
    	document.search.appendChild(perpageInput);
	}
	perpageInput.value = perpage;
	//document.search.action='Form_CorrugatorSearchPending.php?startrange=0&perpage='+perpage;
	document.search.action='Form_CorrugatorSearchPending.php';
	document.search.submit();
}

function SetLastPage(perpage,totalrows){
	var result=totalrows % perpage;
	if (result == 0) result = perpage;
	startrange=totalrows-result;
	  	let startrangeInput = document.search.querySelector('input[name="startrange"]');
	if (!startrangeInput) {
    	startrangeInput = document.createElement('input');
    	startrangeInput.type = 'hidden';
    	startrangeInput.name = 'startrange';
    	document.search.appendChild(startrangeInput);
	}
	startrangeInput.value = startrange;
	  	let perpageInput = document.search.querySelector('input[name="perpage"]');
	if (!perpageInput) {
    	perpageInput = document.createElement('input');
    	perpageInput.type = 'hidden';
    	perpageInput.name = 'perpage';
    	document.search.appendChild(perpageInput);
	}
	perpageInput.value = perpage;
	//document.search.action='Form_CorrugatorSearchPending.php?startrange='+startrange+'&perpage='+perpage;
	document.search.action='Form_CorrugatorSearchPending.php';
	document.search.submit();
}

</script>

HERE;
print <<<HERE
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
</head>
<body OnLoad='LoadForm();' dir='$body_dir' class='$body_class'>
<form name='search' method='post' class='$rtl_class' action=''>
<div class='$rtl_class form-rtl-page' dir='$body_dir'>
HERE;

$PRef = GetSQLValueString($keyword, 'text',$connection);

if (empty($ViewPeriodDate))
   $ViewPeriodDate = 'Null';
if (empty($ViewNumberOfDays))
   $ViewNumberOfDays = 1;

// The PHP 5 page showed the search box but never filtered on it; the options below now filter.
$vKeyword = trim((string)$keyword);
$querywhere = " 1=1 ";
if ($vKeyword != '') {
	if ($searchby == 1)
		$querywhere .= " And temp_corrugator1.corrugator_date >= $PRef And temp_corrugator1.corrugator_date < DATE_ADD($PRef, INTERVAL 1 DAY) ";
	else if ($searchby == 3)
		$querywhere .= " And (jobcard.Clientcode Like Concat($PRef,'%') or clients.Ledger_name Like Concat('%', $PRef,'%')) ";
	else if ($searchby == 4)
		$querywhere .= " And mastercard.mastercard_reference Like Concat('%', $PRef,'%') ";
	else if ($searchby == 6)
		$querywhere .= " And sorder.sorder_reference Like Concat('%', $PRef,'%') ";
	else
		$querywhere .= " And temp_corrugator1.corrugator_reference Like Concat('%', $PRef,'%') ";
}

$queryfrom = " From temp_corrugator1
   left join $jobcardTable as jobcard on jobcard.jobcard_id = temp_corrugator1.jobcardid
   left join mastercard on mastercard.mastercard_id = jobcard.related_mastercard
   left join sorder on sorder.sorder_id = jobcard.related_salesorder
   left join clients on clients.Ledger_number = jobcard.Clientcode ";

$querysearch = " SELECT temp_corrugator1.corrugator_id, temp_corrugator1.jobcardid, temp_corrugator1.corrugator_reference,
		date_format(temp_corrugator1.corrugator_date,'%d/%m/%Y') as corrugator_date, temp_corrugator1.corrugator_approve, temp_corrugator1.corrugator_confirm,
		jobcard.related_salesorder, jobcard.related_mastercard, mastercard.mastercard_reference, sorder.sorder_reference,
		jobcard.Clientcode as ClientCode, clients.Ledger_name as ClientName " .
	$queryfrom .
	" Where " . $querywhere;

$querysearch= $querysearch." Order By temp_corrugator1.corrugator_date Desc ";

//echo $querysearch;

$querycount = " SELECT count(*) " . $queryfrom . " Where " . $querywhere;
$resultcount = mysqli_query($connection,$querycount) or die(mysqli_error($connection));
$rowcount = mysqli_fetch_row($resultcount);
$totalrows = (int)($rowcount[0] ?? 0);

if ($startrange=='' || $PRef != 'Null')
	$startrange = 0;
if ($perpage=='')
	$perpage =$maxperpage;

$querysearch = $querysearch." limit ".(int)$startrange.",".(int)$perpage;
$resultsearch=mysqli_query($connection,$querysearch) or die(mysqli_error($connection));

$FormTitle = $FieldArray['cjrpendingtitle']['FieldLabel'];

echo "<div class='container-fluid'>";

echo "<div class='topbar'>";
echo "<div class='topbar-title'><div class='icon-wrap'><i class='fa fa-layer-group'></i></div><span>$FormTitle</span></div>";
echo "<div class='topbar-actions'>";
echo "<button type='button' class='btn-top btn-top-success' id='btnSubmit' name='btnSubmit' onclick='SubmitForm(); return false;' title='".$ButtonArray[6]['ButtonTitle']."'><i class='fa fa-search'></i><span>".$ButtonArray[6]['ButtonLabel']."</span></button>";
echo "<button type='button' class='btn-top btn-top-danger' id='btnCancel' name='btnCancel' onclick='clearform();return false;' title='".$ButtonArray[7]['ButtonTitle']."'><i class='fa fa-eraser'></i><span>".$ButtonArray[7]['ButtonLabel']."</span></button>";
echo "</div>";
echo "</div>";

echo "<div class='cards-row'>";
echo "  <div class='mini-card'>";
echo "    <div class='mini-card-head'><i class='fa fa-search'></i>" . $FieldArray['cjrpcardsearch']['FieldLabel'] . "</div>";
echo "    <div class='mini-card-body'>";
if (!$FieldArray['cjrpsearchby']['IsHidden']) {
echo "      <div class='field-row'>";
echo "        <span class='style1'>".$FieldArray['cjrpsearchby']['FieldLabel']."</span>";
echo "        <div class='field-control-wrap'>";
echo "          <input type='text' class='inputBox' name='keyword' id='keyword' value='" . htmlspecialchars($keyword, ENT_QUOTES) . "' maxlength='30'>";
echo "        </div>";
echo "      </div>";
} else {
echo "<input type='hidden' name='keyword' id='keyword' value='" . htmlspecialchars($keyword, ENT_QUOTES) . "'>";
}
if (!$FieldArray['cjrpsearchin']['IsHidden']) {
echo "      <div class='field-row'>";
echo "        <span class='style1'>" . $FieldArray['cjrpsearchin']['FieldLabel'] . "</span>";
echo "        <div class='field-control-wrap'>";
echo "          <select name='searchby' class='inputBox' id='searchby' onchange='SelectSearchBy();'>";

			$s21 = $s22 = $s23 = $s24 = $s26 = '';
			if ($searchby==1)
				$s21 = 'selected';
			else if ($searchby==2)
				$s22 = 'selected';
			else if ($searchby==3)
				$s23 = 'selected';
			else if ($searchby==4)
				$s24 = 'selected';
			else if ($searchby==6)
				$s26 = 'selected';

			echo"<option ".$s21." value='1'>".$FieldArray['cjrpsearchdate']['FieldLabel']."</option>
				 <option ".$s22." value='2'>".$FieldArray['cjrpreff']['FieldLabel']."</option>
				 <option ".$s24." value='4'>".$FieldArray['cjrpmcref']['FieldLabel']."</option>
				 <option ".$s26." value='6'>".$FieldArray['cjrpsofref']['FieldLabel']."</option>
				 <option ".$s23." value='3'>".$FieldArray['cjrpclient']['FieldLabel']."</option>
						 </select>";
echo "        </div>";
echo "      </div>";
}
echo "    </div>";
echo "  </div>";

echo "  <div class='mini-card'>";
echo "    <div class='mini-card-head'><i class='fa fa-list-ol'></i>" . $FieldArray['cjrpcardpagination']['FieldLabel'] . "</div>";
echo "    <div class='mini-card-body'>";
if (!$FieldArray['cjrpperpage']['IsHidden']) {
echo "      <div class='field-row'>";
echo "        <span class='style1'>".$FieldArray['cjrpperpage']['FieldLabel']."</span>";
echo "        <div class='field-control-wrap'>";
echo "          <select class='inputBox' name='perpage' id='perpage' onchange='RefreshPage();'>";

		$perpages = (isset($config['trowlimit']) && is_array($config['trowlimit'])) ? $config['trowlimit'] : array(20, 30, 40, 50);
		foreach ($perpages as $val) {
			echo " <option value = '$val' ";
			if ($perpage == $val) echo "selected";
			echo " >$val</option> ";
		}
echo "          </select>";
echo "        </div>";
echo "      </div>";
}
echo "      <div class='field-row search-pagination-row'>";

if ($startrange > 0)
	echo "<img src='img/previous.png' title='".$FieldArray['cjrpfirstpage']['FieldLabel']."' onMouseOver=\"style.cursor='hand'\" onclick=SetFirstPage(0,'" . $perpage . "')>";

$diff = $startrange - $perpage;
if (($diff) >= 0) {
	$prvstartrange = $startrange - $perpage;
	echo "<img src='img/back.png' title='".$FieldArray['cjrpprev']['FieldLabel']."' onMouseOver=\"style.cursor='hand'\" onclick=SetPrvPage('" . $prvstartrange . "','" . $perpage . "')>";
}

if ($startrange <= $totalrows) {
	$startrange1 = $startrange + $perpage;
	if ($startrange1 < $totalrows) {
		echo "<img src='img/next.png' title='".$FieldArray['cjrpnext']['FieldLabel']."' onMouseOver=\"style.cursor='hand'\" onclick=SetNextPage('" . $startrange1 . "','" . $perpage . "')>";
		echo "<img src='img/next2.png' title='".$FieldArray['cjrplastpage']['FieldLabel']."' onMouseOver=\"style.cursor='hand'\" onclick=SetLastPage('" . $perpage . "','" . $totalrows . "')>";
	}
}
echo "      </div>";
echo "    </div>";
echo "  </div>";
echo "</div>";


	if($message!=''){
		echo "<div class='list-grid-section'><div class='text-center searchmessage'>".htmlspecialchars($message, ENT_QUOTES)."</div></div>";
	}

	// Same pending-popup grid as Form_DeliverySearchPending.php: no status pills / rail / legend.
	echo "<div class='list-grid-section'>
		<div id='listDiv' class='table-responsive list-grid-wrap'>
		<table class='table table-bordered mb-0'>";

	echo "<thead><tr class='Search-Label-bg'>";
	echo "<th class='text-alignment'><span class='style1'>&nbsp;</span></th>";
	if(!$FieldArray['cjrpreff']['IsHidden'])
		echo "<th class='text-alignment'><span class='style1'>".$FieldArray['cjrpreff']['FieldLabel']."</span></th>";
	if(!$FieldArray['cjrpdate']['IsHidden'])
		echo "<th class='text-alignment col-nowrap'><span class='style1'>".$FieldArray['cjrpdate']['FieldLabel']."</span></th>";
	if(!$FieldArray['cjrpmcref']['IsHidden'])
		echo "<th class='text-alignment'><span class='style1'>".$FieldArray['cjrpmcref']['FieldLabel']."</span></th>";
	if(!$FieldArray['cjrpsofref']['IsHidden'])
		echo "<th class='text-alignment'><span class='style1'>".$FieldArray['cjrpsofref']['FieldLabel']."</span></th>";
	if(!$FieldArray['cjrpclient']['IsHidden'])
		echo "<th class='text-alignment'><span class='style1'>".$FieldArray['cjrpclient']['FieldLabel']."</span></th>";
	echo "</tr></thead><tbody>";


$ct = 0;
while($rows=mysqli_fetch_array($resultsearch)){

	extract($rows);
	$ct+=1;

	$mastercard_reference = (string)$mastercard_reference;
	if($mastercard_reference != ''){
		if(strlen($mastercard_reference) < 4){
			if(strlen($mastercard_reference) == 1)
				$mastercard_reference = "MC000".$mastercard_reference;
			else if(strlen($mastercard_reference) == 2)  $mastercard_reference = "MC00".$mastercard_reference;
			else  $mastercard_reference = "MC0".$mastercard_reference;
		}
		else  $mastercard_reference = "MC".$mastercard_reference;
	}

	$sorder_reference = (string)$sorder_reference;
	if($sorder_reference != ''){
		if(strlen($sorder_reference) < 4){
			if(strlen($sorder_reference) == 1)
				$sorder_reference = "SOF0000".$sorder_reference;
			else if(strlen($sorder_reference) == 2)  $sorder_reference = "SOF000".$sorder_reference;
			else  $sorder_reference = "SOF00".$sorder_reference;
		}
		else  $sorder_reference = "SOF0".$sorder_reference;
	}

	$refJs = htmlspecialchars(addslashes((string)$corrugator_reference), ENT_QUOTES);

	echo "<tr>";

	// --- Grouped row-action icons ---
	echo "<td class='text-alignment'>";
	echo "<i class='fa fa-pen pa-row-ico' onclick='UpdateRecord(".$corrugator_id.");' title='".htmlspecialchars($FieldArray['cjrpeditrec']['FieldLabel'], ENT_QUOTES)."'></i>";
	echo "<i class='fa fa-trash pa-row-ico' onclick=\"DeleteRecord(".$corrugator_id.", '".$refJs."');\" title='".htmlspecialchars($FieldArray['cjrpdelrec']['FieldLabel'], ENT_QUOTES)."'></i>";
	echo "</td>";

	// --- Data cells (each under the same gate as its <th>) ---
	if(!$FieldArray['cjrpreff']['IsHidden'])
		echo "<td class='text-alignment'><b class='pa-refnum'>".htmlspecialchars((string)$corrugator_reference, ENT_QUOTES)."</b></td>";
	if(!$FieldArray['cjrpdate']['IsHidden'])
		echo "<td class='text-alignment col-nowrap'><b>".$corrugator_date."</b></td>";
	if(!$FieldArray['cjrpmcref']['IsHidden'])
		echo "<td class='text-alignment'><span class='style5'>".htmlspecialchars($mastercard_reference, ENT_QUOTES)."</span></td>";
	if(!$FieldArray['cjrpsofref']['IsHidden'])
		echo "<td class='text-alignment'><span class='style5'>".htmlspecialchars($sorder_reference, ENT_QUOTES)."</span></td>";
	if(!$FieldArray['cjrpclient']['IsHidden']){
		$clientTitle = trim($ClientCode.' '.$ClientName);
		echo "<td class='text-alignment'><div class='pa-client-cell' title='".htmlspecialchars($clientTitle, ENT_QUOTES)."'>";
		echo "<span class='pa-client-code'>".htmlspecialchars((string)$ClientCode, ENT_QUOTES)."</span>";
		echo "<span class='pa-client-name'>".htmlspecialchars((string)$ClientName, ENT_QUOTES)."</span>";
		echo "</div></td>";
	}
	echo "</tr>";
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
		todayBtn.innerHTML = $jsDateToday;
		todayBtn.onclick = () => instance.setDate(new Date());
		const clearBtn = document.createElement("button");
		clearBtn.innerHTML = $jsDateClear;
		clearBtn.onclick = () => instance.clear();
		footer.appendChild(todayBtn);
		footer.appendChild(clearBtn);
		instance.calendarContainer.appendChild(footer);
	}
});
</script>
</body>

HERE;

