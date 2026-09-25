<?php

	session_start();
	header("Content-type: text/html; charset=" . $_SESSION['encodingmode']);
	if ($_SESSION['dbName'] == "") {
		header("location:../index.php");
	}


	include("config.inc.php");
	include("Functions_Purchase.php");
	include("Forms_DataGrid_SalesOrder.php");

	$connection = mysqli_connect($config['dbServer'], $config['dbUser'], $config['dbPass']) or die("Could not connect to DB");
	mysqli_select_db($connection, $_SESSION['dbName']) or die("Could not find DB");

	if ($_SESSION['encodingmode'] == 'utf8' || $_SESSION['encodingmode'] == 'utf-8') {
		mysqli_query($connection, "SET NAMES 'utf8'");
		mysqli_query($connection, 'SET CHARACTER SET utf8');
		mysqli_set_charset($connection, 'utf8');
	}


	$querylog = "SELECT 1 as loginaccess from " . $_SESSION['dbLabel'] . ".login where user_account='" . $_SESSION['useraccount'] . "' 	and Upper(Login_Schema) = Upper('" . $_SESSION['dbName'] . "')	";
	$resultlog = mysqli_query($connection, $querylog) or die(mysqli_error($connection));
	while ($rows = mysqli_fetch_array($resultlog)) {
		extract($rows);
	}

	$querylog = "SELECT ifNull(FI_CJRRestrict,0) as FI_CJRRestrict from " . $_SESSION['dbLabel'] . ".listuserdir" .
		" where Usercode = '" . $_SESSION['useraccount'] . "' and upper(concat(CompanyName,'_',Diryear)) = Upper('" . $_SESSION['dbName'] . "') ";
	$resultlog = mysqli_query($connection, $querylog) or die(mysqli_error($connection));
	while ($rows = mysqli_fetch_array($resultlog)) {
		extract($rows);
	}

	if ($loginaccess == '' or $loginaccess == 0)
		header("location:menu.php");


	// Language / RTL settings - same helper Form_Delivery uses, with LTR fallbacks when it isn't loaded.
	$FieldArray  = array();
	$ButtonArray = array();
	$rtl_class  = '';
	$body_dir   = 'ltr';
	$body_class = '';
	if (function_exists('initFormLanguageUI')) {
		$ui = initFormLanguageUI('CJR', $_SESSION['dbLabel'], $connection, $FieldArray, $ButtonArray);
		$rtl_class  = $ui['container_class'];
		$body_dir   = $ui['dir'];
		$body_class = $ui['body_class'];
	}

	// Input look (bordered / clean) - same INPUT_BORDER setting as Form_Delivery.
	$corrugatordesign = 0;
	if (function_exists('getSmodulevalue')) {
		$corrugatordesign = getSmodulevalue('INPUT_BORDER', 'int', $_SESSION['logincompany'], $_SESSION['dbLabel'], $connection);
	}
	if ($corrugatordesign == 0) {
		$inputClass    = "inputBox-clean";
		$selectClass   = "select-clean";
		$textareaClass = "textarea-clean";
	} else {
		$inputClass    = "inputBox";
		$selectClass   = "form-control form-control-sm inputBox";
		$textareaClass = "form-control form-control-sm inputBox";
	}


	$txtCorrugatorId = $_POST['txtCorrugatorId'] ?? '';
	if ($txtCorrugatorId == '')
		$txtCorrugatorId = $_GET['txtCorrugatorId'] ?? '';
	// $corrId = $_POST['corrId'];
	// if($corrId == '') $corrId = $_GET['corrId'];


	$JobCardId = $_POST['JobCardId'] ?? '';
	if ($JobCardId == '')
		$JobCardId = $_GET['JobCardId'] ?? '';


	$txtItemCode = $_POST['txtItemCode'] ?? '';
	if ($txtItemCode == '')
		$txtItemCode = $_GET['txtItemCode'] ?? '';

	$I = $_POST['I'] ?? '';
	if ($I == '')
		$I = $_GET['I'] ?? '';

	$mode = $_POST['mode'] ?? '';
	if ($mode == '')
		$mode = $_GET['mode'] ?? '';

	$actionmode = $_POST['actionmode'] ?? '';
	if ($actionmode == '')
		$actionmode = $_GET['actionmode'] ?? '';

	$mainaction = $_POST['mainaction'] ?? '';
	if ($mainaction == '')
		$mainaction = $_GET['mainaction'] ?? '';

	$message = $_POST['message'] ?? '';
	if ($message == '')
		$message = $_GET['message'] ?? '';

	$count = $_POST['count'] ?? '';
	if ($count == '')
		$count = $_GET['count'] ?? '';
	if ($count == '') {
		$count = 1;
	}

	$txtGSM = $_GET['txtGSM'] ?? '';
	$txtPaperGrade = $_GET['txtPaperGrade'] ?? '';
	$txtRellDeckle = $_GET['txtRellDeckle'] ?? '';
	$txtPaperMill = $_GET['txtPaperMill'] ?? '';
	$PIndex = $_GET['PIndex'] ?? '';

	//Read From SorderSetup Table
	$querySorderSetup = " Select ifNull(ShowCostCentLayout,0) As ShowCostCentLayout,ifnull(SubItem_Production,0) as SubItem_Production, default_sorderType, ifnull(allow_duplicateItem,0) as allow_duplicateItem, " .
		" ifnull(sorderproduction,0) as sorderproduction,ifnull(ShowProjectLayout,0) as ShowProjectLayout " .
		" From SorderSetup ";
	$resultSorderSetup = mysqli_query($connection, $querySorderSetup) or die(mysqli_error($connection));
	while ($rowSorderSetup = mysqli_fetch_array($resultSorderSetup)) {
		extract($rowSorderSetup);
	}

	$cssStyleVersion = @filemtime(__DIR__ . '/css/style.css') ?: time();

	print <<<HERE
<head>
<meta http-equiv="Content-Type" content="text/html; charset={$_SESSION['encodingmode']}" />
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CJR</title>

<!-- ERP / Vendor CSS -->
<link type="text/css" href="js/calendar/jquery.datepick.css" rel="stylesheet">
<link type="text/css" href="js/grid/scripts/jquery-ui.css" rel="stylesheet">
<link rel="stylesheet" type="text/css" media="all" href="js/grid/UI_DataGrid_V1.css">

<!-- ERP CSS last so ERP styling can override vendor defaults -->
<link rel="stylesheet" type="text/css" media="all" href="r-main-css.css">
<link rel="stylesheet" type="text/css" media="all" href="css/style.css?v=$cssStyleVersion">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

<!-- jQuery FIRST -->
<script type="text/javascript" src="js/grid/jquery-1.9.1.js"></script>

<!-- Plugins that require jQuery -->
<script type="text/javascript" src="js/grid/scripts/jquery-ui.min.js"></script>
<script type="text/javascript" src="js/calendar/jquery.datepick.js"></script>

<!-- Shared ERP JS AFTER jQuery -->
<script type="text/javascript" src="js/grid/UI_DataGrid.js?v=20260521_5"></script>
<script type="text/javascript" src="js/EnableEnterAsTab.js"></script>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

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

// function addItem(ItemCode,PIndex){
// 	var selectItem = document.getElementById('txtItemCode1[' + PIndex + ']');
// 	var option = document.createElement('option');
// 	option.value = ItemCode;
// 	option.text = ItemCode;
// 	selectItem.add(option);
//   }
</script>
<style>
.UpperslidingDiv {
	position: absolute;
    height:90px;
	width:400px;
    background-color: #99CCFF;
    padding:10px;
    margin-top:10px;
    border-bottom:5px solid #3399FF;
	z-index: 1000000;
	font-weight:bold;
}
.table-container {
    display: flex; /* Display the tables side by side */
}

.table {
    margin-right: 20px; /* Add some spacing between the tables, adjust as needed */
}

</style>

<script type="text/javascript" language="javascript">



function ClearPage(){
x = document.mastercard;
	if (confirm ( "Are you sure you want to Clear Page?"))
	{	var txtCorrugatorId =x.txtCorrugatorId.value;
		ClearForm('All');
		x = document.mastercard;
		x.mainaction.value='insert';
		x.mainmode.value='insert';
		x.actionmode.value='';
		x.txtCorrugatorId.value = '';
		if(txtCorrugatorId==''){
			x.action='Form_Corrugator.php';
		}else{
			x.action='Form_Corrugator.php';
			//x.action='Form_Corrugator_actions.php?mainmode=deletefromtemp&txtCorrugatorId='+txtCorrugatorId;
		}
		window.location ='Form_Corrugator.php';
	}	
} 

function CancelFunc(){
	x = document.mastercard;
	var txtCorrugatorId =x.txtCorrugatorId.value;
	x.action='Form_Corrugator_actions.php?mainmode=deletefromtemp&txtCorrugatorId='+txtCorrugatorId;
	x.submit();



}
function ClearPage1(){
	x = document.mastercard;
	var msg = x.message.value;
	var txtCorrugatorId =x.txtCorrugatorId.value;
	ClearForm('All');
	
	x.mainaction.value='insert';
	x.mainmode.value='insert';
	x.actionmode.value='';
	x.message.value=msg;
	if(txtCorrugatorId==''){
		x.action='Form_Corrugator.php';
	}else{
		x.action='Form_Corrugator_actions.php?mainmode=deletefromtemp&txtCorrugatorId='+txtCorrugatorId;
	}
	x.submit();
}



function PostChanges() {
	x = document.mastercard;
	var corrugator_id  = x.txtCorrugatorId.value;
	var corrugator_date  = x.corrugator_date.value;
	var i                = x.i.value;
	// var date = corruDate.split('/');
	// var corrugator_date = date[2] + "-" + date[1] + "-" + date[0];
	var vMainMode = x.mainmode.value;
	var corrIdArr = corrugator_id.split(',');
	var len = corrIdArr.length;
	var count = x.count.value;
	
	if(corrIdArr[i] === undefined) {
		corrIdArr[i]='';
	}

	//for(var i =0 ; i < corrIdArr.length ; i++){

	//if(corrIdArr[i]!=''){
		if (confirm ( "Are you sure you want to Save current CJR?"))
			{
        var corrId      = corrIdArr[i];
		
		var related_salesorder= document.getElementById('related_salesorder['+i+']').value;
		var projectcode = document.getElementById('projectcode['+i+']').value ;
		var costcenter  = document.getElementById('costcenter['+i+']').value ;
		var warehouse   =  document.getElementById('warehouse['+i+']').value ;
		var currency    = document.getElementById('currency['+i+']').value ;
		var Sorderdt_price   =  document.getElementById('Sorderdt_price['+i+']').value ;
		var Sorder_BelongsToGrp = document.getElementById('Sorder_BelongsToGrp['+i+']').value;
		var clientCode = document.getElementById('clientCode['+i+']').value;
		var BorderNeededQty =  document.getElementById('txtBoardNeed['+i+']').value;

  
		
				x.actionmode.value='post';
				x.action="Form_Corrugator_Actions.php?mode=post&mainmode="+vMainMode+"&corrugator_id="+corrId+"&corrugator_idArr="+corrugator_id+"&corrugator_date_="+corrugator_date
				+"&projectcode="+projectcode+"&costcenter="+costcenter+"&warehouse="+warehouse+"&currency="+currency+"&Sorderdt_price="+Sorderdt_price+
				"&Sorder_BelongsToGrp="+Sorder_BelongsToGrp+"&clientCode="+clientCode+"&related_salesorder="+related_salesorder+"&index="+i+"&len="+len+"&count="+count+"&BorderNeededQty="+BorderNeededQty;
				x.submit();
			  
							
			}
	//   }else{
	// 	ClearForm('All');
	// 	x = document.mastercard;
	// 	x.mainaction.value='insert';
	// 	x.mainmode.value='insert';
	// 	x.actionmode.value='';
	// 	x.action='Form_Corrugator.php';
	// 	x.submit();

	//   }
	//}
}




//All= All form; D= Detail Part
function ClearForm(Option){
    x = document.mastercard;
	var vmode = x.actionmode.value;
	
	if (Option == 'All') {
		x.txtCorrugatorId.value='';
		x.JobCardId.value='';
	x.corrugator_reference.value='';
	x.corrugator_date.value='';
	x.count.value='';
	x.txtCorrugatorRef.value='';

		x.message.value='';
		x.actionmode.value = '';
		x.mainaction.value = '';
	} 
}




function SearchForRef(){
    //ClearForm('All');
    x = document.mastercard;
    win = window.open('Form_CorrugatorSearch.php', 'Form_CorrugatorSearch', 'toolbar=no,location=no,status=yes,menubar=no,scrollbars=yes,resizable=yes,width=1100,height=500,top=100');		
	win.creator = self;
	win.focus();		
			
}
	
function SearchApprovedKeyLine(){
	x = document.mastercard;
	corrRef = x.corrugator_reference.value;
	var count   = x.count.value;
	// var txtCorrugatorRef = x.txtCorrugatorRef.value;
	var JobCardId = x.JobCardId.value;
	 //var txtCorrugatorId =  x.txtCorrugatorId.value+',';
	 var txtCorrugatorId =  x.txtCorrugatorId.value;
	win = window.open('Form_LoadJobCard.php?corrRef='+corrRef+'&count='+count+'&txtCorrugatorId='+txtCorrugatorId, 'Form_LoadJobCard', 'toolbar=no,location=no,status=yes,menubar=no,scrollbars=yes,resizable=yes,width=1180,height=580,top=100,left=20');		
		win.creator = self;
		win.focus();	
}
function GetSelectedULQty(tablename,index) {
	
	
		
	var psalesref = document.getElementById('txtSalesRef' + '[' + index + ']').value;
	
	psalesref = encodeURIComponent(psalesref);
	
	if (psalesref != "") {
	
		var xmlhttp = getXMLHttpRequest();
		
		xmlhttp.onreadystatechange=function()
		{
			if (xmlhttp.readyState==4 && xmlhttp.status==200)
			{
				{ 
					var data = $.parseJSON(xmlhttp.responseText);
					
					/*if (document.getElementById('txtBoxSizeL' + '[' + index + ']'))  document.getElementById('txtBoxSizeL' + '[' + index + ']').value = data[0].i_existqty;
					if (document.getElementById('txtBoxSizeW' + '[' + index + ']'))   document.getElementById('txtBoxSizeW' + '[' + index + ']').value = data[0].i_bookqty;
					if (document.getElementById('txtBoxSizeH' + '[' + index + ']'))   document.getElementById('txtBoxSizeH' + '[' + index + ']').value = data[0].i_Reservedqty;
					if (document.getElementById('txtStdPaperTL' + '[' + index + ']')) document.getElementById('txtStdPaperTL' + '[' + index + ']').value = data[0].i_availqty;
					if (document.getElementById('txtStdPaperFL' + '[' + index + ']')) document.getElementById('txtStdPaperFL' + '[' + index + ']').value = data[0].i_existunit2qty;
					if (document.getElementById('txtStdPaperWTL' + '[' + index + ']')) document.getElementById('txtStdPaperWTL' + '[' + index + ']').value = data[0].i_existunit2qty;*/
				}
			}
		}


		xmlhttp.open("GET","Get_AutocompleteQuery.php?tablename="+tablename+"&psalesref="+psalesref,true);
		xmlhttp.send();
	}

}
function UpperShowSlidingDiv(PIndex) {
	var Div = "#UpperslidingDiv"+PIndex;
	$(Div).slideToggle();
	
}

