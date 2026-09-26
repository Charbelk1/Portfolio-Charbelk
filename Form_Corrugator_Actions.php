<?php

session_start();
header("Content-type: text/html; charset=" . $_SESSION['encodingmode']);
if ($_SESSION['dbName'] == "") {
	header("location:../index.php");
}

include("config.inc.php");
include("Functions_Purchase.php");
include("function.inc.php");
include("Functions_UserAccess.php");
set_time_limit(0);

// dd/mm/yyyy -> yyyy-mm-dd (declared up here: a conditional function only exists once this line has run)
if (!function_exists('explodewordrequest')) {
	function explodewordrequest($date)
	{
		$trim = explode('/', $date);
		$trim = ($trim[2] ?? '') . "-" . ($trim[1] ?? '') . "-" . ($trim[0] ?? '');

		return $trim;
	}
}

// PHP 7 GetSQLValueString (same 3-argument call as Form_Delivery_Actions.php). Normally provided by the
// included function libraries - only declared here if none of them has it.
if (!function_exists('GetSQLValueString')) {
	function GetSQLValueString($theValue, $theType, $connection)
	{
		$theValue = trim((string)$theValue);
		$theValue = mysqli_real_escape_string($connection, $theValue);

		switch ($theType) {
			case "text":
				$theValue = ($theValue != "") ? "'" . $theValue . "'" : "Null";
				break;
			case "long":
			case "int":
				$theValue = ($theValue != "") ? intval($theValue) : "Null";
				break;
			case "double":
				$theValue = ($theValue != "") ? "'" . doubleval($theValue) . "'" : "Null";
				break;
			case "date":
				$theValue = ($theValue != "") ? "'" . $theValue . "'" : "Null";
				break;
		}
		return $theValue;
	}
}

$message = '';

// register_globals was removed in PHP 5.4 - this page (like Form_Delivery_Actions.php) still reads
// many request values as plain variables ($corrugator_id, $JobCard_Id, $loc_id, $BorderNeededQty, ...)
if (!ini_get('register_globals')) {
	$superglobals = array(
		$_SERVER,
		$_ENV,
		$_FILES,
		$_COOKIE,
		$_POST,
		$_GET
	);
	if (isset($_SESSION)) {
		array_unshift($superglobals, $_SESSION);
	}
	foreach ($superglobals as $superglobal) {
		extract($superglobal, EXTR_SKIP);
	}
}

$connection = mysqli_connect($config['dbServer'], $config['dbUser'], $config['dbPass']) or die("Could not connect to DB");
mysqli_select_db($connection, $_SESSION['dbName']) or die("Could not find DB");

if ($_SESSION['encodingmode'] == 'utf8' || $_SESSION['encodingmode'] == 'utf-8') {
	mysqli_query($connection, "SET NAMES 'utf8'");
	mysqli_query($connection, 'SET CHARACTER SET utf8');
	mysqli_set_charset($connection, 'utf8');
}

$query_user = "select User_Name from " . $_SESSION['dbLabel'] . ".users where User_account = '" . $_SESSION['useraccount'] . "'";
$result_user = mysqli_query($connection, $query_user);
$rows_user = mysqli_fetch_array($result_user);
if (is_array($rows_user)) extract($rows_user);

//User Access (same as Form_Delivery_Actions.php - access table instead of the old login table)
$query_login = "select ifnull(access_belongstogrp,0) as FI_BelongsToGrp
			from " . $_SESSION['dbLabel'] . ".access
			Left join " . $_SESSION['dbLabel'] . ".languages on languages.language_id = access_language
			where access_code='" . $_SESSION['useraccount'] . "' and access_id='" . $_SESSION['accessid'] . "' ";
$resultlogin = mysqli_query($connection, $query_login) or die(mysqli_error($connection));
while ($rows = mysqli_fetch_array($resultlogin)) {
	extract($rows);
}

