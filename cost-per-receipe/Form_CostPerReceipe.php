<?php


session_start();
if($_SESSION['dbName']==""){
header("location:../index.php");
}

include("config.inc.php");
   
$connection = mysql_connect($config['dbServer'],$config['dbUser'],$config['dbPass']) or die("Could not connect to DB");
mysql_select_db($_SESSION['dbName'],$connection) or die("Could not find DB");


$querylog="SELECT RI_ListofProduction as loginaccess, print as printaccess, ExporttoExcel from ".$_SESSION['dbLabel'].".login where user_account='".$_SESSION['useraccount']."' 	and Upper(Login_Schema) = Upper('".$_SESSION['dbName']."')	";
$resultlog=mysql_query($querylog,$connection) or die(mysql_error());
while($rows=mysql_fetch_array($resultlog)){
	extract($rows);
}

if ($loginaccess=='' or $loginaccess==0)
	header("location:menu.php");

if ($ExporttoExcel==1)
	$ExporttoExcelStatus= 'button';
else
	$ExporttoExcelStatus= 'hidden';	
	


print <<< HERE
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

HERE;

//Read From Module Table
$querymodule=" Select ifNull(Module_Project,0) As Module_Project, ifNull(Module_Costcenter,0) As Module_CostCenter, ifNull(module_ReferenceByCostcenter,0) As module_ReferenceByCostcenter, ".
			 " ifNull(Module_DoubleUnit,0) As Module_DoubleUnit, ifnull(Module_NewProduction,0) as Module_NewProduction, ".
			 " ifNull(Module_Heatno,0) As Module_Heatno, IfNull(module_expiredate,0) As module_expiredate  ".
             " From Module";
$resultmodule=mysql_query($querymodule,$connection) or die(mysql_error());
while($rowmodule=mysql_fetch_array($resultmodule)){
	extract($rowmodule);
}
$query="select ifnull(ShowReportHeader,0) as ShowReportHeader from ".$_SESSION['dbLabel'].".loginstk where user_account='".$_SESSION['useraccount']."'  and Upper(loginstk.company_name) = Upper('".$_SESSION['dbName']."') ";
$result=mysql_query($query,$connection) or die(mysql_error());
while($rows=mysql_fetch_array($result)){
	extract($rows);
}
if($ShowReportHeader=='')
	$ShowReportHeader=1;

$querylog=" select ifNull(FI_RepRestrictProject,0) As FI_RepRestrictProject, ifNull(FI_RepRestrictCostcenter,0) As FI_RepRestrictCostcenter, ".
		  " ifNull(FI_RepRestrictWarehouse,0) As FI_RepRestrictWarehouse, ifNull(FI_ShowCostPrice,0) As FI_ShowCostPrice  ".
          " from ".$_SESSION['dbLabel'].".login where user_account='".$_SESSION['useraccount']."' and Upper(Login_Schema) = Upper('".$_SESSION['dbName']."') ";
$resultlog=mysql_query($querylog) or die(mysql_error());
while($rows=mysql_fetch_array($resultlog)){
	extract($rows);
}

$querysetup ="select base1, base2, if(DefaultBase=1, base1, base2) as defaultcurr from setup ";
$resultsetup=mysql_query($querysetup,$connection) or die(mysql_error());
$rowsetup=mysql_fetch_row($resultsetup);
$defaultcurr = $rowsetup[2];

//Projects List
$queryproject=" select distinct project.project_code, project_name ".
              " from project left join ".$_SESSION['dbLabel'].".loginstk on project.project_code like concat(ifnull(loginstk.project_code,project.project_code),'%') ".
			  " where user_account='".$_SESSION['useraccount']."'  and Upper(loginstk.company_name) = Upper('".$_SESSION['dbName']."') ".
			  " and (loginstk.project_code is not null and loginstk.project_code != '') ".
			  " order by project.project_code";
$projectresult = mysql_query($queryproject,$connection) or die(mysql_error());
$projectexist  = mysql_num_rows($projectresult);	
if ($projectexist == 0 || $FI_RepRestrictProject == 0)
    $queryproject=" select distinct project_code, project_name from project order by project_code ";	  

$resultfromproject=mysql_query($queryproject,$connection) or die(mysql_error());
$resulttoproject  =mysql_query($queryproject,$connection) or die(mysql_error());


//Costcenter List
$querycostcent=" select distinct costcent.costcent_code, costcent_name ".
               " from costcent left join ".$_SESSION['dbLabel'].".loginstk on costcent.costcent_code like concat(ifnull(loginstk.costcent_code,costcent.costcent_code),'%') ".
			   " where user_account='".$_SESSION['useraccount']."'  and Upper(loginstk.company_name) = Upper('".$_SESSION['dbName']."') ".
			   " and (loginstk.costcent_code is not null and loginstk.costcent_code != '') ".
			   " order by costcent.costcent_code";
$costcentresult = mysql_query($querycostcent,$connection) or die(mysql_error());
$costcentexist  = mysql_num_rows($costcentresult);		  
if ($costcentexist == 0 || $FI_RepRestrictCostcenter == 0)
    $querycostcent=" select distinct costcent_code, costcent_name from costcent order by costcent_code ";			  
	
$resultfromcostcent=mysql_query($querycostcent,$connection) or die(mysql_error());
$resulttocostcent  =mysql_query($querycostcent,$connection) or die(mysql_error());


//Get Default Project
$querydflproject="select project_code as defaultproject from ".$_SESSION['dbLabel'].".loginstk where user_account='".$_SESSION['useraccount']."' and default_project=1  and Upper(loginstk.company_name) = Upper('".$_SESSION['dbName']."') ";
$resultdflproject=mysql_query($querydflproject,$connection) or die(mysql_error());
while($rows=mysql_fetch_array($resultdflproject)){
	extract($rows);
}
	
