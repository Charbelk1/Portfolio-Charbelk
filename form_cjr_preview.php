<?php

session_start();
header("Content-type: text/html; charset=" . $_SESSION['encodingmode']);
if ($_SESSION['dbName'] == "") {
  header("location:../index.php");
}


include("config.inc.php");
include("Functions_Purchase.php");
include("Forms_DataGrid_SalesOrder.php");
include("Functions_UserAccess.php");

$connection = mysqli_connect($config['dbServer'], $config['dbUser'], $config['dbPass']) or die("Could not connect to DB");
mysqli_select_db($connection, $_SESSION['dbName']) or die("Could not find DB");

if ($_SESSION['encodingmode'] == 'utf8' || $_SESSION['encodingmode'] == 'utf-8') {
  mysqli_query($connection, "SET NAMES 'utf8'");
  mysqli_query($connection, 'SET CHARACTER SET utf8');
  mysqli_set_charset($connection, 'utf8');
}

/*
$querylog = "SELECT 1 as loginaccess from " . $_SESSION['dbLabel'] . ".login where user_account='" . $_SESSION['useraccount'] . "' 	and Upper(Login_Schema) = Upper('" . $_SESSION['dbName'] . "')	";
$resultlog = mysqli_query($connection, $querylog) or die(mysqli_error($connection));
while ($rows = mysqli_fetch_array($resultlog)) {
  if (is_array($rows)) extract($rows);
}
*/
$sub_result = getformdetails('CJR', $_SESSION['accessschemaid'], $_SESSION['dbLabel']);
$row_login = $sub_result ? mysqli_fetch_row($sub_result) : null;
$loginaccess = $row_login[7] ?? '';

if ($loginaccess == '' or $loginaccess == 0)
  header("location:menu.php");

// Labels (same approach as Form_DeliveryPreview.php): PHP defaults, overridable per schema
// through the 'preview' access fields.
$FieldArray = array();
$ButtonArray = array();
$cjrPreviewDefaults = array(
	'cjrpvtitle' => 'CJR Preview',
	'cjrpvheader' => 'CJR',
	'cjrpvcjrreferenceno' => 'CJR Reference#',
	'cjrpvdate' => 'Date',
	'cjrpvprinted' => 'Printed',
	'cjrpvsofno' => 'SOF#',
	'cjrpvcustomer' => 'Customer',
	'cjrpvitemdescription' => 'Item Description',
	'cjrpvqtyrequest' => 'Qty Request',
	'cjrpvmastercardno' => 'MasterCard#',
	'cjrpvflutetype' => 'Flute Type',
	'cjrpvscoringtype' => 'Scoring Type',
	'cjrpvboardneed' => 'BoardNeed',
	'cjrpvjobcardno' => 'JobCard#',
	'cjrpvinsideliner' => 'Inside Liner',
	'cjrpvoutsideliner' => 'Outside Liner',
	'cjrpvplannedqty' => 'Planned Qty',
	'cjrpvboxtype' => 'Box Type',
	'cjrpvstdgsm' => 'STD GSM',
	'cjrpvproducedqty' => 'Produced Qty',
	'cjrpvboxsizeed' => 'Box Size ED',
	'cjrpvstdpaper' => 'STD.Paper',
	'cjrpvremainingqty' => 'Remaining Qty',
	'cjrpvcorrid' => 'CorrID',
	'cjrpvsalesref' => 'Sales Ref',
	'cjrpvlength' => 'Length',
	'cjrpvwidth' => 'Width',
	'cjrpvflaptop' => 'Flap Top',
	'cjrpvheight' => 'Height',
	'cjrpvflapbot' => 'Flap Bot',
	'cjrpvnoouts' => '# Outs',
	'cjrpvtwidth' => 'T-Width',
	'cjrpvnocuts' => '# Cuts',
	'cjrpvacuts' => 'A cuts',
	'cjrpvlm' => 'LM',
	'cjrpvsq' => 'SQ',
	'cjrpvreeldeckle' => 'Reel Deckle',
	'cjrpvtotwidth' => 'Tot Width',
	'cjrpvtrimming' => 'Trimming',
	'cjrpvitem' => 'Item',
	'cjrpvgsm' => 'GSM',
	'cjrpvpapergrade' => 'Paper Grade',
	'cjrpvsizemm' => 'Size MM',
	'cjrpvpapermill' => 'Paper Mill',
	'cjrpvwarehouse' => 'WareHouse',
	'cjrpvsupplier' => 'Supplier',
	'cjrpvurequired' => 'U Required',
	'cjrpvuact' => 'UAct',
	'cjrpvlrequired' => 'L Required',
	'cjrpvlact' => 'LAct',
	'cjrpvtreq' => 'TReq',
	'cjrpvtact' => 'TAct',
	'cjrpvreqtrim' => 'ReqTrim',
	'cjrpvacttrim' => 'ActTrim',
	'cjrpvireq' => 'I Req.',
	'cjrpvliner1req' => 'LINER-1 Req.',
	'cjrpvinnerstk' => 'Inner Stk.',
	'cjrpvliner1stk' => 'LINER-1 Stk.',
	'cjrpvibooking' => 'I Booking.',
	'cjrpvliner1booking' => 'LINER-1 Booking.',
	'cjrpvwtpcu' => 'WTPC(U)',
	'cjrpvireqtrimkg' => 'I ReqTrim.(KG)',
	'cjrpvliner1reqtrimkg' => 'LINER-1 ReqTrim.(KG)',
	'cjrpvfreq' => 'F Req.',
	'cjrpvflute1req' => 'FLUTE-1 Req.',
	'cjrpvfluttingstk' => 'Flutting Stk.',
	'cjrpvflute1stk' => 'FLUTE-1 Stk.',
	'cjrpvfbooking' => 'F Booking.',
	'cjrpvflute1booking' => 'FLUTE-1 Booking.',
	'cjrpvwtpcl' => 'WTPC(L)',
	'cjrpvfreqtrimkg' => 'F ReqTrim.(KG)',
	'cjrpvflute1reqtrimkg' => 'FLUTE-1 ReqTrim.(KG)',
	'cjrpvtrequired' => 'T Required',
	'cjrpvoreq' => 'O Req.',
	'cjrpvliner2req' => 'LINER-2 Req.',
	'cjrpvouterstk' => 'OUTER Stk.',
	'cjrpvliner2stk' => 'LINER-2 Stk.',
	'cjrpvobooking' => 'O Booking.',
	'cjrpvlinner2booking' => 'Linner-2 Booking.',
	'cjrpvtotgsm' => 'TOT GSM',
	'cjrpvoreqtrimkg' => 'O ReqTrim.(KG)',
	'cjrpvliner2reqtrimkg' => 'LINER-2 ReqTrim.(KG)',
	'cjrpvtrimwasteper' => 'TrimWaste%',
	'cjrpvflute2req' => 'FLUTE-2 Req.',
	'cjrpvflute2stk' => 'Flute-2 Stk.',
	'cjrpvflute2booking' => 'FLUTE-2 Booking.',
	'cjrpvflute2reqtrimkg' => 'FLUTE-2 ReqTrim.(KG)',
	'cjrpvolinnerreq' => 'O LINNER Req.',
	'cjrpvolinnerstk' => 'O LINNER Stk.',
	'cjrpvolinerreqtrimkg' => 'O.Liner ReqTrim.(KG)',
	'cjrpvtotalreq' => 'Total.Req',
);
foreach ($cjrPreviewDefaults as $cjrPreviewCode => $cjrPreviewLabel) {
  $FieldArray[$cjrPreviewCode]['FieldLabel'] = $cjrPreviewLabel;
}
if (function_exists('initFormLanguageUI')) {
  $cjrPreviewUi = initFormLanguageUI('CJR', $_SESSION['dbLabel'], $connection, $FieldArray, $ButtonArray);
}
if (function_exists('get_fields_for_render')) {
  $previewRenderResult = get_fields_for_render('CJR', $_SESSION['accessschemaid'], $_SESSION['dbLabel'], $connection, 'preview');
  while ($previewRenderResult && ($previewField = mysqli_fetch_assoc($previewRenderResult))) {
    $previewCode = strtolower($previewField['fieldcode']);
    if (isset($FieldArray[$previewCode]) && trim((string)$previewField['accessfield_fieldname']) != '') {
      $FieldArray[$previewCode]['FieldLabel'] = $previewField['accessfield_fieldname'];
    }
  }
}

$message = $message ?? '';
$actionmode = $actionmode ?? '';
$mainaction = $mainaction ?? '';

print <<<HERE
<head>

<meta http-equiv="Content-Type" content="text/html; charset=windows-1256" />
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"> 
<meta name="viewport" content="width=device-width, initial-scale=1.0"> 
<link rel="stylesheet" type="text/css" media="all" href="css-v2/style.css">
<link rel="stylesheet" type="text/css" media="all" href="js/grid/UI_DataGrid_V1.css">
<script type="text/javascript" src="js/grid/UI_DataGrid.js"></script>
<script type="text/javascript" src="js/grid/jquery-1.9.1.js"></script>