//Read From Setup Table
/*
$querysetup = " select DecimalBase, DecimalBase1, DecimalBase2, base1, base2, grid_color1, grid_color2, " .
	" Date_Format(Setup_VatStartDate,'%Y%m%d') As Setup_VatStartDate, ifNull(Setup_VatPercentage,0) As Setup_VatPercentage from setup ";
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
$Setup_VatStartDate = getSmodulevalue('Setup_VatStartDate', 'string',  $_SESSION['logincompany'], $_SESSION['dbLabel'], $connection);
$Setup_VatPercentage = getSmodulevalue('Setup_VatPercentage', 'string',  $_SESSION['logincompany'], $_SESSION['dbLabel'], $connection);

//Read From Module Table
$querymodule = " Select Module_AdditionalTaxes, ifNull(Module_NegativeQuantity,0) As Module_NegativeQuantity, " .
	" ifNull(Module_rawmaterial,0) As Module_RawMaterial, ifNull(Module_PorderConfirm,0) As Module_PorderConfirm, " .
	" ifNull(Module_ReferenceByCostcenter,0) As Module_ReferenceByCostcenter, ifNull(Module_ReferenceByProject,0) As Module_ReferenceByProject, " .
	" ifNull(Module_ProjectDt,0) As Module_ProjectDt, ifNull(Module_CostCenterDt,0) As Module_CostCenterDt, " .
	" ifNull(Module_DoubleUnit,0) As Module_DoubleUnit, ifNull(Module_Unit1Method,0) As Module_Unit1Method, ifNull(Module_Grn,0) As Module_Grn,  " .
	" ifNull(Module_Limit,0) As Module_Limit, ifNull(Module_PorderAutoApprove,0) Module_PorderAutoApprove, " .
	" ifNull(Module_LimitProject,0) As Module_LimitProject,ifNull(module_purchasezero,0) Module_PurchaseZero , ifNull(Module_ReferenceByBelongsToGroup,0) as Module_ReferenceByBelongsToGroup  " .
	" From Module";
$resultmodule = mysqli_query($connection, $querymodule) or die(mysqli_error($connection));
while ($rowmodule = mysqli_fetch_array($resultmodule)) {
	extract($rowmodule);
}


$Module_ReferenceByBelongsToGroup  = getCompanyConditionsvalue('Module_ReferenceByBelongsToGroup', $_SESSION['logincompany'], $_SESSION['dbLabel'], $connection);

//Read From PurchaseSetup Table
$queryPurchaseSetup = "Select PurchaseTTC,  ifnull(MultipleVAT,0) as MultipleVAT From PurchaseSetup";
$resultPurchaseSetup = mysqli_query($connection, $queryPurchaseSetup) or die(mysqli_error($connection));
while ($rowPurchaseSetup = mysqli_fetch_array($resultPurchaseSetup)) {
	extract($rowPurchaseSetup);
}


$mode     = $_GET["mode"];
if ($mode == "") {
	$mode = $_POST["mode"];
}
$vvMode     = $_GET["vvMode"];
if ($vvMode == "") {
	$vvMode = $_POST["vvMode"];
}
$mainmode   = $_GET["mainmode"];
if ($mainmode == "") {
	$mainmode = $_POST["mainmode"];
}
$actionmode   = $_GET["actionmode"];
if ($actionmode == "") {
	$actionmode = $_POST["actionmode"];
}
$lastRowLocation   = $_GET["lastRowLocation"];
if ($lastRowLocation == "") {
	$lastRowLocation = $_POST["lastRowLocation"];
}

$txtCorrugatorId   = $_GET["txtCorrugatorId"];
if ($txtCorrugatorId == "") {
	$txtCorrugatorId = $_POST["txtCorrugatorId"];
}


$ulstacker_id = $_GET["ulstacker_id"];

$txtPlannedQty = $_GET["txtPlannedQty"];
$txtProducedQty = $_GET["txtProducedQty"];
$txtRemainingQty = $_GET["txtRemainingQty"];
$txtBoxSizeL = $_GET["txtBoxSizeL"];
$txtBoxSizeW = $_GET["txtBoxSizeW"];
$txtBoxSizeH = $_GET["txtBoxSizeH"];
$txtStdPaperTL = $_GET["txtStdPaperTL"];
$txtStdPaperFL = $_GET["txtStdPaperFL"];
$txtStdPaperWTL = $_GET["txtStdPaperWTL"];
$txtSalesRef = $_GET["txtSalesRef"];
$txtMasterRef = $_GET["txtMasterRef"];
// $txtCorrugatorId = $_GET["txtCorrugatorId"];
$txtCorrugatorId1 = $_GET["txtCorrugatorId1"];
$corgrid_id = $_GET["corgrid_id"];
$txtWareHouse = $_GET['txtWareHouse'];
$txtType      = $_GET['txtType'];

$txtLength = $_GET["txtLength"];
$txtWidth = $_GET["txtWidth"];
$txtFlapTop = $_GET["txtFlapTop"];
$txtHeight = $_GET["txtHeight"];
$txtFlapBot = $_GET["txtFlapBot"];
$txtOuts = $_GET["txtOuts"];
$txtTWidth = $_GET["txtTWidth"];
$txtTCuts = $_GET["txtTCuts"];
$txtActualCuts = $_GET["txtActualCuts"];
$txtReelDec = $_GET["txtReelDec"];
$txtTotWidth = $_GET["txtTotWidth"];
$txtTrimming = $_GET["txtTrimming"];
$txtTrimWaste = $_GET["txtTrimWaste"];
$txtLM = $_GET["txtLM"];
$txtSQ = $_GET["txtSQ"];


$txtLocation = $_GET["txtLocation"];
$txtGSM = $_GET["txtGSM"];
$txtPaperGrade = $_GET["txtPaperGrade"];
$txtRellDeckle = $_GET["txtRellDeckle"];
$txtPaperMill = $_GET["txtPaperMill"];
$txtSupplier = $_GET["txtSupplier"];

$txtURequired = $_GET["txtURequired"];
$txtUActual = $_GET["txtUActual"];
$txtLRequired = $_GET["txtLRequired"];
$txtLActual = $_GET["txtLActual"];
$txtTRequired = $_GET["txtTRequired"];
$txtTActual = $_GET["txtTActual"];

$txtURequiredT = $_GET["txtURequiredT"];
$txtUActualT = $_GET["txtUActualT"];
$txtLRequiredT = $_GET["txtLRequiredT"];
$txtLActualT = $_GET["txtLActualT"];
$txtTRequiredT = $_GET["txtTRequiredT"];
$txtTActualT = $_GET["txtTActualT"];


$txtIPaper = $_GET["txtIPaper"];
$txtFPaper = $_GET["txtFPaper"];
$txtOPaper = $_GET["txtOPaper"];
$txtIBalance = $_GET["txtIBalance"];
$txtFBalance = $_GET["txtFBalance"];
$txtOBalance = $_GET["txtOBalance"];
$txtWTPCU = $_GET["txtWTPCU"];
$txtWTPCL = $_GET["txtWTPCL"];
$txtTotalGSM = $_GET["txtTotalGSM"];



$txtRequiredTrim = $_GET["txtRequiredTrim"];
$txtSizeTrim = $_GET["txtSizeTrim"];
$txtActualTrim = $_GET["txtActualTrim"];

$txtRequiredTrimT = $_GET["txtRequiredTrimT"];
$txtSizeTrimT = $_GET["txtSizeTrimT"];
$txtActualTrimT = $_GET["txtActualTrimT"];


$txtTotalRequired = $_GET["txtTotalRequired"];
$txtTotalActual = $_GET["txtTotalActual"];

$count = $_GET["count"];
$txtCorrugatorRef = $_GET["txtCorrugatorRef"];
$JobCardId = $_GET["JobCardId"];
$Corrugator_Id1 = $_GET["Corrugator_Id1"];

$txtItemCode = $_GET["txtItemCode"];
$txtItemDesc = $_GET["txtItemDesc"];

$projectcode = $_GET["projectcode"];
$costcenter = $_GET["costcenter"];
$warehouse = $_GET["warehouse"];
$currency = $_GET["currency"];
$Sorderdt_price = $_GET["Sorderdt_price"];

$txtSalesRef = $_GET["txtSOF"];
$clientCode = $_GET["clientCode"];
$txtClient = $_GET["txtClient"];
$txtBoardNeed = $_GET["txtBoardNeed"];
$txtFluteType = $_GET["txtFluteType"];
$txtScoringType = $_GET["txtScoringType"];
$txtJobCardRef = $_GET["txtJobCardRef"];
$txtInsideLiner = $_GET["txtInsideLiner"];
$txtFlutting2 = $_GET["txtFlutting2"];
$txtBoxType = $_GET["txtBoxTypeId"];
$txtStdGsm = $_GET["txtStdGsm"];
$txtOutsideLiner = $_GET["txtOutsideLiner"];
$Sorder_BelongsToGrp = $_GET["Sorder_BelongsToGrp"];

$related_salesorder = $_GET["related_salesorder"];
$index = $_GET["index"];
$len = $_GET["len"];
$corrugator_idArr = $_GET['corrugator_idArr'];



if ($mode == "changeFluteType" && $Corrugator_Id != '') {
	$message  = '';
	$flutetype = $_POST['txtFluteType'][$index];
	$ulstacker_id = $_POST['upperlowerid'][$index];

	if($txtWTPCU  == '') $txtWTPCU  ="NULL";
	if($txtWTPCL == '') $txtWTPCL = "NULL";
	if($txtTotalGSM == '') $txtTotalGSM = "NULL";

	$q = " Update temp_upperlowerstacker set flutetype=$flutetype where ulstacker_id = $ulstacker_id";
	$resultdt = mysqli_query($connection, $q);
	$message = mysqli_error($connection);
	$q="update temp_corrugator1 set wtpcupper =$txtWTPCU  ,wtpclower=$txtWTPCL ,totalgsm=$txtTotalGSM where corrugator_id = '" . $Corrugator_Id . "'";
	 mysqli_query($connection, $q);

	header("Location: ./Form_Corrugator.php?message=" . $message . "&txtCorrugatorId=" . $Corrugator_Id);
}

if ($mainmode == "search") {
	// Copy Data From Main Tables To Temp Tables
	$querycheck = "select count(*) as vcount from temp_corrugator1 where corrugator_id=$txtCorrugatorId";
	$resultcheck = mysqli_query($connection, $querycheck) or die(mysqli_error($connection));
	$rowcheck = mysqli_fetch_array($resultcheck);
	if (is_array($rowcheck)) extract($rowcheck);
	//echo $querycheck;echo $vcount ;exit;
	if ($vcount == 0) {
		// Insert into temp_jobcard
		$querya = "INSERT INTO temp_corrugator1 (corrugator_id, jobcardid, corrugator_reference, corrugator_date, corrugator_confirm, corrugator_approve,adjustment_id,jobcardid2,reeldeckle,totwidth,trimming,
	 upperpaperrequiredT ,upperpaperactualT ,lowerpaperrequiredT ,lowerpaperactualT ,totURequiredT ,totUactualT ,paperinner ,paperflutting ,paperouter ,balanceinner ,
 balanceflutting ,balanceouter ,wtpcupper ,wtpclower ,totalgsm ,trimsizeT ,trimrequiredT ,trimactualT ,trimwasteper  )
	SELECT corrugator_id, jobcardid, corrugator_reference, corrugator_date, corrugator_confirm, corrugator_approve,adjustment_id,jobcardid2,reeldeckle,totwidth,trimming,
	upperpaperrequiredT ,upperpaperactualT ,lowerpaperrequiredT ,lowerpaperactualT ,totURequiredT ,totUactualT ,paperinner ,paperflutting ,paperouter ,balanceinner ,
 balanceflutting ,balanceouter ,wtpcupper ,wtpclower ,totalgsm ,trimsizeT ,trimrequiredT ,trimactualT ,trimwasteper 
	FROM corrugator1
	WHERE corrugator_id = $txtCorrugatorId";



		$resulta = mysqli_query($connection, $querya);
		if (!$resulta) {
			$message = mysqli_error($connection);
			// Handle the error or log it
		}
		/// Insert into temp_adjustment from adjustment
		/// Insert into temp_adjustmentdt from adjustmentdt
	}

	// Insert into temp_jobcarddt
	$queryt = "INSERT INTO temp_upperlowerstacker (ulstacker_id, BorderNeededQty,corrugatorid, salesref, masterref, customer, itemdesc, boxtype, flutetype, scoringtype, outsideliner, insideliner, flutting2, stdgsm, boardneed, plannedqty, producedqty, remainingqty, saleorderqty, boxsizelength, boxsizewidth, boxsizeheight, stdpapertl, stdpaperfl, stdpaperwtl, clientCode, jobCardRef,jobcardid )
	SELECT ulstacker_id,BorderNeededQty, corrugatorid, salesref, masterref, customer, itemdesc, boxtype, flutetype, scoringtype, outsideliner, insideliner, flutting2, stdgsm, boardneed, plannedqty, producedqty, remainingqty, saleorderqty, boxsizelength, boxsizewidth, boxsizeheight, stdpapertl, stdpaperfl, stdpaperwtl, clientCode, jobCardRef,jobcardid 
	FROM upperlowerstacker
	WHERE corrugatorid = $txtCorrugatorId";

	$resultt = mysqli_query($connection, $queryt);

	// Insert into temp_newrawmaterials
	$queryn = "INSERT INTO temp_corgrid (corgrid_id, corrugatorid, corref, salesref, length, width, flaptop, height, flapbot, numouts, twidth, numcuts, actualcuts, reeldeckle, totwidth, trimming, trimwaste, lm, sq,jobcardid,ulstacker_id )
	SELECT corgrid_id, corrugatorid, corref, salesref, length, width, flaptop, height, flapbot, numouts, twidth, numcuts, actualcuts, reeldeckle, totwidth, trimming, trimwaste, lm, sq,jobcardid,ulstacker_id 
	FROM corgrid
	WHERE corrugatorid = $txtCorrugatorId";

	$resultn = mysqli_query($connection, $queryn);


	// Insert into temp_oldrawmaterials
	$queryo = "INSERT INTO temp_locationtable (loc_id, corrugatorid,corrugatorid2,location, gsm, papergradle, relldeckle, papermill, supplier, upperpaperrequired,upperpaperrequiredT, upperpaperactual,upperpaperactualT, lowerpaperrequired,lowerpaperrequiredT, lowerpaperactual,lowerpaperactualT, totalrequired, totalactual, paperinner, paperflutting, paperouter, balanceinner, balanceflutting, balanceouter, wtpcupper, wtpclower, totalgsm, trimsize, trimrequired, trimactual, totalwasterequired, totalwasteactual,trimwasteper,
	 totURequired,totURequiredT,totUactual,totUactualT,warehouse_code,itemCode,stockQty,itemType)
	SELECT loc_id, corrugatorid,corrugatorid2, location, gsm, papergradle, relldeckle, papermill, supplier, upperpaperrequired,upperpaperrequiredT, upperpaperactual,upperpaperactualT, lowerpaperrequired,lowerpaperrequiredT, lowerpaperactual,lowerpaperactualT, totalrequired, totalactual, paperinner, paperflutting, paperouter, balanceinner, balanceflutting, balanceouter, wtpcupper, wtpclower, totalgsm, trimsize, trimrequired, trimactual, totalwasterequired, totalwasteactual,trimwasteper, totURequired,totURequiredT,totUactual,totUactualT,warehouse_code,itemCode,
	stockQty,itemType
	FROM locationtable
	WHERE corrugatorid = $txtCorrugatorId";

	$resulto = mysqli_query($connection, $queryo);

	$querycheck = "select adjustment_id from temp_corrugator1 where corrugator_id=$txtCorrugatorId";
	$resultcheck = mysqli_query($connection, $querycheck) or die(mysqli_error($connection));
	$rowcheck = mysqli_fetch_array($resultcheck);
	if (is_array($rowcheck)) extract($rowcheck);
/*
	$queryadj = "INSERT INTO temp_adjustment (Adjustment_Id, Adjustment_Reference, Adjustment_Date, Adjustment_Remak, Adjustment_posted, Currency_Code, Adjustment_Amount, Adjustment_AmountBase1, Adjustment_AmountBase2, Costcent_code, Project_code, Journal_id, Invdebitaccount, Invcreditaccount, Adjustment_isInvoice, Adjustment_BelongsToGroup, Mach_code, Item_code, Adjustment_hours, Adjustment_Type, Sendreceive_reference, Adjustment_RateBase1, Adjustment_RateBase2, Adjustment_Rate, Synchronized, adjustment_isinv_dstk, Adjustment_EntryType, Tag_Reference, Adjustment_RequestBy, User_account, adjustment_TotalQuantity, document_code, Entry_Type, BRANCH_CODE, adjustmentrequest_Id, Adjustment_asset, Logged_Code, Adjustment_confirm, Confirmed_user, client_code, adj_division, chk_loadToChanging, PrePM_id, adjustment_manualNo, form_type, related_stockInId, truck_no, cjrId, cjrId2)
	SELECT Adjustment_Id, Adjustment_Reference, Adjustment_Date, Adjustment_Remak, Adjustment_posted, Currency_Code, Adjustment_Amount, Adjustment_AmountBase1, Adjustment_AmountBase2, Costcent_code, Project_code, Journal_id, Invdebitaccount, Invcreditaccount, Adjustment_isInvoice, Adjustment_BelongsToGroup, Mach_code, Item_code, Adjustment_hours, Adjustment_Type, Sendreceive_reference, Adjustment_RateBase1, Adjustment_RateBase2, Adjustment_Rate, Synchronized, adjustment_isinv_dstk, Adjustment_EntryType, Tag_Reference, Adjustment_RequestBy, User_account, adjustment_TotalQuantity, document_code, Entry_Type, BRANCH_CODE, adjustmentrequest_Id, Adjustment_asset, '" . $_SESSION['useraccount'] . "', Adjustment_confirm, Confirmed_user, client_code, adj_division, chk_loadToChanging, PrePM_id, adjustment_manualNo, form_type, related_stockInId, truck_no, cjrId, cjrId2
	FROM adjustment
	WHERE Adjustment_Id = $adjustment_id";
	$resultadj = mysqli_query($connection, $queryadj) or die(mysqli_error($connection));

	$queryadjdt = "INSERT INTO temp_adjustmentdt (AdjustmentDt_Id, Adjustment_Id, Adjustment_Date, Warehouse_Code, Item_Code, AdjustmentDt_Quantity, AdjustmentDt_Posted, Color_Code, Costcent_code, Project_code, Size_measure, AdjustmentDt_Price, AdjustmentDt_PriceBase1, AdjustmentDt_PriceBase2, AdjustmentDt_ExistingQty, AdjustmentDt_PhysicalQty, Item_Barcode, Journal_Type, AdjustmentDt_DirectStkDbt, AdjustmentDt_DirectStkCrd, AdjustmentDt_DirectStkCur, Deliverydt_id, Item_codepacked, Synchronized, Item_qtyPacked, Currency_Code, Tag_Reference, AdjustmentDt_PricePerUnit, AdjustmentDt_QtyUnit, Item_ManufacturedCode, Item_UnitCoef, Item_Unit2Qty, journal_type2, AdjustmentDt_ExpireDate, AdjustmentDt_BatchNo, adjustmentrequest_Reference, adjustmentrequestdt_Id, AdjustmentDt_AssetPrice, adjustmentdt_heatno, Logged_Code, InvDEBITaccount)
	SELECT AdjustmentDt_Id, Adjustment_Id, Adjustment_Date, Warehouse_Code, Item_Code, AdjustmentDt_Quantity, AdjustmentDt_Posted, Color_Code, Costcent_code, Project_code, Size_measure, AdjustmentDt_Price, AdjustmentDt_PriceBase1, AdjustmentDt_PriceBase2, AdjustmentDt_ExistingQty, AdjustmentDt_PhysicalQty, Item_Barcode, Journal_Type, AdjustmentDt_DirectStkDbt, AdjustmentDt_DirectStkCrd, AdjustmentDt_DirectStkCur, Deliverydt_id, Item_codepacked, Synchronized, Item_qtyPacked, Currency_Code, Tag_Reference, AdjustmentDt_PricePerUnit, AdjustmentDt_QtyUnit, Item_ManufacturedCode, Item_UnitCoef, Item_Unit2Qty, journal_type2, AdjustmentDt_ExpireDate, AdjustmentDt_BatchNo, adjustmentrequest_Reference, adjustmentrequestdt_Id, AdjustmentDt_AssetPrice, adjustmentdt_heatno, '" . $_SESSION['useraccount'] . "', InvDEBITaccount
	FROM adjustmentdt
	WHERE Adjustment_Id = $adjustment_id";
	$resultadjdt = mysqli_query($connection, $queryadjdt) or die(mysqli_error($connection));
	*/
	$qbooking = "  
		insert into temp_bookingQty ( bookingqty_id,bookingQty_qty, bookingQty_usedqty, bookingqty_date,bookingqty_useddate, item_code, warehouse_code , user_account,SerialNo, cjr_id, transaction_id,Transaction_Type,transaction_date)
		Select  bookingqty_id,bookingQty_qty, bookingQty_usedqty, bookingqty_date,bookingqty_useddate, item_code, warehouse_code , user_account,SerialNo, cjr_id, transaction_id,Transaction_Type,transaction_date
		from bookingQty where cjr_id =  $txtCorrugatorId";
		$resultbooking = mysqli_query($connection, $qbooking) or die(mysqli_error($connection));

	header("Location: ./Form_Corrugator.php?actionmode=" . $mode . "&txtCorrugatorId=" . $txtCorrugatorId . "&message=" . $message . "&mainaction=" . $mainaction);
}