//Get Default Coscenter
$querydflcostcent="select costcent_code as defaultcostcent from ".$_SESSION['dbLabel'].".loginstk where user_account='".$_SESSION['useraccount']."' and default_costcenter=1  and Upper(loginstk.company_name) = Upper('".$_SESSION['dbName']."') ";
$resultdflcostcent=mysql_query($querydflcostcent,$connection) or die(mysql_error());
while($rows=mysql_fetch_array($resultdflcostcent)){
	extract($rows);
}



//Warehouse List
$querywarehouse=" select distinct warehouse.warehouse_code, warehouse_name ".
				" from warehouse where warehouse_code in (select warehouse_code from ".$_SESSION['dbLabel'].".loginstk ".
				"                                         where user_account='".$_SESSION['useraccount']."' ".
				"                                         and (loginstk.warehouse_code is not null and loginstk.warehouse_code != '')  and Upper(loginstk.company_name) = Upper('".$_SESSION['dbName']."') ) ".
				" order by warehouse.warehouse_code";
$resultwh = mysql_query($querywarehouse,$connection) or die(mysql_error());
$whexist   = mysql_num_rows($resultwh);
if ($whexist == 0 || $FI_RepRestrictWarehouse == 0)
	$querywarehouse="select warehouse_code,warehouse_name from warehouse order by warehouse_code";
	
$resultfromwarehouse=mysql_query($querywarehouse,$connection) or die(mysql_error());
//$resulttowarehouse  =mysql_query($querywarehouse,$connection) or die(mysql_error());


if ((!isset($fromproject)) && $Module_Project == 1 && $FI_RepRestrictProject == 1)
	$fromproject = $defaultproject;
if ((!isset($toproject)) && $Module_Project == 1 && $FI_RepRestrictProject == 1)
	$toproject = $defaultproject;
if ((!isset($fromcostcent)) && $Module_CostCenter == 1 && $FI_RepRestrictCostcenter == 1)	
	$fromcostcent = $defaultcostcent;	
if ((!isset($tocostcent)) && $Module_CostCenter == 1 && $FI_RepRestrictCostcenter == 1)	
	$tocostcent = $defaultcostcent;	

if (empty($fromdate)) $fromdate = displayToday();
if (empty($todate))   $todate   = displayToday();

if (empty($fromedate)) $fromedate = displayToday();
if (empty($toedate))   $toedate   = displayToday();
	
$ShowCostMode  = '';
$ShowCostCheck = 'checked';
if($FI_ShowCostPrice == 0)  {
	$ShowCostMode  = 'disabled';	
	$ShowCostCheck = 'unchecked';	
}	
	
print <<< HERE

<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=windows-1256" />
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"> 
<meta name="viewport" content="width=device-width, initial-scale=1.0"> 
<link rel="stylesheet" type="text/css" media="all" href="css-v2/style.css">

<script type="text/javascript" src="jquery-1.9.1.js"></script>
<script language="JavaScript" src="CalendarPopup.js" ></script><!-- Date only with year scrolling -->
<script type="text/javascript" language="javascript">
<!--


all = 0;  // 0 means 'no', 1 means 'yes'

function showlistheatno(str){

	window.open('Select_HeatNo.php?heatno='+str,'mywindow','width=900,height=550,toolbar=no,location=no,directories=no,status=yes,menubar=no,scrollbars=yes,copyhistory=no,resizable=yes');
}

function showlistScheduleMixer(str){
    window.open('List_ScheduleMixer.php?frm=9&item='+str+'&PScmixer_reference=&formType=ClosingJC','mywindow','width=850,height=550,toolbar=no,location=no,directories=no,status=yes,menubar=no,scrollbars=yes,copyhistory=no,resizable=yes');

}
function Clear(){
	document.form1.reset();
	document.getElementById('stockItem').value = '';
	document.getElementById('stockItemName').value = '';
	document.getElementById('JobcardRef').value = '';
	document.getElementById('Jobcard').value = '';
	document.getElementById('showdetail').checked = false;
}

document.addEventListener('DOMContentLoaded', function() {
    var optionsSelect = document.getElementById('options');
    if (!optionsSelect) return;
    var selectedOptionsDiv = document.getElementById('selectedOptions');
    var selectedop = '';
    
    optionsSelect.addEventListener('change', function() {
        var selectedOptions = optionsSelect.selectedOptions;
        //selectedOptionsDiv.innerHTML = ''; 
        
        for (var i = 0; i < selectedOptions.length; i++) {
            var option = selectedOptions[i];
            if(selectedop == '') selectedop = option.value;
            else selectedop = selectedop+','+option.value;
          
            var select = document.createElement('select');
            select.id = 'value_' + option.value;
            
          
            var xhr = new XMLHttpRequest();
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
					console.log(xhr.responseText);
                    var options = JSON.parse(xhr.responseText);
					var blankOption = document.createElement('option');
					blankOption.value = '';
					select.appendChild(blankOption);
					blankOption.textContent = 'Select value';
                    for (var j = 0; j < options.length; j++) {
                        var option = document.createElement('option');
                        option.value = options[j].value;
                        option.textContent = options[j].value;
                        select.appendChild(option);
                    }
                }
            };
            xhr.open('GET', 'get_options.php?selected_option=' + option.value, true);
            xhr.send();
            
            // Create label element
            var label = document.createElement('label');
            label.textContent = option.textContent;
            label.style.marginRight = '6px'; // Add margin between label and select
            
            // Append label and select to selectedOptionsDiv
            selectedOptionsDiv.appendChild(label);
            selectedOptionsDiv.appendChild(select);
            selectedOptionsDiv.appendChild(document.createElement('br'));
            selectedOptionsDiv.appendChild(document.createElement('br'));
        }
        document.getElementById('attributes').value = selectedop;
    });
});