<link type="text/css" href="js/calendar/jquery.datepick.css" rel="stylesheet">
<script type="text/javascript" src="js/calendar/jquery.datepick.js"></script>

<link type="text/css" href="js/grid/scripts/jquery-ui.css" rel="stylesheet">
<script type="text/javascript" src="js/grid/scripts/jquery-ui.min.js"></script>

<script type="text/javascript" language="javascript">
	$(document).ready(function() { 
		
		$(document).on('focus', '.date', function() {
			$('.date').datepick({showTrigger: '#calImg'});
		});
		
		$(document).on('focusin', '.table-focus-g1', function() {
			AutoSaveRecordGrid(this.id,'SelectedRec','ModRec_G1','imgSubmit');
			selRecordColor(this,'SelectedRec','SelectedRecColor');
		});
		
		//Set Scroll Positions on Page Load
		scrollToPage();
		
		$(window).scroll(function(){
			scrollPage();
		});
		
		//Set Cursor Position After Page Reload
		var NextElement = readCookie('por-nextcell');
		MoveNextRecord(NextElement);
		eraseCookie('por-nextcell');
		
		
		
	});
	
	function ShowSlidingDiv(PDivID) {
	$(PDivID).slideToggle();
}
</script>


<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>{$FieldArray['cjrpvtitle']['FieldLabel']}</title>


<link type="text/css" rel="stylesheet" href="dhtmlgoodies_calendar.css?random=20051112" media="screen"></link>
<SCRIPT type="text/javascript" src="dhtmlgoodies_calendar.js?random=20060118"></script>

</head>
<body>
<form name='mastercard' method='post' enctype='multipart/form-data'>
<input type='hidden' name='actionmode' value='$actionmode'>
<input type='hidden' name='mainaction' value='$mainaction'>

<input type='hidden' name='message' value='$message' size='200'>
HERE;

//$message='';


//Initializations

$PriceLabel = 'Price';
if ($PurchaseTTC == 1)
  $PriceLabel = 'Price TTC';


//Get Default Project
$querydflproject = "select project_code as defaultproject from " . $_SESSION['dbLabel'] . ".loginstk where user_account='" . $_SESSION['useraccount'] . "' and default_project=1  and Upper(loginstk.company_name) = Upper('" . $_SESSION['dbName'] . "') ";
$resultdflproject = mysqli_query($connection, $querydflproject) or die(mysqli_error($connection));
while ($rows = mysqli_fetch_array($resultdflproject)) {
  if (is_array($rows)) extract($rows);
}

//Get Default Coscenter
$querydflcostcent = "select costcent_code as defaultcostcent from " . $_SESSION['dbLabel'] . ".loginstk where user_account='" . $_SESSION['useraccount'] . "' and default_costcenter=1  and Upper(loginstk.company_name) = Upper('" . $_SESSION['dbName'] . "') ";
$resultdflcostcent = mysqli_query($connection, $querydflcostcent) or die(mysqli_error($connection));
while ($rows = mysqli_fetch_array($resultdflcostcent)) {
  if (is_array($rows)) extract($rows);
}


echo "
<script>
  window.onload = function() {
    printFunction();
  };

  function printFunction() {
    var printWindow = window.open('', '_blank', 'width=1200,height=1200'); // Adjust width and height as needed

    if (printWindow) {
      printWindow.document.open();
      printWindow.document.write('<html><head><title>Print</title><link rel=\"stylesheet\" type=\"text/css\" href=\"print-styles.css\" media=\"print\"></head><body>');
      printWindow.document.write(document.documentElement.innerHTML); // Prints the entire HTML content of the page
      printWindow.document.write('</body></html>');
      printWindow.document.close();
      printWindow.print();
      printWindow.close();
    } else {
      alert('Failed to open print window. Please check your browser settings and try again.');
    }
  }


</script>";


echo "<style>

.overlay-textlength {
    position: absolute;
    top: 14%;
    left: 24%;
    transform: translate(-50%, -50%);
    font-size: 17px;
    color:black;
    font-weight:bold;
    padding: 10px 20px;
    border-radius: 5px;
}



.style1_1 {
  color: #000000;
  font-weight: bold;
  font-size: 15px;
  font-family: Verdana, Geneva, sans-serif;
  /*background-color:#D8D8D8;*/
}


.bg{

  background-color:#D8D8D8;

}

.overlay-textwidth {
    position: absolute;
    top: 14%;
    left: 34%;
    transform: translate(-50%, -50%);
    font-size: 17px;
    color:black;
    font-weight:bold;
    padding: 10px 20px;
    border-radius: 5px;
}

.overlay-textlength2 {
    position: absolute;
    top: 14%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 17px;
    color:black;
    font-weight:bold;
    padding: 10px 20px;
    border-radius: 5px;
}

.overlay-textwidth2 {
    position: absolute;
    top: 14%;
    left: 62%;
    transform: translate(-50%, -50%);
    font-size: 17px;
    color:black;
    font-weight:bold;
    padding: 10px 20px;
    border-radius: 5px;
	width:100%;

}

td {
  /*color: #0A1172;*/
  font-weight: bold;
  word-wrap: break-word;
}
table { max-width: 100%; }

.overlay-texts1{
    position: absolute;
    top: 32%;
    left: 72%;
    transform: translate(-50%, -50%);
    font-size: 15px;
    color:black;
    font-weight:bold;
    padding: 10px 20px;
    border-radius: 5px;
}

.overlay-texts2{
    position: absolute;
    top: 58%;
    left:72%;
    transform: translate(-50%, -50%);
    font-size: 15px;
    color:black;
    font-weight:bold;
    padding: 10px 20px;
    border-radius: 5px;

}

.overlay-texts3{
    position: absolute;
    top: 88%;
    left: 72%;
    transform: translate(-50%, -50%);
    font-size: 15px;
    color:black;
    font-weight:bold;
    padding: 10px 20px;
    border-radius: 5px;
}

.overlay-textgrossboardlength{
	position: absolute;
    top: 8%;
    left: 44%;
    transform: translate(-50%, -50%);
    font-size: 15px;
    color:black;
    font-weight:bold;
    padding: 10px 20px;
    border-radius: 5px;
}

.overlay-textgrossboardheight{
    position: absolute;
    top: 41%;
    left: 10%;
    transform: translate(-50%, -50%);
    font-size: 17px;
    color:black;
    font-weight:bold;
    padding: 10px 20px;
    border-radius: 5px;
}


.overlay-textnetboardheight{
    position: absolute;
    top: 58%;
    left: 77%;
    transform: translate(-50%, -50%);
    font-size: 15px;
    color:black;
    font-weight:bold;
    padding: 10px 20px;
    border-radius: 5px;
}

.overlay-textnetboardheight2{
    position: absolute;
    top: 58%;
    left: 69%;
    transform: translate(-50%, -50%);
    font-size: 15px;
    color:black;
    font-weight:bold;
    padding: 10px 20px;
    border-radius: 5px;
}

.overlay-textnetboardlength{
    position: absolute;
    top: 8%;
    left: 44%;
    transform: translate(-50%, -50%);
    font-size: 15px;
    color:black;
    font-weight:bold;
    padding: 10px 20px;
    border-radius: 5px;
}

.overlay-textnetboardlength2{
    position: absolute;
    top: 23%;
    left: 39%;
    transform: translate(-50%, -50%);
    font-size: 15px;
    color:black;
    font-weight:bold;
    padding: 10px 20px;
    border-radius: 5px;
}


.overlay-textgluelap {
    position: absolute;
    top: 21%;
    left: 8%;
    transform: translate(-50%, -50%);
    font-size: 17px;
    color:black;
    font-weight:bold;
    padding: 10px 20px;
    border-radius: 5px;
}
  .image-container {
    position: relative;
    width: 800px;
    height: 300px;
    overflow: hidden;
}

.absolute-image {
    position: absolute;
    top: 0;
    left: 0;
    width: 80%;
    height: 100%;
}
.absolute-image1 {
    position: absolute;
    top: 0;
    left: 0;
    width: 80%;
    height: 100%;
}
table.grid_data th {
  background-color: #D8D8D8;
  font-weight: bold;
  font-size: 11px;
  font-family: Verdana, Arial, Helvetica, sans-serif;
  padding: 0px;
  border-width: 3px;
  border-bottom: 2px solid #000000;
  color: #000000;
  /* border-left: 0px solid #000000; */
}
  </style>";


$txtCJRId = $_POST["PurchaseId"] ?? '';
if ($txtCJRId == "") {
  $txtCJRId = $_GET["PurchaseId"] ?? '';
}