function SubmitUpperLowerStacker(PIndex,ulstacker_id,morelines, startrange, perpage,txtCorrugatorId) {
    x = document.mastercard;

    // Define 'itemcode' or make sure it's defined elsewhere.
    var itemcode = ''; 

	var corgrid_id = x.corgrid_id.value;
	var corrugatorid  = x.corrugatorid.value;

    var notallowedtoadditemduetocostcenter = false;
    var SubBtnID = 'imgSubmitul[' + PIndex + ']';
    
    if (document.getElementById(SubBtnID)) {
        document.getElementById(SubBtnID).disabled = true;
    }

    $.get("Get_AutocompleteQuery.php?tablename=items&term=" + itemcode, function (data) {
        // Used to restrict the user from adding an item that belongs to another cost center.

        if (parseInt(data.length) == parseInt(0)) {
            alert('Not allowed to add this item!');
            document.getElementById('itemcode').focus();
        } else {
            var result = 1;
            result = CheckFields();

            if (result == 1) {
                result = CheckFieldsDt(PIndex);
            }

			if(ulstacker_id!=''){
				var vMode = 'update';
			}else{
			var vMode = 'post';

			}
			var vvMode='JobCardDT';
			
            var the_data = 'mode=' + vMode +'&vvMode='+ vvMode +
                '&mainmode=' + x.mainaction.value +

				'&txtPlannedQty=' + document.getElementById('txtPlannedQty').value +
                '&txtProducedQty=' + document.getElementById('txtProducedQty').value +
                '&txtRemainingQty=' + document.getElementById('txtRemainingQty').value +
                 '&BorderNeededQty='+ document.getElementById('txtBoardNeed').value +
				

				// '&txtBoxSizeL=' + document.getElementById('txtBoxSizeL').value +
				// '&txtBoxSizeW=' + document.getElementById('txtBoxSizeW').value +
				// '&txtBoxSizeH=' + document.getElementById('txtBoxSizeH').value +
				// '&txtStdPaperTL=' + document.getElementById('txtStdPaperTL').value +
				// '&txtStdPaperFL=' + document.getElementById('txtStdPaperFL').value +
				// '&txtStdPaperWTL=' + document.getElementById('txtStdPaperWTL').value +

				'&txtSalesRef=' + document.getElementById('txtSalesRef['+ PIndex + ']').value +
				'&txtMasterRef=' + document.getElementById('txtMasterRef['+ PIndex + ']').value +
				'&ulstacker_id=' + ulstacker_id+
				'&txtCorrugatorId=' + txtCorrugatorId;
            

            if (result != 1) {
                var SubBtnID = 'imgSubmitul[' + PIndex + ']';
                if (document.getElementById(SubBtnID)) document.getElementById(SubBtnID).disabled = false;
            }
            
            if (result == 1) {
                var xmlhttp = new XMLHttpRequest(); // Use new XMLHttpRequest()
                var getdate = new Date(); // Used to prevent caching during the AJAX call

                $("#Loader").addClass("ajaxsaving");

                var SubBtnID = 'imgSubmitul[' + PIndex + ']';
                if (document.getElementById(SubBtnID)) document.getElementById(SubBtnID).disabled = true;

                if (xmlhttp) {
                    xmlhttp.onreadystatechange = function () {
                        if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
			
                   
   var msg='';
                            if (msg != "") {
                                $("#Loader").removeClass("ajaxsaving");
                                if (document.getElementById(SubBtnID)) document.getElementById(SubBtnID).disabled = false;
                                alert(decodeURIComponent(msg));
                            } else {
                              
                                var index = parseInt(PIndex) + 1;
                                var nextcell = 'itemcode';

                                createCookie('so-nextcell', nextcell);


                                document.getElementById('ModRec_G1').value = "";
                            }
                        }
                    };

				
					x.action="Form_Corrugator_Actions.php?"+the_data;

					
	SubmitCorGrid(PIndex,corgrid_id,morelines, startrange, perpage,corrugatorid);
					 
					x.submit();

					

					
                }
            }	
        }
    });

}



function calculCorWidth(index){
var flaptop = document.getElementById('txtFlapTop['+index+']').value;
var height = document.getElementById('txtHeight['+index+']').value;
var flapbot = document.getElementById('txtFlapBot['+index+']').value;
var flaptop = parseFloat(flaptop);
var height = parseFloat(height);
var flapbot = parseFloat(flapbot);
if(flaptop == '') flaptop = 0;
if(height == '') height = 0;
if(flapbot == '') flapbot = 0;
var width = height +flaptop + flapbot;

if(document.getElementById('olwidth['+index+']')){
  var oldwidth = parseFloat(document.getElementById('olwidth['+index+']').value);
  var oldFlapTop = parseFloat(document.getElementById('oldFlapTop['+index+']').value);
  var oldFlapHeight = parseFloat(document.getElementById('oldFlapHeight['+index+']').value);
  var oldFlapBottom = parseFloat(document.getElementById('oldFlapBottom['+index+']').value);
 
  if(width!=oldwidth){
  	document.getElementById('txtWidth['+index+']').style.background = 'red';
  	//document.getElementById('txtWidth['+index+']').value = 2w1;
  }else { 
  	document.getElementById('txtWidth['+index+']').style.background = 'white';
  }

  if(flaptop!=oldFlapTop){
  	document.getElementById('txtFlapTop['+index+']').style.background = 'red';
  	//document.getElementById('txtFlapTop['+index+']').value = oldwidth;
  }else { 
  	document.getElementById('txtFlapTop['+index+']').style.background = 'white';
  }

  if(flapbot!=oldFlapBottom){
  	document.getElementById('txtFlapBot['+index+']').style.background = 'red';
  	//document.getElementById('txtFlapBot['+index+']').value = oldwidth;
  }else { 
  	document.getElementById('txtFlapBot['+index+']').style.background = 'white';
  }

   if(height!=oldFlapHeight){
  	document.getElementById('txtHeight['+index+']').style.background = 'red';
  	//document.getElementById('txtHeight['+index+']').value = 00443;
  }else { 
  	document.getElementById('txtHeight['+index+']').style.background = 'white';
  }


}
  Twidth(index);
}

function SubmitReelDec(txtCorrugatorId){ 
	var count = x.count.value;
	//var txtCorId1 = document.getElementById('txtCorId[0]');
	//var txtCorId2 = document.getElementById('txtCorId[1]');
  	var the_data = 'mode=SubmitReelDec&vvMode=SubmitReelDec&mainmode=' + x.mainaction.value +'&txtReelDec=' 
                 + document.getElementById('txtReelDec').value +'&txtCorrugatorId=' + txtCorrugatorId+'&count='+count
				 +'&txtTrimming='+document.getElementById('txtTrimming').value+'&txtTotWidth='+document.getElementById('txtTotWidth').value ;
	//if(txtCorId1!='' ||  txtCorId2!=''){	
		x.action="Form_Corrugator_Actions.php?"+the_data;
		x.submit();
	//}
}


function SubmitCorGrid(PIndex,corgrid_id,morelines, startrange, perpage,txtCorrugatorId, ulstacker_id) {
    x = document.mastercard;

    // Define 'itemcode' or make sure it's defined elsewhere.
    var itemcode = ''; 
	var txtCorrugatorId =  x.txtCorrugatorId.value;
	var CorrugatorIdAtrr = txtCorrugatorId.split(',');
	var count = x.count.value;
	var txtCorrugatorRef = x.corrugator_reference.value;
	var JobCardId = document.getElementById('jobCard['+PIndex+']').value;
	//var JobCardIdAtrr = JobCardId.split(',');
	//alert(JobCardId[PIndex]);

    var notallowedtoadditemduetocostcenter = false;
    var SubBtnID = 'imgSubmitcg[' + PIndex + ']';
    
    //if (document.getElementById(SubBtnID)) {
        //document.getElementById(SubBtnID).disabled = true;
    //}

    $.get("Get_AutocompleteQuery.php?tablename=items&term=" + itemcode, function (data) {
        // Used to restrict the user from adding an item that belongs to another cost center.

        if (parseInt(data.length) == parseInt(0)) {
            alert('Not allowed to add this item!');
            document.getElementById('itemcode').focus();
        } else {
           var result = 1;
            //result = CheckFields();
			result = CheckFieldsDt(PIndex);

           
			if(corgrid_id!=''){
				var vMode = 'updatepaper';
			}else{
			var vMode = 'postpaper';

			}
			
		var BorderNeededQty =  document.getElementById('txtBoardNeed['+PIndex+']').value;
			//alert(vMode);
			var vvMode='JobCardDT';
			//'&txtTrimWaste=' + document.getElementById('txtTrimWaste').value +
            var the_data = 'mode=' + vMode +'&vvMode='+ vvMode +
                '&mainmode=' + x.mainaction.value +
				'&txtLength=' + document.getElementById('txtLength[' + PIndex + ']').value +
                '&txtWidth=' + document.getElementById('txtWidth[' + PIndex + ']').value +
                '&txtFlapTop=' + document.getElementById('txtFlapTop[' + PIndex + ']').value +
				'&txtHeight=' + document.getElementById('txtHeight[' + PIndex + ']').value +
				'&txtFlapBot=' + document.getElementById('txtFlapBot[' + PIndex + ']').value +
				'&txtOuts=' + document.getElementById('txtOuts[' + PIndex + ']').value +
				'&txtTWidth=' + document.getElementById('txtTWidth[' + PIndex + ']').value +
				'&txtTCuts=' + document.getElementById('txtTCuts[' + PIndex + ']').value +
				'&txtActualCuts=' + document.getElementById('txtActualCuts[' + PIndex + ']').value +
				'&txtReelDec=' + document.getElementById('txtReelDec').value +
				'&txtTotWidth=' + document.getElementById('txtTotWidth').value +
				'&txtTrimming=' + document.getElementById('txtTrimming').value +

 
				'&txtURequiredT=' + document.getElementById('txtURequiredT').value +
				'&txtLRequiredT=' + document.getElementById('txtLRequiredT').value +
				'&txtTrimWaste=' + document.getElementById('txtTrimWaste').value + 
				'&txtUActualT=' + document.getElementById('txtUActualT').value +
				'&txtLActualT=' + document.getElementById('txtLActualT').value +
				'&txtTRequiredT=' + document.getElementById('txtTRequiredT').value +
				'&txtTActualT=' + document.getElementById('txtTActualT').value +
				'&txtSizeTrimT=' + document.getElementById('txtSizeTrimT').value +
				'&txtRequiredTrimT=' + document.getElementById('txtRequiredTrimT').value +
				'&txtActualTrimT=' + document.getElementById('txtActualTrimT').value +
				'&txtIPaper=' + document.getElementById('txtIPaper').value +
                '&txtFPaper=' + document.getElementById('txtFPaper').value +
                '&txtOPaper=' + document.getElementById('txtOPaper').value +
				'&txtIBalance=' + document.getElementById('txtIBalance').value +
				'&txtFBalance=' + document.getElementById('txtFBalance').value +
				'&txtOBalance=' + document.getElementById('txtOBalance').value +
				'&txtWTPCU=' + document.getElementById('txtWTPCU').value +
				'&txtWTPCL=' + document.getElementById('txtWTPCL').value +
				'&txtTotalGSM=' + document.getElementById('txtTotalGSM').value +
				'&txtTotalRequired=' + document.getElementById('txtTotalRequired').value +
				'&txtTotalActual=' + document.getElementById('txtTotalActual').value + 
				
				'&txtLM=' + document.getElementById('txtLM[' + PIndex + ']').value +
				'&txtSQ=' + document.getElementById('txtSQ[' + PIndex + ']').value +
				'&txtSalesRef=' + document.getElementById('txtSalesRef[' + PIndex + ']').value +
				'&txtMasterRef=' + document.getElementById('txtMasterCardRef[' + PIndex + ']').value +
				
				
				

				'&corgrid_id=' + corgrid_id+

				'&count=' + count+
				'&txtCorrugatorRef=' +txtCorrugatorRef +
				
				'&JobCardId=' +JobCardId +

				'&txtPlannedQty=' + document.getElementById('txtPlannedQty[' + PIndex + ']').value +
				'&txtProducedQty=' + document.getElementById('txtProducedQty[' + PIndex + ']').value +
				'&txtRemainingQty=' + document.getElementById('txtRemainingQty[' + PIndex + ']').value +
				'&lastRowLocation='+document.getElementById('lastRowLocation').value+

				'&txtSOF=' + document.getElementById('txtSOF[' + PIndex + ']').value +
				'&clientCode=' + document.getElementById('clientCode[' + PIndex + ']').value +
				'&txtClient=' + document.getElementById('txtClient[' + PIndex + ']').value +
				'&txtItemDesc=' + encodeURIComponent(document.getElementById('txtItemDesc[' + PIndex + ']').value) +
				'&txtBoardNeed=' + document.getElementById('txtQtyRequest[' + PIndex + ']').value +
				'&txtFluteType=' + document.getElementById('txtFluteType[' + PIndex + ']').value +
				'&txtScoringType=' + document.getElementById('txtScoringType[' + PIndex + ']').value +
				'&txtJobCardRef=' + document.getElementById('txtJobCardRef[' + PIndex + ']').value +
				'&txtInsideLiner=' + document.getElementById('txtInsideLiner[' + PIndex + ']').value +
				'&txtFlutting2=' + document.getElementById('txtFlutting2[' + PIndex + ']').value +
				'&txtBoxTypeId=' + document.getElementById('txtBoxTypeId[' + PIndex + ']').value +
				'&txtStdGsm=' + document.getElementById('txtStdGsm[' + PIndex + ']').value +
				'&txtOutsideLiner=' + document.getElementById('txtOutsideLiner[' + PIndex + ']').value +
                '&ulstacker_id='+ulstacker_id;
				if(document.getElementById('upperlowerid[1]')){
					the_data += '&ulstacker_id2='+ document.getElementById('upperlowerid[1]').value;
				}
				if(document.getElementById('txtCorRef[1]')){
					the_data += '&crid2='+ document.getElementById('txtCorRef[1]').value;
				}
				the_data +='&txtCorrugatorId1=' + CorrugatorIdAtrr[PIndex]+'&PIndex='+PIndex+
				'&txtCorrugatorId=' + txtCorrugatorId+'&BorderNeededQty='+BorderNeededQty
				
				
				;
             
             
            if (result != 1) {
                var SubBtnID = 'imgSubmitcg[' + PIndex + ']';
                if (document.getElementById(SubBtnID)) document.getElementById(SubBtnID).disabled = false;
            }
			
			if (result == 1) {
				x.action="Form_Corrugator_Actions.php?"+the_data;
				x.submit();
			}
			
					
            if (result == 1) {
                var xmlhttp = new XMLHttpRequest(); // Use new XMLHttpRequest()
                var getdate = new Date(); // Used to prevent caching during the AJAX call

                $("#Loader").addClass("ajaxsaving");

                var SubBtnID = 'imgSubmitcg[' + PIndex + ']';
                if (document.getElementById(SubBtnID)) document.getElementById(SubBtnID).disabled = true;

             /*   if (xmlhttp) {
                    xmlhttp.onreadystatechange = function () {
                        if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
			
                   
   var msg='';
                            if (msg != "") {
                                $("#Loader").removeClass("ajaxsaving");
                                if (document.getElementById(SubBtnID)) document.getElementById(SubBtnID).disabled = false;
                                alert(decodeURIComponent(msg));
                            } else {
                              
                                var index = parseInt(PIndex) + 1;
                                var nextcell = 'itemcode';

                                createCookie('so-nextcell', nextcell);


                                document.getElementById('ModRec_G1').value = "";
                            }
                        }
                    };

				
            if(CorrugatorIdAtrr[PIndex]!=''){
					x.action="Form_Corrugator_Actions.php?"+the_data;
					x.submit();
				}
					
                }
					*/

					
            }	
        }
    });

}