function printPrev(printaccess, exp)
{
	width = screen.width - 10;
	height = screen.height - 66;
	var str='';
		
	for (var i=0; i < document.form1.reports.length; i++)
	{
		if (document.form1.reports[i].checked)
		{
			var varreports = document.form1.reports[i].value;
		} 
	}

	// for (var i=0; i < document.form1.groupby.length; i++)
	// {
	// 	if (document.form1.groupby[i].checked)
	// 	{
	// 		var groupby = document.form1.groupby[i].value;
	// 	}
	// }

	// varshowdetail	= 0;
	// if (document.form1.showdetail.checked )
	// 	varshowdetail = 1;
	
	var qtyType = '';
	for (var i=0; i < document.form1.qtyType.length; i++)
	{
		if (document.form1.qtyType[i].checked)
		{
			qtyType = document.form1.qtyType[i].value;
		}
	}

	// var groupbyitem = 0;
	// 	if (document.form1.groupbyitem.checked )
	// 	groupbyitem = 1;
		
	
	varfromdate   = document.form1.fromdate.value;
	vartodate     = document.form1.todate.value;
	var ItemCode = document.getElementById("ItemCode").value;
	
	var prefix = (exp == 1) ? 'Exp_' : 'Rep_';


	str =  prefix+'CostPerReceipe.php?fromdate='+varfromdate+'&todate='+vartodate
		 +"&ItemCode="+encodeURIComponent(ItemCode)+'&qtyType='+qtyType+'&reports='+varreports;		      
	if (printaccess==1 || exp == 1)
		window.open(str);
	else
		window.open(str,"Report", "width=1000,height=600,screenX=0,left=50,screenY=0,top=0,status=no,menubar=no,scrollbars=yes,resizable=yes");
		
		
}