if ($txtCJRId != '') {
  // Read From Temp Tables For Selected Record				 
  $query = "SELECT  corrugator_reference, date_format(corrugator_date,'%Y/%m/%d') as corrugator_date, corrugator_confirm, corrugator_approve, adjustment_id
            From corrugator1 
            where corrugator_id = $txtCJRId ";

  $result = mysqli_query($connection, $query) or die(mysqli_error($connection));
  $rows = mysqli_fetch_array($result);
  if (is_array($rows)) extract($rows);

  $query1 = "Select
	related_salesorder, related_mastercard,jobcard_id,related_mastercard,related_keylineId,mastercard.mastercard_BoxtypeId,mastercard.product_desc as itemdesc,
upperlowerstacker.scoringtype as scoringtype,mastercard.outerlinercolor as outsideliner,mastercard.innerlinercolor as insideliner,papercombinations.temp_flutting2,jobcard.jobcard_gsm,
jobcard.jobcard_qtyrequested as boardneed,clients.ledger_number as txtClientCode,clients.Ledger_name as customer,mastercard.mastercard_FluteTypeId,
sorderdt_quantity,mastercard_reference as masterref,Sorder_reference as salesref,JobCard_Reference as jobCardRef,ifNull(plannedqty,0) as plannedqty,
ifNull(producedqty,0) as producedqty, ifNull(remainingqty,0) as remainingqty,sorder.Project_code as Project_code,sorder.Costcent_code as Costcent_code,
sorder.Currency_code as Currency_code,flutetype.FluteType_code as flutetype_code,boxtype.boxtype_code as BoxType_code,upperlowerstacker.boardneed as boardneed,ifnull(BorderNeededQty,0) as BorderNeededQty,
stdgsm,mastercard.external_length,mastercard.external_width,mastercard.external_height,
papercombinations.temp_TLiner1,
papercombinations.temp_Liner1,
papercombinations.temp_TFlutting1,
papercombinations.temp_Flutting1,
papercombinations.temp_Tliner2,
papercombinations.temp_Liner2,
papercombinations.temp_TFlutting2,
papercombinations.temp_Flutting2,
papercombinations.temp_TOuterLine,
papercombinations.temp_OuterLine,
upperlowerstacker.flutetype,
mastercard.scoringtype as MCscoringtype
from

 upperlowerstacker 

left join jobcard ON jobcard.jobcard_Id = upperlowerstacker.jobcardid

left join mastercard on mastercard.mastercard_id=jobcard.related_mastercard

left join papercombinations on papercombinations.mastercard_id=mastercard.mastercard_id

left join clients on clients.ledger_number = mastercard.ledger_number

left join sorderdt on sorderdt.Sorder_id=jobcard.related_salesorder

left join sorder on sorderdt.Sorder_id=sorder.Sorder_id

left join boxtype on mastercard.mastercard_BoxTypeId = boxtype.boxtype_id

left join flutetype on flutetype.FluteType_ID = upperlowerstacker.flutetype

where corrugatorid=" . $txtCJRId . "  ";

  //echo $query1;
  $result1 = mysqli_query($connection, $query1) or die(mysqli_error($connection));


  if ($client_export == 1) {
    $locExp = 'Export';
  }

  if ($client_local == 1) {
    $locExp = 'Local';
  }

  if ($client_local == 0 && $client_export == 0) {
    $locExp = 'Inactive';
  }


  $mainaction = 'edit';
  $txtLinkedMcRef = explode(" ,", (string)($txtLinkedMcRef ?? ''));



  $data = insertDate($porder_ets ?? '');
  $datevalue = substr($data, 0, 4) . "-" . substr($data, 4, 2) . "-" . substr($data, 6, 2);

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


  $JobCardIdArr = array();

  $printedDate = date("d/m/Y H:i:s");




  echo "<table border=0 align='center' width=100% class='bg' style='table-layout:fixed;'>
    <tr>
    <td align='center'><h1 style='color:black;text-align:center;border:none;background-color:#D8D8D8;'><b>{$FieldArray['cjrpvheader']['FieldLabel']}</b></h1></td></tr></table>";


  $printedDate = date("d/m/Y H:i:s");

  print <<<HERE
	
		<table border="0" style='table-layout:fixed;width:100%;'>
		<col width='12.5%'>
		<col width='8.5%'>
		<col width='9.5%'>
		<col width='15.5%'>
		<col width='13.5%'>
		<col width='2%'>
		<col width='12.5%'>
		<col width='11%'>
		<col width='11.5%'>
		<col width='1.75%'>
		<col width='1.75%'>
   

    <tr height=3px></tr>
		<tr >

        <td align='left' class='bg' ><span class='style1_1'>{$FieldArray['cjrpvcjrreferenceno']['FieldLabel']}</span></td>
        <td >
        <font face='Verdana' size='2'><b>$corrugator_reference</b></font>
        </td>

        <td class='bg'><span class="style1_1">{$FieldArray['cjrpvdate']['FieldLabel']}&nbsp;</span></td>
		<td><font face='Verdana' size='2' ><b>$corrugator_date</b></font></td>

    <td></td>
    <td></td>
    <td></td>

    <td class='bg'><span class="style1_1">{$FieldArray['cjrpvprinted']['FieldLabel']}&nbsp;</span></td>
		<td colspan=2><font face='Verdana' size='2' ><b>$printedDate</b></font></td>




</tr>
<tr height=8px></tr>
HERE;
  $i = 0;
  $scoringTypeArr  = array();
  $outsideLinerArr = array();
  $fluteTypeArr    = array();

  while ($rows1 = mysqli_fetch_array($result1)) {
    if (is_array($rows1)) extract($rows1);
    $JobCardIdArr[$i] = $jobcard_id;
    print <<<HERE
<tr>
    <td class='bg'><span class='style1_1'>{$FieldArray['cjrpvsofno']['FieldLabel']}</span></td>
    <td ><font face='Verdana' size='2' ><b>$salesref</b></font></td>

 
    <td class='bg'><span class='style1_1'>{$FieldArray['cjrpvcustomer']['FieldLabel']} </span></td>
    <td ><font face='Verdana' size='2' ><b>$customer</b></font></td>
	
	
		</td>

		<td class='bg'><span class="style1_1">{$FieldArray['cjrpvitemdescription']['FieldLabel']}&nbsp;</span></td>
		<td colspan=2><font face='Verdana' size='2'><b>$itemdesc</b></font></td>

    <td class='bg'><span class="style1_1">{$FieldArray['cjrpvqtyrequest']['FieldLabel']}&nbsp;</span></td>
		<td><font face='Verdana' size='2'><b>$boardneed</b></font></td>
	 	

HERE;

    echo "</tr>";

    echo "<tr height=3px></tr>";

    echo "   <tr >";
    for ($j = 0; $j < count($scoringTypeArr); $j++) {
			if ($scoringTypeArr[$j] == $scoringtype) {
				$color = '';
			} else {
				$color = 'red';
			}
		}
  $queryFlute = " Select FluteType_id, FluteType_code, FluteType_ply from FluteType where FluteType_id ='$flutetype' order by FluteType_code";
	$resultFlute = mysqli_query($connection, $queryFlute);
  $rowFlute = mysqli_fetch_array($resultFlute);

  for ($j = 0; $j < count($fluteTypeArr); $j++) {
    if (strtoupper((string)$fluteTypeArr[$j]) == strtoupper((string)($fluteType ?? ''))) {
      $color3 = '';
    } else {
      $color3 = 'red';
    }
  }


  $fluteTypeArr[$i] = $fluteType ?? '';

  
  if ($MCscoringtype != $scoringtype) {
    $color1 = 'red';
  } else {
    $color1 = '';
  }

    echo "<td class='bg'><span class='style1_1'>{$FieldArray['cjrpvmastercardno']['FieldLabel']}</span></td>
<td ><font face='Verdana' size='2'><b>$masterref</b></font></td>
<td class='bg' style='background-color:$color3;'><span class='style1_1'>{$FieldArray['cjrpvflutetype']['FieldLabel']}</span></td>
<td  > <font face='Verdana' size='2'><b>".$rowFlute[1]."   &nbsp;&nbsp;  -   &nbsp;&nbsp;  ".$rowFlute[2]." ply</b></font></td>
<td class='bg' style='background-color:$color;'><span class='style1_1'>{$FieldArray['cjrpvscoringtype']['FieldLabel']}</span></td>
<td colspan=2><font face='Verdana' size='2' style='color:$color1'><b>$scoringtype</b></font></td>
<td class='bg'><span class='style1_1'>{$FieldArray['cjrpvboardneed']['FieldLabel']}</span></td>
<td><font face='Verdana' size='2'><b>$BorderNeededQty</b></font></td>";

    echo "</tr>";
    $scoringTypeArr[$i] = $scoringtype;

    echo "<tr height=3px></tr>";

    echo "   <tr>";

		for ($j = 0; $j < count($outsideLinerArr); $j++) {
			if (strtoupper((string)$outsideLinerArr[$j]) == strtoupper((string)($rowsdt[8] ?? ''))) {
				$color2 = '';
			} else {
				$color2 = 'red';
			}
		}


		$outsideLinerArr[$i] = $rowsdt[8] ?? '';


    echo "
<td class='bg'><span class='style1_1'>{$FieldArray['cjrpvjobcardno']['FieldLabel']}</span></td>
<td  ><font face='Verdana' size='2' ><b>$jobCardRef</b></font></td>
<td class='bg'><span class='style1_1'>{$FieldArray['cjrpvinsideliner']['FieldLabel']}</span></td>
<td><font face='Verdana' size='2' ><b>$insideliner</b></font></td>
<td class='bg' style='background-color:$color2'><span class='style1_1'>{$FieldArray['cjrpvoutsideliner']['FieldLabel']}</span></td>
<td colspan=2><font face='Verdana' size='2' ><b>$outsideliner</b></font></td>
<td class='bg'><span class='style1_1'>{$FieldArray['cjrpvplannedqty']['FieldLabel']} </span></td>
<td><font face='Verdana' size='2' ><b>$plannedqty</b></font></td>
";

    echo "</tr>";
    echo "<tr height=3px></tr>";
    echo "<tr>
<td class='bg'><span class='style1_1'>{$FieldArray['cjrpvboxtype']['FieldLabel']}</span></td>
<td><font face='Verdana' size='2'><b>$BoxType_code</b></font></td>
<td class='bg'><span class='style1_1'>{$FieldArray['cjrpvstdgsm']['FieldLabel']}</span></td>
<td><font face='Verdana' size='2' ><b>$stdgsm</b></font></td>
<td><span class='style1_1'>&nbsp;</span></td>
<td colspan=2><font face='Verdana' size='2' ><b>&nbsp;</b></font></td>
<td class='bg'><span class='style1_1'>{$FieldArray['cjrpvproducedqty']['FieldLabel']} </span></td>
<td><font face='Verdana' size='2' ><b>$producedqty</b></font></td>
";
    echo "</tr>";


    // papercombinations.temp_TLiner1,
// papercombinations.temp_Liner1,
// papercombinations.temp_TFlutting1,
// papercombinations.temp_Flutting1,
// papercombinations.temp_Tliner2,
// papercombinations.temp_Liner2,
// papercombinations.temp_TFlutting2,
// papercombinations.temp_Flutting2,
// papercombinations.temp_TOuterLine,
// papercombinations.temp_OuterLine
    echo "<tr height=3px></tr>";
    echo " <tr>
<td class='bg'><span class='style1_1'>{$FieldArray['cjrpvboxsizeed']['FieldLabel']}</span></td>
<td><font face='Verdana' size='2' ><b>$external_length &nbsp;$external_width &nbsp;$external_height</b></font></td>
<td class='bg'><span class='style1_1'>{$FieldArray['cjrpvstdpaper']['FieldLabel']}</span></td>
<td colspan=2><font face='Verdana' size='2' ><b>$temp_TLiner1 &nbsp;$temp_Liner1 &nbsp;$temp_TFlutting1 &nbsp;$temp_Flutting1 &nbsp;$temp_Tliner2 &nbsp;$temp_Liner2
&nbsp;$temp_TFlutting2 &nbsp;$temp_Flutting2 &nbsp;$temp_TOuterLine &nbsp;$temp_OuterLine</b></font></td>
<td><span class='style1_1'>&nbsp;</span></td>
<td><font face='Verdana' size='2' ><b></b></font></td>
<td class='bg'><span class='style1_1'>{$FieldArray['cjrpvremainingqty']['FieldLabel']} </span></td>
<td><font face='Verdana' size='2' ><b>$remainingqty</b></font></td>
<td ></td>
<td></td>
";

    echo "</tr> <tr height=5px></tr>";

    $i = $i + 1;
  }
}