if ($mode == "post" && $vvMode == 'JobCardDT') {
	$querycheckp = "select count(*) as vcountp from temp_corrugator1 where corrugator_id=$txtCorrugatorId";
	$resultcheckp = mysqli_query($connection, $querycheckp) or die(mysqli_error($connection));
	$rowcheckp = mysqli_fetch_array($resultcheckp);
	if (is_array($rowcheckp)) extract($rowcheckp);
	//  echo $querycheckp;echo $vcount ;exit;
	if ($vcountp == 0) {
		$queryimpins = " INSERT INTO temp_corrugator1 (select * from corrugator1 where corrugator_id=$txtCorrugatorId )";
		//echo $queryimpins;echo"<br>";
		$resultimpins = mysqli_query($connection, $queryimpins);
	}
	$queryselectimp = "select corrugator_id as corrugatorid from temp_corrugator1 where corrugator_id=$txtCorrugatorId";
	$resultselectimp = mysqli_query($connection, $queryselectimp) or die(mysqli_error($connection));
	$rowselectimp = mysqli_fetch_array($resultselectimp);
	if (is_array($rowselectimp)) extract($rowselectimp);



	$queryimp = "insert into temp_upperlowerstacker(corrugatorid,salesref,masterref,plannedqty,producedqty,remainingqty,boxsizelength,boxsizewidth,boxsizeheight,stdpapertl,stdpaperfl,stdpaperwtl, BorderNeededQty)
values(" . GetSQLValueString($corrugatorid, 'double', $connection) . "," . GetSQLValueString($txtSalesRef, 'double', $connection) . "," . GetSQLValueString($txtMasterRef, 'text', $connection) . "," . GetSQLValueString($txtPlannedQty, 'double', $connection) . "," . GetSQLValueString($txtProducedQty, 'double', $connection) . "
," . GetSQLValueString($txtRemainingQty, 'double', $connection) . "," . GetSQLValueString($txtBoxSizeL, 'double', $connection) . "," . GetSQLValueString($txtBoxSizeW, 'double', $connection) . "," . GetSQLValueString($txtBoxSizeH, 'double', $connection) . ",
" . GetSQLValueString($txtStdPaperTL, 'double', $connection) . "," . GetSQLValueString($txtStdPaperFL, 'double', $connection) . ",
" . GetSQLValueString($txtStdPaperWTL, 'double', $connection) . ", " . GetSQLValueString($BorderNeededQty, 'double', $connection) . ")";
	// echo $queryimp;exit;
	$resultimp = mysqli_query($connection, $queryimp);


	header("Location: ./Form_Corrugator.php?actionmode=submitrecord&mainaction=" . $mainmode . "&message=" . $message . "&txtCorrugatorId=" . $txtCorrugatorId);
}


//Load KeyLine
if ($mainmode == "loadJobCard" && $JobCard_Id != '') {

	$query = "select count(*) from temp_corrugator1 where corrugator_id=$txtCorrugatorId";
	//echo$query;exit;
	$result = mysqli_query($connection, $query);
	$rowss  = mysqli_fetch_array($result);
	$countCjr = $rowss[0];
	//echo$countCjr;exit;
	if ($countCjr == 0) {
		if ($count == 1) {

			fbegin($connection);
			$corrugator_id = get_nextsequence1('now()', 'corrugator_id', '', $connection);
			fcommit($connection);

			$query = " insert into temp_corrugator1 (corrugator_id,jobcardid,corrugator_date) 
			values (" . GetSQLValueString($corrugator_id, 'double', $connection) . "," . GetSQLValueString($JobCard_Id, 'double', $connection) . ",now())";
			$result = mysqli_query($connection, $query)  or die(mysqli_error($connection));

			$txtCorrugatorId = $corrugator_id;
		} else {

			$query = " update  temp_corrugator1 set 
			jobcardid2 = " . GetSQLValueString($JobCard_Id, 'int', $connection) . " where corrugator_id='$txtCorrugatorId'";
			$result = mysqli_query($connection, $query)  or die(mysqli_error($connection));
			//echo$query ;exit;


		}
	} else {

		$query = "select jobcardid,jobcardid2 from temp_corrugator1 where corrugator_id=$txtCorrugatorId";
		//echo$query;exit;
		$result = mysqli_query($connection, $query);
		$rowss  = mysqli_fetch_array($result);
		$jobcardid  = $rowss[0];
		$jobcardid2 = $rowss[1];

		if ($jobcardid == "") {
			$query = " update  temp_corrugator1 set 
			jobcardid = " . GetSQLValueString($JobCard_Id, 'int', $connection) . " where corrugator_id='$txtCorrugatorId'";
			//echo$query;exit;
			$result = mysqli_query($connection, $query)  or die(mysqli_error($connection));
		} else 
		if ($jobcardid2 == "") {
			$query = " update  temp_corrugator1 set 
			jobcardid2 = " . GetSQLValueString($JobCard_Id, 'int', $connection) . " where corrugator_id='$txtCorrugatorId'";
			$result = mysqli_query($connection, $query)  or die(mysqli_error($connection));
		}
	}



	fbegin($connection);
	$ulstacker_id = get_nextsequence1('now()', 'ulstacker_id', '', $connection);
	fcommit($connection);

	$queryGdQty = " select sum(ifNull(goodqty,0)) ". 
								" from corrudt ".
								" left join sorder  on sorder.Sorder_id=corrudt.related_sorderId  ". 
								" left join jobcard on sorder.Sorder_id=jobcard.related_salesorder  ". 
								" where jobcard.jobcard_Id='$JobCard_Id'  ".
								" group by sorder.Sorder_id  ";
	$resultGdQty = mysqli_query($connection, $queryGdQty);
	$rowsGdQty   = mysqli_fetch_array($resultGdQty);
	$GdQty       = $rowsGdQty[0];

	$qinsert2 = " Insert into temp_upperlowerstacker (ulstacker_id,corrugatorid, salesref,masterref, plannedqty, producedqty,remainingqty, customer,clientCode,itemdesc,
																											boxtype,flutetype,scoringtype,outsideliner,insideliner,flutting2,stdgsm,boardneed,jobCardRef,jobcardid,BorderNeededQty ) 
										Select $ulstacker_id,$txtCorrugatorId,  Sorder_reference, sorderdt.item_code, NULL, " . GetSQLValueString($GdQty, 'int', $connection) . ", NULL, clients.ledger_name, clients.ledger_number,mastercard.product_desc,
										mastercard.mastercard_BoxtypeId,mastercard.mastercard_FluteTypeId,mastercard.scoringtype,mastercard.outerlinercolor,mastercard.innerlinercolor,
										papercombinations.temp_flutting2,jobcard.jobcard_gsm,jobcard.jobcard_qtyrequested,JobCard_Reference,$JobCard_Id,jobcard.jobcard_qtyrequested/mastercard_NumOfOuts
				from  jobcard 

				left join mastercard on mastercard.mastercard_id=jobcard.related_mastercard

				left join papercombinations on papercombinations.mastercard_id=mastercard.mastercard_id

				left join clients on clients.ledger_number = mastercard.ledger_number

				left join sorder on sorder.Sorder_id=jobcard.related_salesorder

				left join sorderdt on sorderdt.Sorder_id=sorder.Sorder_id

				left join boxtype on mastercard.mastercard_BoxTypeId = boxtype.boxtype_id

				left join flutetype on flutetype.FluteType_code = mastercard.mastercard_flutetypeid

				where jobcard_id=" . $JobCard_Id . "  order by jobcard_date desc limit 1";
// echo$qinsert2;exit;
	mysqli_query($connection, $qinsert2) or die(mysqli_error($connection));
	$message = mysqli_error($connection);

	fbegin($connection);
	$corgrid_id = get_nextsequence1('now()', 'corgrid_id', '', $connection);
	fcommit($connection);

	$qinsert = " insert into temp_corgrid (corgrid_id,`length`, width,flaptop, height, flapbot, numouts, twidth, numcuts,actualcuts,reeldeckle,totwidth,
																		trimming, trimwaste, lm,  corref, salesref,  jobcardid, sq, corrugatorid,ulstacker_id  ) 

										Select $corgrid_id,ifNull(jobcard_grossheight,0), ifNull(jobcard_grosswidth,0), ifNull(jobcard_scoring1,0), ifNull(jobcard_scoring2,0)
														, ifNull(jobcard_scoring3,0), null, null, null, null, null, null, null, null, null, null, sorder_reference,
																 $JobCard_Id, null, $txtCorrugatorId,$ulstacker_id
																	from jobcard
																		left join sorder on sorder.sorder_id= jobcard.related_salesorder
																		where jobcard.jobcard_id=$JobCard_Id order by jobcard_date desc limit 1";

	mysqli_query($connection, $qinsert) or die(mysqli_error($connection));
	$message = mysqli_error($connection);

	// $lastid = "SELECT corrugator_id,jobcardid,corrugator_reference,date_format(corrugator_date, '%d/%m/%Y'),corrugator_confirm,corrugator_approve from temp_corrugator1
	// 				 where jobcardid = $JobCard_Id ";
	//  $resultid = mysqli_query($connection, $lastid);
	//  $rowid = mysqli_fetch_array($resultid);
	// echo $lastid;exit;

	header("Location: ./Form_LoadJobCard.php?PMode=1&JobCardId=" . $JobCard_Id . "&txtCorrugatorId=" . $txtCorrugatorId . "&corrugatorRef=" . "&message=" . $message . "&count=" . $count);

	//"&corRef=".$rowid[1]

}
//&& $ulstacker_id!=''
if ($mode == "deletedt" && $Corrugator_Id != '') {
	$message  = '';
	$deletedt = "DELETE FROM temp_upperlowerstacker WHERE corrugatorid = $Corrugator_Id AND ulstacker_id = $ulstacker_id";

	// $deletedt;exit;
	$resultdt = mysqli_query($connection, $deletedt);
	$message = mysqli_error($connection);
	header("Location: ./Form_Corrugator.php?message=" . $message . "&txtCorrugatorId=" . $Corrugator_Id);
}

if ($mainmode == "deletefromtemp" && $txtCorrugatorId != '') {
	$message  = '';
	$deletetemp = "DELETE FROM temp_corrugator1 WHERE corrugator_id = $txtCorrugatorId";
	// $deletedt;exit;
	$resultdt = mysqli_query($connection, $deletetemp);

	$deletetemp = "DELETE FROM temp_upperlowerstacker WHERE corrugatorid = $txtCorrugatorId  ";
	$resultdt = mysqli_query($connection, $deletetemp);

	$deletetemp = "DELETE FROM temp_corgrid WHERE corrugatorid = $txtCorrugatorId ";
	$resultdt = mysqli_query($connection, $deletetemp);

	$deletetemp = "DELETE FROM temp_locationtable WHERE corrugatorid = $txtCorrugatorId";
	$resultdt = mysqli_query($connection, $deletetemp);

	$message = mysqli_error($connection);
	header("Location: ./Form_Corrugator.php?");
}

if ($mode == "deleteUpperLower" && $txtCorrugatorId != '') {
	$message  = '';

	$query="select jobcardid from temp_upperlowerstacker where ulstacker_id='$ulstacker_id'";
	$result=mysqli_query($connection, $query);
	$rows=mysqli_fetch_array($result);
	$jobcardid=$rows[0];

	$query2="select jobcardid,jobcardid2 from temp_corrugator1  WHERE corrugator_id = $txtCorrugatorId";
	$result2=mysqli_query($connection, $query2);
	$rows2=mysqli_fetch_array($result2);
	$jobcard_id=$rows2[0];
	$jobcard_id2=$rows2[1];


	if ($jobcard_id == $jobcardid && $index==0) {
		$update = "Update  temp_corrugator1 set jobcardid = NULL  WHERE corrugator_id = $txtCorrugatorId";
		//echo$update;exit;
		$resultdt = mysqli_query($connection, $update);
	}

	if ($jobcard_id2 == $jobcardid && $index==1) {
		$update = "Update  temp_corrugator1 set jobcardid2 = NULL  WHERE corrugator_id = $txtCorrugatorId";
		//echo$update;exit;
		$resultdt = mysqli_query($connection, $update);
	}


	$deletedt = "DELETE FROM temp_upperlowerstacker where ulstacker_id='$ulstacker_id'  ";
	$resultdt = mysqli_query($connection, $deletedt);

	$deletedt1 = "DELETE FROM temp_corgrid WHERE corrugatorid = $txtCorrugatorId  and jobcardid = '$JobCardId' and ifNull(ulstacker_id,'')='$ulstacker_id'";
	//echo$deletedt;exit;
	$resultdt1 = mysqli_query($connection, $deletedt1);

	if ($jobcard_id == '' && $jobcard_id2 == '') {
		$deletedt = "DELETE FROM temp_locationtable WHERE corrugatorid = $txtCorrugatorId";
		//echo $deletedt;exit;
		$resultdt = mysqli_query($connection, $deletedt);
	}



	$message = mysqli_error($connection);
	header("Location: ./Form_Corrugator.php?message=" . $message . "&txtCorrugatorId=" . $txtCorrugatorId . "&count=" . $count . "&txtCorrugatorRef=" . $txtCorrugatorRef);
}



if ($mode == "deletedtpaper" && $Corrugator_Id != '' && $corgrid_id != '') {
	$message  = '';
	$deletedtpaper = "DELETE FROM temp_corgrid WHERE  corgrid_id = $corgrid_id";
	//corrugatorid = $Corrugator_Id AND
	//echo $deletedtpaper;exit;
	$resultdtpaper = mysqli_query($connection, $deletedtpaper);
	$message = mysqli_error($connection);
	header("Location: ./Form_Corrugator.php?message=" . $message . "&txtCorrugatorId=" . $Corrugator_Id . "&count=" . $count);
}


if ($mode == "deletedtpaperold" && $Corrugator_Id != '' && $loc_id != '') {
	$message  = '';
	$deletedtpaperold = "DELETE FROM temp_locationtable WHERE  loc_id = $loc_id";
	//corrugatorid = $Corrugator_Id AND
	//echo $deletedtpaperold;exit;
	$resultdtpaperold = mysqli_query($connection, $deletedtpaperold);
	$message = mysqli_error($connection);
	header("Location: ./Form_Corrugator.php?message=" . $message . "&txtCorrugatorId=" . $Corrugator_Id . "&count=" . $count);
}