function exportExcel()
{
	width = screen.width - 10;
	height = screen.height - 66;
	var str='';
		
	for (var i=0; i < document.form1.reports.length; i++)
	{
		if (document.form1.reports[i].checked)
		{
			var varreports = document.form1.reports[i].value;
		}
	}	 
	
	for (var i=0; i < document.form1.listby.length; i++)
	{
		if (document.form1.listby[i].checked)
		{
			var varlistby = document.form1.listby[i].value;
		}
	}
	  
	for (var i=0; i < document.form1.currency.length; i++)
	{
		if (document.form1.currency[i].checked)
		{
			var varcurrency = document.form1.currency[i].value;
		}
	}
	
	for (var i=0; i < document.form1.status.length; i++)
	{
		if (document.form1.status[i].checked)
		{
			var varstatus = document.form1.status[i].value;
		}
	} 
		 
	for (var i=0; i < document.form1.orderBy.length; i++)
	{
		if (document.form1.orderBy[i].checked)
		{
			var varOrder = document.form1.orderBy[i].value;
		}
	}
	
	varshowdetail	= 0;
	if (document.form1.showdetail.checked )
		varshowdetail = 1;
		
	varshowcost	= 0;
	if (document.form1.showcost.checked )
		varshowcost = 1;
	
	varshowlastprice = 0;
	if(document.form1.showpurprice.checked)
	varshowlastprice = 1;	
	
	varshowidealqty	= 0;
	if (document.form1.showidealqty.checked )
		varshowidealqty = 1;
		
	varshowqtyexists = 0;
	if (document.form1.showqtyexists.checked )
		varshowqtyexists = 1;
		
	varshowunit2qty = 0;
	if (document.getElementById('showunit2qty')) {
		if (document.form1.showunit2qty.checked)
			varshowunit2qty = 1;	
	}
	
	varprtheader = 0;
	if (document.form1.prtheader.checked)    varprtheader = 1;
	
	varfromdate   = document.form1.fromdate.value;
	vartodate     = document.form1.todate.value;
	varfromitem   = document.form1.fromitem.value;
	vartoitem     = document.form1.toitem.value;
	varfromwrh    = document.form1.fromwarcode.value;
	varfromprj    = document.form1.fromprojcode.value;
	vartoprj      = document.form1.toprojcode.value;
	varfromcst    = document.form1.fromcostcode.value;
	vartocst      = document.form1.tocostcode.value;
	varfromitemRM = document.form1.fromitemRM.value;
	vartoitemRM   = document.form1.toitemRM.value;
	varqtyprod	  = document.form1.qtyprod.value;
	 
	varfromgroup 	  = document.form1.fromgroup_code.value;
	vartogroup 	  	  = document.form1.togroup_code.value;
	varfromsubgroup   = document.form1.fromsubgroup_code.value;
	vartosubgroup     = document.form1.tosubgroup_code.value;	
	varfromcategory   = document.form1.fromcategory.value;
	vartocategory     = document.form1.tocategory.value;
	
	var fromheatno = '';
	if(document.form1.fromheatno)
		fromheatno = document.form1.fromheatno.value;
	var toheatno = '';
	if(document.form1.toheatno)
		toheatno = document.form1.toheatno.value;
	
	 if(document.getElementById("edatechk")){
		if(document.getElementById("edatechk").checked){
			varedatechk = 1;
			varfromedate   = document.form1.fromedate.value;
			vartoedate     = document.form1.toedate.value;
			if(document.getElementById("grpedate").checked)
				vargrpedate = 1;
			else vargrpedate = 0;
		}else {
			varfromedate='';
			vartoedate='';
			varedatechk = 0;
			vargrpedate = 0;
		}
	}else{
		varfromedate='';
		vartoedate='';
		varedatechk = 0;
		vargrpedate = 0;
	}
	
	var	vHtn = 0;
	if (document.form1.Htn)
	if (document.form1.Htn.checked) vHtn = 1;
	
	var dtlWh = 0;
	if(document.getElementById('prtdtlWh').checked)
		dtlWh = 1;
	varzerocost = 0;
	if (document.form1.zerocost.checked)    varzerocost = 1;
	 
	str1 =  'Exp_ListofProduction.php?fromdate='+varfromdate+'&todate='+vartodate+'&fromitem='+varfromitem
		   +'&toitem='+vartoitem+'&fromwarehouse='+varfromwrh+'&fromproject='+varfromprj+'&toproject='+vartoprj
		   +'&fromcostcent='+varfromcst+'&tocostcent='+vartocst+'&curr='+varcurrency+'&showdetail='+varshowdetail
		   +'&showcost='+varshowcost+'&fromitemRM='+varfromitemRM+'&toitemRM='+vartoitemRM+'&showunit2qty='+varshowunit2qty
		   +'&vShowHeatno='+vHtn+'&prtheader='+varprtheader+'&fromgroup='+varfromgroup+'&togroup='+vartogroup
		   +'&fromsubgroup='+varfromsubgroup+'&tosubgroup='+vartosubgroup+'&fromcategory='+varfromcategory
		   +'&tocategory='+vartocategory+'&fromedate='+varfromedate+'&toedate='+vartoedate+'&edatechk='+varedatechk
		   +'&grpedate='+vargrpedate+'&showlastprice='+varshowlastprice+'&fromheatno='+fromheatno+'&toheatno='+toheatno+'&orderBy='+varOrder
		   +'&varzerocost='+zerocost;
		   
	str2 =  'Exp_ProductionReport.php?fromdate='+varfromdate+'&todate='+vartodate+'&fromitem='+varfromitem
		   +'&toitem='+vartoitem+'&fromwarehouse='+varfromwrh+'&fromproject='+varfromprj+'&toproject='+vartoprj
		   +'&fromcostcent='+varfromcst+'&tocostcent='+vartocst+'&curr='+varcurrency+'&showdetail='+varshowdetail
		   +'&showcost='+varshowcost+'&showidealqty='+varshowidealqty+'&listby='+varlistby+'&fromitemRM='+varfromitemRM
		   +'&toitemRM='+vartoitemRM+'&showunit2qty='+varshowunit2qty+'&showlastprice='+varshowlastprice
		   +'&jobcard_id='+document.form1.Jobcard.value;
		   
	str22 =  'Exp_ProductionReport2.php?fromdate='+varfromdate+'&todate='+vartodate+'&fromitem='+varfromitem
		   +'&toitem='+vartoitem+'&fromwarehouse='+varfromwrh+'&fromproject='+varfromprj+'&toproject='+vartoprj
		   +'&fromcostcent='+varfromcst+'&tocostcent='+vartocst+'&curr='+varcurrency+'&showdetail='+varshowdetail
		   +'&showcost='+varshowcost+'&showidealqty='+varshowidealqty+'&listby='+varlistby+'&fromitemRM='+varfromitemRM
		   +'&toitemRM='+vartoitemRM+'&showunit2qty='+varshowunit2qty+'&showlastprice='+varshowlastprice;	   
		   
	str3 =  'Exp_ProductionReportCosting.php?fromdate='+varfromdate+'&todate='+vartodate+'&fromitem='+varfromitem
		   +'&toitem='+vartoitem+'&fromwarehouse='+varfromwrh+'&curr='+varcurrency;
		   
	str4 =  'Exp_ProductionAnalysis.php?fromdate='+varfromdate+'&todate='+vartodate+'&fromitemRM='+varfromitemRM
		   +'&toitemRM='+vartoitemRM+'&fromwarehouse='+varfromwrh+'&fromproject='+varfromprj+'&toproject='+vartoprj
		   +'&fromcostcent='+varfromcst+'&tocostcent='+vartocst+'&curr='+varcurrency;	

	str6 =  'Exp_PreProductionAnalysis.php?fromdate='+varfromdate+'&todate='+vartodate+'&fromitem='+varfromitem
		   +'&toitem='+vartoitem+'&fromwarehouse='+varfromwrh+'&fromproject='+varfromprj+'&toproject='+vartoprj
		   +'&fromcostcent='+varfromcst+'&tocostcent='+vartocst+'&status='+varstatus+'&orderBy='+varOrder;		
	
	str7 =  'Exp_ListofFactoryProduction.php?fromdate='+varfromdate+'&todate='+vartodate+'&fromwarehouse='+varfromwrh+'&fromproject='+varfromprj+'&toproject='+vartoprj
		   +'&fromcostcent='+varfromcst+'&tocostcent='+vartocst+'&curr='+varcurrency+'&showdetail='+varshowdetail
		   +'&showcost='+varshowcost+'&fromitemRM='+varfromitemRM+'&toitemRM='+vartoitemRM+'&showunit2qty='+varshowunit2qty
		   +'&fromgroup='+varfromgroup+'&togroup='+vartogroup+'&fromsubgroup='+varfromsubgroup+'&tosubgroup='+vartosubgroup+'&fromcategory='+varfromcategory
		   +'&tocategory='+vartocategory+'&showlastprice='+varshowlastprice;
		   
	if (varreports == 1) str = str1;		   
	else if (varreports == 2){
		if(dtlWh == 1 && varshowcost == 1) str = str22;
		else str = str2;
	}
	else if (varreports == 3) str = str3;
	else if (varreports == 4) str = str4;
	else if (varreports == 6) str = str6;
	else if (varreports == 7) str = str7;
			   
	window.open(str);
		
}