echo "<tr height=15px></tr>";
//$border = 'border:1px solid black';



echo "   <tr><td colspan=11>";
echo "<table border='0' style='border-collapse:collapse;table-layout:fixed;width:100%;' class='grid_data'>
<colgroup>
		<col width='12%'>
		<col width='10.6%'>
		<col width='6.85%'>
		<col width='6.85%'>
		<col width='6.85%'>
		<col width='6.85%'>
		<col width='7.14%'>
		<col width='7.14%'>
		<col width='7.14%'>
		<col width='7.14%'>
		<col width='7.14%'>
		<col width='7.14%'>
		<col width='7.14%'>
</colgroup>
<tr>
<th style='$border;'><span class='style1_1'>{$FieldArray['cjrpvcorrid']['FieldLabel']}</span></th>
<th style='$border'><span class='style1_1'>{$FieldArray['cjrpvsalesref']['FieldLabel']}</span></th>
<th style='$border'><span class='style1_1'>{$FieldArray['cjrpvlength']['FieldLabel']}</span></th>
<th style='$border'><span class='style1_1'>{$FieldArray['cjrpvwidth']['FieldLabel']}</span></th>
<th style='$border'><span class='style1_1'>{$FieldArray['cjrpvflaptop']['FieldLabel']}</span></th>
<th style='$border'><span class='style1_1'>{$FieldArray['cjrpvheight']['FieldLabel']}</span></th>
<th style='$border'><span class='style1_1'>{$FieldArray['cjrpvflapbot']['FieldLabel']}</span></th>
<th style='$border'><span class='style1_1'>{$FieldArray['cjrpvnoouts']['FieldLabel']}</span></th>
<th style='$border'><span class='style1_1'>{$FieldArray['cjrpvtwidth']['FieldLabel']}</span></th>
<th style='$border'><span class='style1_1'>{$FieldArray['cjrpvnocuts']['FieldLabel']}</span></th>
<th style='$border'><span class='style1_1'>{$FieldArray['cjrpvacuts']['FieldLabel']}</span></th>
<th style='$border'><span class='style1_1'>{$FieldArray['cjrpvlm']['FieldLabel']}</span></th>
<th style='$border'><span class='style1_1'>{$FieldArray['cjrpvsq']['FieldLabel']}</span></th>
</tr>";
for ($i = 0; $i < count($JobCardIdArr); $i++) {
  $query = " SELECT corgrid_id, related_salesorder,corrugatorid, corref, ifNull(length,0) as length , ifNull(width,0) as width,
   ifNull(flaptop,0) as flaptop, ifNull(height,0) as height, ifNull(flapbot,0) as flapbot,ifNull(numouts,0) as numouts, ifNull(twidth,0) as twidth, 
   ifNull(numcuts,0) as numcuts, ifNull(actualcuts,0) actualcuts, ifNull(reeldeckle,0) as reeldeckle, ifNull(totwidth,0) as totwidth, 
   ifNull(trimming,0) as trimming, ifNull(trimwaste,0) as trimwaste, ifNull(lm,0) as lm, 
      ifNull(sq,0) as sq,jobcard.jobcard_id,corref ,corrugatorid ,sorder_reference
      from  jobcard
      left join corgrid on jobcard.jobcard_Id= corgrid.jobcardid
      left join sorder  on sorder.sorder_id= jobcard.related_salesorder
      where corrugatorid=" . $txtCJRId . " and jobcard.jobcard_id=$JobCardIdArr[$i]";

  $result = mysqli_query($connection, $query) or die(mysqli_error($connection));
  $rows = mysqli_fetch_array($result);
  if (is_array($rows)) extract($rows);

  $getwidth = "select scoringflaptop + scorinhheight+scorinhbottom,scoringflaptop,scorinhheight,scorinhbottom
  from mastercard
            inner join jobcard on related_mastercard = mastercard_id
            where jobcard_id = $JobCardIdArr[$i]";
$resultwidth = mysqli_query($connection, $getwidth);
$rowwidth = mysqli_fetch_array($resultwidth);
$oldwidth = $rowwidth[0];
$oldFlapTop = $rowwidth[1];
$oldFlapHeight = $rowwidth[2];
$oldFlapBottom = $rowwidth[3];

if($oldwidth != $width ) $bg1 = 'background-color:red';
else $bg1 = 'background-color:white';

if($oldFlapTop != $flaptop ) $bg2 = 'background-color:red';
else $bg2 = 'background-color:white';

if($oldFlapHeight != $height ) $bg3 = 'background-color:red';
else $bg3 = 'background-color:white';

if($oldFlapBottom != $flapbot ) $bg4 = 'background-color:red';
else $bg4 = 'background-color:white';

  echo "<tr>
<td style='$border; text-align:center;'><font face='Verdana' size='2'><b>$corrugatorid</b></font></td>
<td style='$border; text-align:center;'><font face='Verdana' size='2' ><b>$sorder_reference</b></font></td>
<td style='$border; text-align:center;'><font face='Verdana' size='2'><b>$length</b></font></td>
<td style='$border;$bg1; text-align:center;'><font face='Verdana' size='2'><b>$width</b></font></td>
<td style='$border;$bg2; text-align:center;'><font face='Verdana' size='2'><b>$flaptop</b></font></td>
<td style='$border;$bg3; text-align:center;'><font face='Verdana' size='2'><b>$height</b></font></td>
<td style='$border;$bg4; text-align:center;'><font face='Verdana' size='2'><b>$flapbot</b></font></td>
<td style='$border; text-align:center;'><font face='Verdana' size='2'><b>$numouts</b></font></td>
<td style='$border; text-align:center;'><font face='Verdana' size='2'><b>$twidth</b></font></td>
<td style='$border; text-align:center;'><font face='Verdana' size='2'><b>$numcuts</b></font></td>
<td style='$border; text-align:center;'><font face='Verdana' size='2'><b>$actualcuts</b></font></td>
<td style='$border; text-align:center;'><font face='Verdana' size='2'><b>$lm</b></font></td>
<td style='$border; text-align:center;'><font face='Verdana' size='2'><b>$sq</b></font></td>
</tr>";
}