function SubmitLocationGrid(PIndex,loc_id,morelines, startrange, perpage,txtCorrugatorId,txtType) {
    x = document.mastercard;

    // Define 'itemcode' or make sure it's defined elsewhere.
    var itemcode = ''; 

    var notallowedtoadditemduetocostcenter = false;
    var SubBtnID = 'imgSubmitlg[' + PIndex + ']';
    
    if (document.getElementById(SubBtnID)) {
        document.getElementById(SubBtnID).disabled = true;
    }

    $.get("Get_AutocompleteQuery.php?tablename=items&term=" + itemcode, function (data) {
        // Used to restrict the user from adding an item that belongs to another cost center.

        if (parseInt(data.length) == parseInt(0)) {
            alert('Not allowed to add this item!');
            document.getElementById('txtItemCode[' + PIndex + ']').focus();
        } else {
            var result = 1;
            //result = CheckFields();

            //if (result == 1) {
               // result = CheckFieldsDtPaper(PIndex);
            //}

			if(loc_id!=''){
				var vMode = 'updatepaperold';
			}else{
			var vMode = 'postpaperold';

			}
			var vvMode='JobCardDT';

			var JobCardId = x.JobCardId.value;
			
            var the_data = 'mode=' + vMode +'&vvMode='+ vvMode +
                '&mainmode=' + x.mainaction.value +

                '&txtItemCode=' + document.getElementById('txtItemCode1[' + PIndex + ']').value +
                '&txtItemDesc=' + document.getElementById('txtItemDesc1[' + PIndex + ']').value +
                '&txtGSM=' + document.getElementById('txtGSM[' + PIndex + ']').value +
                '&txtPaperGrade=' + document.getElementById('txtPaperGrade[' + PIndex + ']').value +
				'&txtRellDeckle=' + document.getElementById('txtRellDeckle[' + PIndex + ']').value +
				'&txtSizeTrim=' + document.getElementById('txtSizeTrim[' + PIndex + ']').value +
				'&txtPaperMill=' + document.getElementById('txtPaperMill[' + PIndex + ']').value +
				'&txtSupplier=' + document.getElementById('txtSupplierName[' + PIndex + ']').value +
                '&count=' + x.count.value +


				'&txtURequired=' + document.getElementById('txtURequired[' + PIndex + ']').value +
			
				'&txtTrimWaste=' + document.getElementById('txtTrimWaste').value +
				
				'&txtUActual=' + document.getElementById('txtUActual[' + PIndex + ']').value +
				'&txtUActualT=' + document.getElementById('txtUActualT').value +
				

				'&txtLRequired=' + document.getElementById('txtLRequired[' + PIndex + ']').value +
				


				'&txtLActual=' + document.getElementById('txtLActual[' + PIndex + ']').value +
				'&txtLActualT=' + document.getElementById('txtLActualT').value +

				'&txtTRequired=' + document.getElementById('txtTRequired[' + PIndex + ']').value +
				'&txtTRequiredT=' + document.getElementById('txtTRequiredT').value +


				'&txtTActual=' + document.getElementById('txtTActual[' + PIndex + ']').value +
				'&txtTActualT=' + document.getElementById('txtTActualT').value +

				'&txtSizeTrim=' + document.getElementById('txtSizeTrim[' + PIndex + ']').value +
				'&txtSizeTrimT=' + document.getElementById('txtSizeTrimT').value +


				'&txtRequiredTrim=' + document.getElementById('txtRequiredTrim[' + PIndex + ']').value +
				'&txtRequiredTrimT=' + document.getElementById('txtRequiredTrimT').value +

				'&txtActualTrim=' + document.getElementById('txtActualTrim[' + PIndex + ']').value +
				'&txtActualTrimT=' + document.getElementById('txtActualTrimT').value +

			



				'&txtIPaper=' + document.getElementById('txtIPaper').value +
                '&txtFPaper=' + document.getElementById('txtFPaper').value +
                '&txtOPaper=' + document.getElementById('txtOPaper').value +
				'&txtIBalance=' + document.getElementById('txtIBalance').value +
				'&txtFBalance=' + document.getElementById('txtFBalance').value +
				'&txtOBalance=' + document.getElementById('txtOBalance').value +
				'&txtWTPCU=' + document.getElementById('txtWTPCU').value +
				'&txtWTPCL=' + document.getElementById('txtWTPCL').value +
				'&txtTotalGSM=' + document.getElementById('txtTotalGSM').value +

				'&txtWareHouse=' + document.getElementById('warehousecode[' + PIndex + ']').value +
				'&corrugator_date=' + document.getElementById('corrugator_date').value +
				
				'&txtTotalRequired=' + document.getElementById('txtTotalRequired').value +
				'&txtTotalActual=' + document.getElementById('txtTotalActual').value +
				'&txtType=' + txtType +
				'&index=' + PIndex +
				'&loc_id=' + loc_id+
				'&JobCardId=' + JobCardId+
				'&txtCorrugatorId=' + txtCorrugatorId;
          

            if (result != 1) {
                var SubBtnID = 'imgSubmitlg[' + PIndex + ']';
                if (document.getElementById(SubBtnID)) document.getElementById(SubBtnID).disabled = false;
            }
           
            if (result == 1) {
                var xmlhttp = new XMLHttpRequest(); // Use new XMLHttpRequest()
                var getdate = new Date(); // Used to prevent caching during the AJAX call

                $("#Loader").addClass("ajaxsaving");
 
                var SubBtnID = 'imgSubmitlg[' + PIndex + ']';
                if (document.getElementById(SubBtnID)) document.getElementById(SubBtnID).disabled = true;

                if (xmlhttp) {
                    xmlhttp.onreadystatechange = function () {
                        if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
			
                   
   var msg='';
                            if (msg != "") {
                                $("#Loader").removeClass("ajaxsaving");
                                if (document.getElementById(SubBtnID)) document.getElementById(SubBtnID).disabled = false;
                                alert(decodeURIComponent(msg));
                            } else {
                              
                                var index = parseInt(PIndex) + 1;
                                var nextcell = 'txtItemCode[' + index + ']';

                                createCookie('so-nextcell', nextcell);


                                document.getElementById('ModRec_G1').value = "";
                            }
                        }
                    };

				
					x.action="Form_Corrugator_Actions.php?"+the_data;
					x.submit();
                } 
            }	
        }
    });

}

function Twidth(PIndex){
	x = document.mastercard;

	var width =  parseFloat(document.getElementById('txtWidth[' + PIndex + ']').value);
	var outs  =  parseFloat(document.getElementById('txtOuts[' + PIndex + ']').value);
    var qtyrqst =  parseFloat(document.getElementById('txtQtyRequest[' + PIndex + ']').value);
	var Twidth = width * outs ;
    var BorderNeeded = qtyrqst/outs;
	document.getElementById('txtTWidth[' + PIndex + ']').value = Twidth;

	TWidth1(PIndex);
	if(PIndex==0){
	//TWidth1(PIndex);
	cuts(PIndex);
	Acuts(PIndex);
	
	}else{
		trimming1();
		//TWidth1(PIndex);

	}





if(PIndex == 0) WTPCU();
	else WTPCL();



}



function cuts(PIndex){
	x = document.mastercard;
	

	//if(PIndex == 0){
		var plannedQty = document.getElementById('txtPlannedQty[0]').value;
		var outs       = document.getElementById('txtOuts[0]').value;

		var Tcuts = plannedQty / outs ;
	

		if(Tcuts == 'Infinity'){
			Tcuts = 0;
		}
		document.getElementById('txtTCuts[0]').value  = Math.round(Tcuts);
	
    //}

	//if(PIndex == 1){
		var LM = document.getElementById('txtLM[0]').value;
		var Length       = document.getElementById('txtLength[1]').value;

		if(Length==''){
			var Tcuts=0;
		}else{
			var Tcuts = (LM / Length)*1000 ;
		}

	

		if(Tcuts == 'Infinity'){
			Tcuts = 0;
		}


		document.getElementById('txtTCuts[1]').value  = Math.round(Tcuts);
	
    //}

	
    plannedQty1(1);
	remainingQty(PIndex);
	TWidth1(PIndex);
	// if(PIndex==1){

	// 	LM(PIndex);

	// }



}

function Acuts(PIndex){
	x = document.mastercard;

	var producedQty = document.getElementById('txtProducedQty[0]').value;
	var outs        = document.getElementById('txtOuts[' + PIndex + ']').value;

	var Acuts = producedQty / outs ;

	if(Acuts == 'Infinity'){
		Acuts = 0;
	}

	document.getElementById('txtActualCuts[' + PIndex + ']').value  = Acuts;

	LM(PIndex);




}


function LM(PIndex){
	x = document.mastercard;
    
	if(PIndex==0){
	var length = parseFloat(document.getElementById('txtLength['+PIndex+']').value);
	var cuts = parseFloat(document.getElementById('txtTCuts['+PIndex+']').value);

	var LM = (length/1000)*cuts;

	document.getElementById('txtLM['+PIndex+']').value = Math.round(LM) ;
	
	}
	//if(PIndex == 0){
	SQ(PIndex);
	//prodQty(PIndex);
	//}





}

function SQ(PIndex){
	x = document.mastercard;
    
	var LM = parseFloat(document.getElementById('txtLM['+PIndex+']').value);
	//var cuts = parseFloat(document.getElementById('txtTCuts['+PIndex+']').value);
	var txtReelDec = parseFloat(document.getElementById('txtReelDec').value);
	
	if(LM==''){
      LM=0;
	}
	if(txtReelDec==''){
		txtReelDec=0;
	}

	var SQ = (LM*txtReelDec)/1000;

	document.getElementById('txtSQ['+PIndex+']').value = Math.ceil(SQ) ;
	
	sizeTrim(PIndex);

}

function SQ1(PIndex){
	x = document.mastercard;

    for(var i = 0 ; i < PIndex ; i++){
	var LM = document.getElementById('txtLM['+i+']').value;
	//var cuts = document.getElementById('txtTCuts['+i+']').value;
	var txtReelDec = document.getElementById('txtReelDec').value;
	

	var SQ = (LM*txtReelDec)/1000;

	document.getElementById('txtSQ['+i+']').value = SQ.toFixed(2) ;
	}

	

}




function trimming1(){
	x = document.mastercard;
    
	var TotWidth = document.getElementById('txtTotWidth').value;
	var reelDec = document.getElementById('txtReelDec').value;

	var trim = reelDec - TotWidth;

	

	document.getElementById('txtTrimming').value = trim  ;



}


function sizeTrim(PIndex){
	x = document.mastercard;
	var lastRowLocation = x.lastRowLocation.value;
	for(var xx=0;xx<lastRowLocation;xx++){
	    PIndex=xx;
		var reelDeckle = parseFloat(document.getElementById('txtRellDeckle['+PIndex+']').value);
		var Twidth0 = parseFloat(document.getElementById('txtTWidth[0]').value);
		var Twidth1 = parseFloat(document.getElementById('txtTWidth[1]').value);
		console.log(Twidth1);
		if(Twidth1=='' || isNaN(Twidth1)){
			Twidth1=0;
		}
		if(reelDeckle=='' || isNaN(reelDeckle)){
			reelDeckle=0;
		}

		var sizeTrim = reelDeckle - Twidth0 - Twidth1;

		document.getElementById('txtSizeTrim['+PIndex+']').value = sizeTrim;
		


		UReq(PIndex);
		TrimReq(PIndex);

		totsizeTrim(PIndex);
	}



}

function totsizeTrim(PIndex){
	x = document.mastercard;
	var totsizeTrim = 0;
	for(var i = 0 ; i<=PIndex;i++){
	 var sizeTrim =	parseFloat(document.getElementById('txtSizeTrim['+i+']').value) ;
	 totsizeTrim += sizeTrim;
	}

	document.getElementById('txtSizeTrimT').value = Math.round(totsizeTrim);




}

function TWidth1(PIndex){
	x = document.mastercard;
    let TWidth = 0 ;
	for(var i = 0 ; i <= 1 ; i++) {
		const inputElement=document.getElementById('txtTWidth['+i+']');
		if(inputElement && inputElement.value!=''){
    	 TWidth += parseFloat(document.getElementById('txtTWidth['+i+']').value);
		}
	}


	document.getElementById('txtTotWidth').value = TWidth  ;

	trimming1();


}


function prodQty(PIndex){

	if(PIndex == 1){
		x          = document.mastercard;
		var LM     =  document.getElementById('txtLM[1]').value;
		var length = document.getElementById('txtLength[0]').value;
		var outs   = document.getElementById('txtOuts[0]').value;

		var prodQty = ((LM)/(length/1000))*outs;

		document.getElementById('txtProducedQty[0]').value = prodQty;



	}

	if(PIndex == 0){
		x          = document.mastercard;
		var LM     =  document.getElementById('txtLM[1]').value;
		var length = document.getElementById('txtLength[1]').value;
		var outs   = document.getElementById('txtOuts[1]').value;

		var prodQty = ((LM)/(length/1000))*outs;

		document.getElementById('txtProducedQty[1]').value = prodQty;



	}

	remainingQty(PIndex);



}


function remainingQty(PIndex){


		x                  = document.mastercard;
		var plannedQty     = parseFloat(document.getElementById('txtPlannedQty['+PIndex+']').value);
		var BoardNeed      = parseFloat(document.getElementById('txtBoardNeed['+PIndex+']').value);
		var CJRQty         = 0;
		var ProducedQty    = parseFloat(document.getElementById('txtProducedQty['+PIndex+']').value);
		if(document.getElementById('txtCJRQtyy_'+PIndex)){
			CJRQty         = parseFloat(document.getElementById('txtCJRQtyy_'+PIndex).value);
		}

		if(BoardNeed == '' || isNaN(BoardNeed)){
			BoardNeed = 0;
		}
		if(ProducedQty == '' || isNaN(ProducedQty)){
			ProducedQty = 0;
		}
		if(plannedQty == '' || isNaN(plannedQty)){
			plannedQty = 0;
		}
	
		var remQty           = BoardNeed - plannedQty - CJRQty - ProducedQty ;
	

		document.getElementById('txtRemainingQty['+PIndex+']').value = remQty;



	


	LM(PIndex);
	SQ(PIndex);


}

function plannedQty1(PIndex){

	var outs        = parseFloat(document.getElementById('txtOuts['+PIndex+']').value);
    var cuts        = parseFloat(document.getElementById('txtTCuts['+PIndex+']').value) ;

	if(outs==''){
		outs=0;
	}
	if(cuts==''){
		cuts=0;
	}

	var plannedQty = outs*cuts;

	

	document.getElementById('txtPlannedQty['+PIndex+']').value = plannedQty;



}

function TrimReq(PIndex){
    x = document.mastercard;
	var takeUpFact = document.getElementById('takeUpFact[0]').value;
	var rowsNb     = document.getElementById('rowsNb[0]').value;
    
	if(PIndex %2 != 0 ){
		var Fact = takeUpFact;
	}else{
		var Fact = 1;
	}
		

		var GSM = document.getElementById('txtGSM['+PIndex+']').value;
		var LM0 = document.getElementById('txtLM[0]').value;
		var sizeMM = document.getElementById('txtSizeTrim['+PIndex+']').value;

		if(GSM==''){
			GSM=0
		}
	
		if(sizeMM==''){
			sizeMM=0
		}

		var Trimreq = ( ( (GSM * Fact)*(sizeMM*LM0))/1000000 );
           
		if((rowsNb == 3 || rowsNb == 5)  && PIndex == 1) document.getElementById('txtFReqTrim').value=Math.round(Trimreq);
		else if(rowsNb == 5  && PIndex == 3) document.getElementById('txtF2ReqTrim').value=Math.round(Trimreq);
		else if((rowsNb == 3 || rowsNb == 5) && PIndex == 0) document.getElementById('txtIReqTrim').value=Math.round(Trimreq);
		else if(rowsNb == 5 && PIndex == 2)  document.getElementById('txtI2ReqTrim').value=Math.round(Trimreq);
        else  document.getElementById('txtOReqTrim').value=Math.round(Trimreq);
		document.getElementById('txtRequiredTrim['+PIndex+']').value=Math.round(Trimreq);
      
		Req2(PIndex);


}
function Req2(PIndex){
	var rowsNb     = document.getElementById('rowsNb[0]').value;
var txtURequired = document.getElementById('txtURequired['+PIndex+']').value;
var txtLRequired = document.getElementById('txtLRequired['+PIndex+']').value;

if((rowsNb == 3 || rowsNb == 5)  && PIndex == 1)  var Trimreq =  document.getElementById('txtFReqTrim').value;
		else if(rowsNb == 5  && PIndex == 3) var Trimreq =  document.getElementById('txtF2ReqTrim').value;
		else if((rowsNb == 3 || rowsNb == 5) && PIndex == 0) var Trimreq =  document.getElementById('txtIReqTrim').value;
		else if(rowsNb == 5 && PIndex == 2)  var Trimreq =  document.getElementById('txtI2ReqTrim').value;
        else var Trimreq =   document.getElementById('txtOReqTrim').value;
/*
if(PIndex %2 != 0 ) var Trimreq =  document.getElementById('txtFReqTrim').value;
else if(rowsNb == 3 && PIndex == 0)  var Trimreq = document.getElementById('txtIReqTrim').value;
else if(rowsNb == 5 && (PIndex == 0 || PIndex == 2)) var Trimreq =   document.getElementById('txtIReqTrim').value;
else var Trimreq =  document.getElementById('txtOReqTrim').value;
*/
if(txtURequired == '') txtURequired = 0;
if(txtLRequired == '') txtLRequired = 0;
if(Trimreq == '') Trimreq = 0;

var req = parseFloat(txtURequired) + parseFloat( txtLRequired) +  parseFloat((Trimreq/1000));

if((rowsNb == 3 || rowsNb == 5)  && PIndex == 1) document.getElementById('txtTFRequired').value=Math.round(req);
		else if(rowsNb == 5  && PIndex == 3) document.getElementById('txtTF2Required').value=Math.round(req);
		else if((rowsNb == 3 || rowsNb == 5) && PIndex == 0) document.getElementById('txtTIRequired').value=Math.round(req);
		else if(rowsNb == 5 && PIndex == 2)  document.getElementById('txtTI2Required').value=Math.round(req);
        else  document.getElementById('txtTORequired').value=Math.round(req);
		/*
if(PIndex %2 != 0 ) document.getElementById('txtTFRequired').value=Math.round(req);
		else if(rowsNb == 3 && PIndex == 0) document.getElementById('txtTIRequired').value=Math.round(req);
		else if(rowsNb == 5 && (PIndex == 0 || PIndex == 2))  document.getElementById('txtTIRequired').value=Math.round(req);
        else  document.getElementById('txtTORequired').value=Math.round(req);
		*/
  calculTotalReq(rowsNb);
}