function SelectReport(option){
	var x = document.form1;
	
	if (option == 1 || option == 8) {
		for (var i=0; i < x.listby.length; i++)
		{
			x.listby[i].disabled = true;
		}
		for (var i=0; i < x.status.length; i++)
		{
			x.status[i].disabled = true;
		}
		
		for (var i=0; i < x.orderBy.length; i++)
		{
			x.orderBy[i].disabled = false;
		}
		
		for (var i=0; i < x.currency.length; i++)
		{
			x.currency[i].disabled = false;
		}
		
		x.showidealqty.disabled = true;
		x.showidealqty.checked  = false;
		x.showdetail.disabled = false;
		x.showcost.disabled = false;
		x.showqtyexists.disabled = true;
		x.fromitemRM.disabled = false;
		x.fromitemdescv.disabled = false;
		x.toitemRM.disabled = false;
		x.toitemdescRM.disabled = false;
		if(x.toheatno1) x.toheatno1.disabled = false;
		if(x.toheatnotype1) x.toheatnotype1.disabled = false;
		x.zerocost.disabled = false;
		
		$('.SelectrowMaterial').css('display','inline-block');
		
		if (document.getElementById('showunit2qty')) {
			x.showunit2qty.disabled = true;
		}
		
	}
	else if (option == 2) {
		for (var i=0; i < x.listby.length; i++)
		{
			x.listby[i].disabled = false;
		}
		
		for (var i=0; i < x.status.length; i++)
		{
			x.status[i].disabled = true;
		}
		
		for (var i=0; i < x.orderBy.length; i++)
		{
			x.orderBy[i].disabled = true;
		}
		
		for (var i=0; i < x.currency.length; i++)
		{
			x.currency[i].disabled = false;
		}
		
		x.showdetail.disabled = false;
		x.showcost.disabled = false;
		x.showidealqty.disabled = false;
		x.showqtyexists.disabled = true;
		x.fromitemRM.disabled = false;
		x.fromitemdescv.disabled = false;
		x.toitemRM.disabled = false;
		x.toitemdescRM.disabled = false;
		if(x.toheatno1) x.toheatno1.disabled = false;
		if(x.toheatnotype1) x.toheatnotype1.disabled = false;
		x.zerocost.disabled = false;
		$('.SelectrowMaterial').css('display','inline-block');
		
		if (document.getElementById('showunit2qty')) {
			x.showunit2qty.disabled = false;
		}
		
	}
	
	else if (option == 6) {
		for (var i=0; i < x.listby.length; i++)
		{
			x.listby[i].disabled = true;
		}
		
		for (var i=0; i < x.status.length; i++)
		{
			x.status[i].disabled = false;
		}
		
		for (var i=0; i < x.orderBy.length; i++)
		{
			x.orderBy[i].disabled = false;
		}
		
		for (var i=0; i < x.currency.length; i++)
		{
			x.currency[i].disabled = true;
		}
		
		x.showdetail.disabled = true;
		x.showcost.disabled = true;
		x.showidealqty.disabled = true;
		x.showqtyexists.disabled = true;
		x.fromitemRM.disabled = true;
		x.fromitemdescv.disabled = true;
		x.toitemRM.disabled = true;
		x.toitemdescRM.disabled = true;
		if(x.toheatno1) x.toheatno1.disabled = true;
		if(x.toheatnotype1) x.toheatnotype1.disabled = true;
		x.zerocost.disabled = true;
		
		$('.SelectrowMaterial').css('display','none');
		
		if (document.getElementById('showunit2qty')) {
			x.showunit2qty.disabled = true;
		}
		
	}
	else if (option == 3 || option == 5) {
		for (var i=0; i < x.listby.length; i++)
		{
			x.listby[i].disabled = true;
		}
		
		for (var i=0; i < x.status.length; i++)
		{
			x.status[i].disabled = true;
		}
		
		for (var i=0; i < x.orderBy.length; i++)
		{
			x.orderBy[i].disabled = true;
		}
		
		for (var i=0; i < x.currency.length; i++)
		{
			x.currency[i].disabled = false;
		}
		
		x.showidealqty.disabled = true;
		x.showidealqty.checked  = false;
		x.showdetail.disabled = (option == 5);
		if (option == 5) x.showdetail.checked = false;
		x.showcost.disabled = true;
		x.showqtyexists.disabled = true;
		x.fromitemRM.disabled = false;
		x.fromitemdescv.disabled = false;
		x.toitemRM.disabled = false;
		x.toitemdescRM.disabled = false;
		if(x.toheatno1) x.toheatno1.disabled = false;
		if(x.toheatnotype1) x.toheatnotype1.disabled = false;
		x.zerocost.disabled = true;
		
		$('.SelectrowMaterial').css('display','inline-block');
		
		if (option == 5) x.showqtyexists.disabled = false;
		
		if (document.getElementById('showunit2qty')) {
			x.showunit2qty.disabled = true;
		}
	} else if (option == 7) {
		for (var i=1; i < x.listby.length; i++)
		{
			x.listby[i].disabled = false;
		}
		x.listby[1].checked = true;
	}
    if(option==9){
	 for (var i=0; i < x.groupby.length; i++)
		{
			x.groupby[i].disabled = false;
		}

		 for (var i=0; i < x.filterby.length; i++)
		{
			x.filterby[i].disabled = false;
		}

		
     }
		else{
			 for (var i=0; i < x.groupby.length; i++)
		{
			x.groupby[i].disabled = true;
		}

		 for (var i=0; i < x.filterby.length; i++)
		{
			x.filterby[i].disabled = true;
		}
		
		}
}