//Delete Record Mode From Main Tables
if ($mode == "" && $mainmode == "delete" && $jobcard_id != '') {
// echo"test1";exit;
	$DeleteAllow = 1;
	$message  = '';
	$delete = "Delete from jobcard where jobcard_id =$jobcard_id ";
	$result = mysqli_query($connection, $delete);
	$message = mysqli_error($connection);
	header("Location: ./Form_JobCardSearch.php?message=" . $message);
}

if ($mainmode == "delete" && $corrugator_id != '') {
// echo"test2";exit;
	$DeleteAllow = 1;
	$message  = '';
	$company = explode("_", $_SESSION['dbName']);
	$currCompany = strtoupper($company[0]);

	$query="select ifNull(Relatedcjr_id,'') from corruHeader
	left join corrugator1 on corrugator1.corrugator_id=corruheader.Relatedcjr_id
	where corrugator1.corrugator_id=$corrugator_id";
	//echo$query;exit;

	//where  corrugator_id='$Relatedcjr_id'
	$result=mysqli_query($connection, $query);
	$rowsCjr = mysqli_fetch_array($result);
	$Relatedcjr_id=$rowsCjr[0];

	if($Relatedcjr_id==''){
		$DeleteAllow = 1;
	}else{
		$DeleteAllow = 0;
	}

	//echo"test_".$DeleteAllow;exit;

	if($DeleteAllow ==1){
	if ($config['log_file'] == 1) {
		$transType='D';
		$query_insertToHistory = "call " . $config['dbName_history'] . ".InsertToHistory('$transType', '$corrugator_id', '" . $_SESSION['useraccount'] . "', now(), '" . $_SESSION['dbName'] . "', 'cjr', '$currCompany')";
		//echo $query_insertToHistory;exit;
		$result_insertToHistory = mysqli_query($connection, $query_insertToHistory);
		while (mysqli_more_results($connection) && mysqli_next_result($connection)) {
		}
	}

	$queryDelBQ   = "Delete from bookingqty where cjr_id =$corrugator_id ";
	$resultDelBQ  = mysqli_query($connection, $queryDelBQ);

	$queryDel1    = "DELETE FROM corgrid WHERE corrugatorid = $corrugator_id ";
	$resultDel1   = mysqli_query($connection, $queryDel1);

	$queryDel2    = "DELETE FROM upperlowerstacker WHERE corrugatorid = $corrugator_id  ";
	$resultDel2   = mysqli_query($connection, $queryDel2);

	$queryDel3    = "DELETE FROM locationtable WHERE corrugatorid = $corrugator_id";
	$resultDel3   = mysqli_query($connection, $queryDel3);

	$queryDel4    = "Delete from corrugator1 where corrugator_id =$corrugator_id ";
	$resultDel4 = mysqli_query($connection, $queryDel4);


	$message = mysqli_error($connection);
	}else{
		$message = "CJR cannot be deleted because it is already used in Corrugator.";
	}
	header("Location: ./Form_CorrugatorSearch.php?message=" . $message);
}



if ($mode == "postpaper" && $vvMode == 'JobCardDT') {
	fbegin($connection);
	$ulstacker_id = get_nextsequence1('now()', 'ulstacker_id', '', $connection);
	fcommit($connection);

	$queryimp = "insert into temp_upperlowerstacker(ulstacker_id,corrugatorid,salesref,masterref,plannedqty,producedqty,remainingqty, 
	customer,clientCode, itemdesc, boxtype, flutetype, scoringtype, outsideliner, insideliner, flutting2, stdgsm, boardneed,jobCardRef,jobcardid,BorderNeededQty)
values(" . GetSQLValueString($ulstacker_id, 'int', $connection) . "," . GetSQLValueString($txtCorrugatorId, 'double', $connection) . "," . GetSQLValueString($txtSalesRef, 'double', $connection) . "," . GetSQLValueString($txtMasterRef, 'text', $connection) . "," . GetSQLValueString($txtPlannedQty, 'double', $connection) . "," . GetSQLValueString($txtProducedQty, 'double', $connection) . "
," . GetSQLValueString($txtRemainingQty, 'double', $connection) . "," . GetSQLValueString($txtClient, 'text', $connection) . "," . GetSQLValueString($clientCode, 'text', $connection) . "," . GetSQLValueString($txtItemDesc, 'text', $connection) . "," . GetSQLValueString($txtBoxType, 'double', $connection) . "," . GetSQLValueString($txtFluteType, 'double', $connection) . "
," . GetSQLValueString($txtScoringType, 'text', $connection) . "," . GetSQLValueString($txtOutsideLiner, 'text', $connection) . "," . GetSQLValueString($txtInsideLiner, 'text', $connection) . "," . GetSQLValueString($txtFlutting2, 'double', $connection) . "," . GetSQLValueString($txtStdGsm, 'double', $connection) . "," . GetSQLValueString($txtBoardNeed, 'double', $connection) . "," . GetSQLValueString($txtJobCardRef, 'double', $connection) . ",
" . GetSQLValueString($JobCardId, 'int', $connection) . "," . GetSQLValueString($BorderNeededQty, 'double', $connection) . " )";
	//  echo $queryimp;exit;
	$resultimp = mysqli_query($connection, $queryimp);

	fbegin($connection);
	$corgrid_id = get_nextsequence1('now()', 'corgrid_id', '', $connection);
	fcommit($connection);

	$queryimppaper = "insert into temp_corgrid (
	corgrid_id,
	corrugatorid,
	corref,
	salesref,
	length,
	width, 
	flaptop,
	height,
	flapbot,
	numouts,
	twidth,
 numcuts,
 actualcuts,
 reeldeckle,
 totwidth,
 trimming,
 trimwaste,
	lm, 
	sq ,
	jobcardid)
values(" . GetSQLValueString($corgrid_id, 'int', $connection) . "," . GetSQLValueString($txtCorrugatorId, 'int', $connection) . "," . GetSQLValueString($txtCorrugatorRef, 'double', $connection) . "," . GetSQLValueString($txtSalesRef, 'double', $connection) . "," . GetSQLValueString($txtLength, 'double', $connection) . "," . GetSQLValueString($txtWidth, 'double', $connection) . "
," . GetSQLValueString($txtFlapTop, 'double', $connection) . "," . GetSQLValueString($txtHeight, 'double', $connection) . "," . GetSQLValueString($txtFlapBot, 'double', $connection) . "
," . GetSQLValueString($txtOuts, 'double', $connection) . "," . GetSQLValueString($txtTWidth, 'double', $connection) . "," . GetSQLValueString($txtTCuts, 'double', $connection) . "
," . GetSQLValueString($txtActualCuts, 'double', $connection) . "," . GetSQLValueString($txtReelDec, 'double', $connection) . "," . GetSQLValueString($txtTotWidth, 'double', $connection) . "
," . GetSQLValueString($txtTrimming, 'double', $connection) . "," . GetSQLValueString($txtTrimWaste, 'double', $connection) . "," . GetSQLValueString($txtLM, 'double', $connection) . "," . GetSQLValueString($txtSQ, 'double', $connection) . "," . GetSQLValueString($JobCardId, 'int', $connection) . ")";

	$resultimppaper = mysqli_query($connection, $queryimppaper);
	// echo $queryimppaper;
	// exit;

	// $queryupdatedtpaper = "UPDATE temp_corgrid SET
	// reeldeckle = " . GetSQLValueString($txtReelDec, 'double', $connection) . 

	// " where corref='$txtCorrugatorRef'";

	$queryupdatedt = "UPDATE temp_corrugator1 SET
reeldeckle = " . GetSQLValueString($txtReelDec, 'double', $connection) . ",
totwidth  = " . GetSQLValueString($txtTotWidth, 'double', $connection) . ",
trimming = " . GetSQLValueString($txtTrimming, 'double', $connection) . ",

upperpaperrequiredT = " . GetSQLValueString($txtURequiredT, 'double', $connection) . ",
upperpaperactualT  = " . GetSQLValueString($txtUActualT, 'double', $connection) . ",
lowerpaperrequiredT = " . GetSQLValueString($txtLRequiredT, 'double', $connection) . ",
lowerpaperactualT = " . GetSQLValueString($txtLActualT, 'double', $connection) . ",
totURequiredT  = " . GetSQLValueString($txtTRequiredT, 'double', $connection) . ",
totUactualT = " . GetSQLValueString($txtTActualT, 'double', $connection) . ",
paperinner = " . GetSQLValueString($txtIPaper, 'double', $connection) . ",
paperflutting  = " . GetSQLValueString($txtFPaper, 'double', $connection) . ",
paperouter = " . GetSQLValueString($txtOPaper, 'double', $connection) . ",
paperinner = " . GetSQLValueString($txtIPaper, 'double', $connection) . ",
paperflutting  = " . GetSQLValueString($txtFPaper, 'double', $connection) . ",
paperouter = " . GetSQLValueString($txtOPaper, 'double', $connection) . ",
balanceinner = " . GetSQLValueString($txtIBalance, 'double', $connection) . ",
balanceflutting  = " . GetSQLValueString($txtFBalance, 'double', $connection) . ",
balanceouter = " . GetSQLValueString($txtOBalance, 'double', $connection) . ",
wtpcupper = " . GetSQLValueString($txtWTPCU, 'double', $connection) . ",
wtpclower  = " . GetSQLValueString($txtWTPCL, 'double', $connection) . ",
totalgsm = " . GetSQLValueString($txtTotalGSM, 'double', $connection) . ",
trimsizeT = " . GetSQLValueString($txtSizeTrimT, 'double', $connection) . ",
trimrequiredT  = " . GetSQLValueString($txtRequiredTrimT, 'double', $connection) . ",
trimactualT = " . GetSQLValueString($txtActualTrimT, 'double', $connection) . ",
trimwasteper = " . GetSQLValueString($txtTrimWaste, 'double', $connection) . "

where corrugator_id=" . GetSQLValueString($txtCorrugatorId, 'int', $connection) . "";
	$result = mysqli_query($connection, $queryupdatedt);

	header("Location: ./Form_Corrugator.php?actionmode=submitrecord&mainaction=" . $mainmode . "&message=" . $message . "&txtCorrugatorId=" . $txtCorrugatorId . "&count=" . $count . "&txtCorrugatorRef=" . $txtCorrugatorRef);
}