echo "</table>
</td>
</tr>";


echo "<tr height=5px></tr>";

if ($txtCJRId != '') {
  $query = "select reeldeckle,totwidth,trimming from corrugator1 where corrugator_id=$txtCJRId";
  $result = mysqli_query($connection, $query);
  $rows = mysqli_fetch_array($result);
  $reelDeckle = $rows[0];
  $totWidth = $rows[1];
  $trimming = $rows[2];


}

echo " <tr>
<td class='bg'><span class='style1_1'>{$FieldArray['cjrpvreeldeckle']['FieldLabel']}</span></td>
<td><font face='Verdana' size='2' ><b>$reelDeckle</b></font></td>
<td class='bg'><span class='style1_1'>{$FieldArray['cjrpvtotwidth']['FieldLabel']}</span></td>
<td><font face='Verdana' size='2' ><b>$totWidth</b></font></td>
<td class='bg'><span class='style1_1'>{$FieldArray['cjrpvtrimming']['FieldLabel']}</span></td>
<td><font face='Verdana' size='2' ><b>$trimming</b></font></td>
<td></td>
<td></td>
<td></td>
<td></td>
";

echo "</tr>";


echo "<tr height=15px></tr>";
//$border = 'border:1px solid black';

$queryLoc = "select loc_id, corrugatorid, location, gsm, papergradle, relldeckle, papermill, supplier, upperpaperrequired, upperpaperactual,
    lowerpaperrequired, lowerpaperactual, totalrequired, totalactual, paperinner, paperflutting, paperouter, balanceinner, balanceflutting, 
    balanceouter, wtpcupper, wtpclower, totalgsm, trimsize, trimrequired, trimactual, totalwasterequired, totalwasteactual, itemCode, itemDesc,
    upperpaperrequiredT, upperpaperactualT, lowerpaperrequiredT, lowerpaperactualT, totalrequiredT, totalactualT, trimsizeT, trimrequiredT, 
    trimactualT, totURequired, totURequiredT, totUactual, totUactualT, trimwasteper, corrugatorid2, warehouse_code,itemType
    From locationTable
    where corrugatorid='$txtCJRId' or corrugatorid2='$txtCJRId'";

$resultLoc = mysqli_query($connection, $queryLoc);



echo "   <tr><td colspan=11>";
echo "<table border='0' style='border-collapse:collapse;table-layout:fixed;width:100%;' class='grid_data'>
<colgroup>
		<col width='10.3%'>
		<col width='9.6%'>
		<col width='8.2%'>
		<col width='8.2%'>
		<col width='8.2%'>
		<col width='8.2%'>
		<col width='14.4%'>
		<col width='8.2%'>
		<col width='8.2%'>
		<col width='8.2%'>
		<col width='8.3%'>
</colgroup>
<tr>
<th style='$border;'><span class='style1_1'></span></th>
<th style='$border;'><span class='style1_1'>{$FieldArray['cjrpvitem']['FieldLabel']}</span></th>
<th style='$border'><span class='style1_1'>{$FieldArray['cjrpvreeldeckle']['FieldLabel']}</span></th>
<th style='$border'><span class='style1_1'>{$FieldArray['cjrpvgsm']['FieldLabel']}</span></th>
<th style='$border'><span class='style1_1'>{$FieldArray['cjrpvpapergrade']['FieldLabel']}</span></th>
<th style='$border'><span class='style1_1'>{$FieldArray['cjrpvsizemm']['FieldLabel']}</span></th>
<th style='$border'><span class='style1_1'>{$FieldArray['cjrpvpapermill']['FieldLabel']}</span></th>
<th style='$border'><span class='style1_1'>{$FieldArray['cjrpvwarehouse']['FieldLabel']}</span></th>
<th style='$border'><span class='style1_1'>{$FieldArray['cjrpvsupplier']['FieldLabel']}</span></th>
<th style='$border'><span class='style1_1'>{$FieldArray['cjrpvurequired']['FieldLabel']}</span></th>
<th hidden='hidden' style='$border'><span class='style1_1'>{$FieldArray['cjrpvuact']['FieldLabel']}</span></th>
<th style='$border'><span class='style1_1'>{$FieldArray['cjrpvlrequired']['FieldLabel']}</span></th>
<th hidden='hidden' style='$border'><span class='style1_1'>{$FieldArray['cjrpvlact']['FieldLabel']}</span></th>
<th style='$border' hidden='hidden'><span class='style1_1'>{$FieldArray['cjrpvtreq']['FieldLabel']}</span></th>
<th hidden='hidden' style='$border'><span class='style1_1'>{$FieldArray['cjrpvtact']['FieldLabel']}</span></th>
<th style='$border' hidden='hidden'><span class='style1_1'>{$FieldArray['cjrpvreqtrim']['FieldLabel']}</span></th>
<th hidden='hidden' style='$border'><span class='style1_1'>{$FieldArray['cjrpvacttrim']['FieldLabel']}</span></th>

</tr>";
while ($rowsLoc = mysqli_fetch_array($resultLoc)) {
  extract($rowsLoc);
  echo "
<tr>

<td style='$border; text-align:center;'><font face='Verdana' size='2'><b>$itemType</b></font></td>
<td style='$border; text-align:center;'><font face='Verdana' size='2'><b>$itemCode</b></font></td>
<td style='$border; text-align:center;'><font face='Verdana' size='2'><b>$relldeckle</b></font></td>
<td style='$border; text-align:center;'><font face='Verdana' size='2' ><b>$gsm</b></font></td>
<td style='$border; text-align:center;'><font face='Verdana' size='2'><b>$papergradle</b></font></td>
<td style='$border; text-align:center;'><font face='Verdana' size='2'><b>$trimsize</b></font></td>
<td style='$border; text-align:center;'><font face='Verdana' size='2'><b>$papermill</b></font></td>
<td style='$border; text-align:center;'><font face='Verdana' size='2'><b>$warehouse_code</b></font></td>
<td style='$border; text-align:center;'><font face='Verdana' size='2'><b>$supplier</b></font></td>
<td style='$border; text-align:center;'><font face='Verdana' size='2'><b>$upperpaperrequired</b></font></td>
<td hidden='hidden' style='$border; text-align:center;'><font face='Verdana' size='2'><b>$upperpaperactual</b></font></td>
<td style='$border; text-align:center;'><font face='Verdana' size='2'><b>$lowerpaperrequired</b></font></td>
<td hidden='hidden' style='$border; text-align:center;'><font face='Verdana' size='2'><b>$lowerpaperactual</b></font></td>
<td style='$border; text-align:center;' hidden='hidden'><font face='Verdana' size='2'><b>$totURequired</b></font></td>
<td hidden='hidden' style='$border; text-align:center;' ><font face='Verdana' size='2'><b>$totUactual</b></font></td>
<td style='$border; text-align:center;' hidden='hidden'><font face='Verdana' size='2'><b>$trimrequired</b></font></td>
<td hidden='hidden' style='$border; text-align:center;'><font face='Verdana' size='2'><b>$trimactual</b></font></td>

</tr>";
}


echo "
</table>
</td>
</tr>";

$routingArr = explode(',', (string)($routing ?? ''));
for ($i = 0; $i < count($routingArr); $i++) {
  if ($routingArr[$i] == 'Corrugator') {
    $COR = 'checked';
  } else if ($routingArr[$i] == 'flexo1') {
    $FFG = 'checked';
  } else if ($routingArr[$i] == 'diecutter') {
    $RDC = 'checked';
  } else if ($routingArr[$i] == 'foldergluerstit') {
    $FGS = 'checked';
  } else if ($routingArr[$i] == 'slitterscorer') {
    $SS = 'checked';
  } else if ($routingArr[$i] == 'partitionmachin') {
    $PRT = 'checked';
  }
}

echo "<tr height='5px;'></tr>";


if ($txtCJRId != '') {
  $query = "select upperpaperrequiredT ,upperpaperactualT ,lowerpaperrequiredT ,lowerpaperactualT ,totURequiredT ,totUactualT ,
  paperinner ,paperflutting ,paperouter ,balanceinner ,balanceflutting ,balanceouter ,wtpcupper ,wtpclower ,totalgsm ,trimsizeT ,
  trimrequiredT ,trimactualT ,trimwasteper 
  from corrugator1 where corrugator_id=$txtCJRId";
  $result = mysqli_query($connection, $query);
  $rows = mysqli_fetch_array($result);
  $UReqTot = $rows[0];
  $UActTot = $rows[1];
  $LReqTot = $rows[2];
  $LActTot = $rows[3];
  $TReqTot = $rows[4];
  $TActTot = $rows[5];

  $IPaper = $rows[6];
  $FPaper = $rows[7];
  $OPaper = $rows[8];
  $IBalance = $rows[9];
  $FBalance = $rows[10];
  $OBalance = $rows[11];

  $wtpcupper = $rows[12];
  $wtpclower = $rows[13];
  $totGSM = $rows[14];
  $totSizeTrim = $rows[15];
  $ReqTrimTot = $rows[16];
  $ActTrimTot = $rows[17];

  $trimWastePer = $rows[18];



}