function   calculTotalReq(rowsNb){
   if(rowsNb == 3){
        document.getElementById('txtTotalReq').value = parseFloat(document.getElementById('txtTIRequired').value)+parseFloat(document.getElementById('txtTFRequired').value)+parseFloat(document.getElementById('txtTORequired').value);
    }
	if(rowsNb == 5){
        document.getElementById('txtTotalReq').value =  parseFloat(document.getElementById('txtTI2Required').value)+ parseFloat(document.getElementById('txtTF2Required').value)+parseFloat(document.getElementById('txtTIRequired').value)+parseFloat(document.getElementById('txtTFRequired').value)+parseFloat(document.getElementById('txtTORequired').value);
    }

}
function UReq(PIndex){
	x = document.mastercard;
	var FluteType = document.getElementById('txtFluteType[0]').value;
	

	var takeUpFactB = document.getElementById('takeUpFactB[0]').value;
	var takeUpFactC = document.getElementById('takeUpFactC[0]').value;
	var takeUpFactE = document.getElementById('takeUpFactE[0]').value;
	var takeUpFact = document.getElementById('takeUpFact[0]').value;
	var txttype  = document.getElementById('txttype['+PIndex+']').value;

	var rowsNb     = document.getElementById('rowsNb[0]').value;
    
	if(PIndex %2 != 0 ){
		var Fact = takeUpFact;

		if(FluteType == 4){
			Fact = takeUpFactB;
			if(txttype==='FLUTE-1'){
				Fact = takeUpFactC;
			}
		}else if(FluteType == 5){
			Fact = takeUpFactE;
			if(txttype==='FLUTE-1'){
				Fact = takeUpFactB;
			}
				
		}


	}else{
		var Fact = 1;
	}
		

		var GSM = document.getElementById('txtGSM['+PIndex+']').value;
		var TWidth0 = document.getElementById('txtTWidth[0]').value;
		var LM0 = document.getElementById('txtLM[0]').value;
		var sizeMM = document.getElementById('txtSizeTrim['+PIndex+']').value;

		if(GSM==''){
			GSM=0
		}
	
		if(sizeMM==''){
			sizeMM=0
		}

		var UReq = ( ( (GSM * Fact)*(TWidth0*LM0))/1000000 ) + ( (sizeMM*LM0)*(GSM * Fact)/1000000);
		//alert(UReq);
		document.getElementById('txtURequired['+PIndex+']').value=Math.round(UReq);


    var totUReq=0;
	for(var i =0 ; i<rowsNb ; i++ ){
		if(document.getElementById('txtURequired['+i+']').value!=''){
		var Ureq = parseFloat(document.getElementById('txtURequired['+i+']').value);
		
		totUReq += Ureq;
		}
	}

	document.getElementById('txtURequiredT').value=Math.round(totUReq);


	UAct(PIndex);
	TReq(PIndex);
	ReqTrim(PIndex);
	ActTrim(PIndex);


}


function UAct(PIndex){
	x = document.mastercard;
	if( document.getElementById('takeUpFact[1]') ) var takeUpFact = document.getElementById('takeUpFact[1]').value;
	else  var takeUpFact =  0;
	var rowsNb     = document.getElementById('rowsNb[0]').value;

	var FluteType = document.getElementById('txtFluteType[0]').value;
	var takeUpFactB = document.getElementById('takeUpFactB[0]').value;
	var takeUpFactC = document.getElementById('takeUpFactC[0]').value;
	var takeUpFactE = document.getElementById('takeUpFactE[0]').value;
	var takeUpFact = document.getElementById('takeUpFact[0]').value;
	var txttype  = document.getElementById('txttype['+PIndex+']').value;

	if(PIndex %2 != 0 ){
		var Fact = takeUpFact;

		if(FluteType == 4){
			Fact = takeUpFactB;
			if(txttype==='FLUTE-1'){
				Fact = takeUpFactC;
			}
		}else if(FluteType == 5){
			Fact = takeUpFactE;
			if(txttype==='FLUTE-1'){
				Fact = takeUpFactB;
			}
				
		}
	}else{
		var Fact = 1;
	}
		var GSM = parseFloat(document.getElementById('txtGSM['+PIndex+']').value);
		var TWidth0 = parseFloat(document.getElementById('txtTWidth[0]').value);
		var LM1 = parseFloat(document.getElementById('txtLM[1]').value);
		var sizeMM = parseFloat(document.getElementById('txtSizeTrim['+PIndex+']').value);
		if(GSM=='' || isNaN(GSM)){
			GSM=0
		}
	
		if(sizeMM=='' || isNaN(sizeMM)){
			sizeMM=0
		}

		if(LM1=='' || isNaN(LM1)){
			LM1=0
		}

		var UAct = ( ((GSM*Fact)*(TWidth0*LM1))/1000000 ) + ( (sizeMM*LM1)*(GSM*Fact)/1000000);

		document.getElementById('txtUActual['+PIndex+']').value=Math.round(UAct);



	var totUAct=0;
	for(var i =0 ; i<rowsNb ; i++ ){
	if(document.getElementById('txtUActual['+i+']').value!=''){
			var Uact = parseFloat(document.getElementById('txtUActual['+i+']').value);
			if(Uact=='' || isNaN(Uact)){
				Uact=0
			}
			totUAct += Uact;
	}
	}
	document.getElementById('txtUActualT').value=Math.round(totUAct);

	LReq(PIndex);



}

function LReq(PIndex){
	var takeUpFact = document.getElementById('takeUpFact[0]').value;
	var rowsNb     = document.getElementById('rowsNb[0]').value;

	var FluteType = document.getElementById('txtFluteType[0]').value;
	var takeUpFactB = document.getElementById('takeUpFactB[0]').value;
	var takeUpFactC = document.getElementById('takeUpFactC[0]').value;
	var takeUpFactE = document.getElementById('takeUpFactE[0]').value;
	var txttype  = document.getElementById('txttype['+PIndex+']').value;

	if(PIndex %2 != 0 ){
		var Fact = takeUpFact;

		if(FluteType == 4){
			Fact = takeUpFactB;
			if(txttype==='FLUTE-1'){
				Fact = takeUpFactC;
			}
		}else if(FluteType == 5){
			Fact = takeUpFactE;
			if(txttype==='FLUTE-1'){
				Fact = takeUpFactB;
			}
				
		}
	}else{
		var Fact = 1;
	}

		var GSM = parseFloat(document.getElementById('txtGSM['+PIndex+']').value);
		var TWidth1 = parseFloat(document.getElementById('txtTWidth[1]').value);
		var LM0 = parseFloat(document.getElementById('txtLM[0]').value);

		if(GSM=='' || isNaN(GSM)){
			GSM=0
		}
	
		if(TWidth1=='' || isNaN(TWidth1)){
			TWidth1=0
		}

		if(LM0=='' || isNaN(LM0)){
			LM0=0
		}

		var LReq = ( (GSM*Fact)*(TWidth1*LM0))/1000000  ;
         
		document.getElementById('txtLRequired['+PIndex+']').value=Math.round(LReq);


	var totLReq=0;
	for(var i =0 ; i<rowsNb ; i++ ){
      if(document.getElementById('txtLRequired['+i+']').value!=''){
		var Lreq = parseFloat(document.getElementById('txtLRequired['+i+']').value);
		if(Lreq=='' || isNaN(Lreq)){
			Lreq=0
		}
		totLReq += Lreq;
		
	  }
	}
	document.getElementById('txtLRequiredT').value=Math.round(totLReq);

	LAct(PIndex);



}

function LAct(PIndex){
	var takeUpFact = document.getElementById('takeUpFact[0]').value;
	var rowsNb     = document.getElementById('rowsNb[0]').value;

	var FluteType = document.getElementById('txtFluteType[0]').value;
	var takeUpFactB = document.getElementById('takeUpFactB[0]').value;
	var takeUpFactC = document.getElementById('takeUpFactC[0]').value;
	var takeUpFactE = document.getElementById('takeUpFactE[0]').value;
	var txttype  = document.getElementById('txttype['+PIndex+']').value;

	if(PIndex %2 != 0 ){
		var Fact = takeUpFact;

		if(FluteType == 4){
			Fact = takeUpFactB;
			if(txttype==='FLUTE-1'){
				Fact = takeUpFactC;
			}
		}else if(FluteType == 5){
			Fact = takeUpFactE;
			if(txttype==='FLUTE-1'){
				Fact = takeUpFactB;
			}
				
		}
	}else{
		var Fact = 1;
	}
	//if(PIndex==0){
		var GSM = parseFloat(document.getElementById('txtGSM['+PIndex+']').value);
		var TWidth1 = parseFloat(document.getElementById('txtTWidth[1]').value);
		var LM1 = parseFloat(document.getElementById('txtLM[1]').value);

		if(GSM=='' || isNaN(GSM)){
			GSM=0
		}
	
		if(TWidth1=='' || isNaN(TWidth1)){
			TWidth1=0
		}

		if(LM1=='' || isNaN(LM1)){
			LM1=0
		}

		var LAct = ( (GSM*Fact)*(TWidth1*LM1))/1000000  ;

		document.getElementById('txtLActual['+PIndex+']').value=Math.round(LAct);

	//}

	var totLAct=0;
	for(var i =0 ; i<rowsNb ; i++ ){
	if(document.getElementById('txtLActual['+i+']').value!=''){
		var Lact = parseFloat(document.getElementById('txtLActual['+i+']').value);
		if(Lact=='' || isNaN(Lact)){
			Lact=0
		}
		totLAct += Lact;
	}
	}
	document.getElementById('txtLActualT').value=Math.round(totLAct);



}


function TReq(PIndex){
	var takeUpFact = document.getElementById('takeUpFact[0]').value;
	var rowsNb     = document.getElementById('rowsNb[0]').value;
	
	

	if(PIndex %2 != 0 ){
		var Fact = takeUpFact;
	}else{
		var Fact = 1;
	}

		var UReq = parseFloat(document.getElementById('txtURequired['+PIndex+']').value);
		var LReq = parseFloat(document.getElementById('txtLRequired['+PIndex+']').value);
		var ReqTrim = parseFloat(document.getElementById('txtRequiredTrim['+PIndex+']').value); 

		if(UReq=='' || isNaN(UReq)){
			UReq=0
		}

		if(LReq=='' || isNaN(LReq)){
			LReq=0
		}

		if(ReqTrim=='' || isNaN(ReqTrim)){
			ReqTrim=0
		}

		var TReq = UReq + LReq + (ReqTrim/1000) ;
		

		document.getElementById('txtTRequired['+PIndex+']').value=Math.round(TReq);

		


	var totTReq=0;
	for(var i =0 ; i<rowsNb ; i++ ){
		if(document.getElementById('txtTRequired['+i+']').value!=''){
		var Trec = parseFloat(document.getElementById('txtTRequired['+i+']').value);
		if(Trec=='' || isNaN(Trec)){
			Trec=0
		}
		totTReq += Trec;
		}
       }
	document.getElementById('txtTRequiredT').value=Math.round(totTReq);

	var totTrim=0;
	for(var i =0 ; i<rowsNb ; i++ ){
		if(document.getElementById('txtRequiredTrim['+i+']').value!=''){
		var Trimc = parseFloat(document.getElementById('txtRequiredTrim['+i+']').value);
		if(Trimc=='' || isNaN(Trimc)){
			Trimc=0
		}
		totTrim += Trimc;
		}
       }
	document.getElementById('txtRequiredTrimT').value = totTrim;

	if(totTrim!=''){

     var trimWastePer = (totTrim / totTReq)*100;
	 if(trimWastePer>3){
		document.getElementById('txtTrimWaste').style.color = 'red';
	 }
	 document.getElementById('txtTrimWaste').value=trimWastePer.toFixed(2);
	}



	TAct(PIndex);
	
}



function TAct(PIndex){

	//if(PIndex==0){
		var UAct = parseFloat(document.getElementById('txtUActual['+PIndex+']').value);
		var LAct = parseFloat(document.getElementById('txtLActual['+PIndex+']').value);
		var ActTrim = parseFloat(document.getElementById('txtActualTrim['+PIndex+']').value);
		var rowsNb     = document.getElementById('rowsNb[0]').value;

		if(UAct=='' || isNaN(UAct)){
			UAct=0
		}
		if(LAct=='' || isNaN(LAct)){
			LAct=0
		}
		if(ActTrim=='' || isNaN(ActTrim)){
			ActTrim=0
		}
       
		var TAct = UAct + LAct + (ActTrim/1000) ;


		document.getElementById('txtTActual['+PIndex+']').value=Math.round(TAct);

	//}

	var totTAct=0;
	for(var i =0 ; i<rowsNb ; i++ ){
		if(document.getElementById('txtTActual['+i+']').value!=''){
		var Tact = parseFloat(document.getElementById('txtTActual['+i+']').value);
		if(Tact=='' || isNaN(Tact)){
			Tact=0
		}
		totTAct += Tact;
	}
}
	document.getElementById('txtTActualT').value=Math.round(totTAct);



}


function ReqTrim(PIndex){

	//if(PIndex==0){
		var sizeMM = document.getElementById('txtSizeTrim['+PIndex+']').value;
		var LM = parseFloat(document.getElementById('txtLM[0]').value);
		var GSM = parseFloat(document.getElementById('txtGSM['+PIndex+']').value);
		var rowsNb     = parseFloat(document.getElementById('rowsNb[0]').value);

		if(GSM=='' || isNaN(GSM)){
			GSM=0
		}
	
		if(sizeMM=='' || isNaN(sizeMM)){
			sizeMM=0
		}

		if(LM=='' || isNaN(LM)){
			LM=0
		}

		var ReqTrim = ( (sizeMM*LM)*GSM)/1000000;

		//document.getElementById('txtRequiredTrim['+PIndex+']').value=Math.round(ReqTrim);

	//}

	var totTReqTrim=0;
	for(var i =0 ; i<rowsNb ; i++ ){
		if(document.getElementById('txtRequiredTrim['+i+']').value!=''){
		var Trectrim = parseFloat(document.getElementById('txtRequiredTrim['+i+']').value);
		if(Trectrim=='' || isNaN(Trectrim)){
			Trectrim=0
		}
		totTReqTrim += Trectrim;
	}
}
	document.getElementById('txtRequiredTrimT').value=Math.round(totTReqTrim);

	var txtTRequiredT = document.getElementById('txtTRequiredT').value;

	if(txtTRequiredT!=''){

     var trimWastePer = (totTReqTrim / txtTRequiredT)*100;
	 document.getElementById('txtTrimWaste').value=Math.round(trimWastePer);
	}

	TReq(PIndex);

}

function filterData(PIndex){
	var ReelDeckle = document.getElementById('txtRellDeckle['+PIndex+']').value;
	var GSM = document.getElementById('txtGSM['+PIndex+']').value;
	var PaperGrade = document.getElementById('txtPaperGrade['+PIndex+']').value;

		
	if (window.XMLHttpRequest)
	{// code for IE7+, Firefox, Chrome, Opera, Safari
	xmlhttp=new XMLHttpRequest();
	}
	else
	{// code for IE6, IE5
	xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
	}
	xmlhttp.onreadystatechange=function()
	{
	if (xmlhttp.readyState==4 && xmlhttp.status==200)
		{
					{ 	
						document.getElementById('vacationdiv').innerHTML=xmlhttp.responseText;
						
					}
			}
	}

	

	xmlhttp.open("GET","Form_Corrugator_Actions.php?txtRellDeckle="+ReelDeckle+"&txtGSM="+GSM+"&txtPaperGrade="+PaperGrade
													+"&mode=filterData",true);

	xmlhttp.send();



}
function ActTrim(PIndex){

	//if(PIndex==0){
		var sizeMM = parseFloat(document.getElementById('txtSizeTrim['+PIndex+']').value);
		var LM = parseFloat(document.getElementById('txtLM[1]').value);
		var GSM = parseFloat(document.getElementById('txtGSM['+PIndex+']').value);
		var rowsNb     = document.getElementById('rowsNb[0]').value;

		if(GSM=='' || isNaN(GSM)){
			GSM=0
		}
	
		if(sizeMM=='' || isNaN(sizeMM)){
			sizeMM=0
		}

		if(LM=='' || isNaN(LM)){
			LM=0
		}

		var ActTrim = ( (sizeMM*LM)*GSM)/1000000;

		document.getElementById('txtActualTrim['+PIndex+']').value=Math.round(ActTrim);

	//}

	var totActTrim=0;
	for(var i =0 ; i<rowsNb ; i++ ){
		if(document.getElementById('txtActualTrim['+i+']').value!=''){
		var Tacttrim = parseFloat(document.getElementById('txtActualTrim['+i+']').value);
		if(Tacttrim=='' || isNaN(Tacttrim)){
			Tacttrim=0
		}
		totActTrim += Tacttrim;
	}
}
	document.getElementById('txtActualTrimT').value=Math.round(totActTrim);

	TAct(PIndex);
	ReqTrim(PIndex);



}
function RefreshPage(index){
      TotGSM(index);
	  x = document.mastercard;
	var vCorrugatorId    = x.txtCorrugatorId.value;
	var vMainMode = x.mainaction.value;
	var count = x.count.value; 
	var txtTotalGSM = document.getElementById('txtTotalGSM').value;
	var txtWTPCL = document.getElementById('txtWTPCL').value;
   	var txtWTPCU = document.getElementById('txtWTPCU').value;
	x.action="Form_Corrugator_Actions.php?mode=changeFluteType&mainmode="+vMainMode+"&Corrugator_Id="+vCorrugatorId+'&index='+index+'&count='+count+
	'&txtTotalGSM='+txtTotalGSM+'&txtWTPCU='+txtWTPCU+'&txtWTPCL='+txtWTPCL;
	x.submit();
	
	
	 
}