if ($mode == "postpaperold" && $vvMode == 'JobCardDT') {

	//echo $queryselectimppaper;
	$corrIdArr = explode(',', $txtCorrugatorId);

	$corrId = $corrIdArr[0];
	$corrId2 = $corrIdArr[1];


	$corrugator_date_ = explodewordrequest($corrugator_date);

	$query  =" select count(*) from temp_Locationtable where corrugatorid=$txtCorrugatorId and itemtype='".$txtType."'";
	$result = selectData($connection, $query);
	$countLc = $result[0];

	if($countLc==0){
	fbegin($connection);
	$loc_id = get_nextsequence1('now()', 'loc_id', '', $connection);
	fcommit($connection);
	$Qry_QtyExists = " SELECT ifnull(QtyExists('$txtItemCode', '$txtWareHouse', '" . $corrugator_date_ . "', $txtCorrugatorId, 'SO', '', ''),0) As QtyExists ";
	$result = selectData($connection, $Qry_QtyExists);
	$StockQty = $result[0];
	$txtLRequiredT = $_POST['txtLRequiredT'];
	$txtURequiredT = $_POST['txtURequiredT'];
	$txtTRequired = $txtURequired+$txtLRequired;
	$queryimppaperold = "insert into temp_locationtable (loc_id,
 corrugatorid,corrugatorid2, location, gsm, papergradle, relldeckle, papermill, supplier,

 upperpaperrequired, upperpaperrequiredT,
	upperpaperactual,   upperpaperactualT, 
	lowerpaperrequired,  lowerpaperrequiredT,
	 lowerpaperactual,    lowerpaperactualT, 
	 totURequired,    totURequiredT, 
	 totUactual,    totUactualT, 

 paperinner, paperflutting, paperouter, balanceinner, balanceflutting, balanceouter, wtpcupper, wtpclower, totalgsm, 

 trimsize,  trimsizeT,
 trimrequired,  trimrequiredT, 
 trimactual, trimactualT,

	totalwasterequired, totalwasteactual,itemCode,itemDesc,trimwasteper,warehouse_code,itemType,StockQty)
values(" . GetSQLValueString($loc_id, 'int', $connection) . "," . GetSQLValueString($corrId, 'double', $connection) . "," . GetSQLValueString($corrId2, 'double', $connection) . "," . GetSQLValueString($txtLocation, 'text', $connection) . "," . GetSQLValueString($txtGSM, 'double', $connection) . "
," . GetSQLValueString($txtPaperGrade, 'text', $connection) . "," . GetSQLValueString($txtRellDeckle, 'double', $connection) . "," . GetSQLValueString($txtPaperMill, 'text', $connection) . "
," . GetSQLValueString($txtSupplier, 'text', $connection) . ",

" . GetSQLValueString($txtURequired, 'double', $connection) . "," . GetSQLValueString($txtURequiredT, 'double', $connection) . ",
" . GetSQLValueString($txtUActual, 'double', $connection) . "," . GetSQLValueString($txtUActualT, 'double', $connection) . ",
" . GetSQLValueString($txtLRequired, 'double', $connection) . "," . GetSQLValueString($txtLRequiredT, 'double', $connection) . ",
" . GetSQLValueString($txtLActual, 'double', $connection) . "," . GetSQLValueString($txtLActualT, 'double', $connection) . ",
" . GetSQLValueString($txtTRequired, 'double', $connection) . "," . GetSQLValueString($txtTRequiredT, 'double', $connection) . ",
" . GetSQLValueString($txtTActual, 'double', $connection) . "," . GetSQLValueString($txtTActualT, 'double', $connection) . ",

" . GetSQLValueString($txtIPaper, 'double', $connection) . "," . GetSQLValueString($txtFPaper, 'double', $connection) . "
," . GetSQLValueString($txtOPaper, 'double', $connection) . "," . GetSQLValueString($txtIBalance, 'double', $connection) . "," . GetSQLValueString($txtFBalance, 'double', $connection) . "
," . GetSQLValueString($txtOBalance, 'double', $connection) . "," . GetSQLValueString($txtWTPCU, 'double', $connection) . "," . GetSQLValueString($txtWTPCL, 'double', $connection) . "
," . GetSQLValueString($txtTotalGSM, 'double', $connection) . ",

" . GetSQLValueString($txtSizeTrim, 'double', $connection) . "," . GetSQLValueString($txtSizeTrimT, 'double', $connection) . ",
" . GetSQLValueString($txtRequiredTrim, 'double', $connection) . "," . GetSQLValueString($txtRequiredTrimT, 'double', $connection) . ",
" . GetSQLValueString($txtActualTrim, 'double', $connection) . "," . GetSQLValueString($txtActualTrimT, 'double', $connection) . ",


" . GetSQLValueString($txtTotalRequired, 'double', $connection) . "," . GetSQLValueString($txtTotalActual, 'double', $connection) . ",
" . GetSQLValueString($txtItemCode, 'text', $connection) . "," . GetSQLValueString($txtItemDesc, 'text', $connection) . "," . GetSQLValueString($txtTrimWaste, 'double', $connection) . ",
" . GetSQLValueString($txtWareHouse, 'text', $connection) . "," . GetSQLValueString($txtType, 'text', $connection) . "," . GetSQLValueString($StockQty, 'double', $connection) . " )";
	// $txtItemCode=$_GET["txtItemCode"];
	// $txtItemDesc=$_GET["txtItemDesc"]; 
	//  echo $queryimppaperold;exit;
	$resultimppaperold = mysqli_query($connection, $queryimppaperold);
	//echo $queryimppaperold;exit;

$txtLRequiredT = $_POST['txtLRequiredT'];
$txtURequiredT = $_POST['txtURequiredT'];
$txtWTPCU = $_POST['txtWTPCU'];
$txtWTPCL =  $_POST['txtWTPCL'];
$txtTotalGSM = $_POST['txtTotalGSM'];
	$queryupdatedt = "UPDATE temp_corrugator1 SET
upperpaperrequiredT = " . GetSQLValueString($txtURequiredT, 'double', $connection) . ",
upperpaperactualT  = " . GetSQLValueString($txtUActualT, 'double', $connection) . ",
lowerpaperrequiredT = " . GetSQLValueString($txtLRequiredT, 'double', $connection) . ",

lowerpaperactualT = " . GetSQLValueString($txtLActualT, 'double', $connection) . ",
totURequiredT  = " . GetSQLValueString($txtTRequiredT, 'double', $connection) . ",
totUactualT = " . GetSQLValueString($txtTActualT, 'double', $connection) . ",

paperinner = " . GetSQLValueString($txtIPaper, 'double', $connection) . ",
paperflutting  = " . GetSQLValueString($txtFPaper, 'double', $connection) . ",
paperouter = " . GetSQLValueString($txtOPaper, 'double', $connection) . ",

paperinner = " . GetSQLValueString($txtIPaper, 'double', $connection) . ",
paperflutting  = " . GetSQLValueString($txtFPaper, 'double', $connection) . ",
paperouter = " . GetSQLValueString($txtOPaper, 'double', $connection) . ",

balanceinner = " . GetSQLValueString($txtIBalance, 'double', $connection) . ",
balanceflutting  = " . GetSQLValueString($txtFBalance, 'double', $connection) . ",
balanceouter = " . GetSQLValueString($txtOBalance, 'double', $connection) . ",

wtpcupper = " . GetSQLValueString($txtWTPCU, 'double', $connection) . ",
wtpclower  = " . GetSQLValueString($txtWTPCL, 'double', $connection) . ",
totalgsm = " . GetSQLValueString($txtTotalGSM, 'double', $connection) . ",

trimsizeT = " . GetSQLValueString($txtSizeTrimT, 'double', $connection) . ",
trimrequiredT  = " . GetSQLValueString($txtRequiredTrimT, 'double', $connection) . ",
trimactualT = " . GetSQLValueString($txtActualTrimT, 'double', $connection) . ",

trimwasteper = " . GetSQLValueString($txtTrimWaste, 'double', $connection) . " 
 where corrugator_id=" . GetSQLValueString($txtCorrugatorId, 'int', $connection) . "";
	//echo$queryupdatedt;exit;
	$result = mysqli_query($connection, $queryupdatedt);
	}else{
		$message=$txtType.' already exist on this CJR';
	}
	header("Location: ./Form_Corrugator.php?actionmode=submitrecord&mainaction=" . $mainmode . "&message=" . $message . "&txtCorrugatorId=" . $txtCorrugatorId . "&count=" . $count);
}



if ($mode == "update" && $txtCorrugatorId != '' && $ulstacker_id != '') {

	$ulstacker_id = $_GET['ulstacker_id'];

	$queryupdatedt = "UPDATE temp_upperlowerstacker SET
	plannedqty = " . GetSQLValueString($txtPlannedQty, 'double', $connection) . ",
	producedqty = " . GetSQLValueString($txtProducedQty, 'double', $connection) . ",
	remainingqty = " . GetSQLValueString($txtRemainingQty, 'double', $connection) . ",
	boxsizelength = " . GetSQLValueString($txtBoxSizeL, 'double', $connection) . ",
	boxsizewidth = " . GetSQLValueString($txtBoxSizeW, 'double', $connection) . ",
	boxsizeheight = " . GetSQLValueString($txtBoxSizeH, 'double', $connection) . ",
	stdpapertl = " . GetSQLValueString($txtStdPaperTL, 'double', $connection) . " ,
	stdpaperfl = " . GetSQLValueString($txtStdPaperFL, 'double', $connection) . " ,
	stdpaperwtl = " . GetSQLValueString($txtStdPaperWTL, 'double', $connection) . ",
	BorderNeededQty= " . GetSQLValueString($BorderNeededQty, 'double', $connection) . "
	where ulstacker_id='$ulstacker_id'";
	// echo $queryupdatedt;exit;
	mysqli_query($connection, $queryupdatedt) or die(mysqli_error($connection));
	$message = mysqli_error($connection);


	header("Location: ./Form_Corrugator.php?message=" . $message . "&txtCorrugatorId=" . $txtCorrugatorId);
}

if ($mode == 'SubmitReelDec' && $txtCorrugatorId != '') {
 $queryupdatedt = "UPDATE temp_corrugator1 SET
	reeldeckle = " . GetSQLValueString($txtReelDec, 'double', $connection) . "
	,trimming = " . GetSQLValueString($txtTrimming, 'double', $connection) . "
	 ,totwidth = " . GetSQLValueString($txtTotWidth, 'double', $connection) . "
	where corrugator_id=" . GetSQLValueString($txtCorrugatorId, 'int', $connection) . "";
	//echo $queryupdatedt;exit;
	$result = mysqli_query($connection, $queryupdatedt) or die(mysqli_error($connection));

	for ($i = 0; $i < 2; $i++) {
		$txtSQ = $_POST['txtSQ'][$i];
		$txtCorRef = $_POST['txtCorRef'][$i];

		$queryupdatedtpaper = "UPDATE temp_corgrid SET
		sq= " . GetSQLValueString($txtSQ, 'double', $connection) . " where corgrid_id='" . $txtCorRef . "' ";
		//echo $queryupdatedtpaper;//exit;
		mysqli_query($connection, $queryupdatedtpaper) or die(mysqli_error($connection));
		$message = mysqli_error($connection);
	}
	//exit;


	header("Location: ./Form_Corrugator.php?message=" . $message . "&txtCorrugatorId=" . $txtCorrugatorId . "&count=" . $count);
}