function SelectListBy(option){
	var x = document.form1;
	
	/*if (option == 1) {
		x.showidealqty.disabled = true;
		x.showidealqty.checked  = false;
	}
	else if (option == 2) {
		x.showidealqty.disabled = false;
		
	}*/
}

function SelectStatus(option){
	var x = document.form1;

}

function SelectOrder(option){
	var x = document.form1;

}
function showCosts(indx){
	if(indx==1){
		if(document.getElementById("showcost").checked){
			document.getElementById("showpurprice").checked=false;
		} 
	} 
	if(indx==2){
		if(document.getElementById("showpurprice").checked){
			document.getElementById("showcost").checked=false;
		} 
	}
	
}

function showlistitem(str,itemtype){

	window.open('select_item.php?item='+str+'&itemtype='+itemtype,'mywindow','width=900,height=550,toolbar=no,location=no,directories=no,status=yes,menubar=no,scrollbars=yes,copyhistory=no,resizable=yes');
}

function showlistgroups(str){
	window.open('List_Grouping.php?str='+str,'List_Grouping','width=850,height=400,toolbar=no,location=no,directories=no,status=yes,menubar=no,scrollbars=yes,copyhistory=no,resizable=yes');
}

function showlistsubgroups(str){
	window.open('List_SubGroup.php?str='+str,'List_SubGroup','width=850,height=400,toolbar=no,location=no,directories=no,status=yes,menubar=no,scrollbars=yes,copyhistory=no,resizable=yes');
}

function showlistitemgroup(str){

	window.open('select_itemgroup.php?item='+str,'mywindow','width=850,height=550,toolbar=no,location=no,directories=no,status=yes,menubar=no,scrollbars=yes,copyhistory=no,resizable=yes');
}
  
function showlistitemsubgroup(str){

	window.open('select_itemsubgroup.php?item='+str,'mywindow','width=850,height=550,toolbar=no,location=no,directories=no,status=yes,menubar=no,scrollbars=yes,copyhistory=no,resizable=yes');
}
  
function showlistCategory(str){
	
	window.open('list_categories.php?str='+str,'mywindow','width=900,height=550,toolbar=no,location=no,directories=no,status=yes,menubar=no,scrollbars=yes,copyhistory=no,resizable=yes');
}

function showlistwarehouse(str){

	window.open('select_warehouse.php?item='+str,'mywindow','width=850,height=550,toolbar=no,location=no,directories=no,status=yes,menubar=no,scrollbars=yes,copyhistory=no,resizable=yes');
}

function showlistproject(str){

	window.open('select_project.php?item='+str,'mywindow','width=850,height=550,toolbar=no,location=no,directories=no,status=yes,menubar=no,scrollbars=yes,copyhistory=no,resizable=yes');
}

function showlistItems(str){
	var vDate, vItemCode, vCurrCode;
	
	XPos = (screen.availHeight - 400) / 2;
	YPos = (screen.availWidth - 850) / 2;
	
	vItemCode=document.form1.ItemCode.value;
	window.open('List_SorItems.php?frm='+str+'&PItem_Code='+vItemCode+'&ItemType=All&warehousecode=&TransDate='+vDate,'List_SorItems','width=850,height=400,toolbar=no,location=no,directories=no,status=yes,menubar=no,scrollbars=yes,copyhistory=no,resizable=no,left='+YPos+',top='+XPos);
}

function showlistcostcenter(str){

	window.open('select_costcenter.php?item='+str,'mywindow','width=850,height=550,toolbar=no,location=no,directories=no,status=yes,menubar=no,scrollbars=yes,copyhistory=no,resizable=yes');
}

function showlistclientbusinesstype(str){

	window.open('select_clientbusinesstype.php?item='+str,'mywindow','width=850,height=550,toolbar=no,location=no,directories=no,status=yes,menubar=no,scrollbars=yes,copyhistory=no,resizable=yes');
}