function TotGSM(PIndex){
var totGSM = 0;
var takeUpFact = document.getElementById('takeUpFact[0]').value;
var rowsNb     = document.getElementById('rowsNb[0]').value;
// console.log(rowsNb);
var fluteTypeSelect = document.getElementById("txtFluteType[0]");
var flutetype = fluteTypeSelect.options[fluteTypeSelect.selectedIndex].text;
 flutetype = flutetype.split("-");
 flutetype = flutetype[0];
 flutetype = flutetype.trim().toUpperCase();
if(rowsNb == 3){
var  liner1 = document.getElementById('txtGSM[0]').value;
var flutting1 = document.getElementById('txtGSM[1]').value;
var  liner2 = 0;
var flutting2 = 0;
var outer = document.getElementById('txtGSM[2]').value;

}
else if(rowsNb == 5) {
var  liner1 = document.getElementById('txtGSM[0]').value;
var flutting1 = document.getElementById('txtGSM[1]').value;
var  liner2 = document.getElementById('txtGSM[2]').value;
var flutting2 = document.getElementById('txtGSM[3]').value;
var outer = document.getElementById('txtGSM[4]').value;

}
else {
var  liner1 =0;
var flutting1 =0;
var  liner2 =0;
var flutting2 = 0;
var outer = 0;
}

if(liner1=='' || isNaN(liner1)){
	liner1 =0;
}
if(flutting1=='' || isNaN(flutting1)){
	flutting1 =0;
}
if(liner2=='' || isNaN(liner2)){
  liner2 =0;
}
if(flutting2=='' || isNaN(flutting2)){
 flutting2 = 0;
}
if(outer=='' || isNaN(outer)){
 outer = 0;
}

liner1 = parseFloat(liner1);
flutting1 = parseFloat(flutting1);
liner2 = parseFloat(liner2);
flutting2 = parseFloat(flutting2);
outer = parseFloat(outer);
if( flutetype === 'B' ){
		totGSM = liner1 + ( flutting1 * 1.32 ) + 	liner2  + ( flutting2 * 1.32 ) + outer ;
}
else if( flutetype === 'C' ){
   
		totGSM = liner1 + ( flutting1 * 1.46 ) +	liner2  + ( flutting2 * 1.46 ) + outer ;
	}

else if( flutetype === 'E'){
		totGSM = liner1 + ( flutting1 * 1.27 ) + 	liner2 + ( flutting2 * 1.27 ) + outer ;
	}
else if( flutetype === 'BC' ){
		totGSM = liner1 + ( flutting1 * 1.46 ) +	liner2  + ( flutting2 * 1.32 ) + outer ;
	}
else if( flutetype === 'BE' ){
		totGSM = liner1 + ( flutting1 * 1.32 ) +	liner2  + ( flutting2 * 1.27 ) + outer ;
	}
else if( flutetype === 'CE' ){
		totGSM = liner1 + ( flutting1 * 1.46 ) +	liner2  + ( flutting2 * 1.27 ) + outer ;
	}

	/*for(var i =0 ; i<=PIndex;i++){
		if(document.getElementById('txtGSM['+i+']').value!=''){
		var GSM = document.getElementById('txtGSM['+i+']').value;

		if(i %2 != 0 ){
			var Fact = takeUpFact;
			
		}else{
			var Fact = 1;
		}

         GSM = GSM*Fact;
	
		totGSM += GSM;
	}
}
	*/

	document.getElementById('txtTotalGSM').value=Math.round(totGSM);
	WTPCU();
	WTPCL();
}

function WTPCU(){

	var len = parseFloat(document.getElementById('txtLength[0]').value);
	var wid = parseFloat(document.getElementById('txtWidth[0]').value);
	var totGSM = parseFloat(document.getElementById('txtTotalGSM').value);

	if(len=='' || isNaN(len)){
		len=0;
	}
	if(wid=='' || isNaN(wid)){
		wid=0;
	}
	if(totGSM=='' || isNaN(totGSM)){
		totGSM=0;
	}

	var WTPCU = ((len*wid)*totGSM)/1000000;
	document.getElementById('txtWTPCU').value= Math.round(WTPCU);

}

function WTPCL(){

	var len = parseFloat(document.getElementById('txtLength[1]').value);
	var wid = parseFloat(document.getElementById('txtWidth[1]').value);
	var totGSM = parseFloat(document.getElementById('txtTotalGSM').value);

	if(len=='' || isNaN(len)){
		len=0;
	}
	if(wid=='' || isNaN(wid)){
		wid=0;
	}
	if(totGSM=='' || isNaN(totGSM)){
		totGSM=0;
	}

	var WTPCL = ((len*wid)*totGSM)/1000000;
	document.getElementById('txtWTPCL').value= Math.round(WTPCL);

}

function showlistSupp1(str,PIndex){

	var vDate, txtSupplier;
	x = document.mastercard;
	var mb='fg';
	
	XPos = (screen.availHeight - 400) / 2;
	YPos = (screen.availWidth - 850) / 2;
  
	vDate 	   = x.corrugator_date.value;
	txtSupplier  = encodeURIComponent(document.getElementById('txtSupplierName['+PIndex+']').value);
	 var ItemCode =  encodeURIComponent(document.getElementById('txtItemCode1['+PIndex+']').value);
	window.open('List_Suppliers_item.php?str='+str+'&index='+PIndex+'&txtSupplier='+txtSupplier+'&ItemCode='+ItemCode+'&ItemType=All&TransDate='+vDate+'&mb='+mb,'List_SuppItems','width=850,height=400,toolbar=no,location=no,directories=no,status=yes,menubar=no,scrollbars=yes,copyhistory=no,resizable=no,left='+YPos+',top='+XPos);
}

function setGSM(PIndex){
	x       = document.mastercard;
	var gsm        = document.getElementById('txtGSM['+PIndex+']').value;
	var paperGrade = document.getElementById('txtPaperGrade['+PIndex+']').value;
	var RellDeckle = document.getElementById('txtRellDeckle['+PIndex+']').value;
	var paperMill  = document.getElementById('txtPaperMill['+PIndex+']').value;
    var sizetrimm =  document.getElementById('txtSizeTrim['+PIndex+']').value;

	document.getElementById('txtItemCode1['+PIndex+']').value='';
	if(RellDeckle!='' && paperGrade!=''){
	if (window.XMLHttpRequest)
			{// code for IE7+, Firefox, Chrome, Opera, Safari
			xmlhttp=new XMLHttpRequest();
			}
			else
			{// code for IE6, IE5
			xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
			}
			xmlhttp.onreadystatechange=function()
			{
			if (xmlhttp.readyState==4 && xmlhttp.status==200)
				{
							{ 	//alert(xmlhttp.responseText);
								//var data = xmlhttp.responseText.split("[BRK]");
								var data = xmlhttp.responseText;
								
								//var txtPaperGrade = document.getElementById('txtPaperGrade['+PIndex+']');
								//txtPaperGrade.innerHTML = "";
								var txtGSM = document.getElementById('txtGSM['+PIndex+']');
								txtGSM.innerHTML = "";
								var GSMArr = data.split(',');

								var emptyOption = document.createElement("option");
								emptyOption.value = "";
								emptyOption.textContent = "";
								txtGSM.appendChild(emptyOption);

								for(var xx=0;xx<GSMArr.length;xx++){
									if(GSMArr[xx]!=''){
										var option = document.createElement("option");
										option.value = GSMArr[xx].trim();
										option.textContent = GSMArr[xx].trim();
										txtGSM.appendChild(option);
									}
								}
								setItems(PIndex);
								
							}
					}
			}

  
// document.mastercard.action = "Form_Corrugator_Actions.php?mainmode=setGSM&txtRellDeckle="+RellDeckle;
// document.mastercard.submit();
		xmlhttp.open("GET","Form_Corrugator_Actions.php?mainmode=setGSM&txtRellDeckle="+RellDeckle+"&txtPaperGrade="+paperGrade,true);
		xmlhttp.send();
	}

}

function setPaperGrade(PIndex){
	x       = document.mastercard;
	var gsm        = document.getElementById('txtGSM['+PIndex+']').value;
	var paperGrade = document.getElementById('txtPaperGrade['+PIndex+']').value;
	var RellDeckle = document.getElementById('txtRellDeckle['+PIndex+']').value;
	var paperMill  = document.getElementById('txtPaperMill['+PIndex+']').value;
    var sizetrimm =  document.getElementById('txtSizeTrim['+PIndex+']').value;
	document.getElementById('txtItemCode1['+PIndex+']').value='';
	if(RellDeckle!=''){
		if (window.XMLHttpRequest)
				{// code for IE7+, Firefox, Chrome, Opera, Safari
				xmlhttp=new XMLHttpRequest();
				}
				else
				{// code for IE6, IE5
				xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
				}
				xmlhttp.onreadystatechange=function()
				{
				if (xmlhttp.readyState==4 && xmlhttp.status==200)
					{
								{ 	
									var data = xmlhttp.responseText;
									var txtPaperGrade = document.getElementById('txtPaperGrade['+PIndex+']');
									txtPaperGrade.innerHTML = "";
									var txtGSM = document.getElementById('txtGSM['+PIndex+']');
									txtGSM.innerHTML = "";
									var txtPaperMill = document.getElementById('txtPaperMill['+PIndex+']');
									txtPaperMill.innerHTML = "";
									var PaperGradeArr = data.split(',');
									//console.log(PaperGradeArr);
									var emptyOption = document.createElement("option");
									emptyOption.value = "";
									emptyOption.textContent = "";
									txtPaperGrade.appendChild(emptyOption);

									for(var xx=0;xx<PaperGradeArr.length;xx++){
										if(PaperGradeArr[xx]!=''){
											var option = document.createElement("option");
											option.value = PaperGradeArr[xx].trim();
											option.textContent = PaperGradeArr[xx].trim();
											txtPaperGrade.appendChild(option);
										}
									}
									setItems(PIndex);
									
								}
						}
				}

// 				document.mastercard.action="Form_Corrugator_Actions.php?mainmode=setPaperGrade&txtRellDeckle="+RellDeckle+"&txtGSM="+gsm;
// document.mastercard.submit();
			xmlhttp.open("GET","Form_Corrugator_Actions.php?mainmode=setPaperGrade&txtRellDeckle="+RellDeckle+"&txtGSM="+gsm,true);
			xmlhttp.send();
	}
}

function setPaperMill(PIndex){
	x       = document.mastercard;
	var gsm        = document.getElementById('txtGSM['+PIndex+']').value;
	var paperGrade = document.getElementById('txtPaperGrade['+PIndex+']').value;
	var RellDeckle = document.getElementById('txtRellDeckle['+PIndex+']').value;
    var sizetrimm =  document.getElementById('txtSizeTrim['+PIndex+']').value;
	var corrugator_date  = x.corrugator_date.value;
	var warehousecode = document.getElementById('warehousecode[' + PIndex + ']').value ;
	var txtCorrugatorId =  x.txtCorrugatorId.value;
	document.getElementById('txtItemCode1['+PIndex+']').value='';
	if(gsm!=''){
		if (window.XMLHttpRequest)
				{// code for IE7+, Firefox, Chrome, Opera, Safari
				xmlhttp=new XMLHttpRequest();
				}
				else
				{// code for IE6, IE5
				xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
				}
				xmlhttp.onreadystatechange=function()
				{
				if (xmlhttp.readyState==4 && xmlhttp.status==200)
					{
								{ 	
									var data = xmlhttp.responseText;
									var txtPaperMill = document.getElementById('txtPaperMill['+PIndex+']');
									setItems(PIndex);
								if(data!=''){
								txtPaperMill.innerHTML = "";
								var paperMillArr = data.split(',');
								console.log(data);
								var emptyOption = document.createElement("option");
								emptyOption.value = ""; 
								emptyOption.textContent = "";
								txtPaperMill.appendChild(emptyOption);

								for(var xx=0;xx<paperMillArr.length;xx++){
									if(paperMillArr[xx]!=''){
										var option = document.createElement("option");
										option.value = paperMillArr[xx].trim();
										option.textContent = paperMillArr[xx].trim();
										txtPaperMill.appendChild(option);
									}
								}
								}
// 								else{

								
// 									setItems(PIndex);
// }
								
									
								}
						}
				}


			xmlhttp.open("GET","Form_Corrugator_Actions.php?mainmode=setPaperMill&txtRellDeckle="+RellDeckle+"&txtGSM="+gsm+"&txtPaperGrade="+paperGrade
			+"&corrugator_date_="+corrugator_date+'&txtWareHouse=' + warehousecode+'&txtCorrugatorId='+txtCorrugatorId,true);
			xmlhttp.send();
			
			//  x.action="Form_Corrugator_Actions.php?mainmode=setPaperMill&txtRellDeckle="+RellDeckle+"&txtGSM="+gsm+"&txtPaperGrade="+paperGrade
			// +"&corrugator_date_="+corrugator_date+'&txtWareHouse=' + warehousecode+'&txtCorrugatorId='+txtCorrugatorId;
			//  x.submit();
	}
}

function setItems(PIndex){
    
	x       = document.mastercard;
	var gsm        = document.getElementById('txtGSM['+PIndex+']').value;
	var paperGrade = document.getElementById('txtPaperGrade['+PIndex+']').value;
	var RellDeckle = document.getElementById('txtRellDeckle['+PIndex+']').value;
	var paperMill  = document.getElementById('txtPaperMill['+PIndex+']').value;
    var sizetrimm =  document.getElementById('txtSizeTrim['+PIndex+']').value;
	var corrugator_date  = x.corrugator_date.value;
	var warehousecode = document.getElementById('warehousecode[' + PIndex + ']').value ;
	var txtCorrugatorId =  x.txtCorrugatorId.value;

	// if (paperMill === '' && document.getElementById('txtPaperMill['+PIndex+']').options.length > 2) {
    // 	paperMill = 'NA';
	// }


	if( gsm !='' && paperGrade !='' && RellDeckle !='' )
		{

			if (window.XMLHttpRequest)
			{// code for IE7+, Firefox, Chrome, Opera, Safari
			xmlhttp=new XMLHttpRequest();
			}
			else
			{// code for IE6, IE5
			xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
			}
			xmlhttp.onreadystatechange=function()
			{
			if (xmlhttp.readyState==4 && xmlhttp.status==200)
				{
							{ 	
								var data = xmlhttp.responseText.split("[BRK]");
								document.getElementById('txtItemCode1[' + PIndex + ']').value=data[0];
								//alert(data[0]);
								// var txtPaperMill = document.getElementById('txtPaperMill['+PIndex+']');
								// txtPaperMill.innerHTML = "";
								// var paperMillArr = data[1].split(',');

								// var emptyOption = document.createElement("option");
								// emptyOption.value = "";
								// emptyOption.textContent = "";
								// txtPaperMill.appendChild(emptyOption);

								// for(var xx=0;xx<paperMillArr.length;xx++){
								// 	if(paperMillArr[xx]!=''){
								// 		var option = document.createElement("option");
								// 		option.value = paperMillArr[xx].trim();
								// 		option.textContent = paperMillArr[xx].trim();
								// 		txtPaperMill.appendChild(option);
								// 	}
								// }
								UReq(PIndex);
			 					LReq(PIndex);
			 					TotGSM(PIndex);
			 					sizeTrim(PIndex);
								 var locid = document.getElementById('locid[' + PIndex + ']').value;
 			 if(locid == '') locid = 0;
 			 var txttype =  document.getElementById('txttype[' + PIndex + ']').value;
			 var txtCorrugatorId = x.txtCorrugatorId.value;
								SubmitLocationGrid(PIndex,locid,'','','',txtCorrugatorId,txttype);
								
							}
					}
			}

   
  
			xmlhttp.open("GET","Form_Corrugator_Actions.php?mainmode=setItem&txtGSM="+gsm+"&txtPaperGrade="+paperGrade+"&txtRellDeckle="+RellDeckle+"&txtPaperMill="+paperMill+"&corrugator_date_="+corrugator_date+
			'&txtWareHouse=' + warehousecode+'&txtCorrugatorId='+txtCorrugatorId,true);

			xmlhttp.send();


			//  var locid = document.getElementById('locid[' + PIndex + ']').value;
			//  if(locid == '') locid = 0;
			//  var txttype =  document.getElementById('txttype[' + PIndex + ']').value;
			//  SubmitLocationGrid(PIndex,locid,'','','',$txtCorrugatorId,txttype);
		


			// x.action="Form_Corrugator_Actions.php?mainmode=setItem&txtGSM="+gsm+"&txtPaperGrade="+paperGrade+"&txtRellDeckle="+RellDeckle+"&txtPaperMill="+paperMill+"&corrugator_date_="+corrugator_date+
			// '&txtWareHouse=' + warehousecode+'&txtCorrugatorId='+txtCorrugatorId;
			// x.submit();
		}



 }






function DeleteRecordUpper(txtCorrugatorId,ulstacker_id,index,dftorderby,orderby,morelines,startrange,perpage,direction) {
	x = document.mastercard;
	var vCorrugatorId    = x.txtCorrugatorId.value;
	var vMainMode = x.mainaction.value;
	var vDelMsg = 'Are you sure you want to delete this record?';

	
	if (confirm ( vDelMsg ))
	{
		x.action="Form_Corrugator_Actions.php?mode=deletedt&mainmode="+vMainMode+"&Corrugator_Id="+vCorrugatorId+"&ulstacker_id="+ulstacker_id;
		x.submit();
	
	}	

}