if($mainmode == "updateStatus" && $txtCorrugatorId != ''){

	// $querye = "Select Count(Job_order) From corrugator1 where Job_order = '" . $Job_order . "'";
	// $resulte = selectData($connection, $querye);
	// $ExistIne = $resulte[0];

	if(empty($Job_order)){
		$Job_order=0;
	}
	//if($ExistIne == 0 ){
					$query = "update corrugator1 set Job_order = $Job_order where  corrugator_id='$txtCorrugatorId'";
					$result = mysqli_query($connection, $query);
	// }else{
	//   $message = "Job order Reference already exist!";
	// }
		 header("Location: ./Form_CJRtrack.php?txtCorrugatorId=".$corrugator_id."&message=".$message."&fromdate=".$fromdate."&todate=".$todate);



}
if ($mode == "updatepaper" && $txtCorrugatorId != '' && $corgrid_id != '') {
		//echo"test";exit;

	$queryupdatedtpaper = "UPDATE temp_corgrid SET
	 temp_corgrid.length = " . GetSQLValueString($txtLength, 'double', $connection) . ",
	width = " . GetSQLValueString($txtWidth, 'double', $connection) . ",
	flaptop = " . GetSQLValueString($txtFlapTop, 'double', $connection) . ",
	height = " . GetSQLValueString($txtHeight, 'double', $connection) . ",
	flapbot = " . GetSQLValueString($txtFlapBot, 'double', $connection) . ",
	numouts = " . GetSQLValueString($txtOuts, 'double', $connection) . ",
	twidth = " . GetSQLValueString($txtTWidth, 'double', $connection) . ",
	numcuts = " . GetSQLValueString($txtTCuts, 'double', $connection) . ",
	actualcuts = " . GetSQLValueString($txtActualCuts, 'double', $connection) . ",
	reeldeckle = " . GetSQLValueString($txtReelDec, 'double', $connection) . ",
	totwidth = " . GetSQLValueString($txtTotWidth, 'double', $connection) . ",
	trimming = " . GetSQLValueString($txtTrimming, 'double', $connection) . ",
	trimwaste= " . GetSQLValueString($txtTrimWaste, 'text', $connection) . ",
	lm= " . GetSQLValueString($txtLM, 'double', $connection) . ",
	corref = " . GetSQLValueString($txtCorrugatorRef, 'double', $connection) . ",
	salesref=" . GetSQLValueString($txtSalesRef, 'double', $connection) . ",

	sq= " . GetSQLValueString($txtSQ, 'double', $connection) .
		" where corgrid_id='$corgrid_id' ";

	 // echo$queryupdatedtpaper;exit;
	mysqli_query($connection, $queryupdatedtpaper) or die(mysqli_error($connection));
	$message = mysqli_error($connection);

	if($ulstacker_id==''){
		$PIndex = $_GET["PIndex"];
		// var_dump($_POST);
		// var_dump($_GET);
		$ulstacker_id = $_POST['upperlowerid'][$PIndex];
	}

	$queryupdatedt = "UPDATE temp_upperlowerstacker
	 SET 
			 salesref = " . GetSQLValueString($txtSalesRef, 'double', $connection) . ",
			 masterref = " . GetSQLValueString($txtMasterRef, 'text', $connection) . ",
			 plannedqty = " . GetSQLValueString($txtPlannedQty, 'double', $connection) . ",
			 producedqty = " . GetSQLValueString($txtProducedQty, 'double', $connection) . ",
			 remainingqty = " . GetSQLValueString($txtRemainingQty, 'double', $connection) . ",
			 customer = " . GetSQLValueString($txtClient, 'text', $connection) . ",
			 clientCode = " . GetSQLValueString($clientCode, 'text', $connection) . ",
			 itemdesc = " . GetSQLValueString($txtItemDesc, 'text', $connection) . ",
			 boxtype = " . GetSQLValueString($txtBoxType, 'double', $connection) . ",
			 flutetype = " . GetSQLValueString($txtFluteType, 'double', $connection) . ",
			 scoringtype = " . GetSQLValueString($txtScoringType, 'text', $connection) . ",
			 outsideliner = " . GetSQLValueString($txtOutsideLiner, 'text', $connection) . ",
			 insideliner = " . GetSQLValueString($txtInsideLiner, 'text', $connection) . ",
			 flutting2 = " . GetSQLValueString($txtFlutting2, 'double', $connection) . ",
			 stdgsm = " . GetSQLValueString($txtStdGsm, 'double', $connection) . ",
			 boardneed = " . GetSQLValueString($txtBoardNeed, 'double', $connection) . ",
			 jobCardRef = " . GetSQLValueString($txtJobCardRef, 'double', $connection) . ",
			 BorderNeededQty = " . GetSQLValueString($BorderNeededQty, 'double', $connection) . "

	 where corrugatorid='$txtCorrugatorId' and jobcardid = $JobCardId and ulstacker_id = $ulstacker_id";
	//echo$queryupdatedt;exit;
	 mysqli_query($connection, $queryupdatedt) or die(mysqli_error($connection));
	 $message = mysqli_error($connection);

	 if($PIndex == 0){
		$i = 1;


		$txtSalesRef = $_POST['txtSalesRef'][$i];
		$txtMasterRef =  $_POST['txtMasterCardRef'][$i];
		$txtPlannedQty =  $_POST['txtPlannedQty'][$i];
		$txtProducedQty = $_POST['txtProducedQty'][$i];
		$txtRemainingQty = $_POST['txtRemainingQty'][$i];
		$txtClient = $_POST['txtClient'][$i];
		$clientCode =  $_POST['clientCode'][$i];
		$txtItemDesc =   $_POST['txtItemDesc'][$i];
		$txtBoxType =  $_POST['txtBoxTypeId'][$i];
		$txtFluteType = $_POST['txtFluteType'][$i];
		$txtScoringType =  $_POST['txtScoringType'][$i];
		$txtOutsideLiner = $_POST['txtOutsideLiner'][$i];
		$txtInsideLiner =  $_POST['txtInsideLiner'][$i];
		$txtFlutting2 = $_POST['txtFlutting2'][$i];
		$txtStdGsm =  $_POST['txtStdGsm'][$i];
		$txtBoardNeed =  $_POST['txtBoardNeed'][$i];
		$txtJobCardRef =  $_POST['txtJobCardRef'][$i];
 $queryupdatedt = "UPDATE temp_upperlowerstacker
	SET 
			salesref = " . GetSQLValueString($txtSalesRef, 'double', $connection) . ",
			masterref = " . GetSQLValueString($txtMasterRef, 'text', $connection) . ",
			plannedqty = " . GetSQLValueString($txtPlannedQty, 'double', $connection) . ",
			producedqty = " . GetSQLValueString($txtProducedQty, 'double', $connection) . ",
			remainingqty = " . GetSQLValueString($txtRemainingQty, 'double', $connection) . ",
			customer = " . GetSQLValueString($txtClient, 'text', $connection) . ",
			clientCode = " . GetSQLValueString($clientCode, 'text', $connection) . ",
			itemdesc = " . GetSQLValueString($txtItemDesc, 'text', $connection) . ",
			boxtype = " . GetSQLValueString($txtBoxType, 'double', $connection) . ",
			flutetype = " . GetSQLValueString($txtFluteType, 'double', $connection) . ",
			scoringtype = " . GetSQLValueString($txtScoringType, 'text', $connection) . ",
			outsideliner = " . GetSQLValueString($txtOutsideLiner, 'text', $connection) . ",
			insideliner = " . GetSQLValueString($txtInsideLiner, 'text', $connection) . ",
			flutting2 = " . GetSQLValueString($txtFlutting2, 'double', $connection) . ",
			stdgsm = " . GetSQLValueString($txtStdGsm, 'double', $connection) . ",
			BorderNeededQty = " . GetSQLValueString($txtBoardNeed, 'double', $connection) . ",
			jobCardRef = " . GetSQLValueString($txtJobCardRef, 'double', $connection) . "

	where corrugatorid='$txtCorrugatorId' and ulstacker_id = '$ulstacker_id2'";
 //echo$queryupdatedt;exit;
	mysqli_query($connection, $queryupdatedt) or die(mysqli_error($connection));
	$message = mysqli_error($connection);

	$txtLength1 = $_POST['txtLength'][$i];
	$txtWidth1 =  $_POST['txtWidth'][$i];
	$txtFlapTop1 =  $_POST['txtFlapTop'][$i];
	$txtHeight1 = $_POST['txtHeight'][$i];
	$txtFlapBot1 = $_POST['txtFlapBot'][$i];
	$txtOuts1 = $_POST['txtOuts'][$i];
	$txtTWidth1 =  $_POST['txtTWidth'][$i];
	$txtTCuts1 =   $_POST['txtTCuts'][$i];
	$txtActualCuts1 =  $_POST['txtActualCuts'][$i];
	$txtReelDec1 = $_POST['txtReelDec'][$i];
	$txtTotWidth1 =  $_POST['txtTotWidth'][$i];
	$txtTrimming1 = $_POST['txtTrimming'][$i];
	$txtTrimWaste1 =  $_POST['txtTrimWaste'][$i];
	$txtLM1 = $_POST['txtLM'][$i];
	$txtSQ1 =  $_POST['txtSQ'][$i];


	$queryupdatedtpaper = "UPDATE temp_corgrid SET
	 temp_corgrid.length = " . GetSQLValueString($txtLength1, 'double', $connection) . ",
	width = " . GetSQLValueString($txtWidth1, 'double', $connection) . ",
	flaptop = " . GetSQLValueString($txtFlapTop1, 'double', $connection) . ",
	height = " . GetSQLValueString($txtHeight1, 'double', $connection) . ",
	flapbot = " . GetSQLValueString($txtFlapBot1, 'double', $connection) . ",
	numouts = " . GetSQLValueString($txtOuts1, 'double', $connection) . ",
	twidth = " . GetSQLValueString($txtTWidth1, 'double', $connection) . ",
	numcuts = " . GetSQLValueString($txtTCuts1, 'double', $connection) . ",
	actualcuts = " . GetSQLValueString($txtActualCuts1, 'double', $connection) . ",
	reeldeckle = " . GetSQLValueString($txtReelDec1, 'double', $connection) . ",
	totwidth = " . GetSQLValueString($txtTotWidth1, 'double', $connection) . ",
	trimming = " . GetSQLValueString($txtTrimming1, 'double', $connection) . ",
	trimwaste= " . GetSQLValueString($txtTrimWaste1, 'double', $connection) . ",
	lm= " . GetSQLValueString($txtLM1, 'double', $connection) . ",

	sq= " . GetSQLValueString($txtSQ1, 'double', $connection) .
		" where corgrid_id='$crid2' ";
//echo$queryupdatedtpaper;exit;
	mysqli_query($connection, $queryupdatedtpaper) or die(mysqli_error($connection));
	$message = mysqli_error($connection);

	 }
	//echo $queryupdatedt; //exit;


	//   $queryupdatedtpaper = "UPDATE temp_corgrid SET
	//  reeldeckle = " . GetSQLValueString($txtReelDec, 'double', $connection) . 

	// " where corref='$txtCorrugatorRef'";
	//  //  echo $queryupdatedtpaper;exit;
	//   mysqli_query($connection, $queryupdatedtpaper) or die(mysqli_error($connection));
	//   $message = mysqli_error($connection);

	 $rowsNb = $_POST['rowsNb[0]']; //ya rab
	 for($xx=0;$xx<$lastRowLocation;$xx++){

		$queryupdatedtpaperold = "UPDATE temp_locationtable SET
			trimsize = " . GetSQLValueString($_POST['txtSizeTrim'][$xx], 'double', $connection) . ", 
			upperpaperrequired = " . GetSQLValueString($_POST['txtURequired'][$xx], 'double', $connection) . ",
			lowerpaperrequired = " . GetSQLValueString($_POST['txtLRequired'][$xx], 'double', $connection) . ",
			totURequired = " . GetSQLValueString($_POST['txtTRequired'][$xx], 'text', $connection) . ",  
			upperpaperactual = " . GetSQLValueString($_POST['txtUActual'][$xx], 'double', $connection) . ",
			upperpaperactualT = " . GetSQLValueString($_POST['txtUActualT'][$xx], 'double', $connection) . ",
			lowerpaperrequiredT = " . GetSQLValueString($_POST['txtLRequiredT'][$xx], 'double', $connection) . ",
			lowerpaperactual = " . GetSQLValueString($_POST['txtLActual'][$xx], 'double', $connection) . ",
			lowerpaperactualT = " . GetSQLValueString($_POST['txtLActualT'][$xx], 'double', $connection) . ",
			totalrequired = " . GetSQLValueString($_POST['txtTRequired'][$xx], 'double', $connection) . ",
			totalactual = " . GetSQLValueString($_POST['txtTActual'][$xx], 'double', $connection) . ",

			trimrequired = " . GetSQLValueString($_POST['txtRequiredTrim'][$xx], 'double', $connection) . ",
			trimactual = " . GetSQLValueString($_POST['txtActualTrim'][$xx], 'double', $connection) . "
			where loc_id=" . GetSQLValueString($_POST['locid'][$xx], 'double', $connection) . "";  
			//echo$queryupdatedtpaperold;exit;
			mysqli_query($connection, $queryupdatedtpaperold) or die(mysqli_error($connection));

	 }
	 //exit;


	$txtTotWidth = $_POST['txtTotWidth'];
	$txtReelDec = $_POST['txtReelDec'];
	$txtTrimming = $_POST['txtTrimming'];
	//hon n
	$q="Update temp_corrugator1 set reeldeckle = " . GetSQLValueString($txtReelDec, 'double', $connection) . ",
			totwidth = " . GetSQLValueString($txtTotWidth, 'double', $connection) . ",trimming = " . GetSQLValueString($txtTrimming, 'double', $connection) . " ,

			upperpaperrequiredT = " . GetSQLValueString($txtURequiredT, 'double', $connection) . ",
			upperpaperactualT  = " . GetSQLValueString($txtUActualT, 'double', $connection) . ",
			lowerpaperrequiredT = " . GetSQLValueString($txtLRequiredT, 'double', $connection) . ",
			lowerpaperactualT = " . GetSQLValueString($txtLActualT, 'double', $connection) . ",
			totURequiredT  = " . GetSQLValueString($txtTRequiredT, 'double', $connection) . ",
			totUactualT = " . GetSQLValueString($txtTActualT, 'double', $connection) . ",
			paperinner = " . GetSQLValueString($txtIPaper, 'double', $connection) . ",
			paperflutting  = " . GetSQLValueString($txtFPaper, 'double', $connection) . ",
			paperouter = " . GetSQLValueString($txtOPaper, 'double', $connection) . ",
			paperinner = " . GetSQLValueString($txtIPaper, 'double', $connection) . ",
			paperflutting  = " . GetSQLValueString($txtFPaper, 'double', $connection) . ",
			paperouter = " . GetSQLValueString($txtOPaper, 'double', $connection) . ",
			balanceinner = " . GetSQLValueString($txtIBalance, 'double', $connection) . ",
			balanceflutting  = " . GetSQLValueString($txtFBalance, 'double', $connection) . ",
			balanceouter = " . GetSQLValueString($txtOBalance, 'double', $connection) . ",
			wtpcupper = " . GetSQLValueString($txtWTPCU, 'double', $connection) . ",
			wtpclower  = " . GetSQLValueString($txtWTPCL, 'double', $connection) . ",
			totalgsm = " . GetSQLValueString($txtTotalGSM, 'double', $connection) . ",
			trimsizeT = " . GetSQLValueString($txtSizeTrimT, 'double', $connection) . ",
			trimrequiredT  = " . GetSQLValueString($txtRequiredTrimT, 'double', $connection) . ",
			trimactualT = " . GetSQLValueString($txtActualTrimT, 'double', $connection) . ",
			trimwasteper = " . GetSQLValueString($txtTrimWaste, 'double', $connection) . " 

where corrugator_id=$txtCorrugatorId";
// echo$q;exit;
		 mysqli_query($connection, $q) or die(mysqli_error($connection));
		 $message = mysqli_error($connection);




	header("Location: ./Form_Corrugator.php?message=" . $message . "&txtCorrugatorId=" . $txtCorrugatorId . "&count=" . $count);
}