function showlistarea(str){

	window.open('select_area.php?item='+str,'mywindow','width=850,height=550,toolbar=no,location=no,directories=no,status=yes,menubar=no,scrollbars=yes,copyhistory=no,resizable=yes');
}
-->
</script>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>Cost Per Receipe</title>
<style type="text/css">
<!--
body,td,th {
	font-family: Verdana, Arial, Helvetica, sans-serif;
	font-size: 14px;
}
a:link {
	text-decoration: none;
}
a:visited {
	text-decoration: none;
}
a:hover {
	text-decoration: none;
}
a:active {
	text-decoration: none;
}
.style1 {
	color: #FF0000;
	font-family: "Times New Roman", Times, serif;
	font-size: 20px;
}
.style8 {font-size: 11px; color: #999999; font-weight: bold; }
.style11 {font-size: 11px}
body {
	margin-top: 0px;
}
-->
</style>

<link type="text/css" rel="stylesheet" href="dhtmlgoodies_calendar.css?random=20051112" media="screen"></LINK>
	<SCRIPT type="text/javascript" src="dhtmlgoodies_calendar.js?random=20060118"></script>
</head>
<body>
HERE;

echo "<form id='form1' name='form1' method='post' action='$_SERVER[PHP_SELF]'>";
echo"<table cellpadding='0' cellspacing='0' border='0'  align='center' class='layout-width'>";
include_once("header.php");
echo" <tr><td  valign='top'>";

include_once("sidemenu.php");
echo"  </td></tr>";


echo" <tr style='height:26px;'>
		<td  valign='top' id='content-top'>
			<table border='0' cellpadding='0' cellspacing='0' style='table-layout:fixed; height:26px;' class='layout-width'>
			<tr style='height:26px;'>
				<td style='width:38px; height:26px;'>&nbsp;</td>
				<td valign='middle' style='width:300px; height:26px;'><span class='title'>Cost Per Receipe</span></td>
				<td class='message' valign='middle' style='height:26px;'>".htmlspecialchars($message, ENT_QUOTES)."</td>
			</tr></table>
		</td>
	  </tr>";

echo"<tr>
		<td  height='34'  valign='top' id='content-top2'>
			<table border='0' cellpadding='0' cellspacing='0' class='layout-width'>
			<tr>
				<td width='30'  height='34'>&nbsp;</td>
				<td valign='middle' height='34' width='720px' align='left'>";
		
	echo" <button type='button' id='btnClear' name='btnClear'  title='Clear' onclick='Clear()'><span><span>Clear</span></span></button>";
		echo"<button type='button' id='btnPreview' name='btnPreview'  onClick=\"printPrev('".$printaccess."',0)\"; title='Preview'><span><span>Preview</span></span></button>";

	if ($ExporttoExcel==1){
		echo"<button type='button' id='btnPreview' name='btnPreview'  onClick=\"printPrev('".$printaccess."',1)\"; title='Excel'><span><span>Export Excel</span></span></button>";

	}
echo"	</td></tr></table>";
echo"</td></tr>";				

		
echo"<tr><td id='content-body'>
<table border='0'  align='center' valign='top' width=100% class='tablecontent'>
   <tr bgcolor='#F7F7F7'><td valign='top' align='left' colspan='10'>";		

echo '  <table border="0" align="center" width="100%">';

echo" <tr>";	  
echo"   <td width='15%' height='19' colspan='2' align='left' bgcolor='#F5F5F5'><span class='style8'>Date</span></td> ";
echo"   <td width='20%' height='19' colspan='1' align='left' bgcolor='#F5F5F5'><span class='style8'>Reports</span></td> ";
// echo"   <td width='20%' height='19' colspan='1' align='left' bgcolor='#F5F5F5'><span class='style8'>Group By</span></td> ";
echo"   <td width='20%' height='19' colspan='1' align='left' bgcolor='#F5F5F5'><span class='style8'>Show</span></td> ";
//echo"   <td width='8%' height='19' align='left' bgcolor='#F5F5F5'><span class='style8'>Currency</span></td>";
// echo"   <td width='20%' height='19' align='left' bgcolor='#F5F5F5'><span class='style8'>Reports </span></td>";
// echo"   <td width='11%' height='19' align='left ' bgcolor='#F5F5F5'><span class='style8'>List by </span></td>";
// echo"   <td width='15%' height='19' align='center' bgcolor='#F5F5F5'><span class='style8'>&nbsp;</span></td>";
// echo"   <td width='15%' height='19' align='left' bgcolor='#F5F5F5'><span class='style8'>Status</span></td>";
echo" </tr>";

echo" <tr height='15px'>";	  
echo"   <td width='5%'  valign='middle' bgcolor='#F5F5F5' ><span class='style8'>From</span></td>";
echo"   <td width='10%'  valign='middle' bgcolor='#F5F5F5' ><span class='style8'>";
echo"      <input type='text' name='fromdate' value='$fromdate' maxlength='10' size='10'>";
echo"      <A HREF=\"#\" onClick=\"displayCalendar(document.form1.fromdate,'dd/mm/yyyy',this); return false;\" NAME=\"anchor1\" ID=\"anchor1\"><image src='./img/cal.gif' border=0 align=center></image></A></span></td>";


echo"  
  <td width='20%' valign='top' rowspan='2' bgcolor='#F5F5F5'> ";
echo"<span class='style8'><label>";
echo" <span class='style8'><label>";
if ($reports != "3" && $reports != "4") $reports = "2";
echo "<input name='reports' type='radio' value='2' ".($reports=="2" ? "checked" : "")."/>";
echo "Semi-Finished</label></span>";

echo"   <br><span class='style8'><label>";
echo "<input name='reports' type='radio' value='3' ".($reports=="3" ? "checked" : "")."/>";
echo "Finished Goods</label></span>";

echo"   <br><span class='style8'><label>";
echo "<input name='reports' type='radio' value='4' ".($reports=="4" ? "checked" : "")."/>";
echo "Both</label></span></td>";

// echo"  
//   <td width='20%' valign='top' colspan=1 rowspan='2' bgcolor='#F5F5F5'> 
//  <span class='style8'><label> <input name='groupby' type='radio' value='1'  checked  />Item
// 			<br><span class='style8'><label> <input name='groupby' type='radio' value='2'    />Jobcard
// 					<br><span class='style8'><label> <input name='groupby' type='radio' value='3'  checked  />None
// 						<br><span class='style8'><label> <input name='groupby' type='radio' value='4'  />Day
// 						<br><span class='style8'><label> <input name='groupby' type='radio' value='5'  />Cost Type
// </td>";
// echo"
//   <td width='20%' valign='top' colspan=1 rowspan='2' bgcolor='#F5F5F5'>
//  <span class='style8'><label> <input name='costscope' type='radio' value='all' checked />All</label></span>
//  <br><span class='style8'><label> <input name='costscope' type='radio' value='labor' />Labor</label></span>
//  <br><span class='style8'><label> <input name='costscope' type='radio' value='energy' />Energy</label></span>
// </td>";

echo"
  <td width='20%' valign='top' colspan=1 rowspan='2' bgcolor='#F5F5F5'>
 <span class='style8'><label> <input name='qtyType' type='radio' value='0' checked />Standard</label></span>
 <br><span class='style8'><label> <input name='qtyType' type='radio' value='1' />Production</label></span>
</td>";

echo" </tr>";

echo" <tr>";
echo"   <td width='5%' valign='top' bgcolor='#F5F5F5'><span class='style8'>To</span></td>";
echo"   <td width='10%' valign='top' bgcolor='#F5F5F5'><span class='style8'>";
echo"      <input type='text' name='todate' value='$todate'  maxlength='10' size='10'>";
echo"      <A HREF=\"#\" onClick=\"displayCalendar(document.form1.todate,'dd/mm/yyyy',this); return false;\" NAME=\"anchor1\" ID=\"anchor1\"><image src='./img/cal.gif' border=0 align=center></image></A></span></td>";
echo" </tr>";

echo"<tr><td width='5%' align='left' bgcolor='#F5F5F5'><span class='style8'>Item</span></td>
	    <td width='45%' colspan='3' bgcolor='#F5F5F5'> <span class='style8'><input type='text' name='ItemCode' id='ItemCode' value='$ItemCode' readonly size='15' >
		<input type='text' name='ItemName' id='ItemName' value='$ItemName' readonly size='28' ></span>
		<label><span class='style11'><a href='javascript:void(0);' NAME='My Window Name' title=' Select Stock Item ' 
				onClick='showlistItems(47); '><img src='img/btnopen.jpg' width='15' height='20' border='0' /></a> </span></label> 
		</td>";
// echo "
// <td valign='center'  width='5%' ><span class='style8'>Jobcard</span></td>";
// echo "   <td valign='center' width='20%'><span class='style8'>";
// echo "      <input type='text' name='JobcardRef' id='JobcardRef' value='$JobcardRef' style='width:150px;' readonly>
//   <input type='hidden' name='Jobcard' id='Jobcard' value='$Jobcard' style='width:200px;'>
// 	 <label><span class='style1'><img src='img/btnopen.jpg' title=' Select JobCard ' id='lstClientCodeH' width='15' height='20' border='0' onMouseOver=\"style.cursor='hand'\"  onClick='showlistScheduleMixer(8); ' /> </span></label>";
// echo "   </td>";

// echo"   <td width='8%' valign='top' bgcolor='#F5F5F5'><span class='style8'>Show Details</span></td>";
// echo"   <td width='12%' valign='top' bgcolor='#F5F5F5'><span class='style8'> <input type='checkbox' name='showdetail' id='showdetail'> </span></td>";
// echo"   <td width='8%' valign='top' bgcolor='#F5F5F5'><span class='style8'>group by Items</span></td>";
// echo"   <td width='12%' valign='top' bgcolor='#F5F5F5'><span class='style8'> <input type='checkbox' name='groupbyitem' id='groupbyitem' checked> </span></td>";

echo"</tr>";

echo"<tr>";
// echo"   <td width='8%' valign='top' bgcolor='#F5F5F5'><span class='style8'>Show Details</span></td>";
// echo"   <td width='12%' valign='top' bgcolor='#F5F5F5'><span class='style8'> <input type='checkbox' name='showdetail' id='showdetail'> </span></td>";


// echo"   <td width='8%' valign='top' bgcolor='#F5F5F5'><span class='style8'>Show Details</span></td>";
// echo"   <td width='12%' valign='top' bgcolor='#F5F5F5'><span class='style8'> <input type='checkbox' name='showdetail' id='showdetail'> </span></td>";
// echo"   <td width='8%' valign='top' bgcolor='#F5F5F5'><span class='style8'>group by Items</span></td>";
// echo"   <td width='12%' valign='top' bgcolor='#F5F5F5'><span class='style8'> <input type='checkbox' name='groupbyitem' id='groupbyitem' checked> </span></td>";

// echo"</tr>";

echo"<tr>";
echo"   <td colspan='7' bgcolor='#F5F5F5'><span class='style8'>&nbsp;</span></td>";
echo" </tr>";
   /* <tr>
      <td colspan='9' align='center' bgcolor='#F5F5F5'>
	  <input type='reset'  name='clearbtn' value='Clear' style=' background-color:#4069A7; color:#FFFFFF;font-family:Verdana, Arial, Helvetica, sans-serif; font-size:12px;' />
	  <input type='button' name='preview' id='Preview' value='Preview' style=' background-color:#4069A7; color:#FFFFFF;font-family:Verdana, Arial, Helvetica, sans-serif; font-size:12px;' onClick=\"printPrev('".$printaccess."')\"; />
	  <input type='".$ExporttoExcelStatus."' name='btnExcel' id='btnExcel' value='Export Excel' style=' background-color:#4069A7; color:#FFFFFF;font-family:Verdana, Arial, Helvetica, sans-serif; font-size:12px;' onClick=\"exportExcel()\";/>
	  
      </td>
    </tr>*/
echo"	<tr>
	  <td colspan='9' height='100'>&nbsp;</td>
    </tr>";
echo"</table></td></tr>"; 
 echo"   </table></td></tr><tr height='30'><td align='center' id='content-bottom' ></td></tr>"; 
  echo"</table>
 
</form>
</body>
</html>";


//Return Date in DD/MM/YYYY format
function displayFromDate($data){
	return "01/01/".substr($data, 6, 4);		
}
//Return Today's Date in DD/MM/YYYY format
function displayToday(){
	return strftime("%d")."/".strftime("%m")."/".strftime("%Y");
}
	  
?>