function DeleteRecordUpperLower(txtCorrugatorId,index,JobCardId,ulstacker_id) {
	x = document.mastercard;

	var count = parseFloat(x.count.value);
	var txtCorrugatorRef = x.txtCorrugatorRef.value;
	//var JobCardId = x.JobCardId.value;
	// JobCardId     = JobCardId[index];

	var vCorrugatorId    = x.txtCorrugatorId.value;
	// var corrIdArr = vCorrugatorId.split(',');
	// var corrId = corrIdArr[index];
	// corrIdArr.splice(index,1);
	// x.txtCorrugatorId.value = corrIdArr;



	// var corrRefArr = txtCorrugatorRef.split(',');
	// var corrRef = corrRefArr[index];
	// corrRefArr.splice(index,1);
	// x.txtCorrugatorRef.value = corrRefArr;

	//var JobCardIdArr = JobCardId.split(',');
	//var JobCardId = JobCardIdArr[index];
	// JobCardIdArr.splice(index,1);
	// x.JobCardId.value = JobCardIdArr;
 
	count = count-1;

	x.count.value = count;

	var vMainMode = x.mainaction.value;
	var vDelMsg = 'Are you sure you want to delete this record?';

	
	if (confirm ( vDelMsg ))
	{
		x.action="Form_Corrugator_Actions.php?mode=deleteUpperLower&mainmode="+vMainMode+"&txtCorrugatorId="+vCorrugatorId+"&JobCardId="+JobCardId+"&index="+index+"&txtCorrugatorRef="+txtCorrugatorRef+"&ulstacker_id="+ulstacker_id
		+"&count="+count;
		x.submit();
	
	}	

}

function DeleteRecordCorGrid(txtCorrugatorId,corgrid_id,index,dftorderby,orderby,morelines,startrange,perpage,direction) {
	x = document.mastercard;
	var vCorrugatorId    = x.txtCorrugatorId.value;
	var vMainMode = x.mainaction.value;
	var vDelMsg = 'Are you sure you want to delete this record?';
	var count = x.count.value;
	var JobCardId = x.JobCardId.value;
	
	
	if (confirm ( vDelMsg ))
	{
		x.action="Form_Corrugator_Actions.php?mode=deletedtpaper&mainmode="+vMainMode+"&Corrugator_Id="+vCorrugatorId+"&JobCardId="+JobCardId+"&corgrid_id="+corgrid_id+"&count="+count;
		x.submit();
	
	}	

}

function DeleteRecordLocation(txtCorrugatorId,loc_id,index,dftorderby,orderby,morelines,startrange,perpage,direction) {
	x = document.mastercard;
	var vCorrugatorId    = x.txtCorrugatorId.value;
	var vMainMode = x.mainaction.value;
	var vDelMsg = 'Are you sure you want to delete this record?';
	var count = x.count.value;
	var JobCardId = x.JobCardId.value;
	
	if (confirm ( vDelMsg ))
	{
		x.action="Form_Corrugator_Actions.php?mode=deletedtpaperold&mainmode="+vMainMode+"&Corrugator_Id="+vCorrugatorId+"&JobCardId="+JobCardId+"&loc_id="+loc_id+"&count="+count;
		x.submit();
	
	}	

}

function DefaultWidth(width,S1,S2,S3,PIndex){
	x = document.mastercard;
	document.getElementById('txtWidth['+PIndex+']').value   = width;
	document.getElementById('txtFlapTop['+PIndex+']').value = S1;
	document.getElementById('txtHeight['+PIndex+']').value  = S2;
	document.getElementById('txtFlapBot['+PIndex+']').value = S3;

	document.getElementById('txtHeight['+PIndex+']').style.background = 'white';
	document.getElementById('txtWidth['+PIndex+']').style.background = 'white';
	document.getElementById('txtFlapTop['+PIndex+']').style.background = 'white';
	document.getElementById('txtFlapBot['+PIndex+']').style.background = 'white';
	Twidth(PIndex);

}

function CheckFields() {
    x = document.mastercard;

    var relatedcorr = document.getElementById('corrugator_reference').value;
    var relatedmaster = document.getElementById('corrugator_date').value;

    if (relatedcorr == '') {
        alert('Corrugator is mandatory');
        return 0;
    } else if (relatedmaster == '') {
        alert('Date is mandatory');
        return 0;
    } else {
        return 1;
    }
}



function CheckFieldsDtPaper(PIndex) {
    x = document.mastercard;


return 1;
    
}


function CheckFieldsDt(PIndex) {
    x = document.mastercard;

    var salesref = document.getElementById('txtSalesRef[' + PIndex + ']').value;
    var masterref = document.getElementById('txtMasterCardRef[' + PIndex + ']').value;
	var ScoringType = document.getElementById('txtScoringType[' + PIndex + ']').value;

	var width =  parseFloat(document.getElementById('txtWidth[' + PIndex + ']').value);
	var s1    =  parseFloat(document.getElementById('txtFlapTop[' + PIndex + ']').value);
	var s2    =  parseFloat(document.getElementById('txtHeight[' + PIndex + ']').value);
	var s3    =  parseFloat(document.getElementById('txtFlapBot[' + PIndex + ']').value);

	var CJRRestrict = parseFloat(x.CJRRestrict.value);

	if(width==''){
		width=0;
	}
	if(s1==''){
		s2=0;
	}
	if(s2==''){
		s2=0;
	}
	if(s3==''){
		s3=0;
	}

	var s     = s1+s2+s3;

  



    if (salesref == '') {
        alert('salesref is mandatory');
        return 0;
    } 
	else if (masterref == '') {
        alert('masteref is mandatory');
        return 0;
    }
	else if (width != s && CJRRestrict==0 && ScoringType!='NA') {
        alert('Width and Scoring are not equal');
        return 0;
    }
	else {
        return 1;
    }
}


function showlistItems(str,PIndex){
	var vDate, vItemCode;
	x = document.mastercard;
	var mb='fg';
	
	XPos = (screen.availHeight - 400) / 2;
	YPos = (screen.availWidth - 850) / 2;
  
	vDate 	   = x.corrugator_date.value;
	vItemCode  = encodeURIComponent(document.getElementById('txtItemCode1['+PIndex+']').value);
	
	window.open('List_SorItems.php?frm=32&index='+PIndex+'&PItem_Code='+vItemCode+'&ItemType=All&TransDate='+vDate+'&mb='+mb,'List_SorItems','width=850,height=400,toolbar=no,location=no,directories=no,status=yes,menubar=no,scrollbars=yes,copyhistory=no,resizable=no,left='+YPos+',top='+XPos);
}

function showlistItems1(str,PIndex){
	var vDate, vItemCode;
	x = document.mastercard;
	var mb='fg';
	var vCorrugatorId    = x.txtCorrugatorId.value;
	
	XPos = (screen.availHeight - 400) / 2;
	YPos = (screen.availWidth - 850) / 2;
  
	vDate 	   = x.corrugator_date.value; 
	vItemCode  = encodeURIComponent(document.getElementById('txtItemCode1['+PIndex+']').value);

	
	window.open('List_SorItems.php?frm=32&index='+PIndex+'&str='+str+'&PItem_Code='+vItemCode+'&ItemType=All&TransDate='+vDate+'&mb='+mb,'List_SorItems','width=850,height=400,toolbar=no,location=no,directories=no,status=yes,menubar=no,scrollbars=yes,copyhistory=no,resizable=no,left='+YPos+',top='+XPos);


}








function showlistItemsDesc(str,PIndex){ 
	var vDate, vItemCode;
	x = document.mastercard;
	
	XPos = (screen.availHeight - 400) / 2;
	YPos = (screen.availWidth - 850) / 2;
  
	vDate 	   = x.corrugator_date.value;
	vItemDesc  = encodeURIComponent(document.getElementById('txtItemDesc1['+PIndex+']').value);
	
	window.open('List_SorItems.php?frm=32&index='+PIndex+'&PItem_Description='+vItemDesc+'&ItemType=All&TransDate='+vDate,'List_SorItems','width=850,height=400,toolbar=no,location=no,directories=no,status=yes,menubar=no,scrollbars=yes,copyhistory=no,resizable=no,left='+YPos+',top='+XPos);
}


function SearchForPending(){
    //ClearForm('All');
    x = document.salesorder;
	var type = '';
    win = window.open('Form_CorrugatorSearchPending.php?type='+type, 'Form_CorrugatorSearchPending', 'toolbar=no,location=no,status=yes,menubar=no,scrollbars=yes,resizable=yes,width=1100,height=600,top=30');		
	win.creator = self;
	win.focus();		
			
}



</script>

<link type="text/css" rel="stylesheet" href="dhtmlgoodies_calendar.css?random=20051112" media="screen"></link>
<SCRIPT type="text/javascript" src="dhtmlgoodies_calendar.js?random=20060118"></script>
<style>
.cjr-jobcard-block {
	border: 1px solid #e3e6ea;
	border-radius: 6px;
	margin: 0 0 12px 0;
	padding: 6px 4px 2px 4px;
	background: #fff;
}
.cjr-jobcard-header {
	display: flex;
	align-items: center;
	justify-content: space-between;
	padding: 0 8px 6px 8px;
	border-bottom: 1px solid #eef0f2;
	margin-bottom: 6px;
}
.cjr-jobcard-header .cjr-jobcard-title {
	font-weight: bold;
}
.cjr-multi-input {
	display: flex;
	flex-wrap: wrap;
	gap: 4px;
}
.cjr-multi-input input {
	flex: 1 1 0;
	min-width: 38px;
}
.cjr-field-mismatch {
	background-color: red;
	padding: 0 4px;
	border-radius: 3px;
}
</style>
</head>
<body dir="$body_dir" class="$body_class">
<form name='mastercard' id='mastercard' method='post' enctype='multipart/form-data'>
<input type='hidden' name='actionmode' value='$actionmode'>
<input type='hidden' name='mainaction' value='$mainaction'>
<input type='hidden' name='count' value='$count'>
<input type='hidden' name='CJRRestrict' value='$FI_CJRRestrict'>
<input type='hidden' name='message' value='$message' size='200'>
HERE;

	//$message='';

	// Builds one label + control cell, same markup as Form_Delivery's header fields
	// (field-col > field-row > span.style1 + div.field-control-wrap).
	function cjrField($label, $control, $colClass = 'col-md-3', $labelClass = '')
	{
		return "<div class='$colClass px-2 field-col'><div class='field-row mb-2'>"
			. "<span class='style1 $labelClass'>$label</span>"
			. "<div class='field-control-wrap'>$control</div>"
			. "</div></div>";
	}

	// Returns 'selected' for the matching option (used by the dropdowns below).
	function cjrSelected($value, $current)
	{
		return ($value == $current) ? " selected " : "";
	}


	//Initializations
	$orderimg2 = $orderimg2 ?? '';
	$orderimg3 = $orderimg3 ?? '';
	$KeyDown = $KeyDown ?? '';
	$txtDate = $txtDate ?? '';
	$txtCurrCode = $txtCurrCode ?? '';
	$txtClientCode = $txtClientCode ?? '';

	$PriceLabel = 'Price';
	if (($PurchaseTTC ?? 0) == 1)
		$PriceLabel = 'Price TTC';


	//Get Default Project
	$querydflproject = "select project_code as defaultproject from " . $_SESSION['dbLabel'] . ".loginstk where user_account='" . $_SESSION['useraccount'] . "' and default_project=1  and Upper(loginstk.company_name) = Upper('" . $_SESSION['dbName'] . "') ";
	$resultdflproject = mysqli_query($connection, $querydflproject) or die(mysqli_error($connection));
	while ($rows = mysqli_fetch_array($resultdflproject)) {
		extract($rows);
	}

	//Get Default Coscenter
	$querydflcostcent = "select costcent_code as defaultcostcent from " . $_SESSION['dbLabel'] . ".loginstk where user_account='" . $_SESSION['useraccount'] . "' and default_costcenter=1  and Upper(loginstk.company_name) = Upper('" . $_SESSION['dbName'] . "') ";
	$resultdflcostcent = mysqli_query($connection, $querydflcostcent) or die(mysqli_error($connection));
	while ($rows = mysqli_fetch_array($resultdflcostcent)) {
		extract($rows);
	}


	if ((empty($txtProjectCode)) && ($Module_Project ?? 0) == 1)
		$txtProjectCode = $defaultproject;
	if ((empty($txtCostCentCode)) && ($Module_CostCenter ?? 0) == 1)
		$txtCostCentCode = $defaultcostcent;
	if (empty($txtCurr)) {
		if (($SupplierCur ?? '') != '')
			$txtCurr = $SupplierCur;
		else
			$txtCurr = $base1 ?? '';
	}


	if (empty($mainmode))
		$mainmode = 'insert';

	$txtJobCardIdArr = array();
	$jobcardid = '';
	$jobcardid2 = '';

	if ($txtCorrugatorId != '') {
		// Read From Temp Tables For Selected Record
		$query = "SELECT corrugator_id,jobcardid,corrugator_reference,date_format(corrugator_date, '%d/%m/%Y'),corrugator_confirm,
		corrugator_approve ,jobcardid2
		from temp_corrugator1
						 where corrugator_id = $txtCorrugatorId ";


		//echo $query;exit;
		$result = mysqli_query($connection, $query) or die(mysqli_error($connection));
		$rows = mysqli_fetch_array($result);
		if (is_array($rows)) {
			extract($rows);
			$corrugator_id = $rows[0];
			$jobcardid = $rows[1];
			$corrugator_reference = $rows[2];
			$corrugator_date = $rows[3];
			$corrugator_confirm = $rows[4];
			$corrugator_approve = $rows[5];
			$jobcardid2 = $rows[6];
		}

		$JobCardId = array();
		$JobCardId[0] = $jobcardid;
		$JobCardId[1] = $jobcardid2;
		$txtJobCardIdArr = $JobCardId;
	}



	$data = insertDate($porder_ets ?? '');
	$datevalue = substr($data, 0, 4) . "-" . substr($data, 4, 2) . "-" . substr($data, 6, 2);

	$related_mastercard = $related_mastercard ?? '';
	if ($related_mastercard != '') {
		if (strlen($related_mastercard) < 4) {
			if (strlen($related_mastercard) == 1)
				$related_mastercard = "MC000" . $related_mastercard;
			else if (strlen($related_mastercard) == 2)
				$related_mastercard = "MC00" . $related_mastercard;
			else
				$related_mastercard = "MC0" . $related_mastercard;
		} else
			$related_mastercard = "MC" . $txtRef;
	}

	include_once("datacom.php");
	include_once("menubar.php");


	/* ===================== TOP BAR (title + message + action buttons) ===================== */
	echo "<input type='hidden' name='mainmode' value='$mainmode'>";

	echo "<div class='topbar'>";
	echo "<div class='topbar-title'><div class='icon-wrap'><i class='fa fa-layer-group'></i></div><span>CJR</span></div>";
	if (htmlspecialchars($message, ENT_QUOTES) != '') {
		echo "<div class='topbar-message'>" . htmlspecialchars($message, ENT_QUOTES) . "</div>";
	}
	echo "<div class='topbar-actions'>";

	echo "<button type='button' class='btn-top btn-top-danger' id='btnClear' name='btnClear' onclick='ClearPage();return false;' title='Clear Page'><i class='fa fa-eraser'></i><span>Clear Page</span></button>";

	if ($txtCorrugatorId != '')
		echo "<button type='button' class='btn-top btn-top-danger' id='btnCancel' name='btnCancel' onclick='CancelFunc();return false;' title='Cancel'><i class='fa fa-ban'></i><span>Cancel</span></button>";

	if ($txtCorrugatorId != '')
		echo "<button type='button' class='btn-top btn-top-success' id='btnPost' name='btnPost' onclick='PostChanges();return false;' title='Save Changes'><i class='fa fa-bookmark'></i><span>Save</span></button>";

	if ($txtCorrugatorId == '')
		echo "<button type='button' class='btn-top btn-top-ghost' id='btnSearch' name='btnSearch' onclick='SearchForRef();return false;' title='Search By Reference'><i class='fa fa-search'></i><span>Search</span></button>";

	if ($txtCorrugatorId == '')
		echo "<button type='button' class='btn-top btn-top-ghost' id='btnPending' name='btnPending' onclick='SearchForPending();return false;' title='Pending Preparation Order'><i class='fa fa-clock'></i><span>Pending</span></button>";

	if ($mainaction != 'edit' || $txtCorrugatorId != '')
		echo "<button type='button' class='btn-top btn-top-ghost' id='btnLoad' name='btnLoad' onclick='SearchApprovedKeyLine();return false;' title='Load JobCard'><i class='fa fa-undo'></i><span>Load JobCard</span></button>";

	//echo "<button type='button' class='btn-top btn-top-ghost' id='btnloadTransfer' name='btnloadTransfer' onclick='loadtransfer();return false;' title='Load Transfer'><i class='fa fa-undo'></i><span>Load Transfer</span></button>";

	echo "</div></div>";


	if (empty($I)) {
		$I = 0;
	}

	echo " <div class='$rtl_class form-rtl-page' dir='$body_dir'>