if ($mode == "updatepaperold" && $txtCorrugatorId != '' && $loc_id != '') {


	$corrugator_date_ = explodewordrequest($corrugator_date);
	if($txtItemCode==''){
		$StockQty=0;
	}else{
		$Qry_QtyExists = " SELECT ifnull(QtyExists('$txtItemCode', '$txtWareHouse', '" . $corrugator_date_ . "', $txtCorrugatorId, 'SO', '', ''),0) As QtyExists ";
		$result = selectData($connection, $Qry_QtyExists);
		$StockQty = $result[0];
	}
	$txtLRequiredT = $_POST['txtLRequiredT'];
$txtURequiredT = $_POST['txtURequiredT'];
	$txtTRequired = $txtURequired+$txtLRequired; 
	$queryupdatedtpaperold = "UPDATE temp_locationtable SET

 location = " . GetSQLValueString($txtLocation, 'text', $connection) . ",

	gsm = " . GetSQLValueString($txtGSM, 'double', $connection) . ",
	papergradle = " . GetSQLValueString($txtPaperGrade, 'text', $connection) . ",
	relldeckle = " . GetSQLValueString($txtRellDeckle, 'double', $connection) . ",
	papermill = " . GetSQLValueString($txtPaperMill, 'text', $connection) . ",
	supplier = " . GetSQLValueString($txtSupplier, 'text', $connection) . ",

	upperpaperrequired = " . GetSQLValueString($txtURequired, 'text', $connection) . ",
	totURequired = " . GetSQLValueString($txtTRequired, 'text', $connection) . ",

	upperpaperactual = " . GetSQLValueString($txtUActual, 'double', $connection) . ",
	upperpaperactualT = " . GetSQLValueString($txtUActualT, 'double', $connection) . ",

	lowerpaperrequired = " . GetSQLValueString($txtLRequired, 'double', $connection) . ",
	lowerpaperrequiredT = " . GetSQLValueString($txtLRequiredT, 'double', $connection) . ",

	lowerpaperactual = " . GetSQLValueString($txtLActual, 'double', $connection) . ",
	lowerpaperactualT = " . GetSQLValueString($txtLActualT, 'double', $connection) . ",

	totalrequired = " . GetSQLValueString($txtTRequired, 'double', $connection) . ",
	totalactual = " . GetSQLValueString($txtTActual, 'double', $connection) . ",
	paperinner = " . GetSQLValueString($txtIPaper, 'double', $connection) . ",
	paperflutting = " . GetSQLValueString($txtFPaper, 'double', $connection) . ",
	paperouter = " . GetSQLValueString($txtOPaper, 'double', $connection) . ",
	balanceinner = " . GetSQLValueString($txtIBalance, 'double', $connection) . ",
	balanceflutting = " . GetSQLValueString($txtFBalance, 'double', $connection) . ",
	balanceouter = " . GetSQLValueString($txtOBalance, 'double', $connection) . ",
	wtpcupper = " . GetSQLValueString($txtWTPCU, 'double', $connection) . ",
	wtpclower = " . GetSQLValueString($txtWTPCL, 'double', $connection) . ",
	totalgsm = " . GetSQLValueString($txtTotalGSM, 'double', $connection) . ",


	trimsize = " . GetSQLValueString($txtSizeTrim, 'double', $connection) . ",
	trimsizeT = " . GetSQLValueString($txtSizeTrimT, 'double', $connection) . ",

	trimrequired = " . GetSQLValueString($txtRequiredTrim, 'double', $connection) . ",
	trimrequiredT = " . GetSQLValueString($txtRequiredTrimT, 'double', $connection) . ",


	trimactual = " . GetSQLValueString($txtActualTrim, 'double', $connection) . ",
	trimactualT = " . GetSQLValueString($txtActualTrimT, 'double', $connection) . ",

	trimwasteper = " . GetSQLValueString($txtTrimWaste, 'double', $connection) . ",

	totalwasterequired = " . GetSQLValueString($txtTotalRequired, 'double', $connection) . ",
	totalwasteactual = " . GetSQLValueString($txtTotalActual, 'double', $connection) . ",
	itemCode  = " . GetSQLValueString($txtItemCode, 'text', $connection) . ",
	warehouse_code  = " . GetSQLValueString($txtWareHouse, 'text', $connection) . ",
	itemType  = " . GetSQLValueString($txtType, 'text', $connection) . ",
	StockQty   = " . GetSQLValueString($StockQty, 'double', $connection) . ",
	itemDesc  = " . GetSQLValueString($txtItemDesc, 'text', $connection) .
		" where loc_id='$loc_id'";

	mysqli_query($connection, $queryupdatedtpaperold) or die(mysqli_error($connection));
	$message = mysqli_error($connection);
	$txtLRequiredT = $_POST['txtLRequiredT'];
	$txtURequiredT = $_POST['txtURequiredT'];
	$txtWTPCU = $_POST['txtWTPCU'];
	$txtWTPCL =  $_POST['txtWTPCL'];
	$txtTotalGSM = $_POST['txtTotalGSM'];
	$queryupdatedt = "UPDATE temp_corrugator1 SET
upperpaperrequiredT = " . GetSQLValueString($txtURequiredT, 'double', $connection) . ",
upperpaperactualT  = " . GetSQLValueString($txtUActualT, 'double', $connection) . ",
lowerpaperrequiredT = " . GetSQLValueString($txtLRequiredT, 'double', $connection) . ",

lowerpaperactualT = " . GetSQLValueString($txtLActualT, 'double', $connection) . ",
totURequiredT  = " . GetSQLValueString($txtTRequiredT, 'double', $connection) . ",
totUactualT = " . GetSQLValueString($txtTActualT, 'double', $connection) . ",

paperinner = " . GetSQLValueString($txtIPaper, 'double', $connection) . ",
paperflutting  = " . GetSQLValueString($txtFPaper, 'double', $connection) . ",
paperouter = " . GetSQLValueString($txtOPaper, 'double', $connection) . ",

paperinner = " . GetSQLValueString($txtIPaper, 'double', $connection) . ",
paperflutting  = " . GetSQLValueString($txtFPaper, 'double', $connection) . ",
paperouter = " . GetSQLValueString($txtOPaper, 'double', $connection) . ",

balanceinner = " . GetSQLValueString($txtIBalance, 'double', $connection) . ",
balanceflutting  = " . GetSQLValueString($txtFBalance, 'double', $connection) . ",
balanceouter = " . GetSQLValueString($txtOBalance, 'double', $connection) . ",

wtpcupper = " . GetSQLValueString($txtWTPCU, 'double', $connection) . ",
wtpclower  = " . GetSQLValueString($txtWTPCL, 'double', $connection) . ",
totalgsm = " . GetSQLValueString($txtTotalGSM, 'double', $connection) . ",

trimsizeT = " . GetSQLValueString($txtSizeTrimT, 'double', $connection) . ",
trimrequiredT  = " . GetSQLValueString($txtRequiredTrimT, 'double', $connection) . ",
trimactualT = " . GetSQLValueString($txtActualTrimT, 'double', $connection) . ",

trimwasteper = " . GetSQLValueString($txtTrimWaste, 'double', $connection) . "


where corrugator_id=" . GetSQLValueString($txtCorrugatorId, 'int', $connection) . "";
	$result = mysqli_query($connection, $queryupdatedt);


	header("Location: ./Form_Corrugator.php?message=" . $message . "&txtCorrugatorId=" . $txtCorrugatorId . "&count=" . $count);
}

if ($mainmode == 'deletepending' && $txtCorrugatorId != '') {
	$delete = "Delete from temp_upperlowerstacker where corrugatorid =$txtCorrugatorId ";
	$result = mysqli_query($connection, $delete);
	$message = mysqli_error($connection);
	$delete = "Delete from temp_corgrid where corrugatorid =$txtCorrugatorId ";
	$result = mysqli_query($connection, $delete);
	$message = mysqli_error($connection);

	$delete = "Delete from temp_corrugator1 where corrugator_id =$txtCorrugatorId ";
	$result = mysqli_query($connection, $delete);
	$message = mysqli_error($connection);

	$delete = "Delete from temp_bookingQty where cjr_id = '$txtCorrugatorId'";
	$result = mysqli_query($connection, $delete);

	$delete3 = "DELETE FROM temp_locationtable WHERE corrugatorid = $txtCorrugatorId";
	$result3 = mysqli_query($connection, $delete3);


	header("Location: ./Form_CorrugatorSearchPending.php?message=" . $message);
}