if (strlen((string)($flutetype_code ?? '')) == 1) {
  $rowsNb_ = 3;
} else if (strlen((string)($flutetype_code ?? '')) == 2) {
  $rowsNb_ = 5;
} else {
  $rowsNb_ = 3;
}

echo " </table><table border='0' style='border-collapse:collapse;table-layout:fixed;width:100%;'>
<colgroup>
		<col width='10%'>
		<col width='5%'>
		<col width='12%'>
		<col width='5%'>
		<col width='11%'>
		<col width='5%'>
		<col width='12.5%'>
		<col width='5%'>
		<col width='9%'>
		<col width='5%'>
		<col width='15.5%'>
		<col width='5%'>
</colgroup><tr>
<td class='bg'><span class='style1_1'>{$FieldArray['cjrpvurequired']['FieldLabel']}</span></td>
<td><font face='Verdana' size='2' ><b>$UReqTot</b></font></td>";

if ($rowsNb_ == 3) {
  $query = "select totURequired from locationtable where corrugatorid='$txtCJRId' and itemtype='INNER LINER'";
  $result = mysqli_query($connection, $query);
  $rows = mysqli_fetch_array($result);
  $totalrequired = $rows[0];
  
  $query = "select stockqty from locationtable where corrugatorid='$txtCJRId' and itemtype='INNER LINER'";
	$result = mysqli_query($connection, $query);
	$rows   = mysqli_fetch_array($result);
	$stockQty = $rows[0];

  if ($totalrequired > $stockQty)
    $bg = 'background-color:red';
  echo " <td class='bg' style='$bg'><span class='style1_1' >{$FieldArray['cjrpvireq']['FieldLabel']}</span></td>
  <td><font face='Verdana' size='2' ><b>$totalrequired</b></font></td>";

} else if ($rowsNb_ == 5) {
  $query = "select totURequired from locationtable where corrugatorid='$txtCJRId' and itemtype='LINER-1'";
  $result = mysqli_query($connection, $query);
  $rows = mysqli_fetch_array($result);
  $totalrequired = $rows[0];
  if ($totalrequired > $stockQty)
    $bg = 'background-color:red';
  echo "  <td class='bg'><span class='style1_1' style='$bg'>{$FieldArray['cjrpvliner1req']['FieldLabel']}</span></td>
    <td><font face='Verdana' size='2' ><b>$totalrequired</b></font></td>";

} else {
  echo " <td class='bg'><span class='style1_1'>{$FieldArray['cjrpvireq']['FieldLabel']}</span></td>
    <td><font face='Verdana' size='2' ><b>$totalrequired</b></font></td>";

}

$totalReq = $totalReq + $totalrequired;

if ($rowsNb_ == 3) {
  $query = "select stockqty from locationtable where corrugatorid='$txtCJRId' and itemtype='INNER LINER'";
  $result = mysqli_query($connection, $query);
  $rows = mysqli_fetch_array($result);
  $stockQty = $rows[0];
  echo " <td class='bg'><span class='style1_1'>{$FieldArray['cjrpvinnerstk']['FieldLabel']}</span></td>
    <td ><font face='Verdana' size='2' ><b>$stockQty</b></font></td>";

} else if ($rowsNb_ == 5) {
  $query = "select stockqty from locationtable where corrugatorid='$txtCJRId' and itemtype='LINER-1'";
  $result = mysqli_query($connection, $query);
  $rows = mysqli_fetch_array($result);
  $stockQty = $rows[0];
  echo "  <td class='bg'><span class='style1_1'>{$FieldArray['cjrpvliner1stk']['FieldLabel']}</span></td>
      <td ><font face='Verdana' size='2' ><b>$stockQty</b></font></td>";

} else {
  echo " <td class='bg'><span class='style1_1'>{$FieldArray['cjrpvinnerstk']['FieldLabel']}</span></td>
      <td ><font face='Verdana' size='2' ><b>$stockQty</b></font></td>";

} 

if ($rowsNb_ == 3) {
  $query = "select itemCode from locationtable where corrugatorid='$txtCJRId' and itemtype='INNER LINER'";
  $result = mysqli_query($connection, $query);
  $rows = mysqli_fetch_array($result);
  $itemCode = $rows[0];
  $q = "Select Sum(bookingqty_qty - bookingqty_usedqty) from bookingqty where item_code ='$itemCode' and ifnull(cjr_id,0) <> 0  ";
  $res = mysqli_query($connection, $q);
  $ress = mysqli_fetch_array($res);
  $book = $ress[0];
  echo " <td class='bg'><span class='style1_1'>{$FieldArray['cjrpvibooking']['FieldLabel']}</span></td>
      <td><font face='Verdana' size='2' ><b>$book</b></font></td>";

} else if ($rowsNb_ == 5) {
  $query = "select itemCode from locationtable where corrugatorid='$txtCJRId' and itemtype='LINER-1'";
  $result = mysqli_query($connection, $query);
  $rows = mysqli_fetch_array($result);
  $itemCode = $rows[0];
  $q = "Select Sum(bookingqty_qty - bookingqty_usedqty) from bookingqty where item_code ='$itemCode'  and ifnull(cjr_id,0) <> 0  ";
  $res = mysqli_query($connection, $q);
  $ress = mysqli_fetch_array($res);
  $book = $ress[0];
  echo " <td class='bg'><span class='style1_1'>{$FieldArray['cjrpvliner1booking']['FieldLabel']}</span></td>
      <td><font face='Verdana' size='2' ><b>$book</b></font></td>";
}

echo " <td class='bg'><span class='style1_1'>{$FieldArray['cjrpvwtpcu']['FieldLabel']}</span></td>
    <td><font face='Verdana' size='2' ><b>$wtpcupper</b></font></td>";

if ($rowsNb_ == 3) {
  $query = "select trimrequired from locationtable where corrugatorid='$txtCJRId' and itemtype='INNER LINER'";
  $result = mysqli_query($connection, $query);
  $rows = mysqli_fetch_array($result);
  $trimrequired = $rows[0];
  echo " <td class='bg'><span class='style1_1'>{$FieldArray['cjrpvireqtrimkg']['FieldLabel']}</span></td>
      <td><font face='Verdana' size='2' ><b>$trimrequired</b></font></td>";

} else if ($rowsNb_ == 5) {
  $query = "select trimrequired from locationtable where corrugatorid='$txtCJRId' and itemtype='LINER-1'";
  $result = mysqli_query($connection, $query);
  $rows = mysqli_fetch_array($result);
  $trimrequired = $rows[0];
  echo "  <td class='bg'><span class='style1_1'>{$FieldArray['cjrpvliner1reqtrimkg']['FieldLabel']}</span></td>
        <td><font face='Verdana' size='2' ><b>$trimrequired</b></font></td>";

} else {
  echo " <td class='bg'><span class='style1_1'>{$FieldArray['cjrpvireqtrimkg']['FieldLabel']}</span></td>
        <td><font face='Verdana' size='2' ><b>$trimrequired</b></font></td>";

}

echo "</tr>";

echo "<tr height='8px;'></tr>";

echo " <tr>
<td class='bg'><span class='style1_1'>{$FieldArray['cjrpvlrequired']['FieldLabel']}</span></td>
<td><font face='Verdana' size='2' ><b>$LReqTot</b></font></td>
";
if ($rowsNb_ == 3) {
  $query = "select totURequired from locationtable where corrugatorid='$txtCJRId' and itemtype='FLUTE'";
  $result = mysqli_query($connection, $query);
  $rows = mysqli_fetch_array($result);
  $totalrequired = $rows[0];
  $query = "select stockqty from locationtable where corrugatorid='$txtCJRId' and itemtype='FLUTE'";
  $result = mysqli_query($connection, $query);
  $rows   = mysqli_fetch_array($result);
  $stockQty = $rows[0];
  if ($stockQty < $totalrequired)
    $bg = "style='background-color:red'";
  else
    $bg = '';
  echo " <td class='bg' $bg><span class='style1_1'>{$FieldArray['cjrpvfreq']['FieldLabel']}</span></td>
  <td><font face='Verdana' size='2' ><b>$totalrequired</b></font></td>";

} else if ($rowsNb_ == 5) {
  $query = "select totURequired from locationtable where corrugatorid='$txtCJRId' and itemtype='FLUTE-1'";
  $result = mysqli_query($connection, $query);
  $rows = mysqli_fetch_array($result);
  $totalrequired = $rows[0];
  echo "  <td class='bg'><span class='style1_1'>{$FieldArray['cjrpvflute1req']['FieldLabel']}</span></td>
    <td><font face='Verdana' size='2' ><b>$totalrequired</b></font></td>";

} else {
  echo " <td class='bg'><span class='style1_1'>{$FieldArray['cjrpvfreq']['FieldLabel']}</span></td>
    <td><font face='Verdana' size='2' ><b>$totalrequired</b></font></td>";

}