<div class='container-fluid form-content-pad'>";

	echo "<input type='hidden' name='txtCorrugatorId' value='$txtCorrugatorId' size='10'>
<input type='hidden' name='txtCorrugatorRef' value='" . ($txtCorrugatorRef ?? '') . "' size='10'>
<input type='hidden' name='txtRef' value='" . ($txtRef ?? '') . "' size='10' >
<input type='hidden' id='related_salesorder' name='related_salesorder' value='" . ($related_salesorder ?? '') . "' size='10'>
<input type='hidden' id='related_mastercard' name='related_mastercard' value='$related_mastercard' size='10' >
<input type='hidden' id='corgrid_id' name='corgrid_id' value='" . ($corgrid_id ?? '') . "' size='10' >
<input type='hidden' id='corrugatorid' name='corrugatorid' value='" . ($corrugatorid ?? '') . "' size='10' >
<input type='hidden' id='JobCardId' name='JobCardId' value='" . (is_array($JobCardId) ? implode(',', $JobCardId) : $JobCardId) . "' size='10' >
<input type='hidden' id='i' name='i' value='$I' size='10' >
";


	if (empty($txtDate))
		$txtDate = displayToday();

	if (empty($corrugator_date))
		$corrugator_date = displayToday();

	$corrugator_reference = $corrugator_reference ?? '';


	/* ===================== HEADER FIELDS ===================== */
	echo "<div class='row no-gutters'>
		<div class='col-md-12 bg-d-1 bg-d-1-flush'>
		<div class='row no-gutters render-row'>";

	echo cjrField(
		"CJR Reference#",
		"<input class='$inputClass' type='text' id='corrugator_reference' name='corrugator_reference' value='" . htmlspecialchars($corrugator_reference, ENT_QUOTES) . "' maxlength='10' />"
	);
	echo cjrField(
		"Date",
		"<input class='$inputClass mydate' type='text' id='corrugator_date' name='corrugator_date' value='$corrugator_date' onBlur='checkDateFormat(this)' maxlength='10'>"
	);

	echo "</div></div></div>";


	/* ===================== JOB CARDS (Upper / Lower stacker) ===================== */
	$rowsNb = 1;

	echo "<div class='row no-gutters'><div class='col-md-12'>";

	$k = '';
	$scoringTypeArr = array();
	$outsideLinerArr = array();
	$fluteTypeArr = array();
	$color = '';
	$color2 = '';
	$color3 = '';
	$cjrQty = '';
	$corgridid = '';
	$rowsdt = array();
	//var_dump($txtJobCardIdArr);exit;
	for ($i = 0; $i < count($txtJobCardIdArr); $i++) {

		if ($txtJobCardIdArr[$i] != '') {

			$query = "select corgrid_id from temp_corgrid where corrugatorid=$txtCorrugatorId and jobcardid=$txtJobCardIdArr[$i]";
			//echo$query;exit;
			$result = mysqli_query($connection, $query);
			$numofrows = mysqli_num_rows($result);

			$TotalQty = 0;
			$i_nonstock = 0;
			$i_zeroprice = 0;
			$i_minprice = 0;
			$i_unitcoef = 1;

			if ($numofrows == 0) {
				$query1 = "Select
					related_salesorder, related_mastercard,jobcard_id,related_mastercard,related_keylineId,mastercard.mastercard_BoxtypeId,mastercard.product_desc,
					mastercard.scoringtype as MCscoringtype,mastercard.outerlinercolor,mastercard.innerlinercolor,papercombinations.temp_flutting2,jobcard.jobcard_gsm,
					jobcard.jobcard_qtyrequested,clients.ledger_number as txtClientCode,clients.Ledger_name,mastercard.mastercard_FluteTypeId,sorderdt_quantity,mastercard_reference,Sorder_reference,JobCard_Reference,
					NULL,NULL, NULL,sorder.Project_code as Project_code,sorder.Costcent_code as Costcent_code,
					sorder.Currency_code as Currency_code,mastercard.scoringtype as MCscoringtype,
					mastercard.external_length,mastercard.external_width,mastercard.external_height,
					ifNull(mastercard.mastercard_linked,0) as mastercard_linked,mastercard.mastercard_linked_count as mastercard_linked_count


					from  jobcard

					left join mastercard on mastercard.mastercard_id=jobcard.related_mastercard

					left join papercombinations on papercombinations.mastercard_id=mastercard.mastercard_id

					left join clients on clients.ledger_number = mastercard.ledger_number

					left join sorder on sorder.Sorder_id=jobcard.related_salesorder

					left join sorderdt on sorderdt.Sorder_id=sorder.Sorder_id

					left join boxtype on mastercard.mastercard_BoxTypeId = boxtype.boxtype_id

					left join flutetype on flutetype.FluteType_code = mastercard.mastercard_flutetypeid

					where jobcard_id=" . $txtJobCardIdArr[$i] . " ";
			} else {
				$query1 = "Select
					related_salesorder, related_mastercard,jobcard_id,related_mastercard,related_keylineId,temp_upperlowerstacker.boxtype,temp_upperlowerstacker.itemdesc,
					temp_upperlowerstacker.scoringtype,temp_upperlowerstacker.outsideliner,temp_upperlowerstacker.insideliner,papercombinations.temp_flutting2,stdgsm,
					temp_upperlowerstacker.boardneed,clients.ledger_number as txtClientCode,clients.Ledger_name,temp_upperlowerstacker.flutetype,
					sorderdt_quantity,masterref,salesref,jobCardRef,ifNull(plannedqty,0),
					ifNull(producedqty,0), ifNull(remainingqty,0),sorder.Project_code as Project_code,sorder.Costcent_code as Costcent_code,
					sorder.Currency_code as Currency_code,mastercard.scoringtype as MCscoringtype,mastercard.external_length,mastercard.external_width,mastercard.external_height
					,papercombinations.temp_TLiner1,
					papercombinations.temp_Liner1,
					papercombinations.temp_TFlutting1,
					papercombinations.temp_Flutting1,
					papercombinations.temp_Tliner2,
					papercombinations.temp_Liner2,
					papercombinations.temp_TFlutting2,
					papercombinations.temp_Flutting2,
					papercombinations.temp_TOuterLine,
					papercombinations.temp_OuterLine, temp_upperlowerstacker.ulstacker_id, ifnull(BorderNeededQty,0),jobcard_flutetype
					from

					temp_upperlowerstacker

					left join jobcard ON jobcard.jobcard_Id = temp_upperlowerstacker.jobcardid

					left join mastercard on mastercard.mastercard_id=jobcard.related_mastercard

					left join papercombinations on papercombinations.mastercard_id=mastercard.mastercard_id

					left join clients on clients.ledger_number = temp_upperlowerstacker.clientCode

					left join sorder on sorder.Sorder_id=jobcard.related_salesorder

					left join sorderdt on sorderdt.Sorder_id=sorder.Sorder_id

					left join boxtype on mastercard.mastercard_BoxTypeId = boxtype.boxtype_id

					left join flutetype on flutetype.FluteType_code = mastercard.mastercard_FluteTypeId

					where corrugatorid=" . $txtCorrugatorId . " and jobcard.jobcard_id='$txtJobCardIdArr[$i]' ";
			}


			//echo$query1;exit;
			// get the summ of cjr quantity
			$qget = "Select ifnull(sum(plannedqty), 0) from upperlowerstacker
			          left join corruHeader on  Relatedcjr_id = corrugatorid
			         where upperlowerstacker.jobcardid = '$txtJobCardIdArr[$i]' and corrugatorid <> " . $txtCorrugatorId . "
					  and ifnull(Relatedcjr_id,-1) = -1 ";
			$resget = mysqli_query($connection, $qget);
			$rowsget = mysqli_fetch_array($resget);


			$resultdt1 = mysqli_query($connection, $query1);

			$rowsdt = mysqli_fetch_array($resultdt1);
			if (!is_array($rowsdt)) $rowsdt = array();
			extract($rowsdt);
			// Pad missing numeric columns (first query returns fewer columns than the temp one)
			for ($c = 0; $c <= 42; $c++) {
				if (!isset($rowsdt[$c])) $rowsdt[$c] = '';
			}

			$cjrQty = ($rowsget[0] ?? 0) + (float)$rowsdt[20];
			$cjrQty1 = $rowsget[0] ?? 0;
			$ulstacker_id = $rowsdt[40];
			$jobcard_flutetype = $rowsdt[42];
			$fluteType = $rowsdt[15];

			$queryFlute = " Select FluteType_id, FluteType_code, ifnull(flutetype_ply,3) from FluteType where FluteType_id='$fluteType'";
			$resultFlute = mysqli_query($connection, $queryFlute);
			$rowsFlute = mysqli_fetch_array($resultFlute);
			$fluteType_ = $rowsFlute[1] ?? '';

			$takeUpFact = '';
			if ($fluteType_ == 'E') {
				$takeUpFact = 1.27;
			}
			if ($fluteType_ == 'B') {
				$takeUpFact = 1.32;
			}
			if ($fluteType_ == 'C') {
				$takeUpFact = 1.46;
			}
			if ($fluteType_ == 'BC') {
				$takeUpFact = 2.78;
			}
			if ($fluteType_ == 'BE') {
				$takeUpFact = 2.59;
			}

			// count() on a scalar is deprecated in PHP 7.2+ (it always returned 1 for a single value)
			$fluteTypeLen = is_array($fluteType) ? count($fluteType) : 1;

			if ($fluteTypeLen == 1) {
				//$rowsNb = 3;
			} else {
				//$rowsNb = 5;
			}
			if ($rowsNb < ($rowsFlute[2] ?? 0))
				$rowsNb = $rowsFlute[2];


			for ($j = 0; $j < count($scoringTypeArr); $j++) {
				if ($scoringTypeArr[$j] == $rowsdt[7]) {
					$color = '';
				} else {
					$color = 'red';
				}
			}


			$scoringTypeArr[$i] = $rowsdt[7];

			if (($MCscoringtype ?? '') != $rowsdt[7]) {
				$color1 = 'red';
			} else {
				$color1 = '';
			}



			for ($j = 0; $j < count($outsideLinerArr); $j++) {
				if (strtoupper($outsideLinerArr[$j]) == strtoupper($rowsdt[8])) {
					$color2 = '';
				} else {
					$color2 = 'red';
				}
			}


			$outsideLinerArr[$i] = $rowsdt[8];


			for ($j = 0; $j < count($fluteTypeArr); $j++) {
				if (strtoupper($fluteTypeArr[$j]) == strtoupper($fluteType)) {
					$color3 = '';
				} else {
					$color3 = 'red';
				}
			}

			if ($jobcard_flutetype != $fluteType_) {
				$color3 = 'red';
			}


			$fluteTypeArr[$i] = $fluteType;

			$getcorgrid = "Select corgrid_id from temp_corgrid where corrugatorid=" . $txtCorrugatorId . " and  jobcardid='$txtJobCardIdArr[$i]'";
			$getcorgridresult = mysqli_query($connection, $getcorgrid);
			$getcorgridrow = mysqli_fetch_array($getcorgridresult);
			$corgridid = $getcorgridrow[0] ?? '';


			$mastercardRef = $rowsdt[17];

			$Project_code = $Project_code ?? '';
			$Costcent_code = $Costcent_code ?? '';
			$Warehouse_code = $Warehouse_code ?? '';
			$Currency_Code = $Currency_Code ?? '';
			$Sorderdt_price = $Sorderdt_price ?? '';
			$Sorder_BelongsToGrp = $Sorder_BelongsToGrp ?? '';
			$related_salesorder = $related_salesorder ?? '';
			$ulstackerJs = ($rowsdt[40] !== '') ? $rowsdt[40] : 0;
			$corgridJs = ($corgridid !== '') ? $corgridid : "''";

			$lookupEvents = "onchange=\"SetModRecord('ModRec_G1','$i'); CheckLookup('chkcodeexists','items','item_code',this.value,'lstItemCode'); GetSelectedItem(this.id,'getitem','" . $i . "','" . $txtDate . "','" . $txtCurrCode . "','" . $txtClientCode . "'); checkduplicate('$i',this.value);\" onkeyup=\"AutoCompleteData(event,this.id,'itemcode','itemdescription','itemquality','items');\" " . $KeyDown;

			echo "
			<input type='hidden' name='takeUpFact[$i]' id='takeUpFact[$i]' value='$takeUpFact'>
			<input type='hidden' name='takeUpFactB[$i]' id='takeUpFactB[$i]' value='1.32'>
			<input type='hidden' name='takeUpFactC[$i]' id='takeUpFactC[$i]' value='1.46'>
			<input type='hidden' name='takeUpFactE[$i]' id='takeUpFactE[$i]' value='1.27'>
			<input type='hidden' name='projectcode[$i]' id='projectcode[$i]' value='$Project_code'>
			<input type='hidden' name='jobCard[$i]' id='jobCard[$i]' value='$txtJobCardIdArr[$i]'>
			<input type='hidden' name='costcenter[$i]' id='costcenter[$i]' value='$Costcent_code'>
			<input type='hidden' name='warehouse[$i]' id='warehouse[$i]' value='$Warehouse_code'>
			<input type='hidden' name='currency[$i]' id='currency[$i]' value='$Currency_Code'>
			<input type='hidden' name='Sorderdt_price[$i]' id='Sorderdt_price[$i]' value='$Sorderdt_price'>
			<input type='hidden' name='clientCode[$i]' id='clientCode[$i]' value='$txtClientCode'>
			<input type='hidden' name='Sorder_BelongsToGrp[$i]' id='Sorder_BelongsToGrp[$i]' value='$Sorder_BelongsToGrp'>
			<input type='hidden' name='related_salesorder[$i]' id='related_salesorder[$i]' value='$related_salesorder'>
			<input type='hidden' name='rowsNb[$i]' id='rowsNb[$i]' value='$rowsNb'>
			<input type='hidden' name='upperlowerid[$i]' id='upperlowerid[$i]' value='$rowsdt[40]'>";

			// Flute type dropdown
			$queryFlute = " Select FluteType_id, FluteType_code, FluteType_ply from FluteType order by FluteType_code";
			$resultFlute = mysqli_query($connection, $queryFlute);
			$fluteOptions = "<option></option>";
			while ($rowFlute = mysqli_fetch_array($resultFlute)) {
				$fluteOptions .= "<option value='$rowFlute[0]'" . cjrSelected($rowFlute[0], $rowsdt[15]) . ">$rowFlute[1]   &nbsp;&nbsp;  -   &nbsp;&nbsp;  $rowFlute[2] ply</option>";
			}

			// Scoring type dropdown
			$scoringOptions = "<option value='MF'" . cjrSelected('MF', $rowsdt[7]) . ">MF</option>"
				. "<option value='PF'" . cjrSelected('PF', $rowsdt[7]) . ">PF</option>"
				. "<option value='PP'" . cjrSelected('PP', $rowsdt[7]) . ">PP</option>"
				. "<option value='NA'" . cjrSelected('NA', $rowsdt[7]) . ">N/A</option>";

			// Box type (read-only display)
			$queryBoxType = " Select BoxType_id, BoxType_code from BoxType where BoxType_id='$rowsdt[5]' order by orderby asc";
			$resultBoxType = mysqli_query($connection, $queryBoxType);
			$rowBox = mysqli_fetch_array($resultBoxType);
			$boxTypeCode = $rowBox[1] ?? '';

			$GdQty = $rowsdt[21];
			$queryGdQty = " select sum(ifNull(goodqty,0)) " .
				" from corrudt " .
				" left join sorder  on sorder.Sorder_id=corrudt.related_sorderId  " .
				" left join jobcard on sorder.Sorder_id=jobcard.related_salesorder  " .
				" where jobcard.jobcard_Id='" . $txtJobCardIdArr[$i] . "'  " .
				" group by sorder.Sorder_id  ";
			//echo$queryGdQty;
			$resultGdQty = mysqli_query($connection, $queryGdQty);
			$rowsGdQty = mysqli_fetch_array($resultGdQty);

			if ($GdQty == 0) {
				$GdQty = $rowsGdQty[0] ?? 0;
			}

			$lblFluteClass = ($color3 == 'red') ? 'cjr-field-mismatch' : '';
			$lblScoringClass = ($color == 'red') ? 'cjr-field-mismatch' : '';
			$lblOutsideClass = ($color2 == 'red') ? 'cjr-field-mismatch' : '';

			echo "<div class='cjr-jobcard-block'>";
			echo "<div class='cjr-jobcard-header'>
				<span class='style1 cjr-jobcard-title'>JobCard# " . htmlspecialchars($rowsdt[19], ENT_QUOTES) . "</span>
				<button type='button' class='btn-top btn-top-danger' onclick=\"DeleteRecordUpperLower('" . $txtCorrugatorId . "','" . $i . "','" . $txtJobCardIdArr[$i] . "','" . $ulstacker_id . "');return false;\" title='Delete Record'><i class='fa fa-trash'></i><span>Delete</span></button>
			</div>";

			// Row 1
			echo "<div class='row no-gutters render-row'>";
			echo cjrField("SOF#", "<input class='$inputClass' type='text' name='txtSOF[$i]' id='txtSOF[$i]' value='" . $rowsdt[18] . "' readonly maxlength='20' $lookupEvents>");
			echo cjrField("Customer" . $orderimg2, "<input class='$inputClass' type='text' name='txtClient[$i]' id='txtClient[$i]' value='" . $rowsdt[14] . "' readonly maxlength='20' $lookupEvents>");
			echo cjrField("Item Description" . $orderimg2, "<input class='$inputClass' type='text' name='txtItemDesc[$i]' id='txtItemDesc[$i]' value='" . $rowsdt[6] . "' readonly maxlength='20' $lookupEvents>");
			echo cjrField("Qty Request", "<input class='$inputClass' type='text' id='txtQtyRequest[$i]' name='txtQtyRequest[$i]' value='$rowsdt[12]' readonly />");
			echo "</div>";

			// Row 2
			echo "<div class='row no-gutters render-row'>";
			echo cjrField("MasterCard#", "<input class='$inputClass' type='text' name='txtMasterCardRef[$i]' id='txtMasterCardRef[$i]' value='" . $mastercardRef . "' readonly maxlength='20' $lookupEvents>");
			echo cjrField("Flute Type" . $orderimg2, "<select class='$selectClass' id='txtFluteType[$i]' readonly name='txtFluteType[$i]' onchange='RefreshPage($i)'>$fluteOptions</select>", 'col-md-3', $lblFluteClass);
			echo cjrField("Scoring Type", "<select class='$selectClass' name='txtScoringType[$i]' id='txtScoringType[$i]' style='color:$color1'>$scoringOptions</select>", 'col-md-3', $lblScoringClass);
			echo cjrField("BoardNeed" . $orderimg2, "<input class='$inputClass' type='text' id='txtBoardNeed[$i]' name='txtBoardNeed[$i]' value='$rowsdt[41]' readonly />");
			echo "</div>";

			// Row 3
			echo "<div class='row no-gutters render-row'>";
			echo cjrField("JobCard#", "<input class='$inputClass' type='text' name='txtJobCardRef[$i]' id='txtJobCardRef[$i]' value='" . $rowsdt[19] . "' readonly maxlength='20' $lookupEvents>");
			echo cjrField("Inside Liner" . $orderimg2, "<input class='$inputClass' type='text' id='txtInsideLiner[$i]' name='txtInsideLiner[$i]' value='" . ucfirst($rowsdt[9]) . "' readonly />");
			echo "<input type='hidden' id='txtFlutting2[$i]' name='txtFlutting2[$i]' value='$rowsdt[10]' readonly />";
			echo cjrField("Outside Liner" . $orderimg3, "<input class='$inputClass' type='text' name='txtOutsideLiner[$i]' id='txtOutsideLiner[$i]' value='" . ucfirst($rowsdt[8]) . "' readonly maxlength='20' $lookupEvents>", 'col-md-3', $lblOutsideClass);
			echo cjrField("Planned Qty" . $orderimg3, "<input class='$inputClass' type='number' id='txtPlannedQty[$i]' name='txtPlannedQty[$i]' onchange='cuts($i);remainingQty($i);cuts(1);UReq($i);SubmitCorGrid($i,$corgridJs,1,0,50,$txtCorrugatorId, $ulstackerJs)' value='$rowsdt[20]' />");
			echo "</div>";

			// Row 4
			echo "<div class='row no-gutters render-row'>";
			echo cjrField("Box Type" . $orderimg3, "<input class='$inputClass' type='text' name='txtBoxType[$i]' id='txtBoxType[$i]' value='$boxTypeCode' readonly>"
				. "<input type='hidden' name='txtBoxTypeId[$i]' id='txtBoxTypeId[$i]' value='$rowsdt[5]' readonly>");
			echo cjrField("STD GSM" . $orderimg3, "<input class='$inputClass' type='text' name='txtStdGsm[$i]' id='txtStdGsm[$i]' value='" . $rowsdt[11] . "' readonly maxlength='20' $lookupEvents>");
			echo "<div class='col-md-3 px-2 field-col'></div>";
			echo cjrField("Produced Qty" . $orderimg2, "<input class='$inputClass' type='text' id='txtProducedQty[$i]' readonly name='txtProducedQty[$i]' value='$GdQty' />");
			echo "</div>";

			// Row 5
			echo "<div class='row no-gutters render-row'>";
			echo cjrField("Box Size ED" . $orderimg3, "<div class='cjr-multi-input'>
					<input class='$inputClass' type='number' id='txtExtLen[$i]' value='$rowsdt[27]' readonly>
					<input class='$inputClass' type='number' id='txtExtWid[$i]' value='$rowsdt[28]' readonly>
					<input class='$inputClass' type='number' id='txtExtHei[$i]' value='$rowsdt[29]' readonly>
				</div>");
			$stdPaper = "<div class='cjr-multi-input'>";
			for ($p = 1; $p <= 10; $p++) {
				$stdPaper .= "<input class='$inputClass' type='text' id='stdpaper$p' readonly value='" . $rowsdt[29 + $p] . "' >";
			}
			$stdPaper .= "</div>";
			echo cjrField("STD.PAPER" . $orderimg3, $stdPaper, 'col-md-6');
			echo cjrField("CJR Qty" . $orderimg2, "<input class='$inputClass' type='text' id='txtCJRQty[$i]' readonly name='txtCJRQty[$i]' value='$cjrQty' />"
				. "<input type='hidden' id='txtCJRQtyy_$i' readonly name='txtCJRQty1[$i]' value='$cjrQty1' />");
			echo "</div>";

			// Row 6
			echo "<div class='row no-gutters render-row'>";
			echo "<div class='col-md-9 px-2 field-col'></div>";
			echo cjrField("Remaining Qty" . $orderimg2, "<input class='$inputClass' type='text' id='txtRemainingQty[$i]' name='txtRemainingQty[$i]' readonly value='$rowsdt[22]' />");
			echo "</div>";

			echo "</div>"; // .cjr-jobcard-block
		}

		$k = 2;


		$mainaction = 'edit';
		// $mainmode = 'edit';
		$txtLinkedMcRef = explode(" ,", $txtLinkedMcRef ?? '');
	}

	echo "<input type='hidden' name='index' id='index' value='$i'>";


	/* ===================== EMPTY JOB CARD SLOT (waiting for "Load JobCard") ===================== */
	if ($jobcardid == '' || $jobcardid2 == '') {
		$i = $i - 1;
		$txtPlannedQty = $txtPlannedQty ?? '';
		$txtProducedQty = $txtProducedQty ?? '';
		$txtRemainingQty = $txtRemainingQty ?? '';
		$corgridJs = ($corgridid !== '') ? $corgridid : "''";
		$emptyFlute = $rowsdt[15] ?? '';
		$emptyScoring = $rowsdt[7] ?? '';

		$lookupEvents = "onchange=\"SetModRecord('ModRec_G1','$i'); CheckLookup('chkcodeexists','items','item_code',this.value,'lstItemCode'); GetSelectedItem(this.id,'getitem','" . $i . "','" . $txtDate . "','" . $txtCurrCode . "','" . $txtClientCode . "'); checkduplicate('$i',this.value);\" onkeyup=\"AutoCompleteData(event,this.id,'itemcode','itemdescription','itemquality','items');\" " . $KeyDown;

		$queryFlute = " Select FluteType_id, FluteType_code,FluteType_ply  from FluteType order by FluteType_code";
		$resultFlute = mysqli_query($connection, $queryFlute);
		$fluteOptions = "<option></option>";
		while ($rowFlute = mysqli_fetch_array($resultFlute)) {
			$fluteOptions .= "<option value='$rowFlute[0]'" . cjrSelected($rowFlute[0], $emptyFlute) . ">$rowFlute[1]   &nbsp;&nbsp;  -   &nbsp;&nbsp;  $rowFlute[2] ply</option>";
		}

		$scoringOptions = "<option value='MF'" . cjrSelected('MF', $emptyScoring) . ">MF</option>"
			. "<option value='PF'" . cjrSelected('PF', $emptyScoring) . ">PF</option>"
			. "<option value='PP'" . cjrSelected('PP', $emptyScoring) . ">PP</option>"
			. "<option value='NA'" . cjrSelected('NA', $emptyScoring) . ">N/A</option>";

		echo "<div class='cjr-jobcard-block'>";

		// Row 1
		echo "<div class='row no-gutters render-row'>";
		echo cjrField("SOF#", "<input class='$inputClass' type='text' name='txtSOF[$i]' id='txtSOF[$i]' value='' readonly maxlength='20' $lookupEvents>");
		echo cjrField("Customer" . $orderimg2, "<input class='$inputClass' type='text' name='txtClient[$i]' id='txtClient[$i]' value='' readonly maxlength='20' $lookupEvents>");
		echo cjrField("Item Description" . $orderimg2, "<input class='$inputClass' type='text' name='txtItemDesc[$i]' id='txtItemDesc[$i]' value='' readonly maxlength='20' $lookupEvents>");
		echo cjrField("Qty Request", "<input class='$inputClass' type='text' id='txtQtyRequest[$i]' name='txtQtyRequest[$i]' value='' readonly />");
		echo "</div>";

		// Row 2
		echo "<div class='row no-gutters render-row'>";
		echo cjrField("MasterCard#", "<input class='$inputClass' type='text' name='txtMasterCardRef[$i]' id='txtMasterCardRef[$i]' value='' readonly maxlength='20' $lookupEvents>");
		echo cjrField("Flute Type" . $orderimg2, "<select class='$selectClass' id='txtFluteType[$i]' readonly name='txtFluteType[$i]' onchange='RefreshPage($i)'>$fluteOptions</select>");
		echo cjrField("Scoring Type", "<select class='$selectClass' name='txtScoringType[$i]' id='txtScoringType[$i]'>$scoringOptions</select>");
		echo cjrField("BoardNeed" . $orderimg2, "<input class='$inputClass' type='text' id='txtBoardNeed[$i]' name='txtBoardNeed[$i]' value='' readonly />");
		echo "</div>";

		// Row 3
		echo "<div class='row no-gutters render-row'>";
		echo cjrField("JobCard#", "<input class='$inputClass' type='text' name='txtJobCardRef[$i]' id='txtJobCardRef[$i]' value='' readonly maxlength='20' $lookupEvents>");
		echo cjrField("Inside Liner" . $orderimg2, "<input class='$inputClass' type='text' id='txtInsideLiner[$i]' name='txtInsideLiner[$i]' value='' readonly />");
		echo "<input type='hidden' id='txtFlutting2[$i]' name='txtFlutting2[$i]' value='' readonly />";
		echo cjrField("Outside Liner" . $orderimg3, "<input class='$inputClass' type='text' name='txtOutsideLiner[$i]' id='txtOutsideLiner[$i]' value='' readonly maxlength='20' $lookupEvents>");
		echo cjrField("Planned Qty" . $orderimg3, "<input class='$inputClass' type='number' id='txtPlannedQty[$i]' name='txtPlannedQty[$i]' value='$txtPlannedQty' onchange='cuts($i);remainingQty($i);SubmitCorGrid($i,$corgridJs,1,0,50,$txtCorrugatorId,0)' />");
		echo "</div>";

		// Row 4
		echo "<div class='row no-gutters render-row'>";
		echo cjrField("Box Type" . $orderimg3, "<input class='$inputClass' type='text' name='txtBoxType[$i]' id='txtBoxType[$i]' readonly>");
		echo cjrField("STD GSM" . $orderimg3, "<input class='$inputClass' type='text' name='txtStdGsm[$i]' id='txtStdGsm[$i]' value='' readonly maxlength='20' $lookupEvents>");
		echo "<div class='col-md-3 px-2 field-col'></div>";
		echo cjrField("Produced Qty" . $orderimg2, "<input class='$inputClass' type='number' id='txtProducedQty[$i]' name='txtProducedQty[$i]' value='$txtProducedQty' readonly />");
		echo "</div>";

		// Row 5
		echo "<div class='row no-gutters render-row'>";
		echo cjrField("Box Size ED" . $orderimg3, "", 'col-md-9');
		echo cjrField("CJR Qty" . $orderimg2, "<input class='$inputClass' type='text' id='txtCJRQty[$i]' readonly name='txtCJRQty[$i]' value='$cjrQty' />");
		echo "</div>";

		// Row 6
		echo "<div class='row no-gutters render-row'>";
		echo "<div class='col-md-9 px-2 field-col'></div>";
		echo cjrField("Remaining Qty" . $orderimg2, "<input class='$inputClass' type='number' id='txtRemainingQty[$i]' name='txtRemainingQty[$i]' value='$txtRemainingQty' readonly />");
		echo "</div>";

		echo "</div>"; // .cjr-jobcard-block
	}

	echo "</div></div>";


	/* ===================== CORRUGATOR GRID ===================== */
	echo "<div class='row no-gutters'>
		<div class='col-md-12'>";
	echo "	<div id='datagrid' class='grid'>";

	$arrayparams = array();
	$arrayparams['mainmode'] = $mainmode;
	$arrayparams['rowsNb'] = $rowsNb;
	$arrayparams['SubItem_Production'] = $SubItem_Production;
	$arrayparams['txtJobCardId'] = $txtJobCardIdArr;
	$arrayparams['txtGSM'] = $txtGSM;
	$arrayparams['txtPaperGrade'] = $txtPaperGrade;
	$arrayparams['txtRellDeckle'] = $txtRellDeckle;
	$arrayparams['txtPaperMill'] = $txtPaperMill;

	$arrayparams['PIndex'] = $PIndex;

	$_SESSION['gso_parameters'] = $arrayparams;
	ShowCJRCorGrid($connection, 'SO', $txtCorrugatorId, $_SESSION['gso_parameters'], $_SESSION['gso_arrayaccess'] ?? array(), 'SorderDt.SorderDt_ID');

	echo "	  </div>";
	echo "   </div>";
	echo "</div>";


	/* ===================== LOCATION GRID ===================== */
	echo "<div class='topbar' id='gridToolbar'>";
	echo "  <div class='topbar-title'><div class='icon-wrap'><i class='fa fa-location-dot'></i></div><span>Location</span></div>";
	echo "</div>";

	echo "<div class='row no-gutters'>
		<div class='col-md-12'>";
	echo "	<div id='datagridLocation' class='grid'>";

	//Show Items Grid
	ShowCJRLGrid($connection, 'SO', $txtCorrugatorId, $_SESSION['gso_parameters'], $_SESSION['gso_arrayaccess'] ?? array(), 'SorderDt.SorderDt_ID');

	echo "	  </div>";
	echo "   </div>";
	echo "</div>";

	echo "</div></div>"; // .container-fluid / .form-rtl-page

	// var_dump($_POST);

	$PrPorId = $PrPorId ?? '';
	if (($actionmode == "post" || $actionmode == "confirmorder") && $PrPorId != '') {

		$query = " SELECT jobcard_reference, ifNull(Porder_Confirmed,0) " .
			" FROM jobcard" .
			" WHERE jobcard_id = $PrPorId ";
		$result = selectData($connection, $query);
		$PrPorRef = $result[0];
		$PorConfirmed = $result[1];

		$AskComfirm = 0;

		echo '<script type="text/javascript" language="javascript">';


		if (($AskComfirm == 1 && $actionmode == "confirmorder") || $AskComfirm == 0) {
			if (($Login_FastPrint ?? 0) == 1)
				echo ' PrintPurchaseOrder(\'' . $PrPorId . '\', \'' . $PrPorRef . '\'); ';

			echo ' ClearPage1(); ';
		}

		echo '</script>';
	}

	if ($mode == 'post') {
		echo '
	<script>
	PostChanges();

	</script>';
	}


	print <<<HERE
</form>

<script>
flatpickr(".mydate", {
	dateFormat: "d/m/Y",
	disableMobile: "true",
	animate: true,
	onReady: function(selectedDates, dateStr, instance) {
		const footer = document.createElement("div");
		footer.style.padding = "5px";
		footer.style.display = "flex";
		footer.style.justifyContent = "space-between";
		footer.style.borderTop = "1px solid #eee";
		const todayBtn = document.createElement("button");
		todayBtn.type = "button";
		todayBtn.innerHTML = "Today";
		todayBtn.style.cursor = "pointer";
		todayBtn.onclick = () => instance.setDate(new Date());
		const clearBtn = document.createElement("button");
		clearBtn.type = "button";
		clearBtn.innerHTML = "Clear";
		clearBtn.style.cursor = "pointer";
		clearBtn.onclick = () => instance.clear();
		footer.appendChild(todayBtn);
		footer.appendChild(clearBtn);
		instance.calendarContainer.appendChild(footer);
	}
});
</script>
</body>

HERE;