else if ($mode == 'updateCJRStatus' && $txtCorrugatorId != '') {
	$queryUpd = "Update corrugator1 
 set corrugator_confirm='".$CJR_Confirmedtracker."' where corrugator_id='".$txtCorrugatorId."'";
 $restransfers = mysqli_query($connection, $queryUpd);
 $message  = mysqli_error($connection); 
 header ("Location: ./Form_CJRtrack.php?message=".$message."&fromdate=".$fromdate."&todate=".$todate."&PRef=".$PRef);	

}
//Post Mode	
else if ($mode == "post" && $corrugator_id != '') {

	$corrugator_date_ = insertMySQLDate($corrugator_date_);

	if (empty($vAdjInvDebit))  $AdjInvDebit = 'Null';
	else  $AdjInvDebit = "'" . $vAdjInvDebit . "'";
	if (empty($vAdjInvCredit))  $AdjInvCredit = 'Null';
	else  $AdjInvCredit = "'" . $vAdjInvCredit . "'";

	if (empty($txtManualNo))  $txtManualNo = "Null";
	else  $txtManualNo = "'" . $txtManualNo . "'";

	if (empty($txtTruckNo))  $txtTruckNo = 'Null';
	else  $txtTruckNo = "'" . $txtTruckNo . "'";


	if (empty($currency))
		$currency = $base1;
	if (empty($vAdjIsInvoice))
		$vAdjIsInvoice = 0;

	if ($txtAdjDivision == '') $txtAdjDivision = 0;
	if ($txtloadToChanging == '') $txtloadToChanging = 0;

	$query = "SELECT Curdt_Rate FROM curdt WHERE Currency_Code = '" . $currency . "' AND Curdt_date = (SELECT MAX(Curdt_date) FROM curdt WHERE Currency_Code = '" . $currency . "' )";
	$result = selectData($connection, $query);
	$rate_dt = $result[0];


	$query = "SELECT Curdt_Rate FROM curdt WHERE Currency_Code = '" . $base1 . "' AND Curdt_date = (SELECT MAX(Curdt_date) FROM curdt WHERE Currency_Code = '" . $base1 . "' )";
	$result = selectData($connection, $query);
	$rate_dt1 = $result[0];

	$query = "SELECT Curdt_Rate FROM curdt WHERE Currency_Code = '" . $base2 . "' AND Curdt_date = (SELECT MAX(Curdt_date) FROM curdt WHERE Currency_Code = '" . $base2 . "' )";
	$result = selectData($connection, $query);
	$rate_dt2 = $result[0];

	$queryupdatetemp = "UPDATE temp_corrugator1 SET
	corrugator_date = '$corrugator_date_'
	where corrugator_id='$corrugator_id'";
	//  echo  $queryupdatetemp;exit;
	mysqli_query($connection, $queryupdatetemp) or die(mysqli_error($connection));
	$message = mysqli_error($connection);


	$q = "Select count(*) from temp_bookingQty where cjr_id = '$corrugator_id'";
	$result = mysqli_query($connection, $q);
	$row = mysqli_fetch_array($result);
	$count = $row[0];
	if ($count > 0) {
		$delete = "Delete from temp_bookingQty where cjr_id = '$corrugator_id'";
		$result = mysqli_query($connection, $delete);
	}

		$queryItems = "Select itemCode, ifnull(totURequired,0) as totURequired, warehouse_code,loc_id from temp_locationTable where  corrugatorid = '$corrugator_id' OR corrugatorid2 = '$corrugator_id'  ";

	//echo $queryItems;exit;
	$resultItems = mysqli_query($connection, $queryItems);

	while ($rowsItems = mysqli_fetch_array($resultItems)) {

		if (is_array($rowsItems)) extract($rowsItems);

		if($warehouse_code == '') $warehouse_code = "NULL";
		else $warehouse_code = "'".$warehouse_code."'";
		// $qinsert  =  " insert into temp_bookingQty (bookingQty_qty, bookingQty_usedqty, bookingqty_date,
		//                           bookingqty_useddate, item_code, warehouse_code , user_account,SerialNo, cjr_id, loc_id)
		//                       Values ( $totURequired , 0,'" . $corrugator_date_ . "', NULL, '$itemCode', $warehouse_code, 
		//                       '" . $_SESSION['useraccount'] . "', NULL, $corrugator_id, $loc_id )";
		fbegin($connection);
				$bookingqty_id = get_nextsequence1('now()', 'bookingqty_id', '', $connection);
		fcommit($connection);
		 $qinsert  =  " insert into temp_bookingQty (bookingqty_id,bookingQty_qty, bookingQty_usedqty, transaction_date,
															bookingqty_useddate, item_code, warehouse_code , user_account,SerialNo, cjr_id, transaction_id,Transaction_Type)
													Values ($bookingqty_id, $totURequired , 0,'" . $corrugator_date_ . "', NULL, '$itemCode', $warehouse_code, 
													'" . $_SESSION['useraccount'] . "', NULL, $corrugator_id, $loc_id,'CJR' )";
		$result = mysqli_query($connection, $qinsert);
	}


	if ($mainmode == 'insert') $SMode = 'I';
	if ($mainmode == 'edit') $SMode = 'U';

	$txtWTPCU =$_POST['txtWTPCU'];
	$txtWTPCL =$_POST['txtWTPCL'];
	$txtTotalGSM =$_POST['txtTotalGSM'];
	 if($txtWTPCU  == '') $txtWTPCU  ="NULL";
	 if($txtWTPCL == '') $txtWTPCL = "NULL";
	 if($txtTotalGSM == '') $txtTotalGSM = "NULL";

	$q="update temp_corrugator1 set wtpcupper =$txtWTPCU  ,wtpclower=$txtWTPCL ,totalgsm=$txtTotalGSM where corrugator_id = '" . $corrugator_id . "'";
	 mysqli_query($connection, $q);




	$query = "Select Count(*) From temp_corrugator1 where corrugator_id = '" . $corrugator_id . "'";
	//echo $query; exit;
	$result = selectData($connection, $query);
	$ExistInTemp = $result[0];
	$query_checkDelivery  = "select count(*) as SalesOrderExist from corrugator1 where corrugator_id = '$corrugator_id'";
	$result_checkDelivery = mysqli_query($connection, $query_checkDelivery);
	$row_checkDelivery    = mysqli_fetch_array($result_checkDelivery);
	if (is_array($row_checkDelivery)) extract($row_checkDelivery);
	if ($SalesOrderExist > 0) {
		$transType = "U";
	} else {
		$transType = "I";
	}
	$company = explode("_", $_SESSION['dbName']);
	$currCompany = strtoupper($company[0]);

	if ($ExistInTemp == 1) {
		$query = "Select Count(*) From corrugator1 where corrugator_id = '" . $corrugator_id . "'";
		//echo $query; exit;
		$result = selectData($connection, $query);
		$ExistIn = $result[0];

$querye = "Select Count(corrugator_reference) From corrugator1 where corrugator_reference = '" . $corrugator_reference . "'";
$resulte = selectData($connection, $querye);
 $ExistIne = $resulte[0];

//  echo"rrtest_". $transType ;exit;
if($ExistIne == 0 || $transType=='U'){
				$query = "update temp_corrugator1 set corrugator_reference = '$corrugator_reference' where  corrugator_id='$corrugator_id'";
				$result = mysqli_query($connection, $query);

		fbegin($connection);
		$querycr = "SELECT CopyData_TempCorrugator('" . $SMode . "', " . $corrugator_id . ", '" . $_SESSION['useraccount'] . "') FROM Dual ";
		// exit;
		$resultcr = selectData($connection, $querycr);
		$resultcr = $resultcr[0];
		// echo$resultcr;
		// exit;
		if ($resultcr == 1) {




			// if ($ExistIn == 0) {

			//   if ($index == 0) {

			//     //$vMRefhk = get_nextsequence('corrugator_reference',$vProjCostCent,$connection);

			//     if ($Module_ReferenceByBelongsToGroup == 1) {
			//       $vMRefhk = get_nextsequence_belongsToGroup("'" . $corrugator_date_ . "'", 'corrugator_reference', $FI_BelongsToGrp, $connection);
			//     } else {
			//       $vMRefhk  = get_nextsequence1('now()', 'corrugator_reference', '', $connection);
			//     }
			//   } else {
			//     $query = "select corrugator_reference from sequence order by corrugator_reference desc limit 1";
			//     //echo $query; exit;
			//     $result = mysqli_query($connection, $query);
			//     $rows = mysqli_fetch_array($result);
			//     //echo"Ref:".
			//     $vMRefhk = $rows[0];
			//   }

			//   $query = "update corrugator1 set corrugator_reference = '$vMRefhk' where  corrugator_id='$corrugator_id'";
			//   // echo$query;exit;
			//   $result = mysqli_query($connection, $query);
			// }

			if ($config['log_file'] == 1) {
				$query_insertToHistory = "call " . $config['dbName_history'] . ".InsertToHistory('$SMode', '$corrugator_id', '" . $_SESSION['useraccount'] . "', now(), '" . $_SESSION['dbName'] . "', 'cjr', '$currCompany')";
				//echo $query_insertToHistory;exit;
				$result_insertToHistory = mysqli_query($connection, $query_insertToHistory);
				while (mysqli_more_results($connection) && mysqli_next_result($connection)) {
				}
			}


		}
		fcommit($connection);
			header("Location: ./Form_Corrugator.php?message=".$message);
	}



	else{
			if ($message == '') $message = "CJR Reference already exist!";
			header("Location: ./Form_Corrugator.php?txtCorrugatorId=".$corrugator_id."&message=".$message);
	}

	}

}else if ($mainmode == 'confirmJobCard' && $jobcard_id != '') {
	$date = date('d/m/Y');
	$update = "update jobcard set jobcard_confirm = 1
							where jobcard_id = $jobcard_id";
	$result = mysqli_query($connection, $update);


	if ($message == '') $message = "JobCard has been Confirmed!";
	header("Location: ./Form_JobCardSearch.php?message=" . $message);
} else if ($mainmode == 'approveJobCard' && $jobcard_id != '') {
	$date = date('d/m/Y');
	$update = "update jobcard set jobcard_approve = 1
							 where jobcard_id = $jobcard_id";
	$result = mysqli_query($connection, $update);


	if ($message == '') $message = "JobCard has been Approved!";
	header("Location: ./Form_JobCardSearch.php?message=" . $message);
} else if ($mainmode == 'disapproveJobCard' && $jobcard_id != '') {

	$update = "update jobcard set jobcard_approve = 0 where jobcard_id = $jobcard_id";
	$result = mysqli_query($connection, $update);


	if ($message == '') $message = "Jobcard has been Approved!";
	header("Location: ./Form_JobCardSearch.php?message=" . $message);
} else if ($mainmode == 'approveCorrugator' && $corrugator_id != '') {
	$date = date('d/m/Y');
	$update = "update corrugator1 set corrugator_approve = 1
							 where corrugator_id = $corrugator_id";
	$result = mysqli_query($connection, $update);


	if ($message == '') $message = "CJR has been Approved!";
	header("Location: ./Form_CorrugatorSearch.php?message=" . $message);
} else if ($mainmode == 'confirmcorrugator' && $corrugator_id != '') {
	$date = date('d/m/Y');
	$update = "update corrugator1 set corrugator_confirm = 1
							 where corrugator_id = $corrugator_id";
	$result = mysqli_query($connection, $update);


	if ($message == '') $message = "CJR has been Confirmed!";
	header("Location: ./Form_CorrugatorSearch.php?message=" . $message);
} else if ($mainmode == 'disapproveCorrugator' && $corrugator_id != '') {
	$date = date('d/m/Y');
	$update = "update corrugator1 set corrugator_approve = 0
							 where corrugator_id = $corrugator_id";
	$result = mysqli_query($connection, $update);


	if ($message == '') $message = "CJR has been Disapproved!";
	header("Location: ./Form_CorrugatorSearch.php?message=" . $message);
}else if($mainmode=='setItem' ){

	// //setItem
	// if($txtPaperMill=='NA'){
	//   $query = "SELECT itemsdt.item_code as item_code 
	//           FROM  itemsdt  
	// 		      where itemdt_value in ('$txtGSM','$txtPaperGrade', '$txtRellDeckle','')  GROUP BY ITEM_CODE HAVING COUNT( DISTINCT ITEMDT_VALUE) = 4";		
	// }else 
	if($txtPaperMill==''){
	$query = "SELECT itemsdt.item_code as item_code 
						FROM  itemsdt  
						where itemdt_value in ('$txtGSM','$txtPaperGrade', '$txtRellDeckle')  GROUP BY ITEM_CODE HAVING COUNT( DISTINCT ITEMDT_VALUE) = 3";		
	}else{
	$query = "SELECT itemsdt.item_code as item_code 
						FROM  itemsdt  
						where ifNull(itemdt_value,'') in ('$txtGSM','$txtPaperGrade', '$txtRellDeckle','$txtPaperMill')  GROUP BY ITEM_CODE HAVING COUNT( DISTINCT ITEMDT_VALUE) = 4";		
	}
	//echo$query;exit;
	$result = mysqli_query($connection, $query) or die(mysqli_error($connection));
	$row = mysqli_fetch_array($result);
	$item_code=$row[0];

	$corrugator_date_ = insertMySQLDate($corrugator_date_);
	if($txtCorrugatorId=='') $txtCorrugatorId=0;
	$Qry_QtyExists = " SELECT ifnull(QtyExists('".$item_code."', '$txtWareHouse', '" . $corrugator_date_ . "', $txtCorrugatorId, 'SO', '', ''),0) As QtyExists ";
	//echo  $Qry_QtyExists."<br>";exit;
	$resultQtyExists = selectData($connection, $Qry_QtyExists);
	$StockQty = $resultQtyExists[0];
	if($StockQty>0){
		echo $item_code;
	}

	//PaperMill 
	$paperMill='';
	// $query = "select distinct items.item_code as item_code1 from itemsdt 
	// 							left join items on items.item_code=itemsdt.item_code 
	// 							where 
	// 							itemdt_value in ('$txtGSM','$txtPaperGrade', '$txtRellDeckle' ) 
	// 							and group_code='RM' 
	// 							GROUP BY items.ITEM_CODE HAVING COUNT(DISTINCT ITEMDT_VALUE) = 3 
	// 							order by itemdt_value asc";
	// $result = mysqli_query($connection, $query);
	// while ($rows   = mysqli_fetch_array($result)) {
	//   extract($rows);
	//   $query1 = "select null,itemdt_value from itemsdt where item_code='$item_code1' and ItemParam_Code='paper_mill'";
	//   $result1 = mysqli_query($connection, $query1);
	//   //echo $query;
	//   while ($rows1 = mysqli_fetch_array($result1)) {
	//     $paperMill.=$rows1[1].',';
	//   }
	// }

	//echo $item_code;
	echo "[BRK]";
	// echo  $paperMill;
	// echo "[BRK]";

}


// echo "<br> mainmode = ".$mainmode;
if($mainmode=='setPaperMill' ){

	//setPaperMill
	$corrugator_date_ = insertMySQLDate($corrugator_date_);
 //PaperMill 
	$paperMill='';
	if($txtCorrugatorId=='') $txtCorrugatorId=0;
	$query = "select distinct items.item_code as item_code1 from itemsdt 
								left join items on items.item_code=itemsdt.item_code 
								where 
								itemdt_value in ('$txtGSM','$txtPaperGrade', '$txtRellDeckle' ) 
								and group_code='RM' 
								GROUP BY items.ITEM_CODE HAVING COUNT(DISTINCT ITEMDT_VALUE) = 3";
								//echo$query."<br>";
	$result = mysqli_query($connection, $query);
	while ($rows   = mysqli_fetch_array($result)) { 
		if (is_array($rows)) extract($rows);
		$query1 = "select itemdt_value from itemsdt where item_code='$item_code1' and ItemParam_Code='paper_mill'";
		$result1 = mysqli_query($connection, $query1);
		//echo$query1."<br>";
		while ($rows1 = mysqli_fetch_array($result1)) {

			//

			$queryItem = "select distinct items.item_code  from itemsdt 
									left join items on items.item_code=itemsdt.item_code 
									where 
									itemdt_value in ('$txtGSM','$txtPaperGrade', '$txtRellDeckle','".$rows1[0]."' ) 
									and group_code='RM' 
									GROUP BY items.ITEM_CODE HAVING COUNT(DISTINCT ITEMDT_VALUE) = 4 
									order by itemdt_value asc";
									//echo$queryItem."<br>";
			$resultItem = mysqli_query($connection, $queryItem);
			//echo$queryItem."<br>";
			$rowsItem   = mysqli_fetch_array($resultItem);
			$Qry_QtyExists = " SELECT ifnull(QtyExists('".$rowsItem[0]."', '$txtWareHouse', '" . $corrugator_date_ . "', $txtCorrugatorId, 'SO', '', ''),0) As QtyExists ";
			//echo  $Qry_QtyExists."<br>";exit;
			//echo$Qry_QtyExists."<br>";
			$resultQtyExists = selectData($connection, $Qry_QtyExists);
			$StockQty = $resultQtyExists[0];
			if($StockQty>0){
				$paperMill.=$rows1[0].',';
			}

			//


		}

	}

	echo $paperMill;
	//exit;
	//echo "[BRK]";


}
if($mainmode=='setGSM' ){

	//setGSM

	$GSM         = '';
	$queryAllGSM = "select distinct itemdt_value from itemsdt 
				left join items on items.item_code=itemsdt.item_code 
				where ItemParam_Code='GSM' and group_code='RM' order by itemdt_value asc ";
	$resultAllGSM     = mysqli_query($connection, $queryAllGSM);
	while($rowsAllGSM = mysqli_fetch_array($resultAllGSM)){
		 $txtGSM = $rowsAllGSM[0];
		$query  = "SELECT count(*) From(
			SELECT itemsdt.ITEM_CODE
			FROM itemsdt
			LEFT JOIN items ON items.item_code = itemsdt.item_code
			WHERE itemdt_value IN ('$txtGSM', '$txtRellDeckle','$txtPaperGrade') and group_code='RM' GROUP BY itemsdt.ITEM_CODE HAVING COUNT(distinct ITEMDT_VALUE) = 3
		) as subquery";		
		//echo "\n".$query."\n";
		$result = mysqli_query($connection, $query) or die(mysqli_error($connection));
		$row = mysqli_fetch_array($result);
		$countItem=$row[0];
		if($countItem>0){
			$GSM   .= $rowsAllGSM[0].',';
		}
	}


	echo $GSM;
	//echo "[BRK]";


}

else if($mainmode=='setPaperGrade' ){

	$PaperGrade          = '';
	$queryAllPaperGrade  = "select distinct itemdt_value from itemsdt
			left join items on items.item_code=itemsdt.item_code 
			where ItemParam_Code='type' and group_code='RM' order by itemdt_value asc ";
	$resultAllPaperGrade = mysqli_query($connection, $queryAllPaperGrade);
	while($rowsAllPaperGrade = mysqli_fetch_array($resultAllPaperGrade)){

		$txtPaperGrade = $rowsAllPaperGrade[0];
		$queryPaperGrade  = "SELECT count(*) FROM  (
		SELECT itemsdt.ITEM_CODE
			FROM itemsdt
			LEFT JOIN items ON items.item_code = itemsdt.item_code
			WHERE itemdt_value IN  ('$txtRellDeckle','$txtPaperGrade') and group_code='RM' GROUP BY itemsdt.ITEM_CODE HAVING COUNT(distinct ITEMDT_VALUE) = 2
		)as subquery";		
		//echo$queryPaperGrade;
		$resultPaperGrade = mysqli_query($connection, $queryPaperGrade);
		$rowPaperGrade = mysqli_fetch_array($resultPaperGrade);
		$countItem=$rowPaperGrade[0];
		if ($countItem>0) {
			$PaperGrade   .= $txtPaperGrade.',';
		}

	}


	echo $PaperGrade;
	//exit;
	//echo "[BRK]";


}

// else if( $mainmode == 'confirmCorrugator' && $corrugator_id != ''){
// 	$date = date('d/m/Y');
// 	$update = "update corrugator1 set corrugator_confirm = 0
// 	             where corrugator_id = $corrugator_id";
// 	$result = mysqli_query($connection, $update);


// 	if($message == '') $message = "CJR has been Disconfirmed!";
// 	header ("Location: ./Form_CorrugatorSearch.php?message=".$message);
// }


//$message = preg_replace(' ', '%20', $message);