$totalReq = $totalReq + $totalrequired;

if ($rowsNb_ == 3) {
  $query = "select stockqty from locationtable where corrugatorid='$txtCJRId' and itemtype='FLUTE'";
  $result = mysqli_query($connection, $query);
  $rows = mysqli_fetch_array($result);
  $stockQty = $rows[0];
  echo " <td class='bg'><span class='style1_1'>{$FieldArray['cjrpvfluttingstk']['FieldLabel']}</span></td>
    <td><font face='Verdana' size='2' ><b>$stockQty</b></font></td>";

} else if ($rowsNb_ == 5) {
  $query = "select stockqty from locationtable where corrugatorid='$txtCJRId' and itemtype='FLUTE-1'";
  $result = mysqli_query($connection, $query);
  $rows = mysqli_fetch_array($result);
  $stockQty = $rows[0];
  echo "  <td class='bg'><span class='style1_1'>{$FieldArray['cjrpvflute1stk']['FieldLabel']}</span></td>
      <td><font face='Verdana' size='2' ><b>$stockQty</b></font></td>";

} else {
  echo " <td class='bg'><span class='style1_1'>{$FieldArray['cjrpvfluttingstk']['FieldLabel']}</span></td>
      <td><font face='Verdana' size='2' ><b>$stockQty</b></font></td>";

}

if ($rowsNb_ == 3) {
  $query = "select itemCode from locationtable where corrugatorid='$txtCJRId' and itemtype='FLUTE'";
  $result = mysqli_query($connection, $query);
  $rows = mysqli_fetch_array($result);
  $itemCode = $rows[0];
  $q = "Select Sum(bookingqty_qty - bookingqty_usedqty) from bookingqty where item_code ='$itemCode' ";
  $res = mysqli_query($connection, $q);
  $ress = mysqli_fetch_array($res);
  $book = $ress[0];
  echo " <td class='bg'><span class='style1_1'>{$FieldArray['cjrpvfbooking']['FieldLabel']}</span></td>
      <td><font face='Verdana' size='2' ><b>$book</b></font></td>";
} else if ($rowsNb_ == 5) {
  $query = "select itemCode from locationtable where corrugatorid='$txtCJRId' and itemtype='FLUTE-1'";
  $result = mysqli_query($connection, $query);
  $rows = mysqli_fetch_array($result);
  $itemCode = $rows[0];
  $q = "Select Sum(bookingqty_qty - bookingqty_usedqty) from bookingqty where item_code ='$itemCode' ";
  $res = mysqli_query($connection, $q);
  $ress = mysqli_fetch_array($res);
  $book = $ress[0];
  echo " <td class='bg'><span class='style1_1'>{$FieldArray['cjrpvflute1booking']['FieldLabel']}</span></td>
      <td><font face='Verdana' size='2' ><b>$book</b></font></td>";
}

echo " <td class='bg'><span class='style1_1'>{$FieldArray['cjrpvwtpcl']['FieldLabel']}</span></td>
    <td><font face='Verdana' size='2' ><b>$wtpclower</b></font></td>";

if ($rowsNb_ == 3) {
  $query = "select trimrequired from locationtable where corrugatorid='$txtCJRId' and itemtype='FLUTE'";
  $result = mysqli_query($connection, $query);
  $rows = mysqli_fetch_array($result);
  $trimrequired = $rows[0];
  echo " <td class='bg'><span class='style1_1'>{$FieldArray['cjrpvfreqtrimkg']['FieldLabel']}</span></td>
      <td><font face='Verdana' size='2' ><b>$trimrequired</b></font></td>";

} else if ($rowsNb_ == 5) {
  $query = "select trimrequired from locationtable where corrugatorid='$txtCJRId' and itemtype='FLUTE-1'";
  $result = mysqli_query($connection, $query);
  $rows = mysqli_fetch_array($result);
  $trimrequired = $rows[0];
  echo "  <td class='bg'><span class='style1_1'>{$FieldArray['cjrpvflute1reqtrimkg']['FieldLabel']}</span></td>
        <td><font face='Verdana' size='2' ><b>$trimrequired</b></font></td>";

} else {
  echo " <td class='bg'><span class='style1_1'>{$FieldArray['cjrpvfreqtrimkg']['FieldLabel']}</span></td>
        <td><font face='Verdana' size='2' ><b>$trimrequired</b></font></td>";

}
echo "</tr>";

echo "<tr height='8px;'></tr>";

$TReqTot = $UReqTot + $LReqTot;
echo " <tr>
<td class='bg'><span class='style1_1'>{$FieldArray['cjrpvtrequired']['FieldLabel']}</span></td>
<td><font face='Verdana' size='2' ><b>$TReqTot</b></font></td>
";

if ($rowsNb_ == 3) {
  $query = "select totURequired from locationtable where corrugatorid='$txtCJRId' and itemtype='OUTER LINER'";
  $result = mysqli_query($connection, $query);
  $rows = mysqli_fetch_array($result);
  $totalrequired = $rows[0];
  $query = "select stockqty from locationtable where corrugatorid='$txtCJRId' and itemtype='OUTER LINER'";
	$result = mysqli_query($connection, $query);
	$rows   = mysqli_fetch_array($result);
	$stockQty = $rows[0];
  if ($stockQty < $totalrequired)
    $bg = "style='background-color:red'";
  else
    $bg = '';
  echo " <td class='bg' $bg><span class='style1_1'>{$FieldArray['cjrpvoreq']['FieldLabel']}</span></td>
  <td><font face='Verdana' size='2' ><b>$totalrequired</b></font></td>";

} else if ($rowsNb_ == 5) {
  $query = "select totURequired from locationtable where corrugatorid='$txtCJRId' and itemtype='LINER-2'";
  $result = mysqli_query($connection, $query);
  $rows = mysqli_fetch_array($result);
  $totalrequired = $rows[0];
  echo "  <td class='bg'><span class='style1_1'>{$FieldArray['cjrpvliner2req']['FieldLabel']}</span></td>
    <td><font face='Verdana' size='2' ><b>$totalrequired</b></font></td>";

} else {
  echo " <td class='bg'><span class='style1_1'>{$FieldArray['cjrpvoreq']['FieldLabel']}</span></td>
    <td><font face='Verdana' size='2' ><b>$totalrequired</b></font></td>";

}

$totalReq = $totalReq + $totalrequired;

if ($rowsNb_ == 3) {
  $query = "select stockqty from locationtable where corrugatorid='$txtCJRId' and itemtype='OUTER LINER'";
  $result = mysqli_query($connection, $query);
  $rows = mysqli_fetch_array($result);
  $stockQty = $rows[0];
  echo " <td class='bg'><span class='style1_1'>{$FieldArray['cjrpvouterstk']['FieldLabel']}</span></td>
    <td><font face='Verdana' size='2' ><b>$stockQty</b></font></td>";

} else if ($rowsNb_ == 5) {
  $query = "select stockqty from locationtable where corrugatorid='$txtCJRId' and itemtype='LINER-2'";
  $result = mysqli_query($connection, $query);
  $rows = mysqli_fetch_array($result);
  $stockQty = $rows[0];
  echo "  <td class='bg'><span class='style1_1'>{$FieldArray['cjrpvliner2stk']['FieldLabel']}</span></td>
      <td><font face='Verdana' size='2' ><b>$stockQty</b></font></td>";

} else {
  echo " <td class='bg'><span class='style1_1'>{$FieldArray['cjrpvouterstk']['FieldLabel']}</span></td>
      <td><font face='Verdana' size='2' ><b>$stockQty</b></font></td>";

}


if ($rowsNb_ == 3) {
  $query = "select itemCode from locationtable where corrugatorid='$txtCJRId' and itemtype='OUTER LINER'";
  $result = mysqli_query($connection, $query);
  $rows = mysqli_fetch_array($result);
  $itemCode = $rows[0];
  $q = "Select Sum(bookingqty_qty - bookingqty_usedqty) from bookingqty where item_code ='$itemCode' ";
  $res = mysqli_query($connection, $q);
  $ress = mysqli_fetch_array($res);
  $book = $ress[0];
  echo " <td class='bg'><span class='style1_1'>{$FieldArray['cjrpvobooking']['FieldLabel']}</span></td>
    <td><font face='Verdana' size='2' ><b>$book</b></font></td>";

}else if ($rowsNb_ == 5) {
  $query = "select itemCode from locationtable where corrugatorid='$txtCJRId' and itemtype='LINER-2'";
  $result = mysqli_query($connection, $query);
  $rows = mysqli_fetch_array($result);
  $itemCode = $rows[0];
  $q = "Select Sum(bookingqty_qty - bookingqty_usedqty) from bookingqty where item_code ='$itemCode' ";
  $res = mysqli_query($connection, $q);
  $ress = mysqli_fetch_array($res);
  $book = $ress[0];
  echo " <td class='bg'><span class='style1_1'>{$FieldArray['cjrpvlinner2booking']['FieldLabel']}</span></td>
    <td><font face='Verdana' size='2' ><b>$book</b></font></td>";

}

echo " <td class='bg'><span class='style1_1'>{$FieldArray['cjrpvtotgsm']['FieldLabel']}</span></td>
    <td><font face='Verdana' size='2' ><b>$totGSM</b></font></td>";

if ($rowsNb_ == 3) {
  $query = "select trimrequired from locationtable where corrugatorid='$txtCJRId' and itemtype='OUTER LINER'";
  $result = mysqli_query($connection, $query);
  $rows = mysqli_fetch_array($result);
  $trimrequired = $rows[0];
  echo " <td class='bg'><span class='style1_1'>{$FieldArray['cjrpvoreqtrimkg']['FieldLabel']}</span></td>
      <td><font face='Verdana' size='2' ><b>$trimrequired</b></font></td>";

} else if ($rowsNb_ == 5) {
  $query = "select trimrequired from locationtable where corrugatorid='$txtCJRId' and itemtype='LINER-2'";
  $result = mysqli_query($connection, $query);
  $rows = mysqli_fetch_array($result);
  $trimrequired = $rows[0];
  echo "  <td class='bg'><span class='style1_1'>{$FieldArray['cjrpvliner2reqtrimkg']['FieldLabel']}</span></td>
        <td><font face='Verdana' size='2' ><b>$trimrequired</b></font></td>";

} else {
  echo " <td class='bg'><span class='style1_1'>{$FieldArray['cjrpvoreqtrimkg']['FieldLabel']}</span></td>
        <td><font face='Verdana' size='2' ><b>$trimrequired</b></font></td>";

}

echo "</tr>";

echo "<tr height='8px;'></tr>";

echo " <tr>
<td class='bg'><span class='style1_1'>{$FieldArray['cjrpvtrimwasteper']['FieldLabel']}</span></td>
<td><font face='Verdana' size='2' ><b>$trimWastePer</b></font></td>
";

if ($rowsNb_ == 5) {
  $query = "select totURequired from locationtable where corrugatorid='$txtCJRId' and itemtype='FLUTE-2'";
  $result = mysqli_query($connection, $query);
  $rows = mysqli_fetch_array($result);
  $totalrequired = $rows[0];
  echo " <td class='bg'><span class='style1_1'>{$FieldArray['cjrpvflute2req']['FieldLabel']}" . $orderimg3 . "</span></td>
  <td><font face='Verdana' size='2' ><b>$totalrequired</b></font></td>";

  $query = "select stockqty from locationtable where corrugatorid='$txtCJRId' and itemtype='FLUTE-2'";
  $result = mysqli_query($connection, $query);
  $rows = mysqli_fetch_array($result);
  $stockQty = $rows[0];
  echo " <td class='bg'><span class='style1_1'>{$FieldArray['cjrpvflute2stk']['FieldLabel']}" . $orderimg3 . "</span></td>
  <td><font face='Verdana' size='2' ><b>$stockQty</b></font></td>";

  $query = "select itemCode from locationtable where corrugatorid='$txtCJRId' and itemtype='FLUTE-2'";
		$result = mysqli_query($connection, $query);
		$rows   = mysqli_fetch_array($result);
		$itemCode = $rows[0];
		$q = "Select Sum(bookingqty_qty - bookingqty_usedqty) from bookingqty where item_code ='$itemCode' and ifnull(cjr_id,0) <> 0  ";
		$res = mysqli_query($connection, $q);
		$ress   = mysqli_fetch_array($res);
		$book = $ress[0];

    echo " <td class='bg'><span class='style1_1'>{$FieldArray['cjrpvflute2booking']['FieldLabel']}" . $orderimg3 . "</span></td>
    <td><font face='Verdana' size='2' ><b>$book</b></font></td>";

  echo "<td align='center' ><span class='style1'>&nbsp;</span></td>
  <td align='center' ><span class='style1'>&nbsp;</span></td>";
  $query = "select trimrequired from locationtable where corrugatorid='$txtCJRId' and itemtype='FLUTE-2'";
  $result = mysqli_query($connection, $query);
  $rows = mysqli_fetch_array($result);
  $trimrequired = $rows[0];
  echo " <td class='bg'><span class='style1_1'>{$FieldArray['cjrpvflute2reqtrimkg']['FieldLabel']}" . $orderimg3 . "</span></td>
  <td><font face='Verdana' size='2' ><b>$trimrequired</b></font></td></tr>";
  echo "<tr height='8px;'></tr>";
  echo "<tr><td align='center' ><span class='style1'>&nbsp;</span></td>
  <td align='center' ><span class='style1'>&nbsp;</span></td>";

  $query = "select totURequired from locationtable where corrugatorid='$txtCJRId' and itemtype='OUTER LINER'";
  $result = mysqli_query($connection, $query);
  $rows = mysqli_fetch_array($result);
  $totalrequired = $rows[0];
  echo " <td class='bg'><span class='style1_1'>{$FieldArray['cjrpvolinnerreq']['FieldLabel']}" . $orderimg3 . "</span></td>
  <td><font face='Verdana' size='2' ><b>$totalrequired</b></font></td>";

  $query = "select stockqty from locationtable where corrugatorid='$txtCJRId' and itemtype='OUTER LINER'";
  $result = mysqli_query($connection, $query);
  $rows = mysqli_fetch_array($result);
  $stockqty = $rows[0];
  echo "  <td class='bg'><span class='style1_1'>{$FieldArray['cjrpvolinnerstk']['FieldLabel']}" . $orderimg3 . "</span></td>
			<td><font face='Verdana' size='2' ><b>$stockqty</b></font></td>";
  $query = "select itemCode from locationtable where corrugatorid='$txtCJRId' and itemtype='OUTER LINER'";
	$result = mysqli_query($connection, $query);
	$rows   = mysqli_fetch_array($result);
	$itemCode = $rows[0];
	$q = "Select Sum(bookingqty_qty - bookingqty_usedqty) from bookingqty where item_code ='$itemCode' and ifnull(cjr_id,0) <> 0  ";
	$res = mysqli_query($connection, $q);
	$ress   = mysqli_fetch_array($res);
	$book = $ress[0];
		echo "  <td  class='bg' ><span class='style1_1'>{$FieldArray['cjrpvobooking']['FieldLabel']}" . $orderimg3 . "</span></td>
    	<td><font face='Verdana' size='2' ><b>$book</b></font></td>";
  echo "<td align='center' ><span class='style1'>&nbsp;</span></td>
  <td align='center' ><span class='style1'>&nbsp;</span></td>";
  $query = "select trimrequired from locationtable where corrugatorid='$txtCJRId' and itemtype='OUTER LINER'";
  $result = mysqli_query($connection, $query);
  $rows = mysqli_fetch_array($result);
  $trimrequired = $rows[0];

  echo " <td class='bg'><span class='style1_1'>{$FieldArray['cjrpvolinerreqtrimkg']['FieldLabel']}" . $orderimg3 . "</span></td>
  <td><font face='Verdana' size='2' ><b>$trimrequired</b></font></td></tr>";

  echo "<tr height='8px;'></tr>";

echo " <tr>
<td align='center' ><span class='style1'>&nbsp;</span></td>
  <td align='center' ><span class='style1'>&nbsp;</span></td>
<td class='bg'><span class='style1_1'>{$FieldArray['cjrpvtotalreq']['FieldLabel']}</span></td>
<td><font face='Verdana' size='2' ><b>" . round($totalReq) . "</b></font></td>
</tr>";


echo "</tr>";

}else{
echo "
<td class='bg'><span class='style1_1'>{$FieldArray['cjrpvtotalreq']['FieldLabel']}</span></td>
<td><font face='Verdana' size='2' ><b>" . round($totalReq) . "</b></font></td>
</tr>";
}





echo "<tr height='15px;'></tr>";





echo "<tr height='30px;'></tr>";


echo "   </table></td></tr><tr height='35'><td align='center' id='content-bottom' ></td></tr>";


echo " </table>  ";//end of last table menu

if (($actionmode == "post" || $actionmode == "confirmorder") && ($PrPorId ?? '') != '') {

  $query = " SELECT jobcard_reference, ifNull(Porder_Confirmed,0) " .
    " FROM jobcard" .
    " WHERE jobcard_id = $PrPorId ";
  $result = selectData($connection, $query);
  $PrPorRef = $result[0];
  $PorConfirmed = $result[1];

  $AskComfirm = 0;

  echo '<script type="text/javascript" language="javascript">';
  /*
   if (($mainaction == "insert" || ($mainaction == "edit" && $PorConfirmed == 0)) && $actionmode == "post" && $Module_PorderConfirm == 1 && $FI_PorderConfirmed == 1) {
     echo ' ConfirmOrder(\''.$PrPorId.'\'); ';
     
     $AskComfirm = 1;
   */

  if (($AskComfirm == 1 && $actionmode == "confirmorder") || $AskComfirm == 0) {
    if ($Login_FastPrint == 1)
      echo ' PrintPurchaseOrder(\'' . $PrPorId . '\', \'' . $PrPorRef . '\'); ';

    echo ' ClearPage1(); ';
  }

  echo '</script>';

}



print <<<HERE
</form>


</body>

HERE;

?>