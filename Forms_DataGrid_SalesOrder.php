<?php
session_start();
header("Content-type: text/html; charset=" . $_SESSION['encodingmode']);
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
if (!function_exists('ConvertCurrency')) {
	function ConvertCurrency($FromCurr, $ToCurr, $FromDate, $Amount, $connection)
	{
		$query = "SELECT ifNull(convertcurfunc('" . $FromCurr . "', '" . $ToCurr . "', '" . $FromDate . "', 0, 0, '', '', ifNull($Amount,0)),0) From Dual ";
		$result = selectData($connection, $query);
		//echo $query;
		return	$result[0];
	}
} 

if (!empty($_GET["action"])) {
	$dftorderby   = $_GET["dftorderby"];
	$action 	  = $_GET["action"];
	$orderby 	  = $_GET["orderby"];
	$morelines	  = $_GET["morelines"];
	$startrange   = $_GET["startrange"];
	$perpage 	  = $_GET["perpage"];
	$totalrows    = $_GET["totalrows"];
	$direction    = $_GET["direction"];
	$filepath     = $_GET["filepath"];
	$transid      = $_GET["transid"];
	$transdtid    = $_GET["transdtid"];
	$form      	  = $_GET["form"];

	include("config.inc.php");
	include("Functions_Sales.php");
	include("Functions_UserAccess.php");

	$connection = @mysqli_connect($config['dbServer'], $config['dbUser'], $config['dbPass']) or die("Could not connect to DB");
	mysqli_select_db($connection, $_SESSION['dbName']) or die("Could not find DB");

	if ($_SESSION['encodingmode'] == 'utf8' || $_SESSION['encodingmode'] == 'utf-8') {
		mysqli_query($connection, "SET NAMES 'utf8'");
		mysqli_query($connection, 'SET CHARACTER SET utf8');
		mysqli_set_charset($connection, 'utf8');
	}
   
	if ($form == 'SO') {
		ShowSorderDtGrid($connection, $form, $transid, $_SESSION['gso_parameters'], $_SESSION['gso_arrayaccess'], $dftorderby, $action, $orderby, $morelines, $startrange, $perpage, $totalrows, $direction);
	} else if ($form == 'SO-TOT') {
		ShowSorderTotals($connection, $transid, $_SESSION['gso_parameters'], $_SESSION['gso_arrayaccess']);
	}
}

function ShowSorderDtGrid($connection, $form, $PSorId, $arrayparams, $arrayaccess, $dftorderby, $action, $orderby, $morelines, $startrange, $perpage, $totalrows, $direction)
{




	$FI_SalesCheckSalesman 				= $arrayparams['FI_SalesCheckSalesman'];
	$Login_AllowPrice 					= $arrayparams['Login_AllowPrice'];
	$Login_NegativeQty 					= $arrayparams['Login_NegativeQty'];
	$Login_ModifySellingPrice 			= $arrayparams['Login_ModifySellingPrice'];
	$Allow_MinimumPrice 				= $arrayparams['Allow_MinimumPrice'];
	$Login_ModifyDiscount 				= $arrayparams['Login_ModifyDiscount'];
	$Module_NegativeSorder 				= $arrayparams['Module_NegativeSorder'];
	$Module_Unit1Method 				= $arrayparams['Module_Unit1Method'];
	$Module_SorderCheckQtyOnPost 		= $arrayparams['Module_SorderCheckQtyOnPost'];
	$Module_Pack 						= $arrayparams['Module_Pack'];
	$Module_DoubleUnit 					= $arrayparams['Module_DoubleUnit'];
	$Module_ProjectDt 					= $arrayparams['Module_ProjectDt'];
	$Module_CostCenterDt 				= $arrayparams['Module_CostCenterDt'];
	$Module_RestrictProjCost 			= $arrayparams['Module_RestrictProjCost'];
	$Module_RestrictCost 				= $arrayparams['Module_RestrictCost'];
	$ShowCostCentLayout 				= $arrayparams['ShowCostCentLayout'];
	$SetupDftWareHouse 					= $arrayparams['SetupDftWareHouse'];
	$defaultwarehouse					= $arrayparams['defaultwarehouse'];
	$Module_ShowWarehouseSalesorder  	= $arrayparams['Module_ShowWarehouseSalesorder'];
	$sorder_export  					= $arrayparams['sorder_export'];
	$txtSorType 						= $arrayparams['txtSorType'];
	if ($txtSorType == 'SO') $form_name = 'Sales Order';
	else if ($txtSorType == 'PF') $form_name = 'proforma';
	else if ($txtSorType == 'WB') $form_name = 'Waybill';
	else $form_name = 'Sales Request';
	$SubItem_Production 				= $arrayparams['SubItem_Production'];
	$FI_DeliverySalesPrice  			= $arrayparams['FI_DeliverySalesPrice'];
	$Module_Heatno  					= $arrayparams['Module_Heatno'];
	$sorderproduction  					= $arrayparams['sorderproduction'];
	$showItemColor  					= $arrayparams['showItemColor'];
	$Module_BatchNo  					= $arrayparams['Module_BatchNo'];
	$Module_ExpireDate  				= $arrayparams['Module_ExpireDate'];
	$Module_BPPlan  					= $arrayparams['Module_BPPlan'];
	$Module_ClientDiscount  			= $arrayparams['Module_ClientDiscount'];
	$default_project					= $arrayparams['default_project'];
	$default_costcent					= $arrayparams['default_costcent'];
	$Module_alternativeUnit = $arrayparams['Module_alternativeUnit'];
	$FI_ShowProfit = $arrayparams['FI_ShowProfit'];
	$salesOrderCanManageFields = false;
	if (function_exists('userCanManageAccessFormFields') && isset($_SESSION['accessschemaid']) && isset($_SESSION['dbLabel'])) {
		$salesOrderCanManageFields = userCanManageAccessFormFields($_SESSION['accessschemaid'], $_SESSION['dbLabel']);
	}

	$queryColor = "select color_code from color_setup order by color_code";

	if ($sorder_export == '') {
		$query = " Select Sorder_Export
					  From Temp_Sorder
					 Where Temp_Sorder.Sorder_ID = " . $PSorId;

		if ($Login_ModifyDiscount == 0)	$ModeDiscount = ' readonly ';
		//echo $query;

		$result = mysqli_query($connection, $query);
		$rows = mysqli_fetch_array($result);
		$sorder_export  = $rows[0];
	}
	$SalesTTC = $arrayparams['SalesTTC'];
	$DecimalBase = $arrayparams['DecimalBase'];

	$txtDate         	= $arrayparams['txtDate'];
	$txtClientCode   	= $arrayparams['txtClientCode'];
	$txtClientDetail 	= $arrayparams['txtClientDetail'];
	$txtCurrCode     	= $arrayparams['txtCurrCode'];
	$txtClientPromotion = $arrayparams['txtClientPromotion'];

	if (empty($txtClientPromotion)) $txtClientPromotion = 0;

	if ($txtClientCode == '' && $PSorId != '') {
		$sub_query = "SELECT Client_Code FROM Temp_Sorder WHERE Sorder_ID = '" . $PSorId . "'";
		//echo "<br><br>".$sub_query;
		$sub_result = selectData($connection, $sub_query);
		$txtClientCode   = $sub_result[0];
	}

	$getSalesTTC = "Select ifnull(SalesTTC,0) from clients where ledger_number ='$txtClientCode' ";
	$resSalesTTC = mysqli_query($connection, $getSalesTTC);
	$rowsalesTTc = mysqli_fetch_array($resSalesTTC);
	$SalesTTC = $rowsalesTTc[0];

	//Initializations

	$ClassE = 'grid_roundedtext1';
	$ClassI = 'grid_roundedtext2';

	$filepath = 'Forms_DataGrid_SalesOrder.php';

	if ($salesorderdesign == 0) {
		$inputClass = "inputBox-clean";
		$inputRowClass = "inputBoxRow-clean";
		$selectClass = "select-clean";
		$textareaClass = "textarea-clean";
		$formRowClass = "form-row-clean";
	} else {
		// Use original design classes
		$inputClass = "inputBox";
		$inputRowClass = "inputBox";
		$selectClass = "form-control form-control-sm inputBox";
		$textareaClass = "form-control form-control-sm inputBox";
		$formRowClass = "form-row";
	}

	$FieldArray   = array();
	$ButtonArray   = array();

	

	$FieldArray['salesorderitemcodep']['FieldLabel'] = 'Item Code Packed';
	$FieldArray['salesorderitemcode']['FieldLabel'] = 'Item Code';
	$FieldArray['salesorderdesc']['FieldLabel'] = 'Description';
	$FieldArray['salesorderwh']['FieldLabel'] = 'W.H';
	$FieldArray['salesorderprodqty']['FieldLabel'] = 'Prod. Qty';
	$FieldArray['salesorderassto']['FieldLabel'] = 'Assign To';
	$FieldArray['salesorderorderedqty']['FieldLabel'] = 'Ordered Qty';
	$FieldArray['salesordercancelledqty']['FieldLabel'] = 'Cancelled Qty';
	$FieldArray['salesorderquant']['FieldLabel'] = 'Quantity';
	$FieldArray['salesorderprice']['FieldLabel'] = 'Price';
	$FieldArray['salesorderlastprice']['FieldLabel'] = 'Last Price';
	$FieldArray['salesorderdisc']['FieldLabel'] = 'Disc(%)';
	$FieldArray['salesordertotal']['FieldLabel'] = 'Total';
	$FieldArray['salesorderprofitdt']['FieldLabel'] = 'Profit %';

	$FieldArray['salesorderprojectdt']['FieldLabel'] = 'Project';
	$FieldArray['salesordercostcentdt']['FieldLabel'] = 'Costcenter';
	$FieldArray['salesorderexpiry']['FieldLabel'] = 'Expiry Date';

	$FieldArray['salesorderdr']['FieldLabel'] = 'Delete Record';
	$FieldArray['salesorderap']['FieldLabel'] = 'Add Parameters';
	$FieldArray['salesorderfieldsgrid']['FieldLabel'] = 'Grid Fields';
	$FieldArray['salesorderaddunitqty']['FieldLabel'] = 'Add Unit Qty';
	$FieldArray['salesorderaddquantities']['FieldLabel'] = 'Add Quantities';
	$FieldArray['salesorderqtyunit1label']['FieldLabel'] = 'Qty Unit 1';
	$FieldArray['salesorderqtyunit2label']['FieldLabel'] = 'Qty Unit 2';
	$FieldArray['salesorderqtysave']['FieldLabel'] = 'Save';
	$FieldArray['salesordershowd']['FieldLabel'] = 'Show Details';
	$FieldArray['salesorderexistqty']['FieldLabel'] = 'Existing Qty';
	$FieldArray['salesorderbookingqty']['FieldLabel'] = 'Booking Qty';
	$FieldArray['salesorderavqty']['FieldLabel'] = 'Available Qty';
	$FieldArray['salesorderexistunit2qty']['FieldLabel'] = 'Existing Unit2Qty';
	$FieldArray['salesorderw']['FieldLabel'] = 'Select Warehouse';
	$FieldArray['salesorderheat']['FieldLabel'] = 'Select HeatNo';
	$FieldArray['salesorderselectbatch']['FieldLabel'] = 'Select Batch No';
	$FieldArray['salesordersed']['FieldLabel'] = 'Select Expire Date';

	$FieldArray['salesorderheatno']['FieldLabel'] = 'Heat No.';
	$FieldArray['salesorderunit1']['FieldLabel'] = 'Unit1';
	$FieldArray['salesorderunit1qty']['FieldLabel'] = 'Unit1 Qty';
	$FieldArray['salesorderitemunit']['FieldLabel'] = 'Item Unit';

	$FieldArray['salesorderitempack']['FieldLabel'] = 'Item Package';

	$FieldArray['salesorderqtybox']['FieldLabel'] = 'Qty Box';
	$FieldArray['salesorderunit2']['FieldLabel'] = 'Unit2';
	$FieldArray['salesorderunit2qty']['FieldLabel'] = 'Unit2 Qty';
	$FieldArray['salesorderbv']['FieldLabel'] = 'BV Amount';
	$FieldArray['salesorderbppoint']['FieldLabel'] = 'BP Point';
	$FieldArray['salesordercldisc']['FieldLabel'] = 'Client Disc(%)';
	$FieldArray['salesordercolor']['FieldLabel'] = 'Color';
	$FieldArray['salesorderbatchno']['FieldLabel'] = 'Batch No';

	$FieldArray['salesorderrem']['FieldLabel'] = 'Remark';
	$FieldArray['sordersubmitrec']['FieldLabel'] = 'Submit Record';
	$FieldArray['salesordersi']['FieldLabel'] = 'Select Item';
	$FieldArray['sorderclearrec']['FieldLabel'] = 'Clear Record';
	$FieldArray['sordernorectoview']['FieldLabel'] = 'No records to view';



	foreach ($FieldArray as $fieldKey => $fieldInfo) {
		if (!isset($FieldArray[$fieldKey]['IsHidden'])) $FieldArray[$fieldKey]['IsHidden'] = false;
		if (!isset($FieldArray[$fieldKey]['MandatoryLabel'])) $FieldArray[$fieldKey]['MandatoryLabel'] = '';
		if (!isset($FieldArray[$fieldKey]['FieldMod'])) $FieldArray[$fieldKey]['FieldMod'] = '';
	}

	$render_result = get_fields_for_render($form_name, $_SESSION['accessschemaid'], $_SESSION['dbLabel'], $connection, 'grid');
	while ($render_row = mysqli_fetch_assoc($render_result)) {
		$fieldCode = strtolower($render_row['fieldcode']);
		if (isset($FieldArray[$fieldCode])) {
			$FieldArray[$fieldCode]['IsHidden'] = (intval($render_row['is_hidden']) == 1);
		}
	}

	$sub_result = get_labelbuttons($_SESSION['dbLabel'], $_SESSION['accessid'], $connection);
	while ($sub_row = mysqli_fetch_row($sub_result)) {
		$ButtonArray[$sub_row[0]]['ButtonLabel'] = $sub_row[1];
		$ButtonArray[$sub_row[0]]['ButtonTitle'] = $sub_row[2];
	}

	initFormLanguageUI($form_name, $_SESSION['dbLabel'], $connection, $FieldArray);

	$i = 0;

	$PriceLabel = 'Price';
	if ($SalesTTC == 1)  $PriceLabel = 'Price TTC';

	$ModeModifySellPrice = '';
	if ($Login_ModifySellingPrice != 1) $ModeModifySellPrice = 'readonly';

	if ($startrange == '') $startrange = 0;
	if ($perpage == '')	 $perpage = 50;
	if ($orderby == '')	 $orderby = $dftorderby;
	if ($morelines == '')	 $morelines = 1;

	if ($action == 'COR' || $direction == '') {
		if ($direction == 'asc') $direction = 'desc';
		else if ($direction == 'desc') $direction = 'asc';
		else $direction = 'asc';
	}
	$visibleGridColumns = array();
	$applicableGridFieldCodes = array();
	$columnsarray = array();

	if ($Module_Pack == 1) $applicableGridFieldCodes[] = 'salesorderitemcodep';
	$applicableGridFieldCodes[] = 'salesorderitemcode';
	$applicableGridFieldCodes[] = 'salesorderdesc';
	if (!$FieldArray['salesorderitemcode']['IsHidden'] || !$FieldArray['salesorderdesc']['IsHidden']) {
		if (!$FieldArray['salesorderitemcode']['IsHidden'] && !$FieldArray['salesorderdesc']['IsHidden']) $itemcodetabfield = "itemcode_display";
		else if (!$FieldArray['salesorderitemcode']['IsHidden']) $itemcodetabfield = "itemcode";
		else $itemcodetabfield = "itemdescription";
		array_push($visibleGridColumns, $itemcodetabfield);
	}
	if ($Module_ShowWarehouseSalesorder == 1) {
		$applicableGridFieldCodes[] = 'salesorderwh';
		if (!$FieldArray['salesorderwh']['IsHidden']) array_push($visibleGridColumns, "warehousecode");
	}
	if ($Module_Heatno == 1) {
		$applicableGridFieldCodes[] = 'salesorderheatno';
		if (!$FieldArray['salesorderheatno']['IsHidden']) array_push($visibleGridColumns, 'sorderdt_heatno');
	}
	//if ($Module_DoubleUnit == 1) {
	$applicableGridFieldCodes[] = 'salesorderunit1';

	if ($sorder_export == 1) {
		$applicableGridFieldCodes[] = 'salesorderqtybox';
		$applicableGridFieldCodes[] = 'salesorderitemunit';
		$applicableGridFieldCodes[] = 'salesorderitempack';
		if (!$FieldArray['salesorderqtybox']['IsHidden'])
			array_push($visibleGridColumns, 'sorderdt_quantitybox');
		if (!$FieldArray['salesorderitemunit']['IsHidden'])
			array_push($visibleGridColumns, 'sorderdt_itemunit');
		if (!$FieldArray['salesorderitempack']['IsHidden'])
			array_push($visibleGridColumns, 'sorderdt_itempackage');
	}
	if ($sorderproduction == 	1) {
		$applicableGridFieldCodes[] = 'salesorderprodqty';
		$applicableGridFieldCodes[] = 'salesorderassto';
		if (!$FieldArray['salesorderprodqty']['IsHidden'])
			array_push($visibleGridColumns, "itemprodqty");
		if (!$FieldArray['salesorderassto']['IsHidden'])
			array_push($visibleGridColumns, "txtAssignTo");
	}
	$applicableGridFieldCodes[] = 'salesorderorderedqty';
	if (!$FieldArray['salesorderorderedqty']['IsHidden'])
		array_push($visibleGridColumns, "itemorderedqty");
	$applicableGridFieldCodes[] = 'salesordercancelledqty';
	if (!$FieldArray['salesordercancelledqty']['IsHidden'])
		array_push($visibleGridColumns, "cancelledqty");
	$applicableGridFieldCodes[] = 'salesorderquant';
	if (!$FieldArray['salesorderquant']['IsHidden'])
		array_push($visibleGridColumns, "sordtquantity");

	if ($Module_DoubleUnit == 1) {
		$applicableGridFieldCodes[] = 'salesorderunit2';
		$applicableGridFieldCodes[] = 'salesorderunit2qty';
		if (!$FieldArray['salesorderunit2qty']['IsHidden']) array_push($visibleGridColumns, "unit2qty");
	}

	if ($FI_DeliverySalesPrice == 1) {
		$applicableGridFieldCodes[] = 'salesorderprice';
		$applicableGridFieldCodes[] = 'salesorderlastprice';
		$applicableGridFieldCodes[] = 'salesorderdisc';
		$applicableGridFieldCodes[] = 'salesordertotal';
		if (!$FieldArray['salesorderprice']['IsHidden'])
			array_push($visibleGridColumns, 'price');
		if (!$FieldArray['salesorderdisc']['IsHidden'])
			array_push($visibleGridColumns, 'sordtdiscount');
		//if ($Module_ClientDiscount == 1) array_push($visibleGridColumns, 'sordtClientdiscount');
		if (!$FieldArray['salesordertotal']['IsHidden'])
			array_push($visibleGridColumns, 'sordttotal');

		if ($FI_ShowProfit == 1) {
			$applicableGridFieldCodes[] = 'salesorderprofitdt';
			if (!$FieldArray['salesorderprofitdt']['IsHidden']) array_push($visibleGridColumns, 'gpper');
		}
		if ($Module_BPPlan == 1) {
			$applicableGridFieldCodes[] = 'salesorderbv';
			$applicableGridFieldCodes[] = 'salesorderbppoint';
		}
		if ($Module_ClientDiscount == 1) $applicableGridFieldCodes[] = 'salesordercldisc';
	}
	if ($Module_ProjectDt == 1) {
		$applicableGridFieldCodes[] = 'salesorderprojectdt';
		if (!$FieldArray['salesorderprojectdt']['IsHidden']) array_push($visibleGridColumns, 'projectcodedt');
	}
	if ($Module_CostCenterDt == 1 && $ShowCostCentLayout == 1) {
		$applicableGridFieldCodes[] = 'salesordercostcentdt';
		if (!$FieldArray['salesordercostcentdt']['IsHidden']) array_push($visibleGridColumns, 'costcentcodedt');
	}
	if ($Module_BatchNo == 1) {
		$applicableGridFieldCodes[] = 'salesorderbatchno';
		if (!$FieldArray['salesorderbatchno']['IsHidden']) array_push($visibleGridColumns, 'batchno');
	}
	if ($Module_ExpireDate == 1) {
		$applicableGridFieldCodes[] = 'salesorderexpiry';
		if (!$FieldArray['salesorderexpiry']['IsHidden']) array_push($visibleGridColumns, 'expiredate');
	}
	if ($showItemColor == 1) {
		$applicableGridFieldCodes[] = 'salesordercolor';
		if (!$FieldArray['salesordercolor']['IsHidden']) array_push($visibleGridColumns, 'itemcolor');
	}

	if ($SubItem_Production != 1 && $SubItem_Production != 2) {
		$applicableGridFieldCodes[] = 'salesorderrem';
		if (!$FieldArray['salesorderrem']['IsHidden']) array_push($visibleGridColumns, 'remarkdt');
	}
	array_push($visibleGridColumns, 'chkpromo');
	$columnsarray = $visibleGridColumns;
	$columnsarrayall = array("itemcode", "itemcode_display", "itemdescription", "warehousecode", "sorderdt_heatno", "sorderdt_quantitybox", "sorderdt_itemunit", "sorderdt_itempackage", "itemorderedqty", "sordtquantity", "cancelledqty", "unit2qty", "price", "sordtdiscount", "sordttotal", "projectcodedt", "costcentcodedt", "remarkdt", "sordtid", "existingqty", "bookingqty", "availableqty", "lastprice", "itemunit1", "itemunit2", "itemvat", "itemminprice", "itemnonstock", "itemzeroprice", "itemunitcoef");
	$orderbyarray = array("SorderDt.Item_CodePacked", "Items.Item_Code", "Items.Item_Description", "SorderDt.Warehouse_Code", "Items.Item_UnitSalesDesc", "Items.Item_UnitPurchaseDesc");

	for ($j = 1; $j <= count($orderbyarray); $j++) {

		$index = array_search($orderby, $orderbyarray);
		$index += 1;

		$orderimg = 'orderimg' . $index;

		if (in_array($orderby, $orderbyarray) && $direction == 'asc')
			$$orderimg = "<img src='img/asc-2.png' name='w' width='10'  height='10' style='padding-left:5px;'>";
		else if (in_array($orderby, $orderbyarray) && $direction == 'desc')
			$$orderimg = "<img src='img/desc-2.png' name='w' width='10'  height='10' style='padding-left:5px;'>";
		else
			$$orderimg = "";
	}


	$columnslist = implode(',', $columnsarray);
	$columnslistall = implode(',', $columnsarrayall);

	$query_login = "select language_name as Language, access_code as user_account
	from " . $_SESSION['dbLabel'] . ".access 
	Left join " . $_SESSION['dbLabel'] . ".languages on languages.language_id = access_language 
	where access_code='" . $_SESSION['useraccount'] . "' and access_id='" . $_SESSION['accessid'] . "' ";
	$resultlogin = mysqli_query($connection, $query_login) or die(mysqli_error($connection));
	while ($rows = mysqli_fetch_array($resultlogin)) {
		extract($rows);
	}



	$query_field14  = "select clear,cancel,save,search,pending,reference,folio,jv_ref,date1,supplier,maturity,remark,currency,project,costcenter,invoice_no,vat_prefix,
					manual_no,taxable,non_taxable,gross_total,discount,vat,net_total,ledger_number1,ledger_name1,description,description2,asset,qty,price,total,purchase_jv,disc1,total1,
					item_codepack,cancelled_qty,qty1,unit2,unit2_qty,heat_no,transport,total_BP,monthly_BP,previous_BP,item_unit,qty_box,bv_amount,bp_point,last_price,disc,client_disc,
					color,batch_no,expiry_date,WH,item_code,ord_qty
	From " . $_SESSION['dbLabel'] . ".jv_label
	where Language = '" . $Language . "'";
	$result_field14 = mysqli_query($connection, $query_field14);
	$row_field14    = mysqli_fetch_array($result_field14);
	extract($row_field14);


	// $widthTable mirrors every conditional/IsHidden check the header row below
	// applies, per column, using the same .grid-col-* tier the column's <th>
	// gets - same convention as Forms_DataGrid_Delivery.php's ShowDeliveryDtGrid.
	$widthTable = 34 + 34; // leading action cols: field-settings gear + blank
	if ($SubItem_Production == 1 || $SubItem_Production == 2) $widthTable += 34; // extra blank action col
	if ($Module_Pack == 1 && !$FieldArray['salesorderitemcodep']['IsHidden']) $widthTable += 115; // Item Code Packed, grid-col-md
	$showItemCodeHdr = !$FieldArray['salesorderitemcode']['IsHidden'];
	$showItemDescHdr = !$FieldArray['salesorderdesc']['IsHidden'];
	if ($showItemCodeHdr && $showItemDescHdr) $widthTable += 280; // Item Code/Desc combined, grid-col-xxl
	else if ($showItemCodeHdr) $widthTable += 140; // Item Code only, grid-col-lg
	else if ($showItemDescHdr) $widthTable += 195; // Description only, grid-col-xl
	if ($Module_ShowWarehouseSalesorder == 1 && !$FieldArray['salesorderwh']['IsHidden']) $widthTable += 115; // Warehouse, grid-col-md
	if ($Module_Heatno == 1 && !$FieldArray['salesorderheatno']['IsHidden']) $widthTable += 140; // Heat No, grid-col-lg
	if (!$FieldArray['salesorderunit1']['IsHidden']) $widthTable += 80; // Unit1, grid-col-unit
	if ($sorder_export == 1) {
		if (!$FieldArray['salesorderitempack']['IsHidden']) $widthTable += 60; // grid-col-unit-minus
		if (!$FieldArray['salesorderitemunit']['IsHidden']) $widthTable += 60; // grid-col-unit-minus
		if (!$FieldArray['salesorderqtybox']['IsHidden']) $widthTable += 60; // grid-col-unit-minus
	}
	if ($sorderproduction == 1) {
		if (!$FieldArray['salesorderprodqty']['IsHidden']) $widthTable += 90; // grid-col-xs
		if (!$FieldArray['salesorderassto']['IsHidden']) $widthTable += 90; // grid-col-xs
	}
	if (!$FieldArray['salesorderorderedqty']['IsHidden']) $widthTable += 90; // grid-col-xs
	if (!$FieldArray['salesordercancelledqty']['IsHidden']) $widthTable += 90; // grid-col-xs
	if (!$FieldArray['salesorderquant']['IsHidden']) $widthTable += 90; // grid-col-xs
	if ($Module_DoubleUnit == 1 && !$FieldArray['salesorderunit2']['IsHidden']) $widthTable += 60; // grid-col-unit-minus
	if ($Module_DoubleUnit == 1 && !$FieldArray['salesorderunit2qty']['IsHidden']) $widthTable += 80; // grid-col-unit
	if ($FI_DeliverySalesPrice == 1) {
		if (!$FieldArray['salesorderprice']['IsHidden']) $widthTable += 90; // grid-col-xs
		if ($Module_BPPlan == 1) {
			if (!$FieldArray['salesorderbv']['IsHidden']) $widthTable += 105; // grid-col-sm-plus
			if (!$FieldArray['salesorderbppoint']['IsHidden']) $widthTable += 105; // grid-col-sm-plus
		}
		if (!$FieldArray['salesorderlastprice']['IsHidden']) $widthTable += 90; // grid-col-xs
		if (!$FieldArray['salesorderdisc']['IsHidden']) $widthTable += 80; // grid-col-unit
		if ($Module_ClientDiscount == 1 && !$FieldArray['salesordercldisc']['IsHidden']) $widthTable += 80; // grid-col-unit
		if (!$FieldArray['salesordertotal']['IsHidden']) $widthTable += 105; // grid-col-sm-plus
		if ($FI_ShowProfit == 1 && !$FieldArray['salesorderprofitdt']['IsHidden']) $widthTable += 80; // grid-col-unit
	}
	if ($showItemColor == 1 && !$FieldArray['salesordercolor']['IsHidden']) $widthTable += 140; // grid-col-lg
	if ($Module_ProjectDt == 1 && !$FieldArray['salesorderprojectdt']['IsHidden']) $widthTable += 140; // grid-col-lg
	if ($Module_CostCenterDt == 1 && $ShowCostCentLayout == 1 && !$FieldArray['salesordercostcentdt']['IsHidden']) $widthTable += 140; // grid-col-lg
	if ($Module_BatchNo == 1 && !$FieldArray['salesorderbatchno']['IsHidden']) $widthTable += 105; // grid-col-sm-plus
	if ($Module_ExpireDate == 1 && !$FieldArray['salesorderexpiry']['IsHidden']) $widthTable += 105; // grid-col-sm-plus
	// Remark is the one fluid/no-tier column (absorbs leftover space via .grid-col-fluid's 200px floor) - no contribution here.
	$widthTable += 34 + 34; // trailing action cols

	echo "<div id='container' style='position: relative;z-index:1;' >";
	echo "	<div id='Loader' class=''></div>";
	echo " 	<div id='grid_headerDiv' class='grid_headerDiv'>";
	echo "  	<div id='tablespan' class='table-responsive'>
			<table class='table table-bordered table-striped grid-fixed-cols sales-order-item-grid' style='width:100%;min-width:$widthTable'>
			";
	/*  echo "<col width=3%><col width=3%>";
    if ($SubItem_Production == 1) echo "<col width=3%>";
	if ($Module_Pack == 1) echo "<col width=5%>";
    echo "<col width=8%>";
    echo "<col width=10%>";
   
    
    if ($Module_ShowWarehouseSalesorder == 1)   echo "<col width=5%>";
    if ($Module_Heatno == 1) echo "<col width=5%>";
    if (($Module_DoubleUnit == 1)) echo "<col width=5%>";
    if ($sorder_export == 1)   echo "<col width=5%><col width=5%><col width=5%>";
    if ($sorderproduction == 	1)  echo "<col width=5%><col width=5%>";
    echo "<col width=5%><col width=5%><col width=5%>";
	if ($Module_DoubleUnit == 1)  echo "<col width=5%><col width=5%>";

	if ($FI_DeliverySalesPrice == 1) {
		echo "<col width=5%>";
		if ($Module_BPPlan == 1)
			echo  "<col width=5%><col width=5%>";
		echo "  <col width=5%>";
		echo " <col width=5%>";
		if ($Module_ClientDiscount == 1) echo "<col width=5%>";
		echo " <col width=5%>";
	}

	if ($showItemColor == 1) {
		echo " <col width=5%>";
	}
	if ($Module_ProjectDt == 1) {
		echo " <col width=8%>";
	}
	if ($Module_CostCenterDt == 1 && $ShowCostCentLayout == 1) {
		echo " <col width=8%>";
	}
	if ($Module_BatchNo == 1) 	echo " <col width=5%>";
	if ($Module_ExpireDate == 1) 	echo " <col width=8%>";
	if ($SubItem_Production != 1)	echo " <col width=10%>";

	echo "<col width=3%><col width=3%>";
	*/
	$colspan = 6;
    echo "<script type='text/javascript'>if(window.salesOrderApplicableFieldCodes){salesOrderApplicableFieldCodes.grid = " . json_encode(array_values(array_unique($applicableGridFieldCodes))) . ";}</script>";
	echo "<thead><tr class='grid-header-row'>";
	echo "  <th class='grid-header-cell grid-col-xxs'><span class='grid-header-label'>";
	echo "&nbsp;</span></th>";
	echo "  <th class='grid-header-cell grid-col-xxs'><span class='grid-header-label'>&nbsp;</span></th>";
	echo "  <th class='grid-header-cell grid-col-xxs'><span class='grid-header-label'>&nbsp;</span></th>";
	if ($SubItem_Production == 1 || $SubItem_Production == 2) {
		echo "  <th class='grid-header-cell grid-col-xxs'></th> ";
		$colspan++;
	}
	if ($Module_Pack == 1 && !$FieldArray['salesorderitemcodep']['IsHidden']) {
		echo "  <th class='grid-header-cell grid-col-md' onclick=\"ReloadGrid('" . $filepath . "', '" . $form . "', '" . $PSorId . "', 'datagrid', 1, '" . $dftorderby . "', 'COR','SorderDt.Item_CodePacked','" . $morelines . "','" . $startrange . "','" . $perpage . "','" . $totalrows . "','" . $direction . "');\"><span class='grid-header-label'>" . $FieldArray['salesorderitemcodep']['FieldLabel'] . "" . $orderimg1 . "</span></th>";
		$colspan++;
	}
	$showItemCodeHdr = !$FieldArray['salesorderitemcode']['IsHidden'];
	$showItemDescHdr = !$FieldArray['salesorderdesc']['IsHidden'];
	if ($showItemCodeHdr && $showItemDescHdr) {
		// Combined Item Code / Description column, same as Forms_DataGrid1.php's itemcode_display pattern.
		echo "  <th class='grid-header-cell grid-col-xxl' onclick=\"ReloadGrid('" . $filepath . "', '" . $form . "', '" . $PSorId . "', 'datagrid', 1, '" . $dftorderby . "', 'COR','Items.Item_Code','" . $morelines . "','" . $startrange . "','" . $perpage . "','" . $totalrows . "','" . $direction . "');\"><span class='grid-header-label'>" . $FieldArray['salesorderitemcode']['FieldLabel'] . " / " . $FieldArray['salesorderdesc']['FieldLabel'] . "" . $orderimg2 . "</span></th>";
	} else if ($showItemCodeHdr) {
		echo "  <th class='grid-header-cell grid-col-lg' onclick=\"ReloadGrid('" . $filepath . "', '" . $form . "', '" . $PSorId . "', 'datagrid', 1, '" . $dftorderby . "', 'COR','Items.Item_Code','" . $morelines . "','" . $startrange . "','" . $perpage . "','" . $totalrows . "','" . $direction . "');\"><span class='grid-header-label'>" . $FieldArray['salesorderitemcode']['FieldLabel'] . "" . $orderimg2 . "</span></th>";
	} else if ($showItemDescHdr) {
		echo "  <th class='grid-header-cell grid-col-xl' onclick=\"ReloadGrid('" . $filepath . "', '" . $form . "', '" . $PSorId . "', 'datagrid', 1, '" . $dftorderby . "', 'COR','Items.Item_Description','" . $morelines . "','" . $startrange . "','" . $perpage . "','" . $totalrows . "','" . $direction . "');\"><span class='grid-header-label'>" . $FieldArray['salesorderdesc']['FieldLabel'] . "" . $orderimg3 . "</span></th>";
	}

	if ($Module_ShowWarehouseSalesorder == 1 && !$FieldArray['salesorderwh']['IsHidden']) {
		echo "  <th class='grid-header-cell grid-col-md' onclick=\"ReloadGrid('" . $filepath . "', '" . $form . "', '" . $PSorId . "', 'datagrid', 1, '" . $dftorderby . "', 'COR','SorderDt.Warehouse_Code','" . $morelines . "','" . $startrange . "','" . $perpage . "','" . $totalrows . "','" . $direction . "');\"><span class='grid-header-label'>" . $FieldArray['salesorderwh']['FieldLabel'] . "" . $orderimg4 . "</span></th>";
		$colspan++;
	}
	if ($Module_Heatno == 1 && !$FieldArray['salesorderheatno']['IsHidden']) {

		echo "  <th class='grid-header-cell grid-col-lg'><span class='grid-header-label'>" . $FieldArray['salesorderheatno']['FieldLabel'] . ".</span></th>";
		$colspan++;
	}
	if (!$FieldArray['salesorderunit1']['IsHidden']) {
		echo "  <th class='grid-header-cell grid-col-unit' onclick=\"ReloadGrid('" . $filepath . "', '" . $form . "', '" . $PSorId . "', 'datagrid', 1, '" . $dftorderby . "', 'COR','SorderDt.Item_UnitSalesDesc','" . $morelines . "','" . $startrange . "','" . $perpage . "','" . $totalrows . "','" . $direction . "');\"><span class='grid-header-label'>" . $FieldArray['salesorderunit1']['FieldLabel'] . "" . $orderimg5 . "</span></th>";
		$colspan++;
	}
	//else
	//       echo"  <th width='0px;' align='center' onclick=\"ReloadGrid('".$filepath."', '".$form."', '".$PSorId."', 'datagrid', 1, '".$dftorderby."', 'COR','SorderDt.Item_UnitSalesDesc','".$morelines."','".$startrange."','".$perpage."','".$totalrows."','".$direction."');\"><span>".$orderimg5."</span></th>";

	if ($sorder_export == 1) {
		if (!$FieldArray['salesorderitempack']['IsHidden'])
			echo "  <th class='grid-header-cell grid-col-unit-minus' onclick=\"ReloadGrid('" . $filepath . "', '" . $form . "', '" . $PSoBoxrId . "', 'datagrid', 1, '" . $dftorderby . "', 'COR','Items.Item_UnitSalesDesc','" . $morelines . "','" . $startrange . "','" . $perpage . "','" . $totalrows . "','" . $direction . "');\"><span class='grid-header-label'>" . $FieldArray['salesorderitempack']['FieldLabel'] . "" . $orderimg5 . "</span></th>";
		if (!$FieldArray['salesorderitemunit']['IsHidden'])
			echo "  <th class='grid-header-cell grid-col-unit-minus' onclick=\"ReloadGrid('" . $filepath . "', '" . $form . "', '" . $PSorId . "', 'datagrid', 1, '" . $dftorderby . "', 'COR','Items.Item_UnitSalesDesc','" . $morelines . "','" . $startrange . "','" . $perpage . "','" . $totalrows . "','" . $direction . "');\"><span class='grid-header-label'>" . $FieldArray['salesorderitemunit']['FieldLabel'] . "" . $orderimg5 . "</span></th>";
		if (!$FieldArray['salesorderqtybox']['IsHidden'])
			echo "  <th class='grid-header-cell grid-col-unit-minus' onclick=\"ReloadGrid('" . $filepath . "', '" . $form . "', '" . $PSorId . "', 'datagrid', 1, '" . $dftorderby . "', 'COR','Items.Item_UnitSalesDesc','" . $morelines . "','" . $startrange . "','" . $perpage . "','" . $totalrows . "','" . $direction . "');\"><span class='grid-header-label'>" . $FieldArray['salesorderqtybox']['FieldLabel'] . "" . $orderimg5 . "</span></th>";
		$colspan++;
		$colspan++;
		$colspan++;
	}

	if ($sorderproduction == 	1) {


		if (!$FieldArray['salesorderprodqty']['IsHidden']) {
			echo "  <th class='grid-header-cell grid-col-xs'><span class='grid-header-label'>" . $FieldArray['salesorderprodqty']['FieldLabel'] . "</span></th>";
			$colspan++;
		}
		if (!$FieldArray['salesorderassto']['IsHidden']) {
			echo "		<!-- <th  align='center' width=5%><span class='style1'>Sent To Prod.</span></th> -->
				<th class='grid-header-cell grid-col-xs'><span class='grid-header-label'>" . $FieldArray['salesorderassto']['FieldLabel'] . "</span></th>";
			$colspan++;
		}
	}
	if (!$FieldArray['salesorderorderedqty']['IsHidden'])
		echo "  <th class='grid-header-cell grid-col-xs'><span class='grid-header-label'>" . $FieldArray['salesorderorderedqty']['FieldLabel'] . "</span></th> ";
	if (!$FieldArray['salesordercancelledqty']['IsHidden'])
		echo "  <th class='grid-header-cell grid-col-xs'><span class='grid-header-label'>" . $FieldArray['salesordercancelledqty']['FieldLabel'] . "</span></th>";
	if (!$FieldArray['salesorderquant']['IsHidden'])
		echo "  <th class='grid-header-cell grid-col-xs'><span class='grid-header-label'>" . $FieldArray['salesorderquant']['FieldLabel'] . "</span></th>";
	if ($Module_DoubleUnit == 1 && !$FieldArray['salesorderunit2']['IsHidden']) {
		echo "  <th class='grid-header-cell grid-col-unit-minus' onclick=\"ReloadGrid('" . $filepath . "', '" . $form . "', '" . $PSorId . "', 'datagrid', 1, '" . $dftorderby . "', 'COR','Items.Item_UnitPurchaseDesc','" . $morelines . "','" . $startrange . "','" . $perpage . "','" . $totalrows . "','" . $direction . "');\"><span class='grid-header-label'>" . $FieldArray['salesorderunit2']['FieldLabel'] . "" . $orderimg6 . "</span></th>";
		$colspan++;
	}

	if ($Module_DoubleUnit == 1) {

		if (!$FieldArray['salesorderunit2qty']['IsHidden']) {
			echo "  <th class='grid-header-cell grid-col-unit'><span class='grid-header-label'>" . $FieldArray['salesorderunit2qty']['FieldLabel'] . "</span></th>";
			$colspan++;
		}
	}

	if ($FI_DeliverySalesPrice == 1) {

		if (!$FieldArray['salesorderprice']['IsHidden']) {
			echo "  <th class='grid-header-cell grid-col-xs'><span class='grid-header-label'>" . $FieldArray['salesorderprice']['FieldLabel'] . "</span></th>";
			$colspan++;
		}
		if ($Module_BPPlan == 1) {
			if (!$FieldArray['salesorderbv']['IsHidden']) {
				echo "  <th class='grid-header-cell grid-col-sm-plus'><span class='grid-header-label'>" . $FieldArray['salesorderbv']['FieldLabel'] . "</span></th>";
				$colspan++;
			}
			if (!$FieldArray['salesorderbppoint']['IsHidden']) {
				echo "	<th class='grid-header-cell grid-col-sm-plus'><span class='grid-header-label'>" . $FieldArray['salesorderbppoint']['FieldLabel'] . "</span></th>";
				$colspan++;
			}
		}
		if (!$FieldArray['salesorderlastprice']['IsHidden']) {
			echo "  <th class='grid-header-cell grid-col-xs'><span class='grid-header-label'>" . $FieldArray['salesorderlastprice']['FieldLabel'] . "</span></th> ";
			$colspan++;
		}
		if (!$FieldArray['salesorderdisc']['IsHidden']) {
			echo "  <th class='grid-header-cell grid-col-unit'><span class='grid-header-label'>" . $FieldArray['salesorderdisc']['FieldLabel'] . "</span></th>";
			$colspan++;
		}
		if ($Module_ClientDiscount == 1 && !$FieldArray['salesordercldisc']['IsHidden']) {
			echo "  <th class='grid-header-cell grid-col-unit'><span class='grid-header-label'>" . $FieldArray['salesordercldisc']['FieldLabel'] . "</span></th>";
			$colspan++;
		}
		if (!$FieldArray['salesordertotal']['IsHidden']) {
			echo "  <th class='grid-header-cell grid-col-sm-plus'><span class='grid-header-label'>" . $FieldArray['salesordertotal']['FieldLabel'] . "</span></th>";
			$colspan++;
		}

		if ($FI_ShowProfit == 1 && !$FieldArray['salesorderprofitdt']['IsHidden']) echo "  <th class='grid-header-cell grid-col-unit'><span class='grid-header-label'>" . $FieldArray['salesorderprofitdt']['FieldLabel'] . "</span></th>";
	}
	if ($showItemColor == 1 && !$FieldArray['salesordercolor']['IsHidden']) {
		echo "  <th class='grid-header-cell grid-col-lg'><span class='grid-header-label'>" . $FieldArray['salesordercolor']['FieldLabel'] . "</span></th>";
		$colspan++;
	}
	if ($Module_ProjectDt == 1 && !$FieldArray['salesorderprojectdt']['IsHidden']) {
		echo "  <th class='grid-header-cell grid-col-lg'><span class='grid-header-label'>" . $FieldArray['salesorderprojectdt']['FieldLabel'] . "</span></th>";
		$colspan++;
	}
	if ($Module_CostCenterDt == 1 && $ShowCostCentLayout == 1 && !$FieldArray['salesordercostcentdt']['IsHidden']) {
		echo "  <th class='grid-header-cell grid-col-lg'><span class='grid-header-label'>" . $FieldArray['salesordercostcentdt']['FieldLabel'] . "</span></th>";
		$colspan++;
	}
	if ($Module_BatchNo == 1 && !$FieldArray['salesorderbatchno']['IsHidden']) {
		echo "  <th class='grid-header-cell grid-col-sm-plus'><span class='grid-header-label'>" . $FieldArray['salesorderbatchno']['FieldLabel'] . "</span></th>";
		$colspan++;
	}
	if ($Module_ExpireDate == 1 && !$FieldArray['salesorderexpiry']['IsHidden']) {
		echo "  <th class='grid-header-cell grid-col-sm-plus'><span class='grid-header-label'>" . $FieldArray['salesorderexpiry']['FieldLabel'] . "</span></th>";
		$colspan++;
	}
	if ($SubItem_Production != 1 && $SubItem_Production != 2 && !$FieldArray['salesorderrem']['IsHidden']) {
		echo "  <th class='grid-header-cell grid-col-fluid'><span class='grid-header-label'>" . $FieldArray['salesorderrem']['FieldLabel'] . "</span></th>";
		$colspan++;
	}

	echo "  <th class='grid-header-cell grid-col-xxs'><span class='grid-header-label'>&nbsp;</span></th>";
	echo "</tr></thead>";

	// echo"<div id='grid_contentDiv' class='grid_contentDiv' onscroll=\"ScrollHeader('grid_headerDiv','grid_contentDiv')\">";
	// echo"<table border='0' align='left' id='grid_datatable' class='grid_data' cellpadding='0' cellspacing='1' style='width:1400px;'>";
	echo "<input type='hidden' name='SelectedRec' id='SelectedRec' value='$SelectedRec'>
			 <input type='hidden' name='SelectedRecColor' id='SelectedRecColor' value='$SelectedRecColor'>
			 <input type='hidden' name='sordtClientdiscount[0]' id='sordtClientdiscount[0]' value='$SelectedRecColor'>
			 <input type='hidden' name='ModRec_G1' id='ModRec_G1' value='$ModRec_G1'>";




	if ($PSorId != '') {

		$TotalQty = 0;
		$i_nonstock  = 0;
		$i_zeroprice = 0;
		$i_minprice  = 0;
		$i_unitcoef  = 1;

		$query = " Select SorderDt.SorderDt_id, SorderDt.Item_CodePacked, SorderDt.Item_code, Item_Description, SorderDt.Warehouse_Code, " .
			" SorderDt_Remark, SorderDt_Quantity, Round(SorderDt_Price, " . $DecimalBase . "), SorderDt_PriceBase1, SroderDt_PriceBase2, " .
			" Round(SorderDt_Discount, " . $DecimalBase . "),  " .
			" Round(((SorderDt_Quantity*SorderDt_Price)-((SorderDt_Quantity*SorderDt_Price*ifNull(SorderDt_Discount,0))/100)), " . $DecimalBase . ") As Total, " .
			" Round(SorderDt_PriceTTC, " . $DecimalBase . "), " .
			" Round(((SorderDt_Quantity*SorderDt_PriceTTC)-((SorderDt_Quantity*SorderDt_PriceTTC*ifNull(SorderDt_Discount,0))/100)), " . $DecimalBase . ") As TotalTTC, " .
			" SorderDt.Item_Unit2Qty, Item_UnitPurchaseDesc, Item_UnitSalesDesc, SorderDt.Project_code, SorderDt.CostCent_Code, ifNull(SorderDt_oqty,0), ifNull(SorderDt_cqty,0) " .
			" ,SorderDt.sorderdt_quantitybox sorderdt_quantitybox,SorderDt.sorderdt_itemunit sorderdt_itemunit,SorderDt.sorderdt_itempackage as sorderdt_itempackage, " .
			" sorderdt_clientdiscount,sorderdt_itemdiscount, sorderdt_desc1, sorderdt_heatno , ifNull(sorderdt_promotion,0), sorderdt_ProdQty, ifnull(sorderdt_sentProduction,0)," .
			" sorderdt_colorCode,sorderdt_batchno, date_format(sorderdt_ExpireDate,'%d/%m/%Y'), sorderdt_businesspt, sorderdt_businessPtBv, ifnull(sorderdt_itemIsGift,0), " .
			" ifnull(Sorderdt_discper,0), sorderdt_assignUser, items.item_picture, items.item_picture2 ,sorderdt_qtyUnit1,sorderdt_qtyUnit2" .
			" From Temp_SorderDt As SorderDt Left Join Items on SorderDt.Item_code = Items.Item_code " .
			" Where SorderDt.Sorder_ID = " . $PSorId .
			" Order By $orderby $direction ";

	//	echo $query;

		$result = mysqli_query($connection, $query);
		$totalrows = mysqli_num_rows($result);

		$query = $query . " limit " . $startrange . "," . $perpage;
		$resultdt = mysqli_query($connection, $query);


		while ($rowsdt = mysqli_fetch_array($resultdt)) {

			$recno = $i + 1;

			if ($i % 2 == 0) $bgcolor = 'grid-row-even';
			else $bgcolor = 'grid-row-odd';

			$sorderdt_clientdiscount[$i]	= $rowsdt[24];
			$sorderdt_itemdiscount[$i] = $rowsdt[25];


			$sub_query  = " Select QtyExistsWSorder('" . $rowsdt[2] . "', '" . $rowsdt[4] . "', '" . insertMySQLDate($txtDate) . "', 0, 'SO', '', '') QtyExists From Dual ";
			//$sub_query  = " Select 0 From Dual "; //shukri
			//echo $sub_query;
			$sub_result = selectData($connection, $sub_query);
			$vItemQtyExists = 0;
			$vItemQtyExists = $sub_result[0];

			$TextColor = 'style7';
			$Color = '';
			if ($vItemQtyExists < 0) {
				$Color = "background-color:var(--grid-danger-bg);";
				$TextColor = 'style8';
			}

			$sub_query = " SELECT ifNull(Item_Pack, 0), ifNull(item_zeroPrice, 0), Item_UnitSalesDesc, Item_UnitPurchaseDesc, ifNull(Item_Unit,1), ifNull(Item_NonStock, 0), ifNull(Item_MinimumPrice, 0) " .
				" FROM Items " .
				" WHERE Items.Item_Code = '" . $rowsdt[2] . "'";

			//echo "<br><br>".$sub_query;
			$sub_result = selectData($connection, $sub_query);
			$i_pack      = $sub_result[0];
			$i_zeroprice = $sub_result[1];
			$i_unit1     = $sub_result[2];
			$i_unit2 	 = $sub_result[3];
			$i_unitcoef	 = $sub_result[4];
			$i_nonstock  = $sub_result[5];
			$i_minprice  = $sub_result[6];

			$sorderdt_quantitybox  = $rowsdt[21];
			$sorderdt_itemunit  = $rowsdt[22];
			$sorderdt_itempackage  = $rowsdt[23];

			//if item is gift
			$isGiftRead = "";
			if ($rowsdt[36] != 0) $isGiftRead = "readonly";

			$sub_query = "SELECT ifNull(Client_SellingPrice,1) FROM Clients WHERE Ledger_Number = '" . $txtClientCode . "'";
			//echo "<br><br>".$sub_query;
			$sub_result = selectData($connection, $sub_query);
			$c_sp    = $sub_result[0];

			$txtItemMinPrice[$i]  = $i_minprice;
			$txtItemZeroPrice[$i] = $i_zeroprice;
			$txtItemNonStock[$i]  = $i_nonstock;
			$txtItemUnitCoef[$i]  = $i_unitcoef;

			$Qry_QtyBookTemp = "SELECT ifnull(QtyBooking_Temp('" . $rowsdt[2] . "', '" . $rowsdt[4] . "', '" . insertMySQLDate($txtDate) . "', $rowsdt[0]),0) ";
			$Qry_QtyBookTemp = "SELECT 0 from dual";  //shukri
			$result = selectData($connection, $Qry_QtyBookTemp);
			if ($result[0] < 0) {
				$result[0] = 0;
			}
			$min_book_qty = $result[0];

			$Qry_QtyExists = " SELECT ifnull(QtyExists('" . $rowsdt[2] . "', '" . $rowsdt[4] . "', '" . insertMySQLDate($txtDate) . "', $rowsdt[0], 'SO', '', ''),0) As QtyExists ";
			//$Qry_QtyExists = " SELECT 0 As QtyExists ";	//shukri
			$result = selectData($connection, $Qry_QtyExists);
			$min_exists_qty = $result[0];
			//echo $Qry_QtyExists;

			$Qry_Unit2QtyExists = " SELECT ifnull(Unit2QtyExists('" . $rowsdt[2] . "', '" . $rowsdt[4] . "', '" . insertMySQLDate($txtDate) . "', $rowsdt[0], 'SO', '', ''),0) As QtyExists ";
			$Qry_Unit2QtyExists = " SELECT 0 As QtyExists "; //shurki
			$result = selectData($connection, $Qry_Unit2QtyExists);
			$min_exists_unit2qty = $result[0];
			//echo $Qry_Unit2QtyExists;

			$Qry_QtyAvailable = " Select QtyExistsWSorder_Temp('" . $rowsdt[2] . "', '" . $rowsdt[4] . "', '" . insertMySQLDate($txtDate) . "', $rowsdt[0], 'SO', '', '', 0) QtyExists From Dual ";
			//$Qry_QtyAvailable = " Select 0 as QtyExists From Dual "; //shukri
			$result = selectData($connection, $Qry_QtyAvailable);
			$min_available_qty = $result[0];
			//echo $Qry_QtyAvailable;

			$Qry_QtyInTemp = " Select ifNull(Sum(ifNull(SorderDt_Quantity,0)),0) FROM Temp_Sorderdt " .
				" WHERE Item_code = '" . $rowsdt[2] . "'  And  Warehouse_code = '" . $rowsdt[4] . "' And Sorder_ID = $PSorId And SorderDt_ID != ifNull('" . $rowsdt[0] . "',0) ";
			$result = selectData($connection, $Qry_QtyInTemp);
			$QtyTemp_SO = $result[0];

			$Qry_QtyTransferInTemp = " Select ifNull(Sum(ifNull(TransferDt_Quantity,0)),0) FROM Temp_TransferDt " .
				" WHERE Item_code = '" . $rowsdt[2] . "'  And  TransferDt_WarehouseFrom = '" . $rowsdt[4] . "' ";
			$result = selectData($connection, $Qry_QtyTransferInTemp);
			$QtyTemp_TR = $result[0];

			// $min_available_qty = $min_available_qty - $QtyTemp_SO - $QtyTemp_TR;
			$min_exists_qty = $min_exists_qty;
			$min_book_qty = $min_book_qty + $QtyTemp_SO + $QtyTemp_TR;
			$min_available_qty = $min_available_qty - $min_book_qty;

			$txtBookingQty[$i]    = $min_book_qty;
			$txtAvailableQty[$i]  = $min_available_qty;
			$txtExistingQty[$i]   = $min_exists_qty;
			$txtExistUnit2Qty[$i] = $min_exists_unit2qty;

			$qmeancost = "Select round(Meancostcur('" . $rowsdt[2] . "','" . insertMySQLDate($txtDate) . "',if(upper('$txtCurrCode') = upper('$Base2'), 2,1), if(upper('$txtCurrCode') = upper('$Base2'), '$Base2','$Base1'),'$txtCurrCode'),2)";
			$resultmean = mysqli_query($connection, $qmeancost);
			$rowmean = mysqli_fetch_array($resultmean);
			$meancost = 	$rowmean[0];


			$KeyDown   = 'onkeydown="return dokey(event,this,' . $i . ',\'' . $columnslist . '\',\'imgSubmit[' . $i . ']\')"';

			echo "<tr id='CurRec$i' class='$bgcolor table-focus-g1'>";
			echo "	<td class='grid-action-cell'>$recno</td>";
			echo "	<td class='grid-action-cell'><img src='img/delete.png' class='grid-action-img' onMouseOver=\"style.cursor='pointer'\" onclick=\"DeleteRecordGrid('" . $filepath . "','" . $form . "', '" . $PSorId . "','" . $rowsdt[0] . "','" . $i . "','" . $dftorderby . "','" . $orderby . "','" . $morelines . "','" . $startrange . "','" . $perpage . "','" . $direction . "');return false;\" title='" . $FieldArray['salesorderdr']['FieldLabel'] . "'></td>";
			echo "	<td class='grid-action-cell'><img src='img/confirm-1.png' class='grid-action-img' id='imgSubmit[$i]' onMouseOver=\"style.cursor='pointer'\" onclick=\"SubmitRecordGrid('" . $i . "','" . $filepath . "', '" . $form . "', '" . $PSorId . "', '" . $dftorderby . "','" . $orderby . "','" . $morelines . "','" . $startrange . "','" . $perpage . "','" . $direction . "',$Module_NegativeSorder,$Login_NegativeQty,$Login_AllowPrice,$Module_SorderCheckQtyOnPost,$Allow_MinimumPrice,$Module_RestrictProjCost,$Module_RestrictCost,$txtClientDetail,$FI_SalesCheckSalesman,$txtClientPromotion,$Module_ShowWarehouseSalesorder); return false;\" title='" . $FieldArray['sordersubmitrec']['FieldLabel'] . "'></td>";
			if ($SubItem_Production == 1 || $SubItem_Production == 2)
				echo "  <td class='grid-action-cell'><img src='img/show.png' class='grid-action-img' onmouseover=\"style.cursor='hand'\" onclick=\"AddParamter('$i', '$rowsdt[0]');\" title='" . $FieldArray['salesorderap']['FieldLabel'] . "'></td> ";
			if ($Module_Pack == 1 && !$FieldArray['salesorderitemcodep']['IsHidden'])
				echo "	<td class='grid-col-md' style='" . $Color . "'><span class='" . $TextColor . "'>" . $rowsdt[1] . "</span></td>";
			$showItemCode = !$FieldArray['salesorderitemcode']['IsHidden'];
			$showItemDesc = !$FieldArray['salesorderdesc']['IsHidden'];

			$itemCodeHiddenFields = "
							<input type='hidden' name='txtItemVat[$i]' id='itemvat[$i]' value='$txtItemVat[$i]' >
							<input type='hidden' name='txtItemMinPrice[$i]' id='itemminprice[$i]' value='$txtItemMinPrice[$i]' >
							<input type='hidden' name='txtItemNonStock[$i]' id='itemnonstock[$i]' value='$txtItemNonStock[$i]' >
							<input type='hidden' name='txtItemZeroPrice[$i]' id='itemzeroprice[$i]' value='$txtItemZeroPrice[$i]' >
							<input type='hidden' name='txtItemUnitCoef[$i]' id='itemunitcoef[$i]' value='$txtItemUnitCoef[$i]' >
							<input type='hidden' name='txtSorDtId[$i]' id='sordtid[$i]' value='" . $rowsdt[0] . "' >
							<input type='hidden' name='txtExistingQty[$i]' id='existingqty[$i]' value='$txtExistingQty[$i]' >
							<input type='hidden' name='txtBookingQty[$i]' id='bookingqty[$i]' value='$txtBookingQty[$i]' >
							<input type='hidden' name='txtAvailableQty[$i]' id='availableqty[$i]' value='$txtAvailableQty[$i]' >
							<input type='hidden' name='txtExistUnit2Qty[$i]' id='exitunit2qty[$i]' value='$txtExistUnit2Qty[$i]' >
							<input type='hidden' name='sorderdt_clientdiscount[$i]' id='sorderdt_clientdiscount[$i]' value='$sorderdt_clientdiscount[$i]'>
							<input type='hidden' name='sorderdt_itemdiscount[$i]' id='sorderdt_itemdiscount[$i]' value='$sorderdt_itemdiscount[$i]' >";

			// Existing/booking/available qty popover, shared by both the combined and item-code-only layouts below.
			

			if ($showItemCode && $showItemDesc) {
				// Combined Item Code / Description column: itemcode[$i] and itemdescription[$i] stay as the
				// real hidden values (still read by GetSelectedItem/CheckLookup/ClearRecordGrid etc.), while
				// itemcode_display[$i] is the single visible "code · description" field the user types/sees in,
				// same pattern as Forms_DataGrid1.php's itemcode_display.
				$itemChangeHandlers = "SetModRecord('ModRec_G1','$i'); CheckLookup('chkcodeexists','items','item_code',this.value,'lstItemCode[$i]'); GetSelectedItem(this.id,'getitem','" . $i . "','" . $txtDate . "','" . $txtCurrCode . "','" . $txtClientCode . "'); ";
				if ($Module_ClientDiscount == 1) $itemChangeHandlers .= "UpdateDiscountHeader('$i');";
				$itemDisplayValue = $rowsdt[2] . ($rowsdt[3] != '' ? ' &middot; ' . $rowsdt[3] : '');

				echo "	<td class='grid-cell-nowrap' title='" . $rowsdt[2] . "'>" . $itemCodeHiddenFields . "
							<input type='hidden' name='txtItemCode[$i]' id='itemcode[$i]' value='" . $rowsdt[2] . "' onchange=\"$itemChangeHandlers\">
							<input type='hidden' name='txtItemDesc[$i]' id='itemdescription[$i]' value='" . $rowsdt[3] . "' onchange=\"CheckLookup('chkcodeexists','items','item_description',this.value,'lstItemDesc[$i]');\">
							<div class='grid-inline-control'>
							
							<span class='style4 grid-input-wrap'><input type='text' name='itemcode_display[$i]' id='itemcode_display[$i]' value='" . $itemDisplayValue . "' maxlength='120' class='roundedtext_amortization_mediumlarge' onfocus='this.select();' onblur=\"checkduplicate($i,document.getElementById('itemcode[$i]').value); var self=this; setTimeout(function(){ var _c=document.getElementById('itemcode[$i]').value; var _desc=document.getElementById('itemdescription[$i]').value; if(_c) self.value=_c+(_desc?' &middot; '+_desc:''); },20);\" oninput=\"document.getElementById('itemcode[$i]').value=this.value; document.getElementById('itemdescription[$i]').value='';\" onkeyup=\"AutoCompleteData(event,this.id,'itemcode[$i]','itemdescription[$i]','items');\" " . $KeyDown . "></span>
							<img src='img/openlist.png' class='grid-action-img' title='" . $FieldArray['salesordersi']['FieldLabel'] . "' id='lstItemCode[$i]' onMouseOver=\"style.cursor='pointer'\" onClick='showlistItems(1,$i); '>
							</div></td>";
			} else if ($showItemCode) {
				echo "	<td class='grid-cell-nowrap'>" . $itemCodeHiddenFields . "
							<div class='grid-inline-control'>
							
							<span class='style4 grid-input-wrap'><input type='text' name='txtItemCode[$i]' id='itemcode[$i]' value='" . $rowsdt[2] . "'  maxlength='20' class='roundedtext_amortization_small'   onblur='checkduplicate($i,this.value)'  onchange=\"SetModRecord('ModRec_G1','$i'); CheckLookup('chkcodeexists','items','item_code',this.value,'lstItemCode[$i]'); GetSelectedItem(this.id,'getitem','" . $i . "','" . $txtDate . "','" . $txtCurrCode . "','" . $txtClientCode . "'); ";
				if ($Module_ClientDiscount == 1) echo "UpdateDiscountHeader('$i');";
				echo " \" onkeyup=\"AutoCompleteData(event,this.id,'itemcode[$i]','itemdescription[$i]','items');\" " . $KeyDown . "></span>
							<img src='img/openlist.png' class='grid-action-img' title='" . $FieldArray['salesordersi']['FieldLabel'] . "' id='lstItemCode[$i]' onMouseOver=\"style.cursor='pointer'\" onClick='showlistItems(1,$i); '>
							</div></td>";
				echo "<input type='hidden' name='txtItemDesc[$i]' id='itemdescription[$i]' value='" . $rowsdt[3] . "'>";
			} else if ($showItemDesc) {
				echo "<input type='hidden' name='txtItemCode[$i]' id='itemcode_hidden[$i]' value='$txtItemCode[$i]' >" . $itemCodeHiddenFields;
				echo "  	<td class='grid-cell-nowrap'><span class='grid-single-control'><input type='text' name='txtItemDesc[$i]' id='itemdescription[$i]' value='" . $rowsdt[3] . "' class='roundedtext_amortization_medium' onchange=\"CheckLookup('chkcodeexists','items','item_description',this.value,'lstItemDesc[$i]');\" " . $KeyDown . "></span>
						</td>";
			} else {
				echo "<input type='hidden' name='txtItemCode[$i]' id='itemcode_hidden[$i]' value='$txtItemCode[$i]' >" . $itemCodeHiddenFields;
				echo "<input type='hidden' name='txtItemDesc[$i]' id='itemdescription[$i]' value='" . $rowsdt[3] . "'>";
			}
			if ($Module_ShowWarehouseSalesorder == 1 && !$FieldArray['salesorderwh']['IsHidden'])
				echo "  	<td class='grid-col-md'><div class='grid-inline-control'>
							<span class='style4 grid-input-wrap'><input type='text' name='txtWarehouseCode[$i]' id='warehousecode[$i]' value='" . $rowsdt[4] . "'  maxlength='20' class='roundedtext_amortization_small' onchange=\"SetModRecord('ModRec_G1','$i'); CheckLookup('chkcodeexists','warehouse','warehouse_code',this.value,'lstWarehouseCode[$i]');\" onkeyup=\"AutoCompleteData(event,this.id,'warehousecode[$i]','warehousename[$i]','warehouse');\" " . $KeyDown . "></span>
							<img src='img/openlist.png' class='grid-action-img' title='" . $FieldArray['salesorderw']['FieldLabel'] . "' id='lstWarehouseCode[$i]' onMouseOver=\"style.cursor='pointer'\" onClick='showlistWarehouse($i);'>
						</div></td>";
			else echo " <input type='hidden' name='txtWarehouseCode[$i]' id='warehousecode[$i]' value='" . $rowsdt[4] . "' onchange=\"SetModRecord('ModRec_G1','$i'); CheckLookup('chkcodeexists','warehouse','warehouse_code',this.value,'lstWarehouseCode[$i]');\" onkeyup=\"AutoCompleteData(event,this.id,'warehousecode[$i]','warehousename[$i]','warehouse');\" " . $KeyDown . ">";
			if ($Module_Heatno == 1 && !$FieldArray['salesorderheatno']['IsHidden'])
				echo "  	<td class='grid-col-lg'><div class='grid-inline-control'>
							<span class='style4 grid-input-wrap'><input type='text' name='sorderdt_heatno[$i]' id='sorderdt_heatno[$i]' value='" . $rowsdt[27] . "'  maxlength='20' class='roundedtext_amortization_small' onchange=\"SetModRecord('ModRec_G1','$i');\"  " . $KeyDown . "></span>
							<img src='img/openlist.png' class='grid-action-img' title='" . $FieldArray['salesorderheat']['FieldLabel'] . "' id='lstHeatNo[$i]' onMouseOver=\"style.cursor='pointer'\" onClick='showlistHeatNo($i);'>
						</div></td>";
			else echo "<input type='hidden' name='sorderdt_heatno[$i]' id='sorderdt_heatno[$i]' value=''  />";


			if (!$FieldArray['salesorderunit1']['IsHidden']) {
				echo "	<td class='grid-col-unit' style='" . $Color . "'><div class='grid-inline-control'><span class='" . $TextColor . "'>" . $rowsdt[16] . "</span>";
				if ($Module_alternativeUnit == 1)	echo "<img src='img/openlist.png' class='grid-action-img' title='{$FieldArray['salesorderaddunitqty']['FieldLabel']}' id='lstUnitQty[$i]' onMouseOver=\"style.cursor='pointer'\"  onclick ='OpenModal($i)'>";
				echo "</div></td>";
			}

			//else
			//	echo "	<td width='0px;' align='left'><span class='".$TextColor."'>".$rowsdt[16]."</span></td>";
			if ($sorder_export == 1) {
				if (!$FieldArray['salesorderitempack']['IsHidden'])
					echo "  <td class='grid-col-unit-minus'><span class='grid-single-control'><input type='text' name='sorderdt_quantitybox' id='sorderdt_quantitybox[$i]' value='$sorderdt_quantitybox' maxlength='20' class='roundedtext_amortization_small grid-cell-numeric' " . $KeyDown . "></span></td>";
				else
					echo "  <input type='hidden' name='sorderdt_quantitybox[$i]' id='sorderdt_quantitybox[$i]' value='$sorderdt_quantitybox'>";
				if (!$FieldArray['salesorderitemunit']['IsHidden'])
					echo "  <td class='grid-col-unit-minus'><span class='grid-single-control'><input type='text' name='sorderdt_itemunit' id='sorderdt_itemunit[$i]' value='$sorderdt_itemunit' maxlength='20' class='roundedtext_amortization_small grid-cell-numeric' " . $KeyDown . "></span></td>";
				else
					echo "  <input type='hidden' name='sorderdt_itemunit' id='sorderdt_itemunit[$i]' value='$sorderdt_itemunit' maxlength='20' " . $KeyDown . ">";
				if (!$FieldArray['salesorderqtybox']['IsHidden'])
					echo "  <td class='grid-col-unit-minus'><span class='grid-single-control'><input type='text' name='sorderdt_itempackage' id='sorderdt_itempackage[$i]' value='$sorderdt_itempackage' maxlength='20' class='roundedtext_amortization_small grid-cell-numeric' " . $KeyDown . "></span></td>";
				else
					echo "  <input type='hidden' name='sorderdt_itempackage' id='sorderdt_itempackage[$i]' value='$sorderdt_itempackage' maxlength='20' " . $KeyDown . ">";
			} else {
				echo "  <input type='hidden' name='sorderdt_quantitybox' id='sorderdt_quantitybox[$i]' value='$sorderdt_quantitybox' maxlength='20' " . $KeyDown . ">";
				echo "  <input type='hidden' name='sorderdt_itemunit' id='sorderdt_itemunit[$i]' value='$sorderdt_itemunit' maxlength='20' " . $KeyDown . ">";
				echo "  <input type='hidden' name='sorderdt_itempackage' id='sorderdt_itempackage[$i]' value='$sorderdt_itempackage' maxlength='20' " . $KeyDown . ">";
			}

			if ($sorderproduction == 1) {
				if (!$FieldArray['salesorderprodqty']['IsHidden']) {
					echo "	<td class='grid-col-xs'><span class='grid-single-control'><input type='text' name='txtProdQty[$i]' id='itemprodqty[$i]' value='" . $rowsdt[29] . "' maxlength='20'   class='roundedtext_amortization_small grid-cell-numeric' " . $KeyDown . "></span></td>";
				} else {
					echo "<input type='hidden' name='txtProdQty[$i]' id='itemprodqty[$i]' value=''>";
				}
				/*echo "	<td  align='center'  width=5%><select name='txtSentProd[$i]' id='itemsentprod[$i]'  maxlength='20'  class='" . $ClassI . "' style='font-size:12px; width:60px; height:20px;' " . $KeyDown . ">
								<option value='0' " . ($rowsdt[30] == '0' ? 'selected' : '') . ">Not Sent</option>
								<option value='1' " . ($rowsdt[30] == '1' ? 'selected' : '') . ">Sent</option>
							</select></td>";*/
				if (!$FieldArray['salesorderassto']['IsHidden']) {
					echo "	<td class='grid-col-xs'><div class='grid-inline-control'>
					<input type='hidden' name='txtSentProd[$i]' id='itemsentprod[$i]' value=''>
					<span class='style4 grid-input-wrap'><input type='text' name='txtAssignTo[$i]' readonly id='txtAssignTo[$i]' value='" . $rowsdt[38] . "' maxlength='20'   class='roundedtext_amortization_small' " . $KeyDown . "></span>
					<img src='img/openlist.png' class='grid-action-img' title='" . $FieldArray['salesordersu']['FieldLabel'] . "' onMouseOver=\"style.cursor='hand'\" onClick=\"showlistUsers('7', $i);\">
					</div></td>";
				} else {
					echo "<input type='hidden' name='txtSentProd[$i]' id='itemsentprod[$i]' value=''><input type='hidden' name='txtAssignTo[$i]' id='txtAssignTo[$i]' value=''>";
				}
			} else {
				echo "<input type='hidden' name='txtProdQty[$i]' id='itemprodqty[$i]' value=''>";
				echo "<input type='hidden' name='txtSentProd[$i]' id='itemsentprod[$i]' value=''><input type='hidden' name='txtAssignTo[$i]' id='txtAssignTo[$i]' value=''>";
			}

			if (!$FieldArray['salesorderorderedqty']['IsHidden'])
				echo "	<td class='grid-col-xs'><span class='grid-single-control'><input type='text' name='txtSorDtOQty[$i]' id='itemorderedqty[$i]' value='" . $rowsdt[19] . "' maxlength='20' onKeyup='SetProdQty($i);CalcSorDtQty($i); CalcSorDtTotal($DecimalBase,$i);' onchange=\"CalcQty2($Module_Unit1Method,1,$i); SetModRecord('ModRec_G1','$i');\" class='roundedtext_amortization_small grid-cell-numeric' " . $KeyDown . " $ModeModifyQty $isGiftRead></span></td>";
			else
				echo "	<input type='hidden' name='txtSorDtOQty[$i]' id='itemorderedqty[$i]' value='" . $rowsdt[19] . "'>";
			if (!$FieldArray['salesordercancelledqty']['IsHidden'])
				echo "   <td class='grid-col-xs'><span class='grid-single-control'><input type='text' name='txtSorDtCQty[$i]' id='cancelledqty[$i]' value='" . $rowsdt[20] . "' maxlength='20' onKeyup='CalcSorDtQty($i); CalcSorDtTotal($DecimalBase,$i);'  onchange=\"SetModRecord('ModRec_G1','$i');\" class='roundedtext_amortization_small grid-cell-numeric' " . $KeyDown . " $isGiftRead></span></td>";
			else
				echo "<input type='hidden' name='txtSorDtCQty[$i]' id='cancelledqty[$i]' value='" . $rowsdt[20] . "'>";
			if (!$FieldArray['salesorderquant']['IsHidden'])
				echo "	<td class='grid-col-xs'><span class='grid-single-control'><input type='text' name='txtSorDtQty[$i]' id='sordtquantity[$i]' value='" . $rowsdt[6] . "' maxlength='20'  class='roundedtext_amortization_small grid-cell-numeric grid-cell-bold' style='border-width:0px;' " . $KeyDown . " readonly></span></td>";
			else
				echo "<input type='hidden' name='txtSorDtQty[$i]' id='sordtquantity[$i]' value='" . $rowsdt[6] . "'>";

			if ($Module_DoubleUnit == 1) {
				if (!$FieldArray['salesorderunit2']['IsHidden'])
					echo "  <td class='grid-col-unit-minus' style='" . $Color . "'><span class='" . $TextColor . "'>" . $rowsdt[15] . "</span></td>";
				else
					echo "  <input type='hidden' name='txtItemUnit2[$i]' id='itemunit2[$i]' value='$txtItemUnit2'>";
				if (!$FieldArray['salesorderunit2qty']['IsHidden'])
					echo "  <td class='grid-col-unit'><span class='grid-single-control'><input type='text' name='txtSorDtQty2[$i]' id='unit2qty[$i]' value='" . $rowsdt[14] . "' maxlength='20' onchange=\"CalcQty2($Module_Unit1Method,2,$i); SetModRecord('ModRec_G1','$i');\" onkeyup=\"changeQty2(this.value,'$i');\" class='roundedtext_amortization_small grid-cell-numeric' " . $KeyDown . "></span></td>";
				else
					echo "  <input type='hidden' name='txtSorDtQty2[$i]' id='unit2qty[$i]' value='' >";
			} else {
				echo "  <input type='hidden' name='txtSorDtQty2[$i]' id='unit2qty[$i]' value='' >";
			}

			if ($FI_DeliverySalesPrice == 1) {
				if (!$FieldArray['salesorderprice']['IsHidden']) {
					if ($SalesTTC == 1)
						echo "	<td class='grid-col-xs'><span class='grid-single-control'><input type='text' name='txtSorDtPrice[$i]' id='price[$i]' value='" . $rowsdt[12] . "' maxlength='20' onKeyup=\"CalcSorDtTotal('$DecimalBase','$i'); ";
					else
						echo "	<td class='grid-col-xs'><span class='grid-single-control'><input type='text' name='txtSorDtPrice[$i]' id='price[$i]' value='" . $rowsdt[7] . "' maxlength='20' onKeyup=\"CalcSorDtTotal('$DecimalBase','$i'); ";
					if ($Module_ClientDiscount == 1) echo "UpdateDiscountHeader('$i');";
					echo " \" onchange=\"SetModRecord('ModRec_G1','$i');\" class='roundedtext_amortization_small grid-cell-numeric' " . $KeyDown . " $ModeModifySellPrice $isGiftRead></span></td>";
				}
				if ($Module_BPPlan == 1) {
					if (!$FieldArray['salesorderbv']['IsHidden'])
						echo "<td class='grid-col-sm-plus'><span class='grid-single-control'><input type='text' name='txtbvpoint[$i]' id='bvpoint[$i]' value='" . $rowsdt[35] . "' readonly class='roundedtext_amortization_small grid-cell-numeric'></span></td>";
					else
						echo "<input type='hidden' name='txtbvpoint[$i]' id='bvpoint[$i]' value='" . $rowsdt[35] . "'>";
					if (!$FieldArray['salesorderbppoint']['IsHidden'])
						echo "	<td class='grid-col-sm-plus'><span class='grid-single-control'><input type='text' name='txtbppoint[$i]' id='bppoint[$i]' value='" . $rowsdt[34] . "' readonly class='roundedtext_amortization_small grid-cell-numeric'></span></td>";
					else
						echo "<input type='hidden' name='txtbppoint[$i]' id='bppoint[$i]' value='" . $rowsdt[34] . "'>";
				} else echo "<input type='hidden' name='txtbvpoint[$i]' id='bvpoint[$i]' value='" . $rowsdt[35] . "'>
							<input type='hidden' name='txtbppoint[$i]' id='bppoint[$i]' value='" . $rowsdt[34] . "'>";

				if (!$FieldArray['salesorderlastprice']['IsHidden'])
					echo "	<td class='grid-col-xs grid-cell-numeric' style='" . $Color . "'><span class='" . $TextColor . "'>&nbsp;</span></td>";
				if (!$FieldArray['salesorderdisc']['IsHidden'])
					echo "	<td class='grid-col-unit'><span class='grid-single-control'><input type='text' name='txtSorDtDiscount[$i]'   onblur='UpdateDiscount($i,this.value)'  id='sordtdiscount[$i]' value='" . $rowsdt[10] . "' maxlength='20' onKeyup='CalcSorDtTotal($DecimalBase,$i);' onchange=\"SetModRecord('ModRec_G1','$i');\" class='roundedtext_amortization_small grid-cell-numeric' " . $KeyDown . " $ModeDiscount $isGiftRead></span></td>";
				else
					echo "<input type='hidden' name='txtSorDtDiscount[$i]' value='" . $rowsdt[10] . "'>";

				if ($Module_ClientDiscount == 1 && !$FieldArray['salesordercldisc']['IsHidden'])
					echo "	<td class='grid-col-unit'><span class='grid-single-control'><input type='text' name='txtSorDtClientDiscount[$i]'  id='sordtClientdiscount[$i]' value='" . $rowsdt[37] . "' maxlength='20'  onchange=\"SetModRecord('ModRec_G1','$i');\" class='roundedtext_amortization_small grid-cell-numeric' " . $KeyDown . " readonly></span></td>";
				if (!$FieldArray['salesordertotal']['IsHidden']) {
					if ($SalesTTC == 1)
						echo "   <td class='grid-col-sm-plus'><span class='grid-single-control'><input type='text' name='txtSorDtTotal[$i]' id='sordttotal[$i]' value='" . $rowsdt[13] . "' maxlength='20' class='roundedtext_amortization_small grid-cell-numeric' " . $KeyDown . " $isGiftRead></span></td>";
					else
						echo "   <td class='grid-col-sm-plus'><span class='grid-single-control'><input type='text' name='txtSorDtTotal[$i]' id='sordttotal[$i]' value='" . $rowsdt[11] . "' maxlength='20' class='roundedtext_amortization_small grid-cell-numeric' " . $KeyDown . " $isGiftRead></span></td>";
				} else {
					if ($SalesTTC == 1)
						echo "<input type='hidden' name='txtSorDtTotal[$i]' id='sordttotal[$i]' value='$rowsdt[13]'>";
					else	echo "<input type='hidden' name='txtSorDtTotal[$i]' id='sordttotal[$i]' value='$rowsdt[11]'>";
				}

				$profit = 	(($rowsdt[11] - ($rowsdt[6] * $meancost)) / $rowsdt[11]) * 100;
				if ($FI_ShowProfit == 1 && !$FieldArray['salesorderprofitdt']['IsHidden'])	echo "   <td class='grid-col-unit'><span class='grid-single-control'><input type='text' name='txtgpper[$i]' id='gpper[$i]' value='" . number_format($profit, 4) . "' readonly maxlength='20' class='roundedtext_amortization_small grid-cell-numeric' " . $KeyDown . "></span></td>";
			} else {
				echo "<input type='hidden' name='txtbvpoint[$i]' id='bvpoint[$i]' value='" . $rowsdt[35] . "'>
					<input type='hidden' name='txtbppoint[$i]' id='bppoint[$i]' value='" . $rowsdt[34] . "'>";
				if ($SalesTTC == 1)
					echo "<input type='hidden' name='txtSorDtPrice[$i]' id='price[$i]' value='$rowsdt[12]'>";
				else echo "<input type='hidden' name='txtSorDtPrice[$i]' id='price[$i]' value='$rowsdt[7]'>";
				echo "<input type='hidden' name='txtSorDtDiscount[$i]' id='sordtdiscount[$i]' value='$rowsdt[10]'>";
				echo "<input type='hidden' name='txtSorDtClientDiscount[$i]' id='sordtClientdiscount[$i]' value='$rowsdt[37]'>";
				if ($SalesTTC == 1)
					echo "<input type='hidden' name='txtSorDtTotal[$i]' id='sordttotal[$i]' value='$rowsdt[13]'>";
				else	echo "<input type='hidden' name='txtSorDtTotal[$i]' id='sordttotal[$i]' value='$rowsdt[11]'>";
			}

			if ($showItemColor == 1 && !$FieldArray['salesordercolor']['IsHidden']) {
				echo "	<td class='grid-col-lg'><span class='grid-single-control'><select name='txtItemColor[$i]' id='itemcolor[$i]' class='roundedtext_amortization_small' " . $KeyDown . ">
						<option value='' " . ($rowsdt[31] == '' ? 'selected' : '') . "></option>";
				$resColor = mysqli_query($connection, $queryColor);
				while ($rowColor = mysqli_fetch_array($resColor)) {
					echo "<option value='$rowColor[0]' " . ($rowsdt[31] == $rowColor[0] ? 'selected' : '') . ">$rowColor[0]</option>";
				}
				echo "	</select></span></td>";
			} else echo "<input type='hidden' name='txtItemColor[$i]' id='itemcolor[$i]' value=''>";

			if ($Module_ProjectDt == 1 && !$FieldArray['salesorderprojectdt']['IsHidden']) {
				$projectvalue = $rowsdt[17] != '' ? $rowsdt[17] : $default_project;

				echo "<td class='grid-col-lg'><div class='grid-inline-control'><span class='grid-input-wrap'><input type='text' name='txtProjectCodeDt[$i]' id='projectcodedt[$i]' value='" . $rowsdt[17] . "' class='roundedtext_amortization_small' maxlength='20' onchange=\"SetModRecord('ModRec_G1','$i'); CheckLookup('chkcodeexists','project','project_code',this.value,'lstProjectCode[$i]');\" " . $KeyDown . "></span>
							<img src='img/openlist.png' class='grid-action-img' title='" . $FieldArray['salesordersp']['FieldLabel'] . "' id='lstProjectCode[$i]' onMouseOver=\"style.cursor='pointer'\" onClick='showlistProjectDt(1,$i); '></div></td>";
			} else {
				echo "  <input type='hidden' name='txtProjectCodeDt[$i]' id='projectcodedt[$i]' value='' >";
			}
			if ($Module_CostCenterDt == 1 && $ShowCostCentLayout == 1 && !$FieldArray['salesordercostcentdt']['IsHidden']) {
				echo "<td class='grid-col-lg'><div class='grid-inline-control'><span class='grid-input-wrap'><input type='text' name='txtCostCentCodeDt[$i]' id='costcentcodedt[$i]' value='" . $rowsdt[18] . "' class='roundedtext_amortization_small'  maxlength='15' onchange=\"SetModRecord('ModRec_G1','$i'); CheckLookup('chkcodeexists','costcent','costcent_code',this.value,'lstCostCentCode[$i]');\" " . $KeyDown . "></span>
							<img src='img/openlist.png' class='grid-action-img' title='" . $FieldArray['salesorderscost']['FieldLabel'] . "' id='lstCostCentCode[$i]' onMouseOver=\"style.cursor='pointer'\"  onClick='showlistCostcenterDt(1,$i); '></div></td>";
			} else {
				echo "<input type='hidden' name='txtCostCentCodeDt[$i]' id='costcentcodedt[$i]' value='' >";
				echo "<input type='hidden' name='txtSorDtDiscount[$i]' id='sordtdiscount[$i]' value='' >";
				echo "<input type='hidden' name='txtSorDtTotal[$i]' id='sordttotal[$i]' value=''>";
			}
			if ($Module_BatchNo == 1 && !$FieldArray['salesorderbatchno']['IsHidden']) {
				echo " 	<td class='grid-col-sm-plus'><div class='grid-inline-control'><span class='grid-input-wrap'><input type='text' name='txtSoDtBatchNo[$i]' id='batchno[$i]' value='" . htmlspecialchars($rowsdt[32], ENT_QUOTES) . "'  maxlength='40' onchange=\"SetModRecord('ModRec_G1','$i');\" class='roundedtext_amortization_small' " . $KeyDown . "></span>
							<img src='img/openlist.png' class='grid-action-img' title='" . $FieldArray['salesorderselectbatch']['FieldLabel'] . "' id='lstBatchNo[$i]' onMouseOver=\"style.cursor='pointer'\" onClick='showlistBatchNo(1,$i); '></div></td>
						";
			} else {

				echo " <input type='hidden' name='txtSoDtBatchNo[$i]' id='batchno[$i]' value='" . htmlspecialchars($rowsdt[32], ENT_QUOTES) . "'>";
			}
			if ($Module_ExpireDate == 1 && !$FieldArray['salesorderexpiry']['IsHidden']) {
				if ($module_allowexpiry == 1)
					echo "<td class='grid-col-sm-plus'><div class='grid-inline-control'><span class='grid-input-wrap'><input type='text' name='txtSoDtExpireDate[$i]' id='expiredate[$i]' value='$rowsdt[33]' maxlength='10' onchange=\"SetModRecord('ModRec_G1','$i');\" class='roundedtext_amortization_small' " . $KeyDown . " readonly></span>
					<img src='img/openlist.png' class='grid-action-img' title='" . $FieldArray['salesordersed']['FieldLabel'] . "' id='lstExpDate[$i]' onMouseOver=\"style.cursor='pointer'\" onClick='showlistExpDate(1,$i); '></div></td>";
				else echo "<td class='grid-col-sm-plus'><span class='grid-single-control'><input type='text' name='txtSoDtExpireDate[$i]' id='expiredate[$i]' value='$rowsdt[33]' maxlength='10' onchange=\"SetModRecord('ModRec_G1','$i');\" class='roundedtext_amortization_small date' " . $KeyDown . "></span></td>";
			} else {
				echo " <input type='hidden' name='txtSoDtExpireDate[$i]' id='expiredate[$i]' value='$rowsdt[33]'>";
			}
			if ($SubItem_Production != 1 && $SubItem_Production != 2 && !$FieldArray['salesorderrem']['IsHidden'])
				echo "	<td class='grid-cell-nowrap grid-col-fluid'><span class='grid-single-control'><input type='text' name='txtSorDtRemark[$i]' id='remarkdt[$i]' value='" . htmlspecialchars($rowsdt[5], ENT_QUOTES) . "'  maxlength='200'  class='roundedtext_amortization_small' onchange=\"SetModRecord('ModRec_G1','$i');\" " . $KeyDown . "></span></td>";
			else 	echo "	<input type='hidden' name='txtSorDtRemark[$i]' id='remarkdt[$i]' value='" . htmlspecialchars($rowsdt[5], ENT_QUOTES) . "'  maxlength='200'  >";

			echo "	<td class='grid-col-xxs' style='text-align:center;'><input type='checkbox' name='chkSorDtPromo[$i]' id='chkpromo[$i]' value='1'  onchange=\"SetModRecord('ModRec_G1','$i');\" " . $KeyDown;
			if ($rowsdt[28] == 1) echo " checked ";
			echo "></td>";
			echo "</tr>";

			
			echo "
<div class='modal' id='qtyModal_$i' tabindex='-1'>
    <div class='modal-dialog modal-sm modal-dialog-centered'>
        <div class='modal-content'>

            <div class='modal-header'>
                <h5 class='modal-title'>{$FieldArray['salesorderaddquantities']['FieldLabel']}</h5>
                <button type='button' class='btn-close' data-bs-dismiss='modal'></button>
            </div>
            <input type='hidden' name='modal_item_code_$i' id='modal_item_code_$i' value='$rowsdt[2]'>
            <div class='modal-body'>
                <div class='mb-3'>
                    <label>{$FieldArray['salesorderqtyunit1label']['FieldLabel']}  <span id='unit1_$i'></span></label>
                    <input type='number' class='form-control' id='qty1_$i' value='$rowsdt[41]'>
                </div>

                <div class='mb-3'>
                    <label>{$FieldArray['salesorderqtyunit2label']['FieldLabel']} <span id='unit2_$i'></span></label>
                    <input type='number' class='form-control' id='qty2_$i' value='$rowsdt[42]'>
                </div>
            </div>

            <div class='modal-footer'>
                <button type='button'
                        class='btn-lp btn-lp-primary'
                        onMouseOver=\"style.cursor='pointer'\"
                        onclick='saveQty($i)'>
                    {$FieldArray['salesorderqtysave']['FieldLabel']}
                </button>
            </div>

        </div>
    </div>
</div>";

			if ($SubItem_Production == 1 || $SubItem_Production == 2) {

				$qatt = " Select Attribute_code, Attribute_value from temp_transactionattribute where transactiondt_id = $rowsdt[0] 
			     and (transaction_type='$txtSorType') ";
				$resatt = mysqli_query($connection, $qatt) or die(mysqli_error($connection));
				$numatt = mysqli_num_rows($resatt);
				if ($numatt == 0)  $numatt = 1;
				$getimage = "Select item_picture, item_picture2 from temp_items where item_code = '$rowsdt[2]' and trans_id = $rowsdt[0] ";
				$resimage = mysqli_query($connection, $getimage);
				$rowimag = mysqli_fetch_array($resimage);
				$pic = $rowimag[0];
				$pic2 = $rowimag[1];
				// if($pic ==''){
				// 	$getimage = "Select item_picture, item_picture2 from items where item_code = '$rowsdt[2]'  ";
				// $resimage = mysqli_query($connection,$getimage);
				// $rowimag = mysqli_fetch_array($resimage);
				// $pic = $rowimag[0];
				// }
				/*echo "<tr rowspan='$numatt'><td colspan='3'>&nbsp;</td><td align='center'><img src='uploads/$pic' height='100px' width='100px' ></td>
		
		
			";
				if ($pic2 != '') {
					echo "<td align='center'><img src='uploads/$pic2' height='100px' width='100px' ></td>";
					$ccol = $colspan - 7;
				} else  $ccol = $colspan - 6;

				echo "<td colspan=2' style='font-size:11px'>";

				while ($rowatt = mysqli_fetch_array($resatt)) {


					echo "<i>$rowatt[0] : $rowatt[1]</i> <br>";

					$ii++;
				}
				echo "</td>";
				echo "	<td colspan='$ccol'><i>$rowsdt[26] <br> " . nl2br($rowsdt[5]) . "</i></td>";
				echo "</tr>";*/
			}
			$i += 1;

			$LastItemWarehouse = $rowsdt[4];
			$lstAssign = $rowsdt[38];

		}
	}

	for ($k = 1; $k <= 1; $k++) {
		$recno = $i + 1;
		$mode = 'i';

		if ($i % 2 == 0) $bgcolor = 'grid-row-even';
		else $bgcolor = 'grid-row-odd';

		if (empty($txtWarehouseCode[$i])) {
			if (!empty($LastItemWarehouse))
				$txtWarehouseCode[$i] = $LastItemWarehouse;
			else if (!empty($SetupDftWareHouse))
				$txtWarehouseCode[$i] = $SetupDftWareHouse;
			else
				$txtWarehouseCode[$i] = $defaultwarehouse;
		}

		$KeyDown   = 'onkeydown="return dokey(event,this,' . $i . ',\'' . $columnslist . '\',\'imgSubmit[' . $i . ']\')"';




		echo "<tr id='CurRec$i' class='$bgcolor table-focus-g1'>";
		echo "	<td class='grid-action-cell'>$recno</td>";
		echo "	<td class='grid-action-cell'><img src='img/undo-1.png' class='grid-action-img' onmouseover=\"style.cursor='pointer'\" onclick=\"ClearRecordGrid('" . $columnslistall . "','" . $i . "','ModRec_G1');return false;\" title='" . $FieldArray['sorderclearrec']['FieldLabel'] . "'></td>";
		echo "	<td class='grid-action-cell'><img src='img/confirm-1.png' class='grid-action-img' id='imgSubmit[$i]' onMouseOver=\"style.cursor='pointer'\" onclick=\"SubmitRecordGrid('" . $i . "','" . $filepath . "', '" . $form . "', '" . $PSorId . "', '" . $dftorderby . "','" . $orderby . "','" . $morelines . "','" . $startrange . "','" . $perpage . "','" . $direction . "',$Module_NegativeSorder,$Login_NegativeQty,$Login_AllowPrice,$Module_SorderCheckQtyOnPost,$Allow_MinimumPrice,$Module_RestrictProjCost,$Module_RestrictCost,$txtClientDetail,$FI_SalesCheckSalesman,$txtClientPromotion,$Module_ShowWarehouseSalesorder); return false;\" title='" . $FieldArray['sordersubmitrec']['FieldLabel'] . "'></td>";
		if ($SubItem_Production == 1 || $SubItem_Production == 2)
			echo "  <td class='grid-action-cell'></td> ";
		if ($Module_Pack == 1 && !$FieldArray['salesorderitemcodep']['IsHidden'])
			echo "  <td class='grid-col-md'><span>&nbsp;</span></td>";
		$showItemCode = !$FieldArray['salesorderitemcode']['IsHidden'];
		$showItemDesc = !$FieldArray['salesorderdesc']['IsHidden'];

		$itemCodeHiddenFields = "
   			 <input type='hidden' name='txtItemVat[$i]' id='itemvat[$i]' value='$txtItemVat[$i]' >
   			 <input type='hidden' name='txtItemMinPrice[$i]' id='itemminprice[$i]' value='$txtItemMinPrice[$i]' >
   			 <input type='hidden' name='txtItemNonStock[$i]' id='itemnonstock[$i]' value='$txtItemNonStock[$i]' >
   			 <input type='hidden' name='txtItemZeroPrice[$i]' id='itemzeroprice[$i]' value='$txtItemZeroPrice[$i]' >
   			 <input type='hidden' name='txtItemUnitCoef[$i]' id='itemunitcoef[$i]' value='$txtItemUnitCoef[$i]' >
   			 <input type='hidden' name='txtSorDtId[$i]' id='sordtid[$i]' value='$txtSorDtId[$i]' >

   			 <input type='hidden' name='txtExistingQty[$i]' id='existingqty[$i]' value='$txtExistingQty[$i]' >
   			 <input type='hidden' name='txtBookingQty[$i]' id='bookingqty[$i]' value='$txtBookingQty[$i]' >
   			 <input type='hidden' name='txtAvailableQty[$i]' id='availableqty[$i]' value='$txtAvailableQty[$i]' >
   			 <input type='hidden' name='txtExistUnit2Qty[$i]' id='exitunit2qty[$i]' value='$txtExistUnit2Qty[$i]' >

   			 <input type='hidden' name='sorderdt_clientdiscount[$i]' id='sorderdt_clientdiscount[$i]' value='0' style='width:60'>
   			 <input type='hidden' name='sorderdt_itemdiscount[$i]' id='sorderdt_itemdiscount[$i]' value='0' style='width:60' >";

		// Existing/booking/available qty popover for the blank "add new item" row. These are real
		// (not hidden) readonly inputs because GetSelectedItemQty() fills them in via ajax after an
		// item is picked, unlike the existing-rows loop above where the values are already known from the DB.
		

		if ($showItemCode && $showItemDesc) {
			// Combined Item Code / Description column for the blank "add new item" row, same pattern
			// as the existing-rows loop above and Forms_DataGrid1.php's itemcode_display.
			$itemChangeHandlers = "SetModRecord('ModRec_G1','$i'); CheckLookup('chkcodeexists','items','item_code',this.value,'lstItemCode[$i]'); GetSelectedItem(this.id,'getitem','" . $i . "','" . $txtDate . "','" . $txtCurrCode . "','" . $txtClientCode . "'); GetSelectedItemQty('getitemqty','" . $i . "','" . $txtDate . "'); ";
			if ($sorderproduction == 1 && !$FieldArray['salesorderassto']['IsHidden']) $itemChangeHandlers .= "SetAssign($i);";
			if ($Module_BPPlan == 1) $itemChangeHandlers .= "getBPPrice('$i');";
			if ($Module_ClientDiscount == 1) $itemChangeHandlers .= "UpdateDiscountHeader('$i');";
			$itemDisplayValue = $txtItemCode[$i] . ($txtItemDesc[$i] != '' ? ' &middot; ' . $txtItemDesc[$i] : '');

			echo "	<td class='grid-cell-nowrap'>" . $itemCodeHiddenFields . "
						<input type='hidden' name='txtItemCode[$i]' id='itemcode_hidden[$i]' value='$txtItemCode[$i]' >
						<input type='hidden' name='txtItemCode[$i]' id='itemcode[$i]' value='$txtItemCode[$i]' onchange=\"$itemChangeHandlers\">
						<input type='hidden' name='txtItemDesc[$i]' id='itemdescription[$i]' value='$txtItemDesc[$i]' onchange=\"CheckLookup('chkcodeexists','items','item_description',this.value,'lstItemDesc[$i]');\">
						<div class='grid-inline-control'>
						
						<span class='style4 grid-input-wrap'><input type='text' name='itemcode_display[$i]' id='itemcode_display[$i]' value='" . $itemDisplayValue . "' maxlength='120' class='roundedtext_amortization_mediumlarge' onfocus='this.select();' onblur=\"checkduplicate($i,document.getElementById('itemcode[$i]').value); var self=this; setTimeout(function(){ var _c=document.getElementById('itemcode[$i]').value; var _desc=document.getElementById('itemdescription[$i]').value; if(_c) self.value=_c+(_desc?' &middot; '+_desc:''); },20);\" oninput=\"document.getElementById('itemcode[$i]').value=this.value; document.getElementById('itemdescription[$i]').value='';\" onkeyup=\"AutoCompleteData(event,this.id,'itemcode[$i]','itemdescription[$i]','items');\" " . $KeyDown . "></span>
						<img src='img/openlist.png' class='grid-action-img' title='" . $FieldArray['salesordersi']['FiedLabel'] . "' id='lstItemCode[$i]' onMouseOver=\"style.cursor='pointer'\" onClick='showlistItems(1,$i); '>
						</div></td>";
		} else if ($showItemCode) {
			echo "	<td class='grid-cell-nowrap'>" . $itemCodeHiddenFields;
			echo "			<input type='hidden' name='txtItemCode[$i]' id='itemcode_hidden[$i]' value='$txtItemCode[$i]' >";
			echo "				<div class='grid-inline-control'>
						
						<span class='style4 grid-input-wrap'><input type='text' name='txtItemCode[$i]' id='itemcode[$i]' value='$txtItemCode[$i]'   onblur='checkduplicate($i,this.value)'   maxlength='20'  class='roundedtext_amortization_small'  onchange=\"SetModRecord('ModRec_G1','$i'); CheckLookup('chkcodeexists','items','item_code',this.value,'lstItemCode[$i]'); GetSelectedItem(this.id,'getitem','" . $i . "','" . $txtDate . "','" . $txtCurrCode . "','" . $txtClientCode . "');
						GetSelectedItemQty('getitemqty','" . $i . "','" . $txtDate . "'); ";
			if ($sorderproduction == 1 && !$FieldArray['salesorderassto']['IsHidden']) echo " SetAssign($i);";
			if ($Module_BPPlan == 1) echo "getBPPrice('$i');";
			if ($Module_ClientDiscount == 1) echo "UpdateDiscountHeader('$i');";
			echo	"\" onkeyup=\"AutoCompleteData(event,this.id,'itemcode[$i]','itemdescription[$i]','items');\"  " . $KeyDown . "></span>
						<img src='img/openlist.png' class='grid-action-img' title='" . $FieldArray['salesordersi']['FiedLabel'] . "' id='lstItemCode[$i]' onMouseOver=\"style.cursor='pointer'\" onClick='showlistItems(1,$i); '>
						</div></td>";
			echo "<input type='hidden' name='txtItemDesc[$i]' id='itemdescription[$i]' value='$txtItemDesc[$i]'>";
		} else if ($showItemDesc) {
			echo $itemCodeHiddenFields;
			echo "			<input type='hidden' name='txtItemCode[$i]' id='itemcode_hidden[$i]' value='$txtItemCode[$i]' >";
			echo "  	<td class='grid-cell-nowrap'><span class='grid-single-control'><input type='text' name='txtItemDesc[$i]' id='itemdescription[$i]' value='$txtItemDesc[$i]'  class='roundedtext_amortization_medium' onchange=\"CheckLookup('chkcodeexists','items','item_description',this.value,'lstItemDesc[$i]');\" " . $KeyDown . "></span>
					</td>";
		} else {
			echo $itemCodeHiddenFields;
			echo "			<input type='hidden' name='txtItemCode[$i]' id='itemcode_hidden[$i]' value='$txtItemCode[$i]' >";
			echo "<input type='hidden' name='txtItemDesc[$i]' id='itemdescription[$i]' value='$txtItemDesc[$i]'>";
		}

		if ($Module_ShowWarehouseSalesorder == 1 && !$FieldArray['salesorderwh']['IsHidden'])
			echo "  	<td class='grid-col-md'><div class='grid-inline-control'>
						<span class='style4 grid-input-wrap'><input type='text' name='txtWarehouseCode[$i]' id='warehousecode[$i]' value='$txtWarehouseCode[$i]'  maxlength='20'  class='roundedtext_amortization_small' onchange=\"SetModRecord('ModRec_G1','$i'); CheckLookup('chkcodeexists','warehouse','warehouse_code',this.value,'lstWarehouseCode[$i]'); GetSelectedItemQty('getitemqty','" . $i . "','" . $txtDate . "');\" onkeyup=\"AutoCompleteData(event,this.id,'warehousecode[$i]','warehousename[$i]','warehouse');\"  " . $KeyDown . "></span>
						<img src='img/openlist.png' class='grid-action-img' title='" . $FieldArray['salesorderw']['FieldLabel'] . "' id='lstWarehouseCode[$i]' onMouseOver=\"style.cursor='pointer'\" onClick='showlistWarehouse($i);'>
					</div></td>";
		else
			echo " <input type='hidden' name='txtWarehouseCode[$i]' id='warehousecode[$i]' value='" . $rowsdt[4] . "' onchange=\"SetModRecord('ModRec_G1','$i'); CheckLookup('chkcodeexists','warehouse','warehouse_code',this.value,'lstWarehouseCode[$i]');\" onkeyup=\"AutoCompleteData(event,this.id,'warehousecode[$i]','warehousename[$i]','warehouse');\" " . $KeyDown . ">";

		if ($Module_Heatno == 1 && !$FieldArray['salesorderheatno']['IsHidden'])
			echo "  	<td class='grid-col-lg'><div class='grid-inline-control'>
							<span class='style4 grid-input-wrap'><input type='text' name='sorderdt_heatno[$i]' id='sorderdt_heatno[$i]' value='" . $sorderdt_heatno[$i] . "'  maxlength='20' class='roundedtext_amortization_small' onchange=\"SetModRecord('ModRec_G1','$i');\"  " . $KeyDown . "></span>
							<img src='img/openlist.png' class='grid-action-img' title='" . $FieldArray['salesorderheat']['FieldLabel'] . "' id='lstHeatNo[$i]' onMouseOver=\"style.cursor='pointer'\" onClick='showlistHeatNo($i);'>
						</div></td>";
		else echo "<input type='hidden' name='sorderdt_heatno[$i]' id='sorderdt_heatno[$i]' value=''  />";

		if (!$FieldArray['salesorderunit1']['IsHidden']) {
			echo "  <td class='grid-col-unit'><div class='grid-inline-control'><span class='grid-input-wrap'><input type='text' name='txtItemUnit1' id='itemunit1[$i]' value='$txtItemUnit1' maxlength='20' class='roundedtext_amortization_small' readonly></span>";
			if ($Module_alternativeUnit == 1)	echo "<img src='img/openlist.png' class='grid-action-img' title='{$FieldArray['salesorderaddunitqty']['FieldLabel']}' id='lstUnitQty[$i]' onMouseOver=\"style.cursor='pointer'\"  onclick ='OpenModal($i)'>";
			echo "</div></td>";
		} else {
			echo "  <input type='hidden' name='txtItemUnit1' id='itemunit1[$i]' value='$txtItemUnit1' maxlength='20' readonly>";
		}
		if ($sorder_export == 1) {
			$sorderdt_quantitybox = 0;
			$sorderdt_itemunit = 0;
			$sorderdt_itempackage = '';
			if (!$FieldArray['salesorderitempack']['IsHidden'])
				echo "  <td class='grid-col-unit-minus'><span class='grid-single-control'><input type='text' name='sorderdt_quantitybox' id='sorderdt_quantitybox[$i]' value='$sorderdt_quantitybox' maxlength='20' class='roundedtext_amortization_small grid-cell-numeric' " . $KeyDown . "></span></td>";
			else
				echo "  <input type='hidden' name='sorderdt_quantitybox[$i]' id='sorderdt_quantitybox[$i]' value='$sorderdt_quantitybox'>";
			if (!$FieldArray['salesorderitemunit']['IsHidden'])
				echo "  <td class='grid-col-unit-minus'><span class='grid-single-control'><input type='text' name='sorderdt_itemunit' id='sorderdt_itemunit[$i]' value='$sorderdt_itemunit' maxlength='20' class='roundedtext_amortization_small grid-cell-numeric' " . $KeyDown . "></span></td>";
			else
				echo "  <input type='hidden' name='sorderdt_itemunit' id='sorderdt_itemunit[$i]' value='$sorderdt_itemunit' maxlength='20' " . $KeyDown . ">";
			if (!$FieldArray['salesorderqtybox']['IsHidden'])
				echo "  <td class='grid-col-unit-minus'><span class='grid-single-control'><input type='text' name='sorderdt_itempackage' id='sorderdt_itempackage[$i]' value='$sorderdt_itempackage' maxlength='20' class='roundedtext_amortization_small grid-cell-numeric' " . $KeyDown . "></span></td>";
			else
				echo "  <input type='hidden' name='sorderdt_itempackage' id='sorderdt_itempackage[$i]' value='$sorderdt_itempackage' maxlength='20' " . $KeyDown . ">";
		} else {
			echo "  <input type='hidden' name='sorderdt_quantitybox' id='sorderdt_quantitybox[$i]' value='$sorderdt_quantitybox' maxlength='20' " . $KeyDown . ">";
			echo "  <input type='hidden' name='sorderdt_itemunit' id='sorderdt_itemunit[$i]' value='$sorderdt_itemunit' maxlength='20' " . $KeyDown . ">";
			echo "  <input type='hidden' name='sorderdt_itempackage' id='sorderdt_itempackage[$i]' value='$sorderdt_itempackage' maxlength='20' " . $KeyDown . ">";
		}
		if ($sorderproduction == 	1) {
			if (!$FieldArray['salesorderprodqty']['IsHidden'])
				echo "	<td class='grid-col-xs'><span class='grid-single-control'><input type='text' name='txtProdQty[$i]' id='itemprodqty[$i]' value='" . $rowsdt[29] . "' maxlength='20'   class='roundedtext_amortization_small grid-cell-numeric' " . $KeyDown . "></span></td>";
			else
				echo "<input type='hidden' name='txtProdQty[$i]' id='itemprodqty[$i]' value=''>";
			/*echo "	<td  align='center'  width=5%><select name='txtSentProd[$i]' id='itemsentprod[$i]'  maxlength='20'  class='" . $ClassI . "' style='font-size:12px; width:60px; height:20px;' " . $KeyDown . ">
							<option value='0' " . ($rowsdt[30] == '0' ? 'selected' : '') . ">Not Sent</option>
							<option value='1' " . ($rowsdt[30] == '1' ? 'selected' : '') . ">Sent</option>
						</select></td>";*/
			if (!$FieldArray['salesorderassto']['IsHidden']) {
				echo "	<td class='grid-col-xs'><div class='grid-inline-control'>
												<input type='hidden' name='txtSentProd[$i]' id='itemsentprod[$i]' value=''>
						<span class='style4 grid-input-wrap'><input type='text' name='txtAssignTo[$i]' readonly id='txtAssignTo[$i]' value='" . $lstAssign . "' maxlength='20'   class='roundedtext_amortization_small' " . $KeyDown . "></span>
						<img src='img/openlist.png' class='grid-action-img' title=' " . $FieldArray['salesordersu']['FieldLabel'] . " ' onMouseOver=\"style.cursor='hand'\" onClick=\"showlistUsers('7', $i);\"></div></td>";
			} else {
				echo "<input type='hidden' name='txtSentProd[$i]' id='itemsentprod[$i]' value=''><input type='hidden' name='txtAssignTo[$i]' id='txtAssignTo[$i]' value=''>";
			}
		} else {
			echo "<input type='hidden' name='txtProdQty[$i]' id='itemprodqty[$i]' value=''>";
			echo "<input type='hidden' name='txtSentProd[$i]' id='itemsentprod[$i]' value=''><input type='hidden' name='txtAssignTo[$i]' id='txtAssignTo[$i]' value=''>";
		}
		if (!$FieldArray['salesorderorderedqty']['IsHidden'])
			echo "	<td class='grid-col-xs'><span class='grid-single-control'><input type='text' name='txtSorDtOQty[$i]' id='itemorderedqty[$i]' value='$txtSorDtOQty[$i]' maxlength='20' onKeyup='SetProdQty($i);CalcSorDtQty($i); CalcSorDtTotal($DecimalBase,$i);' onchange=\"CalcQty2($Module_Unit1Method,1,$i); SetModRecord('ModRec_G1','$i');\"  class='roundedtext_amortization_small grid-cell-numeric' " . $KeyDown . " $ModeModifyQty></span></td>";
		else
			echo "<input type='hidden' name='txtSorDtOQty[$i]' id='itemorderedqty[$i]' value='$txtSorDtOQty[$i]'>";
		if (!$FieldArray['salesordercancelledqty']['IsHidden'])
			echo "   <td class='grid-col-xs'><span class='grid-single-control'><input type='text' name='txtSorDtCQty[$i]' id='cancelledqty[$i]' value='$txtSorDtCQty[$i]' maxlength='20' onKeyup='CalcSorDtQty($i); CalcSorDtTotal($DecimalBase,$i);' onchange=\"SetModRecord('ModRec_G1','$i');\"  class='roundedtext_amortization_small grid-cell-numeric' " . $KeyDown . "></span></td>";
		else
			echo "<input type='hidden' name='txtSorDtCQty[$i]' id='cancelledqty[$i]' value='$txtSorDtCQty[$i]'>";
		if (!$FieldArray['salesorderquant']['IsHidden'])
			echo "	<td class='grid-col-xs'><span class='grid-single-control'><input type='text' name='txtSorDtQty[$i]' id='sordtquantity[$i]' value='$txtSorDtQty[$i]' maxlength='20' class='roundedtext_amortization_small grid-cell-numeric grid-cell-bold' style='border-width:0px;' " . $KeyDown . " readonly></span></td>";
		else
			echo "<input type='hidden' name='txtSorDtQty[$i]' id='sordtquantity[$i]' value='$txtSorDtQty[$i]'>";


		if ($Module_DoubleUnit == 1) {
			if (!$FieldArray['salesorderunit2']['IsHidden'])
				echo "  <td class='grid-col-unit-minus'><span class='grid-single-control'><input type='text' name='txtItemUnit2' id='itemunit2[$i]' value='$txtItemUnit2' maxlength='20' class='roundedtext_amortization_small' readonly></span></td>";
			else
				echo "  <input type='hidden' name='txtItemUnit2[$i]' id='itemunit2[$i]' value='$txtItemUnit2'>";
			if (!$FieldArray['salesorderunit2qty']['IsHidden'])
				echo "  <td class='grid-col-unit'><span class='grid-single-control'><input type='text' name='txtSorDtQty2[$i]' id='unit2qty[$i]' value='$txtSorDtQty2[$i]' maxlength='20' onchange=\"CalcQty2('$Module_Unit1Method','2','$i'); SetModRecord('ModRec_G1','$i');\" onkeyup=\"changeQty2(this.value,'$i');\" class='roundedtext_amortization_small grid-cell-numeric' " . $KeyDown . "></span></td>";
			else
				echo "<input type='hidden' name='txtSorDtQty2[$i]' id='unit2qty[$i]' value='' >";
		} else {
			echo "<input type='hidden' name='txtSorDtQty2[$i]' id='unit2qty[$i]' value='' >";
		}
		if ($FI_DeliverySalesPrice == 1) {
			if (!$FieldArray['salesorderprice']['IsHidden']) {
				echo "	<td class='grid-col-xs'><span class='grid-single-control'><input type='text' name='txtSorDtPrice[$i]' id='price[$i]' value='$txtSorDtPrice[$i]' maxlength='20' onKeyup=\"CalcSorDtTotal('$DecimalBase','$i');  ";
				if ($Module_ClientDiscount == 1) echo "UpdateDiscountHeader('$i');";
				echo " \" class='roundedtext_amortization_small grid-cell-numeric' " . $KeyDown . " $ModeModifySellPrice></span></td>";
			} else
				echo "<input type='hidden' name='txtSorDtPrice[$i]' id='price[$i]' value='' maxlength='20'>";

			if ($Module_BPPlan == 1) {
				if (!$FieldArray['salesorderbv']['IsHidden'])
					echo "<td class='grid-col-sm-plus'><span class='grid-single-control'><input type='text' name='txtbvpoint[$i]' id='bvpoint[$i]' value='$txtbvpoint[$i]' readonly class='roundedtext_amortization_small grid-cell-numeric'></span></td>";
				else
					echo "<input type='hidden' name='txtbvpoint[$i]' id='bvpoint[$i]' value='$txtbvpoint[$i]'>";
				if (!$FieldArray['salesorderbppoint']['IsHidden'])
					echo "<td class='grid-col-sm-plus'><span class='grid-single-control'><input type='text' name='txtbppoint[$i]' id='bppoint[$i]' value='$txtbppoint[$i]' readonly class='roundedtext_amortization_small grid-cell-numeric'></span></td>";
				else
					echo "<input type='hidden' name='txtbppoint[$i]' id='bppoint[$i]' value='$txtbppoint[$i]'>";
			} else echo "<input type='hidden' name='txtbvpoint[$i]' id='bvpoint[$i]' value='$txtbvpoint[$i]'>
						<input type='hidden' name='txtbppoint[$i]' id='bppoint[$i]' value='$txtbppoint[$i]'>";
			if (!$FieldArray['salesorderlastprice']['IsHidden'])
				echo "   <td class='grid-col-xs'><span class='grid-single-control'><input type='text' name='txtItemLastSellPrice' id='lastprice[$i]' value='$txtItemLastSellPrice' maxlength='20' class='roundedtext_amortization_small grid-cell-numeric' readonly></span></td>";
			else
				echo "<input type='hidden' name='txtItemLastSellPrice' id='lastprice[$i]' value='' maxlength='20'>";
			if (!$FieldArray['salesorderdisc']['IsHidden'])
				echo "	<td class='grid-col-unit'><span class='grid-single-control'><input type='text' name='txtSorDtDiscount[$i]'   onblur='UpdateDiscount($i,this.value)'  id='sordtdiscount[$i]' value='$txtSorDtDiscount[$i]' maxlength='20' onKeyup='CalcSorDtTotal($DecimalBase,$i);' onchange=\"SetModRecord('ModRec_G1','$i');\" class='roundedtext_amortization_small grid-cell-numeric' " . $KeyDown . " $ModeDiscount></span></td>";
			else
				echo "<input type='hidden' name='txtSorDtDiscount[$i]' id='sordtdiscount[$i]' value='' maxlength='20'>";
			if ($Module_ClientDiscount == 1 && !$FieldArray['salesordercldisc']['IsHidden'])
				echo "	<td class='grid-col-unit'><span class='grid-single-control'><input type='text' name='txtSorDtClientDiscount[$i]'     id='sordtClientdiscount[$i]' value='" . $txtSorDtClientDiscount[$i] . "' maxlength='20'  onchange=\"SetModRecord('ModRec_G1','$i');\" class='roundedtext_amortization_small grid-cell-numeric' " . $KeyDown . " readonly></span></td>";
			else
				echo "<input type='hidden' name='txtSorDtClientDiscount[$i]' id='sordtClientdiscount[$i]' value='' maxlength='20'>";
			if (!$FieldArray['salesordertotal']['IsHidden']) {
				echo "   <td class='grid-col-sm-plus'><span class='grid-single-control'><input type='text' name='txtSorDtTotal[$i]' id='sordttotal[$i]' value='$txtSorDtTotal[$i]' maxlength='20' class='roundedtext_amortization_small grid-cell-numeric' onchange=\"SetModRecord('ModRec_G1','$i');";
				echo "\" " . $KeyDown . "></span></td>";
			} else
				echo "<input type='hidden' name='txtSorDtTotal[$i]' id='sordttotal[$i]' value='' maxlength='20'>";

			if ($FI_ShowProfit == 1 && !$FieldArray['salesorderprofitdt']['IsHidden']) echo "   <td class='grid-col-unit'><span class='grid-single-control'><input type='text' name='txtgpper[$i]' id='gpper[$i]' value='' readonly maxlength='20' class='roundedtext_amortization_small grid-cell-numeric'  " . $KeyDown . "></span></td>";
		} else {
			echo "<input type='hidden' name='txtbvpoint[$i]' id='bvpoint[$i]' value='$txtbvpoint[$i]'>
						<input type='hidden' name='txtbppoint[$i]' id='bppoint[$i]' value='$txtbppoint[$i]'>";
			echo "<input type='hidden' name='txtSorDtPrice[$i]' id='price[$i]' value='' maxlength='20'></td>";
			echo "<input type='hidden' name='txtItemLastSellPrice' id='lastprice[$i]' value='' maxlength='20'>";

			echo "<input type='hidden' name='txtSorDtDiscount[$i]' id='sordtdiscount[$i]' value='' maxlength='20'>";
			echo "<input type='hidden' name='txtSorDtClientDiscount[$i]' id='sordtClientdiscount[$i]' value='' maxlength='20'>";
			echo "<input type='hidden' name='txtSorDtTotal[$i]' id='sordttotal[$i]' value='' maxlength='20'>";
		}

		if ($showItemColor == 1 && !$FieldArray['salesordercolor']['IsHidden']) {
			echo "	<td class='grid-col-lg'><span class='grid-single-control'><select name='txtItemColor[$i]' id='itemcolor[$i]' class='roundedtext_amortization_small' " . $KeyDown . ">
					<option value=''></option>";
			$resColor = mysqli_query($connection, $queryColor);
			while ($rowColor = mysqli_fetch_array($resColor)) {
				echo "<option value='$rowColor[0]'>$rowColor[0]</option>";
			}
			echo "	</select></span></td>";
		} else echo "<input type='hidden' name='txtItemColor[$i]' id='itemcolor[$i]' value=''>";

		if ($Module_ProjectDt == 1 && !$FieldArray['salesorderprojectdt']['IsHidden']) {
			if ($txtProjectCodeDt[$i] == '') {
				$txtProjectCodeDt[$i] = $default_project;
			}
			echo "<td class='grid-col-lg'><div class='grid-inline-control'><span class='grid-input-wrap'><input type='text' name='txtProjectCodeDt[$i]' id='projectcodedt[$i]' value='$txtProjectCodeDt[$i]' class='roundedtext_amortization_small'  maxlength='15' onchange=\"SetModRecord('ModRec_G1','$i'); CheckLookup('chkcodeexists','project','project_code',this.value,'lstProjectCode[$i]');\" " . $KeyDown . "></span>
						<img src='img/openlist.png' class='grid-action-img' title='" . $FieldArray['salesordersp']['FieldLabel'] . "' id='lstProjectCode[$i]' onMouseOver=\"style.cursor='pointer'\" onClick='showlistProjectDt(1,$i); '></div></td>";
		} else {
			echo "  <input type='hidden' name='txtProjectCodeDt[$i]' id='projectcodedt[$i]' value='' >";
		}
		if ($Module_CostCenterDt == 1 && $ShowCostCentLayout == 1 && !$FieldArray['salesordercostcentdt']['IsHidden']) {
			if ($txtCostCentCodeDt[$i] == '') {
				$txtCostCentCodeDt[$i] = $default_costcent;
			}
			echo "<td class='grid-col-lg'><div class='grid-inline-control'><span class='grid-input-wrap'><input type='text' name='txtCostCentCodeDt[$i]' id='costcentcodedt[$i]' value='$txtCostCentCodeDt[$i]' class='roundedtext_amortization_small' maxlength='20' onchange=\"SetModRecord('ModRec_G1','$i'); CheckLookup('chkcodeexists','costcent','costcent_code',this.value,'lstCostCentCode[$i]');\" " . $KeyDown . "></span>
						<img src='img/openlist.png' class='grid-action-img' title='" . $FieldArray['salesorderscost']['FieldLabel'] . "' id='lstCostCentCode[$i]' onMouseOver=\"style.cursor='pointer'\"  onClick='showlistCostcenterDt(1,$i); '></div></td>";
		} else {
			echo "  <input type='hidden' name='txtCostCentCodeDt[$i]' id='costcentcodedt[$i]' value='' >";
		}
		if ($Module_BatchNo == 1 && !$FieldArray['salesorderbatchno']['IsHidden']) {
			echo " 	<td class='grid-col-sm-plus'><div class='grid-inline-control'><span class='grid-input-wrap'><input type='text' name='txtSoDtBatchNo[$i]' id='batchno[$i]' value='" . htmlspecialchars($txtSoDtBatchNo[$i], ENT_QUOTES) . "'  maxlength='40' onchange=\"SetModRecord('ModRec_G1','$i');\" class='roundedtext_amortization_small' " . $KeyDown . "></span>
							<img src='img/openlist.png' class='grid-action-img' title='" . $FieldArray['salesorderselectbatch']['FieldLabel'] . "' id='lstBatchNo[$i]' onMouseOver=\"style.cursor='pointer'\" onClick='showlistBatchNo(1,$i); '></div></td>
						";
		} else {

			echo " <input type='hidden' name='txtSoDtBatchNo[$i]' id='batchno[$i]' value=''>";
		}
		if ($Module_ExpireDate == 1 && !$FieldArray['salesorderexpiry']['IsHidden']) {
			if ($module_allowexpiry == 1)
				echo "<td class='grid-col-sm-plus'><div class='grid-inline-control'><span class='grid-input-wrap'><input type='text' name='txtSoDtExpireDate[$i]' id='expiredate[$i]' value='$txtSoDtExpireDate[$i]' maxlength='10' onchange=\"SetModRecord('ModRec_G1','$i');\" class='roundedtext_amortization_small' " . $KeyDown . " readonly></span>
					<img src='img/openlist.png' class='grid-action-img' title='" . $FieldArray['salesordersed']['FieldLabel'] . "' id='lstExpDate[$i]' onMouseOver=\"style.cursor='pointer'\" onClick='showlistExpDate(1,$i); '></div></td>";
			else echo "<td class='grid-col-sm-plus'><span class='grid-single-control'><input type='text' name='txtSoDtExpireDate[$i]' id='expiredate[$i]' value='$txtSoDtExpireDate[$i]' maxlength='10' onchange=\"SetModRecord('ModRec_G1','$i');\" class='roundedtext_amortization_small date' " . $KeyDown . "></span></td>";
		} else {
			echo " <input type='hidden' name='txtSoDtExpireDate[$i]' id='expiredate[$i]' value=''>";
		}
		if ($SubItem_Production != 1 && $SubItem_Production != 2 && !$FieldArray['salesorderrem']['IsHidden'])
			echo "	<td class='grid-cell-nowrap grid-col-fluid'><span class='grid-single-control'><input type='text' name='txtSorDtRemark[$i]' id='remarkdt[$i]' value='" . htmlspecialchars($txtSorDtRemark[$i], ENT_QUOTES) . "'  maxlength='200' class='roundedtext_amortization_small' onchange=\"SetModRecord('ModRec_G1','$i');\" " . $KeyDown . "></span></td>";
		else echo "<input type='hidden' name='txtSorDtRemark[$i]' id='remarkdt[$i]' value='" . htmlspecialchars($txtSorDtRemark[$i], ENT_QUOTES) . "'  maxlength='200'>";

		echo "	<td class='grid-col-xxs' style='text-align:center;'><input type='checkbox' name='chkSorDtPromo[$i]' id='chkpromo[$i]' value='1'  onchange=\"SetModRecord('ModRec_G1','$i');\" " . $KeyDown . "></td>";
		echo "</tr>";
		echo "
<div class='modal' id='qtyModal_$i' tabindex='-1'>
    <div class='modal-dialog modal-sm modal-dialog-centered'>
        <div class='modal-content'>

            <div class='modal-header'>
                <h5 class='modal-title'>{$FieldArray['salesorderaddquantities']['FieldLabel']}</h5>
                <button type='button' class='btn-close' data-bs-dismiss='modal'></button>
            </div>
            <input type='hidden' name='modal_item_code_$i' id='modal_item_code_$i' value=''>
            <div class='modal-body'>
                <div class='mb-3'>
                    <label>{$FieldArray['salesorderqtyunit1label']['FieldLabel']}  <span id='unit1_$i'></span></label>
                    <input type='number'
                           class='form-control'
                           id='qty1_$i'>
                </div>

                <div class='mb-3'>
                    <label>{$FieldArray['salesorderqtyunit2label']['FieldLabel']} <span id='unit2_$i'></span></label>
                    <input type='number'
                           class='form-control'
                           id='qty2_$i'>
                </div>
            </div>

            <div class='modal-footer'>
                <button type='button'
                        class='btn-lp btn-lp-primary'
                        onMouseOver=\"style.cursor='pointer'\"
                        onclick='saveQty($i)'>
                    {$FieldArray['salesorderqtysave']['FieldLabel']}
                </button>
            </div>

        </div>
    </div>
</div>";

		$i += 1;
	}



	echo "	</table>";
	echo "	</div>";

	showDataGridFooter($filepath, $form, $PSorId, 'datagrid', $dftorderby, $action, $orderby, $morelines, $startrange, $perpage, $totalrows, $direction);

	echo "</div>";
}

function ShowSorderTotals($connection, $PSorId, $arrayparams, $arrayaccess)
{

	$DecimalBase  			= $arrayparams['DecimalBase'];
	$SalesTTC	 			= $arrayparams['SalesTTC'];
	$Setup_VatPercentage 	= $arrayparams['Setup_VatPercentage'];
	$Module_AdditionalTaxes = $arrayparams['Module_AdditionalTaxes'];
	$ExportTransportToJV 	= $arrayparams['ExportTransportToJV'];
	$VatApplied 			= $arrayparams['VatApplied'];
	$AllowModifyVAT 		= $arrayparams['AllowModifyVAT'];
	$Login_ModifyDiscount 	= $arrayparams['Login_ModifyDiscount'];

	$txtClientCode   		= $arrayparams['txtClientCode'];
	$Module_BPPlan  	 	= $arrayparams['Module_BPPlan'];
	$vat_id = $arrayparams['vat_id'];
	$so_type = $arrayparams['so_type'];

	$sub_query = " Select ifNull(SalesTTC,0) From Clients Where Ledger_Number = '" . $txtClientCode . "'";
	$sub_result = selectData($connection, $sub_query);
	$SalesTTC = $sub_result[0];

	if ($salesorderdesign == 0) {
		$inputClass = "inputBox-clean";
		$inputRowClass = "inputBoxRow-clean";
		$selectClass = "select-clean";
		$textareaClass = "textarea-clean";
		$formRowClass = "form-row-clean";
	} else {
		// Use original design classes
		$inputClass = "inputBox";
		$inputRowClass = "inputBox";
		$selectClass = "form-control form-control-sm inputBox";
		$textareaClass = "form-control form-control-sm inputBox";
		$formRowClass = "form-row";
	}


	if ($Login_ModifyDiscount == 0)	$DiscountMode = ' readonly ';

	if ($PSorId != "") {
		$getsorder = "Select sorder_date, currency_code from temp_sorder where sorder_id = $PSorId";
		$sub_result = selectData($connection, $getsorder);
		$date = $sub_result[0];
		$sorder_currency = $sub_result[1];

		$VatFormula = getVatFormula($vat_id, $date, $connection);
		$sub_query = " Select Round(if($SalesTTC=1,(ifNull(Sorder_Taxable,0)*$VatFormula),ifNull(Sorder_Taxable,0)), " . $DecimalBase . "), Round(ifNull(Sorder_NoneTaxable,0), " . $DecimalBase . "), " .
			" Round(if($SalesTTC=1,ifNull(Sorder_Taxable,0)*$VatFormula ,ifNull(Sorder_Taxable,0)) + ifNull(Sorder_NoneTaxable,0), " . $DecimalBase . ") As GrossTotal, " .
			" Round(ifNull(Sorder_DiscountPer,0), " . $DecimalBase . "), Round(ifNull(Sorder_DiscountAmount,0), " . $DecimalBase . "),  " .
			" ifNull(Sorder_VatChckbx,0) As Sorder_VatChckbx, Round(ifNull(Sorder_VAT,0), " . $DecimalBase . ") As Sorder_VAT, " .
			" Round((if($SalesTTC=1,ifNull(Sorder_Taxable,0)*$VatFormula ,ifNull(Sorder_Taxable,0)  +ifNull(Sorder_VAT,0)+  ifnull(Sorder_TotalTax2,0)+ifnull(Sorder_TotalVat,0) + ifnull(Sorder_TotalTax1,0) ) + ifNull(Sorder_NoneTaxable,0) + ifNull(Sorder_TransportAmount,0) + ifnull(sorder_OnGrosscharges,0)), " . $DecimalBase . ") As Sorder_NetTotal,  " .
			" Round((ifnull(Sorder_TotalTax2,0)+ifnull(Sorder_TotalVat,0) + ifnull(Sorder_TotalTax1,0)), " . $DecimalBase . ") As TotalTaxes,  " .
			" Round(if($SalesTTC=1,ifNull(Sorder_TransportTTC,0),ifNull(Sorder_TransportAmount,0)), " . $DecimalBase . ") As TransportAmt, ifnull(sorder_totalBusinesspt,0), date_format(sorder_Date,'%Y-%m'), " .
			" date_format(last_day(sorder_Date) - INTERVAL 1 MONTH,'%Y-%m') as lastMonth, vat_id, ifnull(Sorder_TotalTax2,0),
			   ifnull(Sorder_TotalVat,0), ifnull(Sorder_TotalTax1,0), " .
			" Round(ifNull(Sorder_Taxable,0) + ifNull(Sorder_NoneTaxable,0), " . $DecimalBase . ") As GrossTotalBeforeTTC,
			 Round(ifnull(sorder_charges,0), " . $DecimalBase . "),  Round(ifnull(sorder_OnGrosscharges,0), " . $DecimalBase . ")  " .
			" From Temp_Sorder As Sorder " .
			" Where Sorder_ID = " . $PSorId;

		//	echo $sub_query;
		$sub_result = selectData($connection, $sub_query);

		$txtSorTaxable      = $sub_result[0];
		$txtSorNoneTaxable  = $sub_result[1];
		$txtSorGrossTotal   = $sub_result[2];
		$txtSorDiscount     = $sub_result[3];
		$txtSorDiscountAmt  = $sub_result[4];
		$txtSorVatChk       = $sub_result[5];
		$txtSorVat          = $sub_result[6];
		$txtSorNetTotal     = $sub_result[7];
		$txtSorTotalTaxes   = $sub_result[8];
		$txtSorTransportAmt = $sub_result[9];
		$totalBpp		    = $sub_result[10];
		$sorderdate		    = $sub_result[11];
		$previousMonth	    = $sub_result[12];
		$vat_id		    = $sub_result[13];
		$totalVatGross = $sub_result[14];
		$totalVatNet = $sub_result[15];
		$totalVatFirst =  $sub_result[16];
		$GrossTotalBeforeTTC = $sub_result[17];
		$txtSorCharges = $sub_result[18];
		$txtChargesOngross = $sub_result[19];
		$txtSorGrossTotal = $txtSorGrossTotal + $txtChargesOngross;
		$txtSorNetTotalCharges = $txtSorNetTotal +  $txtSorCharges;
		$gettotalbeforedisc = "Select ifNull(sum(SorderDt_Quantity*SorderDt_Price),0) from Temp_SorderDt where Sorder_ID = $PSorId ";
		$sub_result = selectData($connection, $gettotalbeforedisc);
		$totalbeforediscount = $sub_result[0];
	} else {

		// $sub_query = " Select ifNull(Client_VAT,0) From Clients Where Ledger_Number = '" . $txtClientCode . "'";
		// $sub_result = selectData($connection, $sub_query);
		// $txtSorVatChk = $sub_result[0];
		$totalbeforediscount = 0;
	}

		echo "<input type='hidden' name='totalbeforediscount'  id='totalbeforediscount' value='$totalbeforediscount'>";

	if($Setup_VatPercentage > 0) $VatApplied = 1;
	else $VatApplied = 0;

	//echo $VatApplied
	if ($date == '') $date = date('Y-m-d');
	$getVatDetails = "Select Vat_code, Vat_percentage ,  if(calculation_on='tax1',1,0),  if(calculation_on='tax2',1,0), 
	                     if(calculation_on='vat',1,0) from vat where Parent_VatId = $vat_id
						and date_format('$date','%Y-%m-%d') between date_format(ifnull(fromdate,'$date'),'%Y-%m-%d') and date_format(ifnull(todate,'$date'),'%Y-%m-%d')
						 order by vat_order";
	$resultvatDetails = mysqli_query($connection, $getVatDetails);

	$query_login = "select language_name as Language, access_code as user_account from " . $_SESSION['dbLabel'] . ".access  Left join " . $_SESSION['dbLabel'] . ".languages on languages.language_id = access_language 
			            where access_code='" . $_SESSION['useraccount'] . "' and access_id='" . $_SESSION['accessid'] . "' ";
	$resultlogin = mysqli_query($connection, $query_login) or die(mysqli_error($connection));
	while ($rows = mysqli_fetch_array($resultlogin)) {
		extract($rows);
	}

	$FieldArray   = array();
	$ButtonArray   = array();

	// Initialize field labels from database or set defaults
	$FieldArray['salesordertaxable']['FieldLabel'] = 'Taxable';
	$FieldArray['salesordernonetax']['FieldLabel'] = 'Non Taxable';
	$FieldArray['salesordergtotal']['FieldLabel'] = 'Gross Total';
	$FieldArray['salesorderdiscount']['FieldLabel'] = 'Discount';
	$FieldArray['salesordertrans']['FieldLabel'] = 'Transport';
	$FieldArray['salesorderexcise']['FieldLabel'] = 'EXCISE';
	$FieldArray['salesordercovid']['FieldLabel'] = 'COVID';
	$FieldArray['salesorderlevi']['FieldLabel'] = 'LEVY';
	$FieldArray['salesordervatsm']['FieldLabel'] = 'Vat';
	$FieldArray['salesordernettotal']['FieldLabel'] = 'Net Total';
	$FieldArray['salesordernettotalcharges']['FieldLabel'] = 'Net Total + Charges';
	$FieldArray['salesordertbp']['FieldLabel'] = 'Total BP';
	$FieldArray['salesordermbp']['FieldLabel'] = 'Monthly BP';
	$FieldArray['salesorderpmbp']['FieldLabel'] = 'Previous Month BP';
	$FieldArray['salesorderchargessm']['FieldLabel'] = 'Charges';
	$FieldArray['salesordertaxableamt']['FieldLabel'] = 'Taxable Amount';


	foreach ($FieldArray as $fieldKey => $fieldInfo) {
		if (!isset($FieldArray[$fieldKey]['IsHidden'])) $FieldArray[$fieldKey]['IsHidden'] = false;
		if (!isset($FieldArray[$fieldKey]['MandatoryLabel'])) $FieldArray[$fieldKey]['MandatoryLabel'] = '';
		if (!isset($FieldArray[$fieldKey]['FieldMod'])) $FieldArray[$fieldKey]['FieldMod'] = '';
	}

	if ($so_type == 'SO')
		$form_name = 'Sales Order';
	else if ($so_type == 'PF')
		$form_name = 'proforma';
	else if ($so_type == 'WB')
		$form_name = 'Waybill';
	else
		$form_name = 'Sales Request';

	$render_result = get_fields_for_render($form_name, $_SESSION['accessschemaid'], $_SESSION['dbLabel'], $connection, 'summary');
	while ($render_row = mysqli_fetch_assoc($render_result)) {
		$fieldCode = strtolower($render_row['fieldcode']);
		if (isset($FieldArray[$fieldCode])) {
			$FieldArray[$fieldCode]['IsHidden'] = (intval($render_row['is_hidden']) == 1);
		}
	}

	$sub_result = get_labelbuttons($_SESSION['dbLabel'], $_SESSION['accessid'], $connection);
	while ($sub_row = mysqli_fetch_row($sub_result)) {
		$ButtonArray[$sub_row[0]]['ButtonLabel'] = $sub_row[1];
		$ButtonArray[$sub_row[0]]['ButtonTitle'] = $sub_row[2];
	}

	initFormLanguageUI($form_name, $_SESSION['dbLabel'], $connection, $FieldArray);

	$query_field14  = "select clear,cancel,save,search,pending,reference,folio,jv_ref,date1,supplier,maturity,remark,currency,project,costcenter,invoice_no,vat_prefix,
					manual_no,taxable,non_taxable,gross_total,discount,vat,net_total,ledger_number1,ledger_name1,description,description2,asset,qty,price,total,purchase_jv,disc1,total1,
					item_codepack,cancelled_qty,qty1,unit2,unit2_qty,heat_no,transport,total_BP,monthly_BP,previous_BP,itemcode_pack,WH,unit1,packbox,prod_qty,sent_prod,ord_qty
	From " . $_SESSION['dbLabel'] . ".jv_label
	where Language = '" . $Language . "'";
	$result_field14 = mysqli_query($connection, $query_field14);
	$row_field14    = mysqli_fetch_array($result_field14);
	extract($row_field14);


	if ($Module_BPPlan == 1) {
	}
	$applicableSummaryFieldCodes = array('salesordertaxable', 'salesordernonetax', 'salesordergtotal', 'salesorderdiscount', 'salesordernettotal', 'salesorderchargessm', 'salesordernettotalcharges');
	if ($ExportTransportToJV == 1) $applicableSummaryFieldCodes[] = 'salesordertrans';
	if ($vat_id != '') {
		$applicableSummaryFieldCodes[] = 'salesorderexcise';
		$applicableSummaryFieldCodes[] = 'salesordercovid';
		$applicableSummaryFieldCodes[] = 'salesorderlevi';
		$applicableSummaryFieldCodes[] = 'salesordertaxableamt';
	}
	if ($Setup_VatPercentage > 0) $applicableSummaryFieldCodes[] = 'salesordervatsm';
	if ($Module_BPPlan == 1) {
		$applicableSummaryFieldCodes[] = 'salesordertbp';
		$applicableSummaryFieldCodes[] = 'salesordermbp';
		$applicableSummaryFieldCodes[] = 'salesorderpmbp';
	}
	echo "<script type='text/javascript'>if(window.salesOrderApplicableFieldCodes){salesOrderApplicableFieldCodes.summary = " . json_encode(array_values(array_unique($applicableSummaryFieldCodes))) . ";}</script>";

	echo "<div class='summary-totals pr-2 pl-2'>";

	if (!$FieldArray['salesordertaxable']['IsHidden']) {
		echo "	<div class='row summary-row'>
				<div class='summary-label-col'><span class='style10'>" . $FieldArray['salesordertaxable']['FieldLabel'] . "</span>&nbsp;</div>
				<div class='summary-input-col'><input class='inputBox-clean summary-input' type='text' id='taxable'  name='txtSorTaxable' value='" . number_format($txtSorTaxable, $DecimalBase) . "' maxlength='20' readonly></div>
			</div>";
	} else {
		echo "<input type='hidden' id='taxable' name='txtSorTaxable' value=''>";
	}

	if (!$FieldArray['salesordernonetax']['IsHidden']) {
		echo "	<div class='row summary-row'>
				<div class='summary-label-col'><span class='style10'>" . $FieldArray['salesordernonetax']['FieldLabel'] . "</span>&nbsp;</div> 
				<div class='summary-input-col'><input class='inputBox-clean summary-input' type='text' id='nontaxable' name='txtSorNoneTaxable' value='" . number_format($txtSorNoneTaxable, $DecimalBase) . "' maxlength='20' readonly></div>
			</div>";
	} else {
	}

	if($txtChargesOngross > 0){
		   	echo "	<div class='row summary-row'>
				<div class='summary-label-col'><span class='style10'>Charges</span>&nbsp;</div> 
				<div class='summary-input-col'><input class='inputBox-clean summary-input' type='text' id='chargesongross' name='txtChargesongross' value='" . number_format($txtChargesOngross, $DecimalBase) . "' maxlength='20' readonly></div>
			</div>";
	}


	if (!$FieldArray['salesordergtotal']['IsHidden']) {
		echo "	<div class='row summary-row'>
				<div class='summary-label-col'><span class='style10'>" . $FieldArray['salesordergtotal']['FieldLabel'] . "</span>&nbsp;</div> 
				<div class='summary-input-col'><input class='inputBox-clean summary-input' type='text' id='grosstotal' name='txtSorGrossTotal' value='" . number_format($txtSorGrossTotal, $DecimalBase) . "' maxlength='20' readonly></div>
			</div>";
	} else {
		echo "<input type='hidden' id='grosstotal' name='txtSorGrossTotal' value=''>";
	}

	if (!$FieldArray['salesorderdiscount']['IsHidden']) {
	//	$txtSorDiscountAmt = $txtSorGrossTotal * ($txtSorDiscount / 100);
		echo "	<div class='row summary-row'>
				<div class='summary-label-col'><span class='style10'>" . $FieldArray['salesorderdiscount']['FieldLabel'] . "</span>&nbsp;</div> 
				<div class='summary-input-col'>
					<div class='summary-discount-wrap'>
						<input class='inputBox-clean summary-input summary-discount-per' type='text' id='sordiscount' name='txtSorDiscount' value='" . number_format($txtSorDiscount, $DecimalBase) . "' maxlength='5' onchange='CalcSorDiscount(1, $DecimalBase,$VatApplied,$Setup_VatPercentage,$SalesTTC);' $DiscountMode >
						<span class='style10 summary-discount-sign'> %</span>
						<input class='inputBox-clean summary-input summary-discount-amt' type='text' id='sordiscountamt' name='txtSorDiscountAmt' value='" . number_format($txtSorDiscountAmt, $DecimalBase) . "' maxlength='20' onchange='CalcSorDiscount(2, $DecimalBase,$VatApplied,$Setup_VatPercentage,$SalesTTC);' $DiscountMode >
					</div>
				</div>
			</div>";
	} else {
		echo "<input type='hidden' id='sordiscount' name='txtSorDiscount' value=''>";
		echo "<input type='hidden' id='sordiscountamt' name='txtSorDiscountAmt' value=''>";
	}

	if ($SalesTTC == 1) $txtSorGrossTotal = $GrossTotalBeforeTTC;
	if ($ExportTransportToJV == 1 && !$FieldArray['salesordertrans']['IsHidden']) {
		echo "	<div class='row summary-row'>
				<div class='summary-label-col'><span class='style10'>" . $FieldArray['salesordertrans']['FieldLabel'] . "</span>&nbsp;</div> 
				<div class='summary-input-col'><input class='inputBox-clean summary-input' type='text' id='sortransportamt' name='txtSorTransportAmt' value='" . number_format($txtSorTransportAmt, $DecimalBase) . "' maxlength='20' onKeyup='CalcNetTotal($DecimalBase,$VatApplied,$Setup_VatPercentage,$SalesTTC);'></div>
			</div>";
	} else {
		echo "	<input type='hidden' id='sortransportamt' name='txtSorTransportAmt' value='$txtSorTransportAmt'>";
	}

	if ($vat_id != '') {
		$q = "Select Vat_percentage, if(calculation_on='tax1',1,0),  if(calculation_on='tax2',1,0),  if(calculation_on='vat',1,0) from vat where vat_id = $vat_id";
		$result = mysqli_query($connection, $q);
		$row = mysqli_fetch_array($result);
		$Setup_VatPercentage = $row[0];
		$vatOngross = $row[1];
		$vatOnNetgross = $row[2];
		$vatOnTotal = $row[3];
	}

	echo "	<input type='hidden' name='txtSorTotalTaxes' value='$txtSorTotalTaxes'>";
	$vatindex = 0;
	//if($SalesTTC == 1)

	while ($rowdetailsvat = mysqli_fetch_array($resultvatDetails)) {
		if ($rowdetailsvat[2] == 1) {  // && !$FieldArray['salesorderexcise']['FieldLabel']
			$vatDetailAmount = number_format(($txtSorTaxable + $txtChargesOngross) * $rowdetailsvat[1] / 100, $DecimalBase);
			if (!$FieldArray['salesorderexcise']['IsHidden'])
				echo "<div class='row summary-row'> 
					<div class='summary-label-col'><span class='style10'>$rowdetailsvat[0]</span>&nbsp;</div> 
					<div class='summary-input-col'><input class='inputBox-clean summary-input' type='text' id='vatdetail$vatindex' name='vatdetail$vatindex' value='" . $vatDetailAmount . "' maxlength='20' readonly>
					<input type='hidden'  id='vatdetailVal$vatindex' name='vatdetailVal$vatindex' value='$rowdetailsvat[1]'>
					</div>
				</div> ";
			else
				echo "<input type='hidden' id='vatdetail$vatindex' name='vatdetail$vatindex' value='" . $vatDetailAmount . "'><input type='hidden' id='vatdetailVal$vatindex' name='vatdetailVal$vatindex' value='$rowdetailsvat[1]'>";
		} else if ($rowdetailsvat[3] == 1) {
			if ($vatOngross == 1) $vatso = $txtSorVat;
			else $vatso = 0;  // && !$FieldArray['salesordercovid']['IsHidden']

			//echo "<br> $vatso + $txtSorTaxable + $totalVatFirst ";
			//echo "$vatso + $txtSorTaxable + $txtChargesOngross + $totalVatFirst";
			$taxableAmountValue = number_format(($vatso + $txtSorTaxable + $txtChargesOngross + $totalVatFirst), $DecimalBase);
			if ($ii == 0) {
				if (!$FieldArray['salesordertaxableamt']['IsHidden']) echo "<div class='row summary-row'>
					<div class='summary-label-col'><span class='style10'>" . $FieldArray['salesordertaxableamt']['FieldLabel'] . "</span>&nbsp;</div>
					<div class='summary-input-col'><input class='inputBox-clean summary-input' type='text' id='vatdetail$vatindex' name='vatdetail$vatindex' value='" . $taxableAmountValue . "' maxlength='20' readonly>
					<input type='hidden'  id='vatdetailVal$vatindex' name='vatdetailVal$vatindex' value='$rowdetailsvat[1]'>
					</div>
				</div> ";
				else echo "<input type='hidden' id='vatdetail$vatindex' name='vatdetail$vatindex' value='" . $taxableAmountValue . "'><input type='hidden' id='vatdetailVal$vatindex' name='vatdetailVal$vatindex' value='$rowdetailsvat[1]'>";
			}



			$vatDetailAmount = number_format(($vatso + $txtSorTaxable+ $txtChargesOngross + $totalVatFirst) * $rowdetailsvat[1] / 100, $DecimalBase);
			if (!$FieldArray['salesordercovid']['IsHidden'])
				echo "<div class='row summary-row'>
					<div class='summary-label-col'><span class='style10'>$rowdetailsvat[0]</span>&nbsp;</div> 
					<div class='summary-input-col'><input class='inputBox-clean summary-input' type='text' id='vatdetail$vatindex' name='vatdetail$vatindex' value='" . $vatDetailAmount . "' maxlength='20' readonly>
					<input type='hidden'  id='vatdetailVal$vatindex' name='vatdetailVal$vatindex' value='$rowdetailsvat[1]'>
					</div>
				</div> ";
			else
				echo "<input type='hidden' id='vatdetail$vatindex' name='vatdetail$vatindex' value='" . $vatDetailAmount . "'><input type='hidden' id='vatdetailVal$vatindex' name='vatdetailVal$vatindex' value='$rowdetailsvat[1]'>";
			$ii++;
		} else if ($rowdetailsvat[4] == 1) {

			if ($vatOnNetgross == 1) $vatso = $txtSorVat;
			else $vatso = 0;
			$taxableAmountValue = number_format(($vatso + $txtSorTaxable + $txtChargesOngross + $totalVatFirst + $totalVatGross), $DecimalBase);
			if ($ij == 0) {
				if (!$FieldArray['salesordertaxableamt']['IsHidden']) echo "<div class='row summary-row'>
					<div class='summary-label-col'><span class='style10'>" . $FieldArray['salesordertaxableamt']['FieldLabel'] . "</span>&nbsp;</div>
					<div class='summary-input-col'><input class='inputBox-clean summary-input' type='text' id='vatdetail$vatindex' name='vatdetail$vatindex' value='" . $taxableAmountValue . "' maxlength='20' readonly>
					<input type='hidden'  id='vatdetailVal$vatindex' name='vatdetailVal$vatindex' value='$rowdetailsvat[1]'>
					</div>
				</div> ";
				else echo "<input type='hidden' id='vatdetail$vatindex' name='vatdetail$vatindex' value='" . $taxableAmountValue . "'><input type='hidden' id='vatdetailVal$vatindex' name='vatdetailVal$vatindex' value='$rowdetailsvat[1]'>";
			}
			//  && !$FieldArray['salesorderlevi']['IsHidden']
			$vatDetailAmount = number_format(($vatso + $txtSorTaxable + $totalVatFirst + $totalVatGross+ $txtChargesOngross) * $rowdetailsvat[1] / 100, $DecimalBase);
			if (!$FieldArray['salesorderlevi']['IsHidden'])
				echo "<div class='row summary-row'>
					<div class='summary-label-col'><span class='style10'>$rowdetailsvat[0]</span>&nbsp;</div> 
					<div class='summary-input-col'><input class='inputBox-clean summary-input' type='text' id='vatdetail$vatindex' name='vatdetail$vatindex' value='" . $vatDetailAmount . "' maxlength='20' readonly>
					<input type='hidden'  id='vatdetailVal$vatindex' name='vatdetailVal$vatindex' value='$rowdetailsvat[1]'>
					</div>
				</div> ";
			else
				echo "<input type='hidden' id='vatdetail$vatindex' name='vatdetail$vatindex' value='" . $vatDetailAmount . "'><input type='hidden' id='vatdetailVal$vatindex' name='vatdetailVal$vatindex' value='$rowdetailsvat[1]'>";
			$ij++;
		}
	}
	if ($Setup_VatPercentage > 0) {
		// $txtSorVatChk = 1;
		// if ($vatOngross == 1) {
		// 	$txtSorVat = number_format(($txtSorGrossTotal + $txtChargesOngross) * $Setup_VatPercentage / 100, $DecimalBase);
		// } else if ($vatOnNetgross == 1) { 
		// 	$txtSorVat = number_format(($txtSorGrossTotal + $txtChargesOngross + $totalVatFirst) * $Setup_VatPercentage / 100, $DecimalBase);
		// } else if ($vatOnTotal == 1)  $txtSorVat = number_format(($txtSorGrossTotal  + $txtChargesOngross+ $totalVatGross +  $totalVatFirst) * $Setup_VatPercentage / 100, $DecimalBase);
		if (!$FieldArray['salesordervatsm']['IsHidden']) {
			echo "<div class='row summary-row'>
				<div class='summary-label-col'><span class='style10'>" . $FieldArray['salesordervatsm']['FieldLabel'] . " $Setup_VatPercentage%</span>&nbsp;</div> 
				";

			echo "	
					<input type='hidden' name='txtSorVatChk' id='txtSorVatChk' value='$txtSorVatChk' size='1'/>
					<input hidden name='chkSorVat' type='checkbox' id='chkSorVat' value='1' >";
			echo "				</span>
				
				<div class='summary-input-col'>
					<input class='inputBox-clean summary-input' type='text' id='sorvat' name='txtSorVat' value='$txtSorVat' maxlength='20' readonly>
				</div>
			</div>";
		} else {
			echo "<input class='form-control form-control-sm' type='hidden' id='sorvat' name='txtSorVat' value='$txtSorVat' maxlength='20' style='text-align:right; width:100%; height:20px!important;font-size:12px!important; background-color:#ddd; margin-bottom: 4px;' >
		<input name='chkSorVat' type='hidden' id='chkSorVat' value='1' />";
		}
	} else {
		$txtSorVatChk = 0;
		echo "<input type='hidden' name='txtSorVatChk' id='txtSorVatChk' value='$txtSorVatChk' size='1'/>
		<input name='chkSorVat' type='hidden' id='chkSorVat' value='1' />";
		echo "<input class='form-control form-control-sm' type='hidden' id='sorvat' name='txtSorVat' value='$txtSorVat' maxlength='20' style='text-align:right; width:100%; height:20px!important;font-size:12px!important; background-color:#ddd; margin-bottom: 4px;' >
				";
	}

	// if ($Module_AdditionalTaxes == 1) {
	// 	echo "	<div class='row'>
	// 				<div class='col-md-6'><span class='style1'>$taxes</span>&nbsp;</div> 
	// 				<div class='col-md-6'><input class='form-control form-control-sm' type='text' id='tax' name='txtSorTotalTaxes' value='$txtSorTotalTaxes' maxlength='20' style='text-align:right; width:100%; height:20px;background:#C6D4E5;' readonly></div>
	// 			</div>";
	// } else {
	// 	echo "	<input type='hidden' name='txtSorTotalTaxes' value='$txtSorTotalTaxes'>";
	// }



	if (!$FieldArray['salesordernettotal']['IsHidden']) {
		$newtxtSorNetTotal = $txtSorNetTotal ;
		$netTotalClass = 'inputBox-clean summary-input summary-net-total';
		if ($txtSorNetTotal < 0) $netTotalClass .= ' negative-amount';

		echo "		<div class='row summary-row'>
					<div class='summary-label-col'><span class='style10 summary-net-total'>" . $FieldArray['salesordernettotal']['FieldLabel'] . "</span>&nbsp;</div>
					<div class='summary-input-col'><input class='$netTotalClass' type='text' id='nettotal' name='txtSorNetTotal' value='" . number_format($newtxtSorNetTotal, $DecimalBase) . "' maxlength='20'";

		echo " readonly></div>
				</div>";
	} else {
		echo "<input class='$inputRowClass' type='text' id='nettotal' name='txtSorNetTotal' value=''>";
	}

	if (!$FieldArray['salesorderchargessm']['IsHidden']) {
		echo "	<div class='row summary-row'>
				<div class='summary-label-col'><span class='style10'>" . $FieldArray['salesorderchargessm']['FieldLabel'] . "</span>&nbsp;</div>
				<div class='summary-input-col'><input class='inputBox-clean summary-input' type='text' id='sordercharges' name='txtSorCharges' value='" . number_format($txtSorCharges, $DecimalBase) . "' maxlength='20' readonly></div>
			</div>";
	} else {
		echo "<input type='hidden' id='grosstotal' name='txtSorGrossTotal' value=''>";
	}
	if (!$FieldArray['salesordernettotalcharges']['IsHidden']) {
		$newtxtSorNetTotal = $txtSorNetTotalCharges;
		$netTotalChargesClass = 'inputBox-clean summary-input summary-net-total';
		if ($txtSorNetTotalCharges < 0) $netTotalChargesClass .= ' negative-amount';

		echo "	<div class='row summary-row'>
				<div class='summary-label-col'><span class='style10 summary-net-total'>" . $FieldArray['salesordernettotalcharges']['FieldLabel'] . "</span>&nbsp;</div>
				<div class='summary-input-col'><input class='$netTotalChargesClass' type='text' id='nettotal' name='txtSorNetTotalCh' value='" . number_format($newtxtSorNetTotal, $DecimalBase) . "' maxlength='20'";

		echo " readonly></div>
			</div>";
	} else {
		echo "<input class='$inputRowClass' type='text' id='nettotal' name='txtSorNetTotalCh' value=''>";
	}
	echo "</div>";


	if ($Module_BPPlan == 1) {
		$querybp = "select sum(totbp) from (
				select sum(ifnull(sorder_totalBusinesspt,0)) as totbp
				from sorder
				where date_format(Sorder_date,'%Y-%m') = '$sorderdate' and sorder_id <> '$PSorId'
				and client_code = '$txtClientCode'
				union
				select sum(ifnull(sorder_totalBusinesspt,0)) as totbp
				from temp_sorder
				where sorder_id = '$PSorId'
				) as Q";
		$resultbp = mysqli_query($connection, $querybp);
		$rowbp = mysqli_fetch_row($resultbp);
		$montotalBpp = $rowbp[0];

		$queryPrev = "select sum(round(totbp)) from (
				select sum(ifnull(sorder_totalBusinesspt,0)) as totbp
				from sorder
				where date_format(Sorder_date,'%Y-%m') = '$previousMonth' 
				and client_code = '$txtClientCode'
				) as Q";
		$resultbp = mysqli_query($connection, $queryPrev);
		$rowbp = mysqli_fetch_row($resultbp);
		$prevTotMonthBpp = $rowbp[0];
		if ($prevTotMonthBpp == '') $prevTotMonthBpp = 0;

		$queryUsedPrev = "select sum(ifnull(tot,0)) from (
						select sum(round(SorderDt_Quantity*item_businessPt6)) as tot
						from sorderdt
						inner join items on items.item_code = sorderdt.item_code
						where ifnull(item_gift,0) = 5 and ifnull(sorderdt_itemIsGift,0) = 5 and ifnull(item_businessPt6,0) > 0
						and date_format(Sorder_date,'%Y-%m') = '$sorderdate' and sorder_id <> '$PSorId' and client_code = '$txtClientCode'
						union 
						select sum(round(SorderDt_Quantity*item_businessPt6)) as tot
						from temp_sorderdt
						inner join items on items.item_code = temp_sorderdt.item_code
						where ifnull(item_gift,0) = 5 and ifnull(sorderdt_itemIsGift,0) = 5 and ifnull(item_businessPt6,0) > 0
						and sorder_id = '$PSorId' 
					) as TotQ
					";
		$resUsedPrev = mysqli_query($connection, $queryUsedPrev);
		$rowUsedPrev = mysqli_fetch_row($resUsedPrev);
		$prevMonthBpp = $prevTotMonthBpp - $rowUsedPrev[0];
		if ($prevMonthBpp == '') $prevMonthBpp = 0;

		echo "<div class='summary-totals pr-2 pl-2 mt-2'>";

		echo "	<div class='row summary-row'>
				<div class='summary-label-col'><span class='style10'>" . $FieldArray['salesordertbp']['FieldLabel'] . "</span>&nbsp;</div> 
				<div class='summary-input-col'><input class='inputBox-clean summary-input' type='text' name='txtTotalBpp' id='totalBpp' value='$totalBpp' maxlength='20' readonly></div>
			</div>
			<div class='row summary-row'>
				<div class='summary-label-col'><span class='style10'>" . $FieldArray['salesordermbp']['FieldLabel'] . "</span>&nbsp;</div> 
				<div class='summary-input-col'><input class='inputBox-clean summary-input' type='text' name='txtMonTotalBpp' id='montotalBpp' value='$montotalBpp' maxlength='20' readonly></div>
			</div>
			<div class='row summary-row'>
				<div class='summary-label-col'><span class='style10'>" . $FieldArray['salesorderpmbp']['FieldLabel'] . "</span>&nbsp;</div> 
				<div class='summary-input-col'>
				<input type='hidden' name='txtPrevMonthBpp' id='prevMonthBpp' value='$prevMonthBpp' maxlength='20' readonly>
				<input class='inputBox-clean summary-input' type='text' name='txtPrevTotMonthBpp' id='prevTotMonthBpp' value='$prevMonthBpp / $prevTotMonthBpp' maxlength='20' readonly>
				</div>
			</div>";

		echo "</div>";
	}
}

function showDataGridFooter($filepath, $form, $transid, $gridid, $dftorderby, $action, $orderby, $morelines, $startrange, $perpage, $totalrows, $direction)
{


	echo "<div>
			<table width='100%' border=0>
			<tr class='datagrid-footer-row'>";

	$rangefrom = ($startrange + 1);
	if (($startrange + $perpage) >= $totalrows) $rangeto = $totalrows;
	else $rangeto = ($startrange + $perpage);

	if ($totalrows > 0)
		echo "	<td class='datagrid-footer-view'><span class='toolbar_style'>View " . $rangefrom . " - " . $rangeto . " of " . $totalrows . "</span></td>";
	else
		echo "	<td class='datagrid-footer-view'><span class='toolbar_style'>No records to view</span></td>";

	echo "		<td class='datagrid-footer-perpage'><span class='toolbar_style'>Per Page </span><span class='toolbar_style'><select name='perpage' id='perpage' value='$perpage' onchange=\"ReloadGrid('" . $filepath . "', '" . $form . "', '" . $transid . "', '" . $gridid . "', 1, '" . $dftorderby . "', 'CP','" . $orderby . "','" . $morelines . "',0,this.value,'" . $totalrows . "','" . $direction . "');\">";

	if ($perpage == 10)		 $s10 = 'selected';
	else if ($perpage == 20)	 $s20 = 'selected';
	else if ($perpage == 30)	 $s30 = 'selected';
	else if ($perpage == 40)	 $s40 = 'selected';
	else if ($perpage == 50)	 $s50 = 'selected';
	else if ($perpage == 100)	 $s100 = 'selected';
	else if ($perpage == 1000) $s1000 = 'selected';


	echo '<option ' . $s10 . ' value="10">10</option>
					 <option ' . $s20 . ' value="20">20</option>
					 <option ' . $s30 . ' value="30">30</option>
					 <option ' . $s40 . ' value="40">40</option>
					 <option ' . $s50 . ' value="50">50</option>
					 <option ' . $s100 . ' value="100">100</option>
					 <option ' . $s1000 . ' value="1000">1000</option>
					 </select></span>
				</td>';

	echo "		<td class='datagrid-footer-nav-prev'>";

	if ($startrange > 0)
		echo "<img src='img/firstpage.png' title='First Page' onMouseOver=\"style.cursor='pointer'\"    onclick=\"ReloadGrid('" . $filepath . "', '" . $form . "', '" . $transid . "', '" . $gridid . "', 1, '" . $dftorderby . "', 'F','" . $orderby . "','" . $morelines . "','" . $startrange . "','" . $perpage . "','" . $totalrows . "','" . $direction . "');return false;\" >";

	$diff = $startrange - $perpage;

	if (($diff) >= 0) {
		$prvstartrange = $startrange - $perpage;
		echo "&nbsp;&nbsp;<img src='img/arrowleft.png' title='Previous' onMouseOver=\"style.cursor='pointer'\"  onclick=\"ReloadGrid('" . $filepath . "', '" . $form . "', '" . $transid . "', '" . $gridid . "', 1, '" . $dftorderby . "', 'P','" . $orderby . "','" . $morelines . "','" . $prvstartrange . "','" . $perpage . "','" . $totalrows . "','" . $direction . "');return false;\" >";
	} else
		echo "&nbsp;";

	echo "		</td><td class='datagrid-footer-nav-next'>&nbsp;";


	if ($startrange <= $totalrows) {
		$startrange1 = $startrange + $perpage;
		if ($startrange1 < $totalrows) {
			echo "<img src='img/arrowright.png' title='Next' onclick=\"ReloadGrid('" . $filepath . "', '" . $form . "', '" . $transid . "', '" . $gridid . "', 1, '" . $dftorderby . "', 'N','" . $orderby . "','" . $morelines . "','" . $startrange1 . "','" . $perpage . "','" . $totalrows . "','" . $direction . "');return false;\" onmouseover=\"style.cursor='pointer'\" >";
			echo "&nbsp;&nbsp;<img src='img/lastpage.png' title='Last Page' onMouseOver=\"style.cursor='pointer'\" onclick=\"ReloadGrid('" . $filepath . "', '" . $form . "', '" . $transid . "', '" . $gridid . "', 1, '" . $dftorderby . "', 'L','" . $orderby . "','" . $morelines . "','" . $prvstartrange . "','" . $perpage . "','" . $totalrows . "','" . $direction . "');return false;\" >";
		}
	} else
		echo "&nbsp;";


	$currentpage = ($startrange / $perpage) + 1;
	$totalpages  = ceil($totalrows / $perpage);

	echo "		</td><td class='datagrid-footer-page'>&nbsp;<span class='toolbar_style'>Page $currentpage / $totalpages</span></td>";



	echo "		<td class='datagrid-footer-refresh'><img src='img/Refresh-icon.png'  onMouseOver=\"style.cursor='pointer'\"  width='20' height='20' onclick=\"ReloadGrid('" . $filepath . "', '" . $form . "', '" . $transid . "', '" . $gridid . "', 1, '" . $dftorderby . "', 'R','" . $orderby . "','" . $morelines . "','" . $startrange . "','" . $perpage . "','" . $totalrows . "','" . $direction . "');\" ></td>";
	echo "		<td class='datagrid-footer-spacer'>&nbsp;</td>";

	echo "	</tr>";
	echo "	</table></div>";
}




/* =====================================================================================
   CJR (Form_Corrugator) grids - converted from the PHP 5 Forms_Datagrid_SalesOrder.php
   to the same PHP 7 / grid-fixed-cols layout as ShowSorderDtGrid above.
   ===================================================================================== */

// Summary cell (label + read-only input) in the same markup as ShowSorderTotals' summary rows.
if (!function_exists('cjrSummaryCell')) {
	function cjrSummaryCell($label, $inputHtml, $labelStyle = '', $colClass = 'col-md-2')
	{
		return "<div class='$colClass px-2'><div class='row summary-row'>
					<div class='summary-label-col'><span class='style10' style='$labelStyle'>$label</span>&nbsp;</div>
					<div class='summary-input-col'>$inputHtml</div>
				</div></div>";
	}
}

function ShowCJRCorGrid($connection, $form, $txtCorrugatorId, $arrayparams, $arrayaccess, $dftorderby, $action = '', $orderby = '', $morelines = '', $startrange = '', $perpage = '', $totalrows = '', $direction = '')
{
	$FI_SorderCanceledQty = $arrayparams['FI_SorderCanceledQty'] ?? '';
	$FI_SalesCheckSalesman = $arrayparams['FI_SalesCheckSalesman'] ?? '';
	$Login_AllowPrice = $arrayparams['Login_AllowPrice'] ?? '';
	$Login_NegativeQty = $arrayparams['Login_NegativeQty'] ?? '';
	$Login_ModifySellingPrice = $arrayparams['Login_ModifySellingPrice'] ?? '';
	$Allow_MinimumPrice = $arrayparams['Allow_MinimumPrice'] ?? '';
	$Login_ModifyDiscount = $arrayparams['Login_ModifyDiscount'] ?? '';
	$Module_NegativeSorder = $arrayparams['Module_NegativeSorder'] ?? '';
	$Module_Unit1Method = $arrayparams['Module_Unit1Method'] ?? '';
	$Module_SorderCheckQtyOnPost = $arrayparams['Module_SorderCheckQtyOnPost'] ?? '';
	$Module_Pack = $arrayparams['Module_Pack'] ?? '';
	$Module_DoubleUnit = $arrayparams['Module_DoubleUnit'] ?? '';
	$Module_ProjectDt = $arrayparams['Module_ProjectDt'] ?? '';
	$Module_CostCenterDt = $arrayparams['Module_CostCenterDt'] ?? '';
	$Module_RestrictProjCost = $arrayparams['Module_RestrictProjCost'] ?? '';
	$Module_RestrictCost = $arrayparams['Module_RestrictCost'] ?? '';
	$ShowCostCentLayout = $arrayparams['ShowCostCentLayout'] ?? '';
	$SetupDftWareHouse = $arrayparams['SetupDftWareHouse'] ?? '';
	$defaultwarehouse = $arrayparams['defaultwarehouse'] ?? '';
	$Module_ShowWarehouseSalesorder = $arrayparams['Module_ShowWarehouseSalesorder'] ?? '';
	$sorder_export = $arrayparams['sorder_export'] ?? '';
	$txtSorType = $arrayparams['txtSorType'] ?? '';
	$SubItem_Production = $arrayparams['SubItem_Production'] ?? '';
	$FI_DeliverySalesPrice = $arrayparams['FI_DeliverySalesPrice'] ?? '';
	$Module_Heatno = $arrayparams['Module_Heatno'] ?? '';
	$sorderproduction = $arrayparams['sorderproduction'] ?? '';
	$showItemColor = $arrayparams['showItemColor'] ?? '';
	$FI_ShowPriceBase1 = $arrayparams['FI_ShowPriceBase1'] ?? '';
	$txtCurrCode = $arrayparams['txtCurrCode'] ?? '';
	$Base1 = $arrayparams['Base1'] ?? '';
	$Base2 = $arrayparams['Base2'] ?? '';
	$DM_showDescription = $arrayparams['DM_showDescription'] ?? '';
	$mainmode = $arrayparams['mainmode'] ?? '';
	$txtJobCardId = $arrayparams['txtJobCardId'] ?? array();
	if (!is_array($txtJobCardId)) $txtJobCardId = ($txtJobCardId == '') ? array() : explode(',', $txtJobCardId);

	// Grid is not tied to a sales order - kept for parity with the PHP 5 version.
	$PSorId = '';
	$CurrCode = $txtCurrCode;
	if ($CurrCode == $Base1 || $CurrCode == $Base2)
		$FI_ShowPriceBase1 = 0;

	$queryColor = "select color_code from color_setup order by color_code";

	if ($sorder_export == '' && $PSorId != '') {
		$query = " Select Sorder_Export
					  From Temp_Sorder
					 Where Temp_Sorder.Sorder_ID = " . $PSorId;
		$result = mysqli_query($connection, $query);
		$rows = $result ? mysqli_fetch_array($result) : null;
		$sorder_export = $rows[0] ?? '';
	}
	$ModeDiscount = '';
	if ($Login_ModifyDiscount == 0)
		$ModeDiscount = ' readonly ';
	$SalesTTC = $arrayparams['SalesTTC'] ?? '';
	$DecimalBase = $arrayparams['DecimalBase'] ?? '';

	$txtDate = $arrayparams['txtDate'] ?? '';
	$txtClientCode = $arrayparams['txtClientCode'] ?? '';
	$txtClientDetail = $arrayparams['txtClientDetail'] ?? '';
	$txtClientPromotion = $arrayparams['txtClientPromotion'] ?? '';
	$module_sorderReservedQty = $arrayparams['module_sorderReservedQty'] ?? '';

	if (empty($txtClientPromotion))
		$txtClientPromotion = 0;

	//Initializations

	$ClassE = 'grid_roundedtext1';
	$ClassI = 'grid_roundedtext2';

	$filepath = 'Forms_DataGrid_SalesOrder.php';

	$i = 0;
	$width = 0;
	$reelDec = 0;
	$reelDeckle = '';
	$totWidth = '';
	$LastItemWarehouse = '';
	$rowsdt = array();
	$SelectedRec = '';
	$SelectedRecColor = '';
	$ModRec_G1 = '';

	$PriceLabel = 'Price';
	if ($SalesTTC == 1)
		$PriceLabel = 'Price TTC';

	$ModeModifySellPrice = '';
	if ($Login_ModifySellingPrice != 1)
		$ModeModifySellPrice = 'readonly';

	if ($startrange == '')
		$startrange = 0;
	if ($perpage == '')
		$perpage = 50;
	if ($orderby == '')
		$orderby = $dftorderby;
	if ($morelines == '')
		$morelines = 1;

	if ($action == 'COR' || $direction == '') {
		if ($direction == 'asc')
			$direction = 'desc';
		else if ($direction == 'desc')
			$direction = 'asc';
		else
			$direction = 'asc';
	}

	$columnsarray = array("itemcode", "itemdescription", "itemquality", "warehousecode");
	if ($Module_Heatno == 1)
		array_push($columnsarray, 'sorderdt_heatno');

	if ($Module_DoubleUnit == 1)
		array_push($columnsarray, 'unit2qty');
	if ($sorder_export == 1) {
		array_push($columnsarray, 'sorderdt_quantitybox');
		array_push($columnsarray, 'sorderdt_itemunit');
		array_push($columnsarray, 'sorderdt_itempackage');
	}
	if ($sorderproduction == 1) {
		array_push($columnsarray, "itemprodqty");
		array_push($columnsarray, "itemsentprod");
	}
	array_push($columnsarray, "itemorderedqty");
	array_push($columnsarray, "cancelledqty");

	if ($FI_DeliverySalesPrice == 1) {
		array_push($columnsarray, 'price');
		if ($FI_ShowPriceBase1 == 1)
			array_push($columnsarray, 'pricebase1');
		array_push($columnsarray, 'sordtdiscount');
		array_push($columnsarray, 'sordttotal');
	}
	if ($showItemColor == 1)
		array_push($columnsarray, 'itemcolor');
	if ($Module_ProjectDt == 1 && $ShowCostCentLayout == 1)
		array_push($columnsarray, 'projectcodedt');
	if ($Module_CostCenterDt == 1 && $ShowCostCentLayout == 1)
		array_push($columnsarray, 'costcentcodedt');
	if ($DM_showDescription == 1)
		array_push($columnsarray, 'remarkdt');

	$columnsarrayall = array("itemcode", "itemdescription", "itemquality", "warehousecode", "sorderdt_heatno", "sorderdt_quantitybox", "sorderdt_itemunit", "sorderdt_itempackage", "itemorderedqty", "sordtquantity", "cancelledqty", "unit2qty", "price", "sordtdiscount", "sordttotal", "projectcodedt", "costcentcodedt", "remarkdt", "sordtid", "existingqty", "bookingqty", "Reservedqty", "availableqty", "lastprice", "itemunit1", "itemunit2", "itemvat", "itemminprice", "itemnonstock", "itemzeroprice", "itemunitcoef");
	$orderbyarray = array("SorderDt.Item_CodePacked", "Items.Item_Code", "Items.Item_Description", "SorderDt.Warehouse_Code", "Items.Item_UnitSalesDesc", "Items.Item_UnitPurchaseDesc");

	for ($j = 1; $j <= count($orderbyarray); $j++) {

		$index = array_search($orderby, $orderbyarray);
		$index += 1;

		$orderimg = 'orderimg' . $index;

		if (in_array($orderby, $orderbyarray) && $direction == 'asc')
			$$orderimg = "<img src='img/asc-2.png' name='w' width='10'  height='10' style='padding-left:5px;'>";
		else if (in_array($orderby, $orderbyarray) && $direction == 'desc')
			$$orderimg = "<img src='img/desc-2.png' name='w' width='10'  height='10' style='padding-left:5px;'>";
		else
			$$orderimg = "";
	}
	$orderimg2 = $orderimg2 ?? '';
	$orderimg3 = $orderimg3 ?? '';


	$columnslist = implode(',', $columnsarray);
	$columnslistall = implode(',', $columnsarrayall);


	// $widthTable = sum of each column's .grid-col-* tier, same convention as ShowSorderDtGrid.
	$widthTable = 34 + 34;       // rec# + delete
	$widthTable += 80 + 105;     // Cor Id (grid-col-unit), Sales Ref (grid-col-sm-plus)
	$widthTable += 80;           // Length
	$widthTable += 105;          // Width + reset icon (grid-col-sm-plus)
	$widthTable += 80 * 9;       // Flap Top, Height, Flap Bot, # Outs, T-Width, # Cuts, A Cuts, LM, SQ
	$widthTable += 34;           // submit

	echo "<div id='container' style='position: relative;z-index:1;' >";
	echo "	<div id='Loader' class=''></div>";
	echo " 	<div id='grid_headerDiv1' class='grid_headerDiv'>";
	echo "  	<div id='tablespan' class='table-responsive'>
			<table class='table table-bordered table-striped grid-fixed-cols cjr-cor-grid' style='width:100%;min-width:{$widthTable}px'>
			";

	echo "<thead><tr class='grid-header-row'>";
	echo "  <th class='grid-header-cell grid-col-xxs'><span class='grid-header-label'>&nbsp;</span></th>";
	echo "  <th class='grid-header-cell grid-col-xxs'><span class='grid-header-label'>&nbsp;</span></th>";
	echo "  <th class='grid-header-cell grid-col-unit'><span class='grid-header-label'>Cor Id" . $orderimg2 . "</span></th>";
	echo "  <th class='grid-header-cell grid-col-sm-plus'><span class='grid-header-label'>Sales Ref" . $orderimg3 . "</span></th>";
	echo "  <th class='grid-header-cell grid-col-unit'><span class='grid-header-label'>Length" . $orderimg2 . "</span></th>";
	echo "  <th class='grid-header-cell grid-col-sm-plus'><span class='grid-header-label'>Width" . $orderimg3 . "</span></th>";
	echo "  <th class='grid-header-cell grid-col-unit'><span class='grid-header-label'>Flap Top" . $orderimg3 . "</span></th>";
	echo "  <th class='grid-header-cell grid-col-unit'><span class='grid-header-label'>Height" . $orderimg3 . "</span></th>";
	echo "  <th class='grid-header-cell grid-col-unit'><span class='grid-header-label'>Flap Bot" . $orderimg3 . "</span></th>";
	echo "  <th class='grid-header-cell grid-col-unit'><span class='grid-header-label'># Outs" . $orderimg3 . "</span></th>";
	echo "  <th class='grid-header-cell grid-col-unit'><span class='grid-header-label'>T-Width" . $orderimg3 . "</span></th>";
	echo "  <th class='grid-header-cell grid-col-unit'><span class='grid-header-label'># Cuts" . $orderimg3 . "</span></th>";
	echo "  <th class='grid-header-cell grid-col-unit'><span class='grid-header-label'>A Cuts" . $orderimg3 . "</span></th>";
	echo "  <th class='grid-header-cell grid-col-unit'><span class='grid-header-label'>LM" . $orderimg3 . "</span></th>";
	echo "  <th class='grid-header-cell grid-col-unit'><span class='grid-header-label'>SQ" . $orderimg3 . "</span></th>";
	echo "  <th class='grid-header-cell grid-col-xxs'><span class='grid-header-label'>&nbsp;</span></th>";
	echo "</tr></thead>";

	echo "<input type='hidden' name='SelectedRec' id='SelectedRec' value='$SelectedRec'>
			 <input type='hidden' name='SelectedRecColor' id='SelectedRecColor' value='$SelectedRecColor'>
			 <input type='hidden' name='ModRec_G1' id='ModRec_G1' value='$ModRec_G1'>";

	$dangerBg = 'background-color:var(--grid-danger-bg);';

	for ($x = 0; $x < count($txtJobCardId); $x++) {

		if ($txtJobCardId[$x] != '') {

			$TotalQty = 0;

			$query = "select corgrid_id,jobcardid from temp_corgrid where corrugatorid=$txtCorrugatorId and jobcardid=$txtJobCardId[$x]";
			$result1 = mysqli_query($connection, $query);
			$numofrows = $result1 ? mysqli_num_rows($result1) : 0;
			while ($result1 && ($rows = mysqli_fetch_array($result1))) {

				if ($numofrows == 0) {

					$query = " SELECT null, sorder_reference,null, null, ifNull(jobcard_grossheight,0), ifNull(jobcard_grosswidth,0),
						ifNull(jobcard_scoring1,0), ifNull(jobcard_scoring2,0), ifNull(jobcard_scoring3,0)
						from jobcard
						left join sorder on sorder.sorder_id= jobcard.related_salesorder
						where jobcard.jobcard_id=$txtJobCardId[$x]";
				} else {
					$query = " SELECT corgrid_id, sorder_reference,corrugatorid, corref, ifNull(length,0), ifNull(width,0), ifNull(flaptop,0), ifNull(height,0), ifNull(flapbot,0),
						ifNull(numouts,0), ifNull(twidth,0), ifNull(numcuts,0), ifNull(actualcuts,0), ifNull(reeldeckle,0), ifNull(totwidth,0), ifNull(trimming,0), ifNull(trimwaste,0), ifNull(lm,0),
						ifNull(sq,0),jobcard.jobcard_id,corref ,corrugatorid
						from  jobcard
						left join temp_corgrid on jobcard.jobcard_Id= temp_corgrid.jobcardid
						left join sorder on sorder.sorder_id= jobcard.related_salesorder
						where corrugatorid=" . $txtCorrugatorId . "  and temp_corgrid.corgrid_Id='" . $rows[0] . "'";
				}

				$getwidth = "select ifNull(jobcard_grosswidth,0), ifNull(jobcard_scoring1,0),ifNull(jobcard_scoring2,0),
								    ifNull(jobcard_scoring3,0)
					        from jobcard
                            left join sorder on sorder.sorder_id= jobcard.related_salesorder
                                where jobcard.jobcard_id= '" . $rows[1] . "'";
				$resultwidth = mysqli_query($connection, $getwidth);
				$rowwidth = $resultwidth ? mysqli_fetch_array($resultwidth) : null;
				$oldwidth = $rowwidth[0] ?? '';
				$oldFlapTop = $rowwidth[1] ?? '';
				$oldFlapHeight = $rowwidth[2] ?? '';
				$oldFlapBottom = $rowwidth[3] ?? '';

				$result = mysqli_query($connection, $query);
				$totalrows = $result ? mysqli_fetch_array($result) : null;

				$query = $query . " limit " . $startrange . "," . $perpage;
				$resultdt = mysqli_query($connection, $query);

				while ($resultdt && ($rowsdt = mysqli_fetch_array($resultdt))) {

					$width += (float)$rowsdt[10];
					if ($i == 0) {
						$reelDec = $rowsdt[13];
					}

					$recno = $i + 1;

					if ($i % 2 == 0) $bgcolor = 'grid-row-even';
					else $bgcolor = 'grid-row-odd';

					$q = " select ulstacker_id from temp_upperlowerstacker where  corrugatorid=" . $txtCorrugatorId . " and jobcardid= '" . $rows[1] . "'";
					$res = mysqli_query($connection, $q);
					$ulstacker_row = $res ? mysqli_fetch_array($res) : null;
					$ulstacker_id = $ulstacker_row[0] ?? '';
					// JS call arguments must never be empty (was a syntax error in the PHP 5 version)
					$ulstackerJs = ($ulstacker_id !== '' && $ulstacker_id !== null) ? $ulstacker_id : 0;
					$corgridJs = ($rowsdt[0] !== '' && $rowsdt[0] !== null) ? $rowsdt[0] : "''";

					$corgrid_id = $rowsdt[0];

					$KeyDown = 'onkeydown="return dokey(event,this,' . $i . ',\'' . $columnslist . '\',\'imgSubmitcg[' . $i . ']\')"';

					$bgWidth = ($oldwidth != $rowsdt[5]) ? $dangerBg : '';
					$bgFlapTop = ($oldFlapTop != $rowsdt[6]) ? $dangerBg : '';
					$bgHeight = ($oldFlapHeight != $rowsdt[7]) ? $dangerBg : '';
					$bgFlapBot = ($oldFlapBottom != $rowsdt[8]) ? $dangerBg : '';

					$submitCorGrid = "SubmitCorGrid($i,$corgridJs,$morelines,$startrange,$perpage,$txtCorrugatorId, $ulstackerJs)";

					echo "<tr id='CurRec$i' class='$bgcolor table-focus-g1'>";
					echo "	<td class='grid-action-cell'>$recno
								<input type='hidden' id='olwidth[$i]' value='$oldwidth'>
								<input type='hidden' id='oldFlapTop[$i]' value='$oldFlapTop'>
								<input type='hidden' id='oldFlapHeight[$i]' value='$oldFlapHeight'>
								<input type='hidden' id='oldFlapBottom[$i]' value='$oldFlapBottom'>
								<input type='hidden' name='txtCorId[$i]' id='txtCorId[$i]' value='" . $corgrid_id . "'>
							</td>";

					if ($corgrid_id != '') {
						echo "	<td class='grid-action-cell'><img src='img/delete.png' class='grid-action-img' onMouseOver=\"style.cursor='pointer'\" onclick=\"DeleteRecordCorGrid('" . $txtCorrugatorId . "','" . $rowsdt[0] . "','" . $i . "','" . $dftorderby . "','" . $orderby . "','" . $morelines . "','" . $startrange . "','" . $perpage . "','" . $direction . "');return false;\" title='Delete Record'></td>";
					} else {
						echo "	<td class='grid-action-cell'></td>";
					}

					echo "	<td class='grid-col-unit'><span class='grid-single-control'><input type='number' name='txtCorRef[$i]' id='txtCorRef[$i]' value='" . $rowsdt[0] . "' readonly maxlength='20' class='roundedtext_amortization_small grid-cell-numeric'></span></td>";
					echo "	<td class='grid-col-sm-plus'><span class='grid-single-control'><input type='number' name='txtSalesRef[$i]' id='txtSalesRef[$i]' value='" . $rowsdt[1] . "' readonly maxlength='20' class='roundedtext_amortization_small grid-cell-numeric'></span></td>";
					echo "	<td class='grid-col-unit'><span class='grid-single-control'><input type='number' name='txtLength[$i]' id='txtLength[$i]' value='" . $rowsdt[4] . "' onchange='LM($i);cuts($i);' maxlength='20' class='roundedtext_amortization_small grid-cell-numeric'></span></td>";
					echo "	<td class='grid-col-sm-plus'><div class='grid-inline-control'>
								<span class='style4 grid-input-wrap'><input type='number' name='txtWidth[$i]' id='txtWidth[$i]' value='$rowsdt[5]' class='roundedtext_amortization_small grid-cell-numeric' style='$bgWidth' onchange='Twidth($i);$submitCorGrid'></span>
								<img src='img/undo-1.png' class='grid-action-img' onmouseover=\"style.cursor='pointer'\" onclick=\"DefaultWidth('" . $oldwidth . "','" . $oldFlapTop . "','" . $oldFlapHeight . "','" . $oldFlapBottom . "','" . $i . "','ModRec_G1');return false;\" title='Reset To Job Card Values'>
							</div></td>";
					echo "	<td class='grid-col-unit'><span class='grid-single-control'><input type='number' name='txtFlapTop[$i]' id='txtFlapTop[$i]' value='" . $rowsdt[6] . "' maxlength='20' class='roundedtext_amortization_small grid-cell-numeric' style='$bgFlapTop' onchange='calculCorWidth($i);$submitCorGrid'></span></td>";
					echo "	<td class='grid-col-unit'><span class='grid-single-control'><input type='number' name='txtHeight[$i]' id='txtHeight[$i]' value='$rowsdt[7]' class='roundedtext_amortization_small grid-cell-numeric' style='$bgHeight' onchange='calculCorWidth($i);$submitCorGrid'></span></td>";
					echo "	<td class='grid-col-unit'><span class='grid-single-control'><input type='number' name='txtFlapBot[$i]' id='txtFlapBot[$i]' value='" . $rowsdt[8] . "' maxlength='20' class='roundedtext_amortization_small grid-cell-numeric' style='$bgFlapBot' onchange='calculCorWidth($i);$submitCorGrid'></span></td>";
					echo "	<td class='grid-col-unit'><span class='grid-single-control'><input type='number' name='txtOuts[$i]' id='txtOuts[$i]' value='$rowsdt[9]' class='roundedtext_amortization_small grid-cell-numeric' onchange='Twidth($i);plannedQty1($i);LM($i);cuts($i);$submitCorGrid'></span></td>";
					echo "	<td class='grid-col-unit'><span class='grid-single-control'><input type='number' name='txtTWidth[$i]' id='txtTWidth[$i]' value='" . $rowsdt[10] . "' readonly maxlength='20' class='roundedtext_amortization_small grid-cell-numeric'></span></td>";
					echo "	<td class='grid-col-unit'><span class='grid-single-control'><input type='number' name='txtTCuts[$i]' id='txtTCuts[$i]' value='$rowsdt[11]' readonly class='roundedtext_amortization_small grid-cell-numeric' onchange='Twidth($i);LM($i)'></span></td>";
					echo "	<td class='grid-col-unit'><span class='grid-single-control'><input type='number' name='txtActualCuts[$i]' id='txtActualCuts[$i]' value='$rowsdt[12]' readonly class='roundedtext_amortization_small grid-cell-numeric'></span></td>";
					echo "	<td class='grid-col-unit'><span class='grid-single-control'><input type='number' name='txtLM[$i]' id='txtLM[$i]' value='$rowsdt[17]' readonly class='roundedtext_amortization_small grid-cell-numeric' onchange='prodQty($i);ReqTrim($i);'></span></td>";
					echo "	<td class='grid-col-unit'><span class='grid-single-control'><input type='number' name='txtSQ[$i]' id='txtSQ[$i]' value='$rowsdt[18]' readonly class='roundedtext_amortization_small grid-cell-numeric'></span></td>";
					echo "	<td class='grid-action-cell'><img src='img/confirm-1.png' class='grid-action-img' id='imgSubmitcg[$i]' onMouseOver=\"style.cursor='pointer'\" onclick=\"SubmitCorGrid('" . $i . "','" . $rowsdt[0] . "','" . $morelines . "','" . $startrange . "','" . $perpage . "','" . $txtCorrugatorId . "',$ulstackerJs); return false;\" title='Submit Record'></td>";
					echo "</tr>";

					$i += 1;
					$LastItemWarehouse = $rowsdt[4];
				}
			}
		}
	}

	// Empty entry line (shown until there are 2 corrugator rows)
	if ($i < 2) {
		for ($k = 1; $k <= 1; $k++) {
			$recno = $i + 1;
			$mode = 'i';

			if ($i % 2 == 0) $bgcolor = 'grid-row-even';
			else $bgcolor = 'grid-row-odd';

			$KeyDown = 'onkeydown="return dokey(event,this,' . $i . ',\'' . $columnslist . '\',\'imgSubmitcg[' . $i . ']\')"';
			$lookupEvents = "onchange=\"SetModRecord('ModRec_G1','$i'); CheckLookup('chkcodeexists','items','item_code',this.value,'lstItemCode[$i]'); GetSelectedItem(this.id,'getitem','" . $i . "','" . $txtDate . "','" . $txtCurrCode . "','" . $txtClientCode . "');\" onkeyup=\"AutoCompleteData(event,this.id,'itemcode[$i]','itemdescription[$i]','itemquality[$i]','items');\" " . $KeyDown;
			$emptyCorGridId = $rowsdt[0] ?? '';

			echo "<tr id='CurRec$i' class='$bgcolor table-focus-g1'>";
			echo "	<td class='grid-action-cell'>$recno<input type='hidden' name='txtCorId[$i]' id='txtCorId[$i]' value=''></td>";
			echo "	<td class='grid-action-cell'></td>";
			echo "	<td class='grid-col-unit'><span class='grid-single-control'><input type='number' name='txtCorRef[$i]' id='txtCorRef[$i]' value='' readonly maxlength='20' class='roundedtext_amortization_small grid-cell-numeric' $lookupEvents></span></td>";
			echo "	<td class='grid-col-sm-plus'><span class='grid-single-control'><input type='number' name='txtSalesRef[$i]' id='txtSalesRef[$i]' value='' readonly maxlength='20' class='roundedtext_amortization_small grid-cell-numeric' $lookupEvents></span></td>";
			echo "	<td class='grid-col-unit'><span class='grid-single-control'><input type='number' name='txtLength[$i]' id='txtLength[$i]' value='' maxlength='20' class='roundedtext_amortization_small grid-cell-numeric' onchange='LM($i);cuts($i)' " . $KeyDown . "></span></td>";
			echo "	<td class='grid-col-sm-plus'><span class='grid-single-control'><input type='number' name='txtWidth[$i]' id='txtWidth[$i]' value='' class='roundedtext_amortization_small grid-cell-numeric'></span></td>";
			echo "	<td class='grid-col-unit'><span class='grid-single-control'><input type='number' name='txtFlapTop[$i]' id='txtFlapTop[$i]' value='' maxlength='20' class='roundedtext_amortization_small grid-cell-numeric' onchange=\"SetModRecord('ModRec_G1','$i'); calculCorWidth($i); CheckLookup('chkcodeexists','items','item_code',this.value,'lstItemCode[$i]'); GetSelectedItem(this.id,'getitem','" . $i . "','" . $txtDate . "','" . $txtCurrCode . "','" . $txtClientCode . "'); \" onkeyup=\"AutoCompleteData(event,this.id,'itemcode[$i]','itemdescription[$i]','itemquality[$i]','items');\" " . $KeyDown . "></span></td>";
			echo "	<td class='grid-col-unit'><span class='grid-single-control'><input type='number' name='txtHeight[$i]' id='txtHeight[$i]' value='' class='roundedtext_amortization_small grid-cell-numeric' onchange='calculCorWidth($i);'></span></td>";
			echo "	<td class='grid-col-unit'><span class='grid-single-control'><input type='number' name='txtFlapBot[$i]' id='txtFlapBot[$i]' value='' maxlength='20' class='roundedtext_amortization_small grid-cell-numeric' onchange=\"SetModRecord('ModRec_G1','$i'); calculCorWidth($i); CheckLookup('chkcodeexists','items','item_code',this.value,'lstItemCode[$i]'); GetSelectedItem(this.id,'getitem','" . $i . "','" . $txtDate . "','" . $txtCurrCode . "','" . $txtClientCode . "'); \" onkeyup=\"AutoCompleteData(event,this.id,'itemcode[$i]','itemdescription[$i]','itemquality[$i]','items');\" " . $KeyDown . "></span></td>";
			echo "	<td class='grid-col-unit'><span class='grid-single-control'><input type='number' name='txtOuts[$i]' id='txtOuts[$i]' value='' class='roundedtext_amortization_small grid-cell-numeric' onchange='Twidth($i);plannedQty1($i);LM($i);cuts($i);'></span></td>";
			echo "	<td class='grid-col-unit'><span class='grid-single-control'><input type='number' name='txtTWidth[$i]' id='txtTWidth[$i]' value='' readonly maxlength='20' class='roundedtext_amortization_small grid-cell-numeric' $lookupEvents></span></td>";
			echo "	<td class='grid-col-unit'><span class='grid-single-control'><input type='number' name='txtTCuts[$i]' id='txtTCuts[$i]' value='' readonly class='roundedtext_amortization_small grid-cell-numeric' onchange='Twidth($i);LM($i)'></span></td>";
			echo "	<td class='grid-col-unit'><span class='grid-single-control'><input type='number' name='txtActualCuts[$i]' id='txtActualCuts[$i]' value='' readonly class='roundedtext_amortization_small grid-cell-numeric'></span></td>";
			echo "	<td class='grid-col-unit'><span class='grid-single-control'><input type='number' name='txtLM[$i]' id='txtLM[$i]' value='' readonly class='roundedtext_amortization_small grid-cell-numeric' onchange='prodQty($i);ReqTrim($i);'></span></td>";
			echo "	<td class='grid-col-unit'><span class='grid-single-control'><input type='number' name='txtSQ[$i]' id='txtSQ[$i]' value='' readonly class='roundedtext_amortization_small grid-cell-numeric'></span></td>";
			echo "	<td class='grid-action-cell'><img src='img/confirm-1.png' class='grid-action-img' id='imgSubmitcg[$i]' onMouseOver=\"style.cursor='pointer'\" onclick=\"SubmitCorGrid('" . $i . "','" . $emptyCorGridId . "','" . $morelines . "','" . $startrange . "','" . $perpage . "','" . $txtCorrugatorId . "',0); return false;\" title='Submit Record'></td>";
			echo "</tr>";

			$i += 1;
		}
	}

	echo "	</table>";
	echo "	</div>";
	echo "	</div>";

	// Reel deckle / total width / trimming totals (same summary-row markup as ShowSorderTotals)
	$trimming = (float)$reelDec - $width;

	if ($txtCorrugatorId != '') {
		$query = "select reeldeckle,totwidth,trimming from temp_corrugator1 where corrugator_id=$txtCorrugatorId";
		$result = mysqli_query($connection, $query);
		$rows = $result ? mysqli_fetch_array($result) : null;
		$reelDeckle = $rows[0] ?? '';
		$totWidth = $rows[1] ?? '';
		$trimming = $rows[2] ?? '';
	}
	$trimLabelStyle = '';
	if ($trimming < 30 || $trimming > 50) {
		$trimLabelStyle = $dangerBg;
	}

	echo "<div class='summary-totals pr-2 pl-2 mt-2'><div class='row no-gutters'>";
	echo cjrSummaryCell("Reel Deckle", "<input class='inputBox-clean summary-input' type='number' name='txtReelDec' id='txtReelDec' value='" . $reelDeckle . "' onchange=\"trimming1();SQ1($i);SubmitReelDec('" . $txtCorrugatorId . "');\">", '', 'col-md-3');
	echo cjrSummaryCell("Tot Width", "<input class='inputBox-clean summary-input' type='number' id='txtTotWidth' name='txtTotWidth' value='$totWidth' readonly>", '', 'col-md-3');
	echo cjrSummaryCell("Trimming", "<input class='inputBox-clean summary-input' type='number' id='txtTrimming' name='txtTrimming' value='$trimming' readonly>", $trimLabelStyle, 'col-md-3');
	echo "</div></div>";

	echo "</div>";
}


function ShowCJRLGrid($connection, $form, $txtCorrugatorId, $arrayparams, $arrayaccess, $dftorderby, $action = '', $orderby = '', $morelines = '', $startrange = '', $perpage = '', $totalrows = '', $direction = '')
{
	$FI_SorderCanceledQty = $arrayparams['FI_SorderCanceledQty'] ?? '';
	$FI_SalesCheckSalesman = $arrayparams['FI_SalesCheckSalesman'] ?? '';
	$Login_AllowPrice = $arrayparams['Login_AllowPrice'] ?? '';
	$Login_NegativeQty = $arrayparams['Login_NegativeQty'] ?? '';
	$Login_ModifySellingPrice = $arrayparams['Login_ModifySellingPrice'] ?? '';
	$Allow_MinimumPrice = $arrayparams['Allow_MinimumPrice'] ?? '';
	$Login_ModifyDiscount = $arrayparams['Login_ModifyDiscount'] ?? '';
	$Module_NegativeSorder = $arrayparams['Module_NegativeSorder'] ?? '';
	$Module_Unit1Method = $arrayparams['Module_Unit1Method'] ?? '';
	$Module_SorderCheckQtyOnPost = $arrayparams['Module_SorderCheckQtyOnPost'] ?? '';
	$Module_Pack = $arrayparams['Module_Pack'] ?? '';
	$Module_DoubleUnit = $arrayparams['Module_DoubleUnit'] ?? '';
	$Module_ProjectDt = $arrayparams['Module_ProjectDt'] ?? '';
	$Module_CostCenterDt = $arrayparams['Module_CostCenterDt'] ?? '';
	$Module_RestrictProjCost = $arrayparams['Module_RestrictProjCost'] ?? '';
	$Module_RestrictCost = $arrayparams['Module_RestrictCost'] ?? '';
	$ShowCostCentLayout = $arrayparams['ShowCostCentLayout'] ?? '';
	$SetupDftWareHouse = $arrayparams['SetupDftWareHouse'] ?? '';
	$defaultwarehouse = $arrayparams['defaultwarehouse'] ?? '';
	$Module_ShowWarehouseSalesorder = $arrayparams['Module_ShowWarehouseSalesorder'] ?? '';
	$sorder_export = $arrayparams['sorder_export'] ?? '';
	$txtSorType = $arrayparams['txtSorType'] ?? '';
	$SubItem_Production = $arrayparams['SubItem_Production'] ?? '';
	$FI_DeliverySalesPrice = $arrayparams['FI_DeliverySalesPrice'] ?? '';
	$Module_Heatno = $arrayparams['Module_Heatno'] ?? '';
	$sorderproduction = $arrayparams['sorderproduction'] ?? '';
	$showItemColor = $arrayparams['showItemColor'] ?? '';
	$FI_ShowPriceBase1 = $arrayparams['FI_ShowPriceBase1'] ?? '';
	$txtCurrCode = $arrayparams['txtCurrCode'] ?? '';
	$Base1 = $arrayparams['Base1'] ?? '';
	$Base2 = $arrayparams['Base2'] ?? '';
	$DM_showDescription = $arrayparams['DM_showDescription'] ?? '';
	$rowsNb = $arrayparams['rowsNb'] ?? 1;
	$PIndex = $arrayparams['PIndex'] ?? '';
	$txtGSM = $arrayparams['txtGSM'] ?? '';
	$txtPaperGrade = $arrayparams['txtPaperGrade'] ?? '';
	$txtRellDeckle = $arrayparams['txtRellDeckle'] ?? '';
	$txtPaperMill = $arrayparams['txtPaperMill'] ?? '';

	// Grid is not tied to a sales order - kept for parity with the PHP 5 version.
	$PSorId = '';
	$CurrCode = $txtCurrCode;
	if ($CurrCode == $Base1 || $CurrCode == $Base2)
		$FI_ShowPriceBase1 = 0;

	$queryColor = "select color_code from color_setup order by color_code";

	if ($sorder_export == '' && $PSorId != '') {
		$query = " Select Sorder_Export
					  From Temp_Sorder
					 Where Temp_Sorder.Sorder_ID = " . $PSorId;
		$result = mysqli_query($connection, $query);
		$rows = $result ? mysqli_fetch_array($result) : null;
		$sorder_export = $rows[0] ?? '';
	}
	$ModeDiscount = '';
	if ($Login_ModifyDiscount == 0)
		$ModeDiscount = ' readonly ';
	$SalesTTC = $arrayparams['SalesTTC'] ?? '';
	$DecimalBase = $arrayparams['DecimalBase'] ?? '';

	$txtDate = $arrayparams['txtDate'] ?? '';
	$txtClientCode = $arrayparams['txtClientCode'] ?? '';
	$txtClientDetail = $arrayparams['txtClientDetail'] ?? '';
	$txtClientPromotion = $arrayparams['txtClientPromotion'] ?? '';
	$module_sorderReservedQty = $arrayparams['module_sorderReservedQty'] ?? '';

	if (empty($txtClientPromotion))
		$txtClientPromotion = 0;

	//Initializations

	$ClassE = 'grid_roundedtext1';
	$ClassI = 'grid_roundedtext2';

	$filepath = 'Forms_DataGrid_SalesOrder.php';

	$i = 0;
	$rowsdt = array();
	$SelectedRec = '';
	$SelectedRecColor = '';
	$ModRec_G1 = '';
	$LastItemWarehouse = '';
	$txtWarehouseCode = array();
	$txtItemCode = array();
	$txtItemDesc = array();
	$txtSizeTrim = array();
	$txtURequired = array();
	$txtUActual = array();
	$txtLRequired = array();
	$txtLActual = array();
	$txtTRequired = array();
	$txtTActual = array();
	$txtRequiredTrim = array();
	$txtActualTrim = array();
	$txtSupplier = '';
	$totSizeTrim = 0;
	$UReqTot = 0;
	$UActTot = 0;
	$LReqTot = 0;
	$LActTot = 0;
	$TReqTot = 0;
	$TActTot = 0;
	$ReqTrimTot = 0;
	$ActTrimTot = 0;
	$txtURequiredT = '';
	$txtLRequiredT = '';
	$txtWTPCU = '';
	$txtWTPCL = '';
	$txtTotalGSM = '';
	$trimWaste = '';
	$TextColor = '';

	$PriceLabel = 'Price';
	if ($SalesTTC == 1)
		$PriceLabel = 'Price TTC';

	$ModeModifySellPrice = '';
	if ($Login_ModifySellingPrice != 1)
		$ModeModifySellPrice = 'readonly';

	if ($startrange == '')
		$startrange = 0;
	if ($perpage == '')
		$perpage = 50;
	if ($orderby == '')
		$orderby = $dftorderby;
	if ($morelines == '')
		$morelines = 1;

	if ($action == 'COR' || $direction == '') {
		if ($direction == 'asc')
			$direction = 'desc';
		else if ($direction == 'desc')
			$direction = 'asc';
		else
			$direction = 'asc';
	}

	$columnsarray = array("itemcode", "itemdescription", "itemquality", "warehousecode");
	if ($Module_Heatno == 1)
		array_push($columnsarray, 'sorderdt_heatno');

	if ($Module_DoubleUnit == 1)
		array_push($columnsarray, 'unit2qty');
	if ($sorder_export == 1) {
		array_push($columnsarray, 'sorderdt_quantitybox');
		array_push($columnsarray, 'sorderdt_itemunit');
		array_push($columnsarray, 'sorderdt_itempackage');
	}
	if ($sorderproduction == 1) {
		array_push($columnsarray, "itemprodqty");
		array_push($columnsarray, "itemsentprod");
	}
	array_push($columnsarray, "itemorderedqty");
	array_push($columnsarray, "cancelledqty");

	if ($FI_DeliverySalesPrice == 1) {
		array_push($columnsarray, 'price');
		if ($FI_ShowPriceBase1 == 1)
			array_push($columnsarray, 'pricebase1');
		array_push($columnsarray, 'sordtdiscount');
		array_push($columnsarray, 'sordttotal');
	}
	if ($showItemColor == 1)
		array_push($columnsarray, 'itemcolor');
	if ($Module_ProjectDt == 1 && $ShowCostCentLayout == 1)
		array_push($columnsarray, 'projectcodedt');
	if ($Module_CostCenterDt == 1 && $ShowCostCentLayout == 1)
		array_push($columnsarray, 'costcentcodedt');
	if ($DM_showDescription == 1)
		array_push($columnsarray, 'remarkdt');

	$columnsarrayall = array("txtItemDesc1", "txtItemCode1", "txtLocation", "txtGSM", "txtPaperGrade", "txtRellDeckle", "txtPaperMill", "txtSupplier", "txtURequired", "txtUActual", "txtLRequired", "txtLActual", "txtTRequired", "txtTActual", "txtIPaper", "txtFPaper", "txtOPaper", "txtIBalance", "txtFBalance", "txtOBalance", "txtWTPCU", "txtWTPCL", "txtTotalGSM", "txtSizeTrim", "txtRequiredTrim", "txtActualTrim", "txtTotalRequired", "txtTotalActual");
	$orderbyarray = array("SorderDt.Item_CodePacked", "Items.Item_Code", "Items.Item_Description", "SorderDt.Warehouse_Code", "Items.Item_UnitSalesDesc", "Items.Item_UnitPurchaseDesc");

	for ($j = 1; $j <= count($orderbyarray); $j++) {

		$index = array_search($orderby, $orderbyarray);
		$index += 1;

		$orderimg = 'orderimg' . $index;

		if (in_array($orderby, $orderbyarray) && $direction == 'asc')
			$$orderimg = "<img src='img/asc-2.png' name='w' width='10'  height='10' style='padding-left:5px;'>";
		else if (in_array($orderby, $orderbyarray) && $direction == 'desc')
			$$orderimg = "<img src='img/desc-2.png' name='w' width='10'  height='10' style='padding-left:5px;'>";
		else
			$$orderimg = "";
	}
	$orderimg2 = $orderimg2 ?? '';
	$orderimg3 = $orderimg3 ?? '';
	$recno = 0;

	$columnslist = implode(',', $columnsarray);
	$columnslistall = implode(',', $columnsarrayall);

	$dangerBg = 'background-color:var(--grid-danger-bg);';

	// $widthTable = sum of each column's .grid-col-* tier, same convention as ShowSorderDtGrid.
	$widthTable = 34;            // clear record
	$widthTable += 115;          // type label (grid-col-md)
	$widthTable += 105;          // Item (grid-col-sm-plus)
	$widthTable += 90 * 4;       // Reel Deckle, Paper Grade, GSM, Size MM (grid-col-xs)
	$widthTable += 115;          // Paper Mill (grid-col-md)
	$widthTable += 115;          // WareHouse (grid-col-md)
	$widthTable += 140;          // Supplier + lookup (grid-col-lg)
	$widthTable += 90 * 2;       // U Required, L Required (grid-col-xs)
	$widthTable += 34;           // submit

	echo "<div id='container' style='position: relative;z-index:1;' >";
	echo "	<div id='Loader' class=''></div>";
	echo " 	<div id='headerDivl' class='grid_headerDiv'>";
	echo "  	<div id='tablespan' class='table-responsive'>
			<table class='table table-bordered table-striped grid-fixed-cols cjr-location-grid' style='width:100%;min-width:{$widthTable}px'>
			";

	echo "<thead><tr class='grid-header-row'>";
	echo "  <th class='grid-header-cell grid-col-xxs'><span class='grid-header-label'>&nbsp;</span></th>";
	echo "  <th class='grid-header-cell grid-col-md'><span class='grid-header-label'>&nbsp;</span></th>";
	echo "  <th class='grid-header-cell grid-col-sm-plus'><span class='grid-header-label'>Item" . $orderimg2 . "</span></th>";
	echo "  <th class='grid-header-cell grid-col-xs'><span class='grid-header-label'>Reel Deckle" . $orderimg3 . "</span></th>";
	echo "  <th class='grid-header-cell grid-col-xs'><span class='grid-header-label'>Paper Grade" . $orderimg2 . "</span></th>";
	echo "  <th class='grid-header-cell grid-col-xs'><span class='grid-header-label'>GSM" . $orderimg3 . "</span></th>";
	echo "  <th class='grid-header-cell grid-col-xs'><span class='grid-header-label'>Size MM" . $orderimg3 . "</span></th>";
	echo "  <th class='grid-header-cell grid-col-md'><span class='grid-header-label'>Paper Mill" . $orderimg3 . "</span></th>";
	echo "  <th class='grid-header-cell grid-col-md'><span class='grid-header-label'>WareHouse" . $orderimg3 . "</span></th>";
	echo "  <th class='grid-header-cell grid-col-lg'><span class='grid-header-label'>Supplier" . $orderimg3 . "</span></th>";
	echo "  <th class='grid-header-cell grid-col-xs'><span class='grid-header-label'>U Required" . $orderimg3 . "</span></th>";
	echo "  <th class='grid-header-cell grid-col-xs'><span class='grid-header-label'>L Required" . $orderimg3 . "</span></th>";
	echo "  <th class='grid-header-cell grid-col-xxs'><span class='grid-header-label'>&nbsp;</span></th>";
	echo "</tr></thead>";

	echo "<input type='hidden' name='SelectedRec' id='SelectedRec' value='$SelectedRec'>
			 <input type='hidden' name='SelectedRecColor' id='SelectedRecColor' value='$SelectedRecColor'>
			 <input type='hidden' name='ModRec_G1' id='ModRec_G1' value='$ModRec_G1'>";


	$corrId = explode(',', $txtCorrugatorId);
	$rowsNb_ = $rowsNb;

	$corrugator_date_ = '';
	if ($txtCorrugatorId != '') {
		$queryHeader = "SELECT corrugator_date from temp_corrugator1 where corrugator_id = '$txtCorrugatorId' ";
		$resultHeader = mysqli_query($connection, $queryHeader) or die(mysqli_error($connection));
		$rowsHeader = mysqli_fetch_array($resultHeader);
		$corrugator_date_ = $rowsHeader[0] ?? '';
	}

	// Warehouses list (same for every row)
	$warehouseList = array();
	$resutlwh = mysqli_query($connection, "Select Warehouse_code from adjustmentsetup ");
	while ($resutlwh && ($rowswh = mysqli_fetch_array($resutlwh))) {
		$warehouseList[] = $rowswh[0];
	}

	for ($k = 1; $k <= $rowsNb; $k++) {
		$recno = $i + 1;
		$mode = 'i';

		if ($i % 2 == 0) $bgcolor = 'grid-row-even';
		else $bgcolor = 'grid-row-odd';

		if (empty($txtWarehouseCode[$i])) {
			if (!empty($LastItemWarehouse))
				$txtWarehouseCode[$i] = $LastItemWarehouse;
			else if (!empty($SetupDftWareHouse))
				$txtWarehouseCode[$i] = $SetupDftWareHouse;
			else
				$txtWarehouseCode[$i] = $defaultwarehouse;
		}

		$KeyDown = 'onkeydown="return dokey(event,this,' . $i . ',\'' . $columnslist . '\',\'imgSubmitlg[' . $i . ']\')"';

		if ($rowsNb_ == 3) {
			if ($recno == 1) {
				$recno = "INNER LINER";
			} else if ($recno == 2) {
				$recno = "FLUTE";
			} else if ($recno == 3) {
				$recno = "OUTER LINER";
			}
		}
		if ($rowsNb_ == 5) {
			if ($recno == 1) {
				$recno = "LINER-1";
			} else if ($recno == 2) {
				$recno = "FLUTE-1";
			} else if ($recno == 3) {
				$recno = "LINER-2";
			} else if ($recno == 4) {
				$recno = "FLUTE-2";
			} else if ($recno == 5) {
				$recno = "OUTER LINER";
			}
		}

		$TotalQty = 0;
		$i_nonstock = 0;
		$i_zeroprice = 0;
		$i_minprice = 0;
		$i_unitcoef = 1;

		$query = " Select loc_id, corrugatorid, location, gsm, papergradle, relldeckle, papermill, supplier,
		upperpaperrequired, upperpaperactual, lowerpaperrequired, lowerpaperactual,
		totURequired, totURequiredT,totUactual,totUactualT,
		paperinner, paperflutting, paperouter, balanceinner, balanceflutting, balanceouter, wtpcupper,
		wtpclower, totalgsm, trimsize, trimrequired,
		trimactual, totalwasterequired, totalwasteactual,corrugatorid,itemCode,itemDesc, totalrequired, totalactual,totURequired,totURequiredT,
		totUactual,totUactualT,trimwasteper,warehouse_code,itemType from
		temp_locationtable
		where corrugatorid='" . $corrId[0] . "' and itemtype='$recno'  order by loc_id asc ";

		$result = mysqli_query($connection, $query);
		$totalrows = $result ? mysqli_fetch_array($result) : null;
		$rowsNb1 = $result ? mysqli_num_rows($result) : 0;

		$query = $query . " limit " . $startrange . "," . $perpage;
		$resultdt = mysqli_query($connection, $query);

		if ($rowsNb1 > 0) {
			while ($resultdt && ($rowsdt = mysqli_fetch_array($resultdt))) {

				$txtType = $rowsdt[41];

				$txtWTPCU = $rowsdt[22];
				$wtpclower = $rowsdt[23];
				$totGSM = $rowsdt[24];

				$sorderdt_clientdiscount[$i] = $rowsdt[24];
				$sorderdt_itemdiscount[$i] = $rowsdt[25];

				$sub_query = " Select QtyExistsWSorder('" . $rowsdt[2] . "', '" . $rowsdt[4] . "', '" . insertMySQLDate($txtDate) . "', 0, 'SO', '', '') QtyExists From Dual ";
				$sub_result = selectData($connection, $sub_query);
				$vItemQtyExists = 0;
				$vItemQtyExists = $sub_result[0];

				$TextColor = 'style7';
				$Color = '';
				if ($vItemQtyExists < 0) {
					$Color = $dangerBg;
					$TextColor = 'style8';
				}

				$sub_query = " SELECT ifNull(Item_Pack, 0), ifNull(item_zeroPrice, 0),
					Item_UnitSalesDesc, Item_UnitPurchaseDesc, ifNull(Item_Unit,1), ifNull(Item_NonStock, 0),
					ifNull(Item_MinimumPrice, 0) ,ifNull(item_allowfloat, 0),ifNull(Item_ModifySellingPrice, 0)" .
					" FROM Items " .
					" WHERE Items.Item_Code = '" . $rowsdt[2] . "'";
				$sub_result = selectData($connection, $sub_query);
				$i_pack = $sub_result[0];
				$i_zeroprice = $sub_result[1];
				$i_unit1 = $sub_result[2];
				$i_unit2 = $sub_result[3];
				$i_unitcoef = $sub_result[4];
				$i_nonstock = $sub_result[5];
				$i_minprice = $sub_result[6];
				$item_allowfloat = $sub_result[7];
				$Item_ModifySellingPrice = $sub_result[8];

				$sorderdt_quantitybox = $rowsdt[21];
				$sorderdt_itemunit = $rowsdt[22];
				$sorderdt_itempackage = $rowsdt[23];

				$sub_query = "SELECT ifNull(Client_SellingPrice,1) FROM Clients WHERE Ledger_Number = '" . $txtClientCode . "'";
				$sub_result = selectData($connection, $sub_query);
				$c_sp = $sub_result[0];

				$txtItemMinPrice[$i] = $i_minprice;
				$txtItemZeroPrice[$i] = $i_zeroprice;
				$txtItemNonStock[$i] = $i_nonstock;
				$txtItemUnitCoef[$i] = $i_unitcoef;

				$Qry_QtyBookTemp = "SELECT ifnull(QtyBooking_Temp1('" . $rowsdt[2] . "', '" . $rowsdt[4] . "', '" . insertMySQLDate($txtDate) . "', $rowsdt[0]),0) ";
				$result = selectData($connection, $Qry_QtyBookTemp);
				$min_book_qty = $result[0];

				$Qry_QtyReservedTemp = "SELECT ifnull(QtyReserved_Temp('" . $rowsdt[2] . "', '" . $rowsdt[4] . "', '" . insertMySQLDate($txtDate) . "', $rowsdt[0]),0) ";
				$result = selectData($connection, $Qry_QtyReservedTemp);
				if ($result[0] < 0) {
					$result[0] = 0;
				}
				$min_Reserved_qty = $result[0];

				$Qry_QtyExists = " SELECT ifnull(QtyExists('" . $rowsdt[2] . "', '" . $rowsdt[4] . "', '" . insertMySQLDate($txtDate) . "', $rowsdt[0], 'SO', '', ''),0) As QtyExists ";
				$result = selectData($connection, $Qry_QtyExists);
				$min_exists_qty = $result[0];

				$Qry_Unit2QtyExists = " SELECT 0 As QtyExists "; //shurki
				$result = selectData($connection, $Qry_Unit2QtyExists);
				$min_exists_unit2qty = $result[0];

				$Qry_QtyAvailable = " Select QtyExistsWSorder_Temp('" . $rowsdt[2] . "', '" . $rowsdt[4] . "', '" . insertMySQLDate($txtDate) . "', $rowsdt[0], 'SO', '', '', 0) QtyExists From Dual ";
				$result = selectData($connection, $Qry_QtyAvailable);
				$min_available_qty = $result[0];

				// $PSorId is always empty here, so compare against 0 to keep the SQL valid
				$Qry_QtyInTemp = " Select ifNull(Sum(ifNull(SorderDt_Quantity,0)),0) FROM Temp_Sorderdt " .
					" WHERE Item_code = '" . $rowsdt[2] . "'  And  Warehouse_code = '" . $rowsdt[40] . "' And Sorder_ID = '" . $PSorId . "' And SorderDt_ID != ifNull('" . $rowsdt[0] . "',0) ";
				$result = selectData($connection, $Qry_QtyInTemp);
				$QtyTemp_SO = $result[0];

				$Qry_QtyTransferInTemp = " Select ifNull(Sum(ifNull(TransferDt_Quantity,0)),0) FROM Temp_TransferDt " .
					" WHERE Item_code = '" . $rowsdt[2] . "'  And  TransferDt_WarehouseFrom = '" . $rowsdt[40] . "' ";
				$result = selectData($connection, $Qry_QtyTransferInTemp);
				$QtyTemp_TR = $result[0];

				$min_available_qty = $min_available_qty - $QtyTemp_SO - $QtyTemp_TR;
				$min_book_qty = $min_book_qty + $QtyTemp_SO + $QtyTemp_TR;
				$min_Reserved_qty = $min_Reserved_qty + $QtyTemp_SO + $QtyTemp_TR;

				$txtBookingQty[$i] = $min_book_qty;
				$txtReservedQty[$i] = $min_Reserved_qty;
				$txtAvailableQty[$i] = $min_available_qty;
				$txtExistingQty[$i] = $min_exists_qty;
				$txtExistUnit2Qty[$i] = $min_exists_unit2qty;

				$submitLocation = "SubmitLocationGrid('" . $i . "','" . $rowsdt[0] . "','" . $morelines . "','" . $startrange . "','" . $perpage . "','" . $txtCorrugatorId . "','" . $txtType . "');";

				echo "<tr id='CurRec$i' class='$bgcolor table-focus-g1'>";
				echo "	<td class='grid-action-cell'><img src='img/undo-1.png' class='grid-action-img' onmouseover=\"style.cursor='pointer'\" onclick=\"ClearRecordGrid('" . $columnslistall . "','" . $i . "','ModRec_G1');return false;\" title='Clear Record'></td>";
				echo "	<td class='grid-col-md grid-cell-nowrap'><span class='style1'>$txtType</span>
							<input type='hidden' name='locid[$i]' id='locid[$i]' value='" . $rowsdt[0] . "'>
							<input type='hidden' name='txttype[$i]' id='txttype[$i]' value='" . $txtType . "'></td>";

				// Item
				echo "	<td class='grid-col-sm-plus'><span class='grid-single-control'>
							<input type='text' name='txtItemCode1[$i]' id='txtItemCode1[$i]' value='" . $rowsdt[31] . "' readonly maxlength='20' class='roundedtext_amortization_small' onchange=\"SetModRecord('ModRec_G1','$i'); CheckLookup('chkcodeexists','items','item_code',this.value,'lstItemCode[$i]'); GetSelectedItem(this.id,'getitem','" . $i . "','" . $txtDate . "','" . $txtCurrCode . "','" . $txtClientCode . "'); checkduplicate('$i',this.value);\" onkeyup=\"AutoCompleteData(event,this.id,'itemcode[$i]','itemdescription[$i]','itemquality[$i]','items');\" " . $KeyDown . ">
							<input type='hidden' name='txtItemDesc1[$i]' id='txtItemDesc1[$i]' value='" . $rowsdt[32] . "'>
						</span></td>";

				// Reel Deckle
				$query = "select distinct ItemParam_Code,itemdt_value from itemsdt
				left join items on items.item_code=itemsdt.item_code
				where ItemParam_Code='WIDTH' and group_code='RM' order by itemdt_value asc";
				$result = mysqli_query($connection, $query);
				echo "	<td class='grid-col-xs'><span class='grid-single-control'><select name='txtRellDeckle[$i]' id='txtRellDeckle[$i]' onchange='setPaperGrade($i);sizeTrim($i);' class='roundedtext_amortization_small' " . $KeyDown . ">";
				echo "<option></option>";
				while ($result && ($rows = mysqli_fetch_array($result))) {
					$s = '';
					if ($rows[1] == $rowsdt[5]) {
						$s = "selected";
						$relldec = $rows[1];
					}
					echo "<option value='$rows[1]' $s>$rows[1]</option>";
				}
				echo "</select></span></td>";

				// Paper Grade (only grades that exist for the chosen reel deckle)
				$txtRellDeckle = $rowsdt[5];
				$query = "select distinct ItemParam_Code,itemdt_value from itemsdt
				left join items on items.item_code=itemsdt.item_code
				where ItemParam_Code='type' and group_code='RM' order by itemdt_value asc ";
				$result = mysqli_query($connection, $query);
				echo "	<td class='grid-col-xs'><span class='grid-single-control'><select name='txtPaperGrade[$i]' id='txtPaperGrade[$i]' class='roundedtext_amortization_small' onchange='setGSM($i);'>";
				echo "<option></option>";
				$txtGSM = $rowsdt[3];
				while ($result && ($rows = mysqli_fetch_array($result))) {
					$txtPaperGrade = $rows[1];
					$queryPaperGrade = "SELECT count(*) FROM  (
											SELECT itemsdt.ITEM_CODE
											FROM itemsdt
											LEFT JOIN items ON items.item_code = itemsdt.item_code
											WHERE itemdt_value IN  ('$txtRellDeckle','$txtPaperGrade') and group_code='RM' GROUP BY itemsdt.ITEM_CODE HAVING COUNT(distinct ITEMDT_VALUE) = 2
											)as subquery";
					$resultPaperGrade = mysqli_query($connection, $queryPaperGrade);
					$rowPaperGrade = $resultPaperGrade ? mysqli_fetch_array($resultPaperGrade) : null;
					$countItem = $rowPaperGrade[0] ?? 0;
					if ($countItem > 0) {
						$s = '';
						if ($rows[1] == $rowsdt[4]) {
							$s = "selected";
							$paperGrade = $rows[1];
						}
						echo "<option value='$rows[1]' $s>$rows[1]</option>";
					}
				}
				echo "</select></span></td>";

				// GSM (only values that exist for the chosen reel deckle + paper grade)
				$query = "select distinct itemdt_value from itemsdt
				left join items on items.item_code=itemsdt.item_code
				where ItemParam_Code='GSM' and group_code='RM' order by itemdt_value asc ";
				$result = mysqli_query($connection, $query);
				echo "	<td class='grid-col-xs'><span class='grid-single-control'><select name='txtGSM[$i]' id='txtGSM[$i]' class='roundedtext_amortization_small' onchange='setPaperMill($i);TotGSM($i);TrimReq($i);UReq($i);LReq($i);' " . $KeyDown . ">";
				echo "<option></option>";

				$txtRellDeckle = $rowsdt[5];
				$txtPaperGrade = $rowsdt[4];

				while ($result && ($rows = mysqli_fetch_array($result))) {
					$txtGSM = $rows[0];
					$queryGSM = "SELECT count(*) From(
									SELECT itemsdt.ITEM_CODE
									FROM itemsdt
									LEFT JOIN items ON items.item_code = itemsdt.item_code
									WHERE itemdt_value IN ('$txtGSM', '$txtRellDeckle','$txtPaperGrade') and group_code='RM' GROUP BY itemsdt.ITEM_CODE HAVING COUNT(distinct ITEMDT_VALUE) = 3
									) as subquery";
					$resultGSM = mysqli_query($connection, $queryGSM);
					$rowGSM = $resultGSM ? mysqli_fetch_array($resultGSM) : null;
					$countItem = $rowGSM[0] ?? 0;
					if ($countItem > 0) {
						$s = '';
						if ($rows[0] == $rowsdt[3]) {
							$s = "selected";
							$gsmm = $rows[0];
						}
						echo "<option value='$rows[0]' $s>$rows[0]</option>";
					}
				}
				echo "</select></span></td>";

				// Size MM
				$txtSizeTrim[$i] = $rowsdt[25];
				$totSizeTrim += (float)$txtSizeTrim[$i];

				$txtGSM = $rowsdt[3];
				$txtPaperGrade = $rowsdt[4];
				$txtRellDeckle = $rowsdt[5];
				$txtPaperMill = $rowsdt[6];
				echo "	<td class='grid-col-xs'><span class='grid-single-control'><input type='number' id='txtSizeTrim[$i]' name='txtSizeTrim[$i]' value='$txtSizeTrim[$i]' class='roundedtext_amortization_small grid-cell-numeric' onchange='UReq($i);LReq($i);' readonly></span></td>";

				// Paper Mill (only mills that have stock for the chosen deckle/grade/GSM)
				echo "	<td class='grid-col-md'><span class='grid-single-control'><select name='txtPaperMill[$i]' id='txtPaperMill[$i]' onchange=\"setItems($i)\" class='roundedtext_amortization_small' " . $KeyDown . ">";
				echo "<option></option>";
				if ($txtGSM != '' && $txtPaperGrade != '' && $txtRellDeckle != '') {
					$query = "select distinct items.item_code as item_code from itemsdt
								left join items on items.item_code=itemsdt.item_code
								where
								itemdt_value in ('$txtGSM','$txtPaperGrade', '$txtRellDeckle' )
								and group_code='RM'
								GROUP BY items.ITEM_CODE HAVING COUNT(distinct ITEMDT_VALUE) = 3
								order by itemdt_value asc";
					$result = mysqli_query($connection, $query);

					while ($result && ($rows = mysqli_fetch_array($result))) {
						extract($rows);
						$query1 = "select null,itemdt_value from itemsdt where item_code='$item_code' and ItemParam_Code='paper_mill'";
						$result1 = mysqli_query($connection, $query1);

						while ($result1 && ($rows1 = mysqli_fetch_array($result1))) {

							$queryItem = "select distinct items.item_code  from itemsdt
											left join items on items.item_code=itemsdt.item_code
											where
											itemdt_value in ('$txtGSM','$txtPaperGrade', '$txtRellDeckle','" . $rows1[1] . "' )
											and group_code='RM'
											GROUP BY items.ITEM_CODE HAVING COUNT(DISTINCT ITEMDT_VALUE) = 4
											order by itemdt_value asc";
							$resultItem = mysqli_query($connection, $queryItem);
							$rowsItem = $resultItem ? mysqli_fetch_array($resultItem) : null;
							$Qry_QtyExists = " SELECT ifnull(QtyExists('" . ($rowsItem[0] ?? '') . "', '" . $rowsdt[40] . "', '" . $corrugator_date_ . "', $txtCorrugatorId, 'SO', '', ''),0) As QtyExists ";
							$resultQtyExists = mysqli_query($connection, $Qry_QtyExists);
							$rowsQtyExists = $resultQtyExists ? mysqli_fetch_array($resultQtyExists) : null;
							$StockQty = $rowsQtyExists[0] ?? 0;
							if ($StockQty > 0) {
								$select = '';
								if ($txtPaperMill != '' && $txtPaperMill == $rows1[1]) {
									$select = 'selected';
								}
								echo " <option value='$rows1[1]' $select>$rows1[1]</option>";
							}
						}
					}
				}
				// (PHP 5 version also had an "else" branch here that looped over an already
				// exhausted result set, so it never added options - left out.)
				echo "</select></span></td>";

				// Warehouse
				echo "	<td class='grid-col-md'><span class='grid-single-control'><select name='warehouse[$i]' id='warehousecode[$i]' class='roundedtext_amortization_small' onchange=\"$submitLocation\">";
				echo "<option></option>";
				foreach ($warehouseList as $whCode) {
					$select = ($whCode == $rowsdt[40]) ? 'selected' : '';
					echo "<option value='$whCode' $select>$whCode</option>";
				}
				echo "</select></span></td>";

				// Supplier
				echo "	<td class='grid-col-lg'><div class='grid-inline-control'>
							<input type='hidden' id='txtSupplierCode[$i]' name='txtSupplierCode[$i]' value=''>
							<span class='style4 grid-input-wrap'><input type='text' id='txtSupplierName[$i]' name='txtSupplierName[$i]' value='$rowsdt[7]' class='roundedtext_amortization_small' onchange=\"$submitLocation\"></span>
							<img src='img/openlist.png' class='grid-action-img' title='Select Supplier' id='lstsuppliercode[$i]' onMouseOver=\"style.cursor='pointer'\" onClick='showlistSupp1(2,$i);'>
						</div></td>";

				$txtURequired[$i] = $rowsdt[8];
				$UReqTot += (float)$txtURequired[$i];
				$txtUActual[$i] = $rowsdt[9];
				$UActTot += (float)$txtUActual[$i];
				$txtLRequired[$i] = $rowsdt[10];
				$LReqTot += (float)$txtLRequired[$i];
				$txtLActual[$i] = $rowsdt[11];
				$LActTot += (float)$txtLActual[$i];
				$txtTRequired[$i] = $rowsdt[12];
				$TReqTot += (float)$txtTRequired[$i];
				$txtTActual[$i] = $rowsdt[14];
				$TActTot += (float)$txtTActual[$i];
				$txtRequiredTrim[$i] = $rowsdt[26];
				$ReqTrimTot += (float)$txtRequiredTrim[$i];
				$txtActualTrim[$i] = $rowsdt[27];
				$ActTrimTot += (float)$txtActualTrim[$i];

				echo "	<td class='grid-col-xs'><span class='grid-single-control'><input type='number' name='txtURequired[$i]' id='txtURequired[$i]' value='" . $txtURequired[$i] . "' maxlength='20' class='roundedtext_amortization_small grid-cell-numeric' readonly " . $KeyDown . "></span></td>";
				echo "	<td class='grid-col-xs'><span class='grid-single-control'><input type='number' name='txtLRequired[$i]' id='txtLRequired[$i]' value='" . $txtLRequired[$i] . "' maxlength='20' class='roundedtext_amortization_small grid-cell-numeric' readonly " . $KeyDown . "></span></td>";

				// Submit + the per-row values that were hidden columns in the PHP 5 grid
				echo "	<td class='grid-action-cell'>
							<input type='hidden' id='txtUActual[$i]' name='txtUActual[$i]' value='$txtUActual[$i]'>
							<input type='hidden' id='txtLActual[$i]' name='txtLActual[$i]' value='$txtLActual[$i]'>
							<input type='hidden' id='txtTRequired[$i]' name='txtTRequired[$i]' value='" . $txtTRequired[$i] . "'>
							<input type='hidden' id='txtTActual[$i]' name='txtTActual[$i]' value='$txtTActual[$i]'>
							<input type='hidden' id='txtRequiredTrim[$i]' name='txtRequiredTrim[$i]' value='$txtRequiredTrim[$i]'>
							<input type='hidden' id='txtActualTrim[$i]' name='txtActualTrim[$i]' value='$txtActualTrim[$i]'>
							<img src='img/confirm-1.png' class='grid-action-img' id='imgSubmitlg[$i]' onMouseOver=\"style.cursor='pointer'\" onclick=\"$submitLocation return false;\" title='Submit Record'>
						</td>";
				echo "</tr>";

				$LastItemWarehouse = $rowsdt[4];
			}
		} else {

			$emptyLocId = $rowsdt[0] ?? '';
			$submitLocation = "SubmitLocationGrid('" . $i . "','" . $emptyLocId . "','" . $morelines . "','" . $startrange . "','" . $perpage . "','" . $txtCorrugatorId . "','" . $recno . "');";
			$txtItemDesc[$i] = $txtPaperMill;

			echo "<tr id='CurRec$i' class='$bgcolor table-focus-g1'>";
			echo "	<td class='grid-action-cell'><img src='img/undo-1.png' class='grid-action-img' onmouseover=\"style.cursor='pointer'\" onclick=\"ClearRecordGrid('" . $columnslistall . "','" . $i . "','ModRec_G1');return false;\" title='Clear Record'></td>";
			echo "	<td class='grid-col-md grid-cell-nowrap'><span class='style1'>$recno</span>
						<input type='hidden' name='locid[$i]' id='locid[$i]' value='" . $emptyLocId . "'>
						<input type='hidden' name='txttype[$i]' id='txttype[$i]' value='" . $recno . "'></td>";

			// Item
			echo "	<td class='grid-col-sm-plus'><span class='grid-single-control'>
						<input type='text' name='txtItemCode1[$i]' id='txtItemCode1[$i]' value='" . ($txtItemCode[$i] ?? '') . "' readonly maxlength='20' class='roundedtext_amortization_small' onchange=\"GetAttr(this.value,$i);\" onkeyup=\"AutoCompleteData(event,this.id,'itemcode[$i]','itemdescription[$i]','itemquality[$i]','items');\" " . $KeyDown . ">
						<input type='hidden' name='txtItemDesc1[$i]' id='txtItemDesc1[$i]' value='" . $txtItemDesc[$i] . "'>
					</span></td>";

			// Reel Deckle
			$query = "select distinct ItemParam_Code,itemdt_value from itemsdt
				left join items on items.item_code=itemsdt.item_code
				where ItemParam_Code='WIDTH' and group_code='RM' order by itemdt_value asc";
			$result = mysqli_query($connection, $query);
			echo "	<td class='grid-col-xs'><span class='grid-single-control'><select name='txtRellDeckle[$i]' id='txtRellDeckle[$i]' class='roundedtext_amortization_small' onchange='setPaperGrade($i);sizeTrim($i);' " . $KeyDown . ">
				<option></option>";
			while ($result && ($rows = mysqli_fetch_array($result))) {
				echo " <option value='$rows[1]'>$rows[1]</option>";
			}
			echo "</select></span></td>";

			// Paper Grade
			$query = "select distinct ItemParam_Code,itemdt_value from itemsdt
			left join items on items.item_code=itemsdt.item_code
			where ItemParam_Code='type' and group_code='RM' order by itemdt_value asc";
			$result = mysqli_query($connection, $query);
			echo "	<td class='grid-col-xs'><span class='grid-single-control'><select name='txtPaperGrade[$i]' id='txtPaperGrade[$i]' class='roundedtext_amortization_small' onchange='setGSM($i)' " . $KeyDown . ">
			<option></option>";
			while ($result && ($rows = mysqli_fetch_array($result))) {
				echo " <option value='$rows[1]'>$rows[1]</option>";
			}
			echo "</select></span></td>";

			// GSM
			$query = "select distinct ItemParam_Code,itemdt_value from itemsdt
			left join items on items.item_code=itemsdt.item_code
			where ItemParam_Code='GSM' and group_code='RM' order by itemdt_value asc";
			$result = mysqli_query($connection, $query);
			echo "	<td class='grid-col-xs'><span class='grid-single-control'><select name='txtGSM[$i]' id='txtGSM[$i]' class='roundedtext_amortization_small' onchange='setPaperMill($i);TotGSM($i);TrimReq($i);UReq($i);LReq($i);' " . $KeyDown . ">
			<option></option>";
			while ($result && ($rows = mysqli_fetch_array($result))) {
				echo " <option value='$rows[1]'>$rows[1]</option>";
			}
			echo "</select></span></td>";

			// Size MM
			echo "	<td class='grid-col-xs'><span class='grid-single-control'><input type='number' id='txtSizeTrim[$i]' name='txtSizeTrim[$i]' value='" . ($txtSizeTrim[$i] ?? '') . "' class='roundedtext_amortization_small grid-cell-numeric' onchange='UReq($i);LReq($i);' readonly></span></td>";

			// Paper Mill
			$query = "select distinct ItemParam_Code,itemdt_value from itemsdt
						left join items on items.item_code=itemsdt.item_code
						where ItemParam_Code='paper_mill' and group_code='RM' order by itemdt_value asc";
			$result = mysqli_query($connection, $query);
			echo "	<td class='grid-col-md'><span class='grid-single-control'><select name='txtPaperMill[$i]' id='txtPaperMill[$i]' onchange=\"setItems($i)\" class='roundedtext_amortization_small' " . $KeyDown . ">
						<option></option>";
			while ($result && ($rows = mysqli_fetch_array($result))) {
				echo " <option value='$rows[1]'>$rows[1]</option>";
			}
			echo "</select></span></td>";

			// Warehouse
			echo "	<td class='grid-col-md'><span class='grid-single-control'><select name='warehouse[$i]' id='warehousecode[$i]' class='roundedtext_amortization_small' onchange=\"$submitLocation\">
					<option></option>";
			foreach ($warehouseList as $whCode) {
				echo "<option value='$whCode'>$whCode</option>";
			}
			echo "</select></span></td>";

			// Supplier
			echo "	<td class='grid-col-lg'><div class='grid-inline-control'>
						<input type='hidden' id='txtSupplierCode[$i]' name='txtSupplierCode[$i]' value=''>
						<span class='style4 grid-input-wrap'><input type='text' id='txtSupplierName[$i]' name='txtSupplierName[$i]' value='$txtSupplier' class='roundedtext_amortization_small' onchange=\"$submitLocation\"></span>
						<img src='img/openlist.png' class='grid-action-img' title='Select Supplier' id='lstsuppliercode[$i]' onMouseOver=\"style.cursor='pointer'\" onClick='showlistSupp1(2,$i);'>
					</div></td>";

			echo "	<td class='grid-col-xs'><span class='grid-single-control'><input type='number' name='txtURequired[$i]' id='txtURequired[$i]' value='" . ($txtURequired[$i] ?? '') . "' readonly maxlength='20' class='roundedtext_amortization_small grid-cell-numeric' " . $KeyDown . "></span></td>";
			echo "	<td class='grid-col-xs'><span class='grid-single-control'><input type='number' name='txtLRequired[$i]' id='txtLRequired[$i]' value='" . ($txtLRequired[$i] ?? '') . "' readonly maxlength='20' class='roundedtext_amortization_small grid-cell-numeric' " . $KeyDown . "></span></td>";

			echo "	<td class='grid-action-cell'>
						<input type='hidden' id='txtUActual[$i]' name='txtUActual[$i]' value='" . ($txtUActual[$i] ?? '') . "'>
						<input type='hidden' id='txtLActual[$i]' name='txtLActual[$i]' value='" . ($txtLActual[$i] ?? '') . "'>
						<input type='hidden' id='txtTRequired[$i]' name='txtTRequired[$i]' value='" . ($txtTRequired[$i] ?? '') . "'>
						<input type='hidden' id='txtTActual[$i]' name='txtTActual[$i]' value='" . ($txtTActual[$i] ?? '') . "'>
						<input type='hidden' id='txtRequiredTrim[$i]' name='txtRequiredTrim[$i]' value='" . ($txtRequiredTrim[$i] ?? '') . "'>
						<input type='hidden' id='txtActualTrim[$i]' name='txtActualTrim[$i]' value='" . ($txtActualTrim[$i] ?? '') . "'>
						<img src='img/confirm-1.png' class='grid-action-img' id='imgSubmitlg[$i]' onMouseOver=\"style.cursor='pointer'\" onclick=\"$submitLocation return false;\" title='Submit Record'>
					</td>";
			echo "</tr>";
		}
		$i += 1;
	}

	echo "	</table>";
	echo "	</div>";
	echo "	</div>";
	echo "<input type='hidden' id='lastRowLocation' value='$i'/>";

	if ($txtCorrugatorId != '') {
		$query = "select upperpaperrequiredT ,upperpaperactualT ,lowerpaperrequiredT ,lowerpaperactualT ,totURequiredT ,totUactualT ,
			paperinner ,paperflutting ,paperouter ,balanceinner ,balanceflutting ,balanceouter ,wtpcupper ,wtpclower ,totalgsm ,trimsizeT ,
			trimrequiredT ,trimactualT ,trimwasteper
			from temp_corrugator1 where corrugator_id=$txtCorrugatorId";
		$result = mysqli_query($connection, $query);
		$rows = $result ? mysqli_fetch_array($result) : null;
		$txtURequiredT = $rows[0] ?? '';
		$UActTot = $rows[1] ?? '';
		$txtLRequiredT = $rows[2] ?? '';
		$LActTot = $rows[3] ?? '';
		$TReqTot = $rows[4] ?? '';
		$TActTot = $rows[5] ?? '';

		$IPaper = $rows[6] ?? '';
		$FPaper = $rows[7] ?? '';
		$OPaper = $rows[8] ?? '';
		$IBalance = $rows[9] ?? '';
		$FBalance = $rows[10] ?? '';
		$OBalance = $rows[11] ?? '';

		$txtWTPCU = $rows[12] ?? '';
		$txtWTPCL = $rows[13] ?? '';
		$txtTotalGSM = $rows[14] ?? '';
		$totSizeTrim = $rows[15] ?? '';
		$ReqTrimTot = $rows[16] ?? '';
		$ActTrimTot = $rows[17] ?? '';

		$trimWaste = $rows[18] ?? '';

		if ($trimWaste > 3) {
			$TextColor = 'red';
		} else {
			$TextColor = '';
		}
	} else {
		$TextColor = '';
	}


	/* ===================== PAPER REQUIREMENT SUMMARY ===================== */
	// One lookup per paper layer: required qty, stock qty, item code, booked qty and required trim.
	$cjrLayer = function ($itemType) use ($connection, $txtCorrugatorId) {
		$layer = array('required' => '', 'stock' => '', 'item' => '', 'booking' => '', 'trim' => '');
		$query = "select totURequired,stockqty,itemCode,trimrequired from temp_locationtable where corrugatorid='$txtCorrugatorId' and itemtype='$itemType'";
		$result = mysqli_query($connection, $query);
		$rows = $result ? mysqli_fetch_array($result) : null;
		$layer['required'] = $rows[0] ?? '';
		$layer['stock'] = $rows[1] ?? '';
		$layer['item'] = $rows[2] ?? '';
		$layer['trim'] = $rows[3] ?? '';

		$q = "Select Sum(bookingqty_qty - bookingqty_usedqty) from bookingqty where item_code ='" . $layer['item'] . "' and ifnull(cjr_id,0) <> 0 ";
		$res = mysqli_query($connection, $q);
		$ress = $res ? mysqli_fetch_array($res) : null;
		$layer['booking'] = $ress[0] ?? '';
		return $layer;
	};
	$cjrInput = function ($id, $value, $style = '') {
		return "<input class='inputBox-clean summary-input' type='number' id='$id' name='$id' value='$value' style='$style' readonly>";
	};
	$shortage = function ($layer) use ($dangerBg) {
		return ((float)$layer['stock'] < (float)$layer['required'] + (float)$layer['booking']) ? $dangerBg : '';
	};

	$totalReq = 0;

	// Hidden totals kept for the JS (were hidden table cells in the PHP 5 version)
	echo "<input type='hidden' id='txtUActualT' name='txtUActualT' value='$UActTot'>
		<input type='hidden' id='txtLActualT' name='txtLActualT' value='$LActTot'>
		<input type='hidden' id='txtTActualT' name='txtTActualT' value='$TActTot'>
		<input type='hidden' id='txtIPaper' name='txtIPaper' value='" . ($rowsdt[14] ?? '') . "'>
		<input type='hidden' id='txtFPaper' name='txtFPaper' value='" . ($rowsdt[15] ?? '') . "'>
		<input type='hidden' id='txtOPaper' name='txtOPaper' value='" . ($rowsdt[16] ?? '') . "'>
		<input type='hidden' id='txtIBalance' name='txtIBalance' value='" . ($rowsdt[17] ?? '') . "'>
		<input type='hidden' id='txtFBalance' name='txtFBalance' value='" . ($rowsdt[18] ?? '') . "'>
		<input type='hidden' id='txtOBalance' name='txtOBalance' value='" . ($rowsdt[19] ?? '') . "'>
		<input type='hidden' id='txtSizeTrimT' name='txtSizeTrimT' value='$totSizeTrim'>
		<input type='hidden' id='txtRequiredTrimT' name='txtRequiredTrimT' value='$ReqTrimTot'>
		<input type='hidden' id='txtActualTrimT' name='txtActualTrimT' value='$ActTrimTot'>
		<input type='hidden' id='txtTotalRequired' name='txtTotalRequired' value='" . ($rowsdt[26] ?? '') . "'>
		<input type='hidden' id='txtTotalActual' name='txtTotalActual' value='" . ($rowsdt[27] ?? '') . "'>";

	echo "<div class='summary-totals pr-2 pl-2 mt-2'>";

	// --- Line 1: upper totals + inner liner (3-ply) / liner-1 (5-ply)
	echo "<div class='row no-gutters'>";
	echo cjrSummaryCell("U Required" . $orderimg3, "<input class='inputBox-clean summary-input' type='number' name='txtURequiredT' id='txtURequiredT' value='" . $txtURequiredT . "' readonly>");
	if ($rowsNb_ == 3 || $rowsNb_ == 5) {
		$first = ($rowsNb_ == 3) ? 'INNER LINER' : 'LINER-1';
		$layer = $cjrLayer($first);
		$reqStyle = ((float)$layer['required'] + (float)$layer['booking'] > (float)$layer['stock']) ? $dangerBg : '';
		$totalReq += (float)$layer['required'];
		if ($rowsNb_ == 3) {
			echo cjrSummaryCell("I Req." . $orderimg3, $cjrInput('txtTIRequired', $layer['required']), $reqStyle);
			echo cjrSummaryCell("Inner Stk." . $orderimg3, $cjrInput('txtInnerStock', $layer['stock']));
			echo cjrSummaryCell("I Booking." . $orderimg3, $cjrInput('txtIBooking', $layer['booking']));
		} else {
			echo cjrSummaryCell("LINER-1 Req." . $orderimg3, $cjrInput('txtTIRequired', $layer['required']), $reqStyle);
			echo cjrSummaryCell("LINER-1 Stk." . $orderimg3, $cjrInput('txtLINNER1Stock', $layer['stock']));
			echo cjrSummaryCell("LINER-1 Booking." . $orderimg3, $cjrInput('txtI1Booking', $layer['booking']));
		}
		echo cjrSummaryCell("WTPC(U)" . $orderimg3, $cjrInput('txtWTPCU', $txtWTPCU));
		echo cjrSummaryCell(($rowsNb_ == 3 ? "I" : "LINER-1") . " ReqTrim.(KG)" . $orderimg3, $cjrInput('txtIReqTrim', $layer['trim']));
	} else {
		echo cjrSummaryCell("I Req." . $orderimg3, $cjrInput('txtTIRequired', ''));
		echo cjrSummaryCell("Inner Stk." . $orderimg3, $cjrInput('txtInnerStock', ''));
		echo cjrSummaryCell("WTPC(U)" . $orderimg3, $cjrInput('txtWTPCU', $txtWTPCU));
		echo cjrSummaryCell("I ReqTrim.(KG)" . $orderimg3, $cjrInput('txtIReqTrim', ''));
	}
	echo "</div>";

	// --- Line 2: lower totals + flute (3-ply) / flute-1 (5-ply)
	echo "<div class='row no-gutters'>";
	echo cjrSummaryCell("L Required" . $orderimg3, "<input class='inputBox-clean summary-input' type='number' name='txtLRequiredT' id='txtLRequiredT' value='$txtLRequiredT' readonly>");
	if ($rowsNb_ == 3 || $rowsNb_ == 5) {
		$layer = $cjrLayer(($rowsNb_ == 3) ? 'FLUTE' : 'FLUTE-1');
		$totalReq += (float)$layer['required'];
		if ($rowsNb_ == 3) {
			echo cjrSummaryCell("F Req." . $orderimg3, $cjrInput('txtTFRequired', $layer['required']), $shortage($layer));
			echo cjrSummaryCell("Flutting Stk." . $orderimg3, $cjrInput('txtFluttingStock', $layer['stock']));
			echo cjrSummaryCell("F Booking." . $orderimg3, $cjrInput('txtFBooking', $layer['booking']));
		} else {
			echo cjrSummaryCell("FLUTE-1 Req." . $orderimg3, $cjrInput('txtTFRequired', $layer['required']), $shortage($layer));
			echo cjrSummaryCell("Flute-1 Stk." . $orderimg3, $cjrInput('txtFlutte1Stock', $layer['stock']));
			echo cjrSummaryCell("FLUTE-1 Booking." . $orderimg3, $cjrInput('txtFBooking', $layer['booking']));
		}
		echo cjrSummaryCell("WTPC(L)" . $orderimg3, $cjrInput('txtWTPCL', $txtWTPCL));
		echo cjrSummaryCell(($rowsNb_ == 3 ? "F" : "FLUTE-1") . " ReqTrim.(KG)" . $orderimg3, $cjrInput('txtFReqTrim', $layer['trim']));
	} else {
		echo cjrSummaryCell("F Req." . $orderimg3, $cjrInput('txtTFRequired', ''));
		echo cjrSummaryCell("Flutting Stk." . $orderimg3, $cjrInput('txtFluttingStock', ''));
		echo cjrSummaryCell("WTPC(L)" . $orderimg3, $cjrInput('txtWTPCL', $txtWTPCL));
		echo cjrSummaryCell("F ReqTrim.(KG)" . $orderimg3, $cjrInput('txtFReqTrim', ''));
	}
	echo "</div>";

	// --- Line 3: total required + outer liner (3-ply) / liner-2 (5-ply)
	$TReqTot = (float)$txtURequiredT + (float)$LReqTot;
	echo "<div class='row no-gutters'>";
	echo cjrSummaryCell("T Required" . $orderimg3, "<input class='inputBox-clean summary-input' type='number' name='txtTRequiredT' id='txtTRequiredT' value='" . $TReqTot . "' readonly>");
	if ($rowsNb_ == 3 || $rowsNb_ == 5) {
		$layer = $cjrLayer(($rowsNb_ == 3) ? 'OUTER LINER' : 'LINER-2');
		$totalReq += (float)$layer['required'];
		if ($rowsNb_ == 3) {
			echo cjrSummaryCell("O Req." . $orderimg3, $cjrInput('txtTORequired', $layer['required']), $shortage($layer));
			echo cjrSummaryCell("Outer Stk." . $orderimg3, $cjrInput('txtOuterStock', $layer['stock']));
			echo cjrSummaryCell("O Booking." . $orderimg3, $cjrInput('txtOBooking', $layer['booking']));
			echo cjrSummaryCell("Tot GSM" . $orderimg3, $cjrInput('txtTotalGSM', $txtTotalGSM));
			echo cjrSummaryCell("O ReqTrim.(KG)" . $orderimg3, $cjrInput('txtOReqTrim', $layer['trim']));
		} else {
			echo cjrSummaryCell("LINER-2 Req." . $orderimg3, $cjrInput('txtTI2Required', $layer['required']), $shortage($layer));
			echo cjrSummaryCell("Liner-2 Stk." . $orderimg3, $cjrInput('txtINNER2Stk', $layer['stock']));
			echo cjrSummaryCell("Linner-2 Booking." . $orderimg3, $cjrInput('txtI2Booking', $layer['booking']));
			echo cjrSummaryCell("Tot GSM" . $orderimg3, $cjrInput('txtTotalGSM', $txtTotalGSM));
			echo cjrSummaryCell("LINER-2 ReqTrim.(KG)" . $orderimg3, $cjrInput('txtI2ReqTrim', $layer['trim']));
		}
	} else {
		echo cjrSummaryCell("O Req." . $orderimg3, $cjrInput('txtTORequired', ''));
		echo cjrSummaryCell("Outer Stk." . $orderimg3, $cjrInput('txtOuterStock', ''));
		echo cjrSummaryCell("Tot GSM" . $orderimg3, $cjrInput('txtTotalGSM', $txtTotalGSM));
		echo cjrSummaryCell("O ReqTrim.(KG)" . $orderimg3, $cjrInput('txtOReqTrim', ''));
	}
	echo "</div>";

	// --- Line 4: trim waste + (5-ply only) flute-2 and outer liner, then total required
	$trimWasteInput = "<input class='inputBox-clean summary-input' type='number' id='txtTrimWaste' name='txtTrimWaste' value='" . $trimWaste . "' readonly style='color:$TextColor'>";
	$spacer = "<div class='col-md-2 px-2'></div>";

	echo "<div class='row no-gutters'>";
	echo cjrSummaryCell("TrimWaste%", $trimWasteInput);
	if ($rowsNb_ == 5) {
		$layer = $cjrLayer('FLUTE-2');
		echo cjrSummaryCell("FLUTE-2 Req." . $orderimg3, $cjrInput('txtTF2Required', $layer['required']), $shortage($layer));
		echo cjrSummaryCell("Flute-2 Stk." . $orderimg3, $cjrInput('txtFlute2Stock', $layer['stock']));
		echo cjrSummaryCell("FLUTE-2 Booking." . $orderimg3, $cjrInput('txtF2Booking', $layer['booking']));
		echo $spacer;
		echo cjrSummaryCell("FLUTE-2 ReqTrim.(KG)" . $orderimg3, $cjrInput('txtF2ReqTrim', $layer['trim']));
		echo "</div>";

		// Total.Req keeps the PHP 5 behaviour: LINER-1 + FLUTE-1 + LINER-2 only.
		$layer = $cjrLayer('OUTER LINER');
		echo "<div class='row no-gutters'>";
		echo $spacer;
		echo cjrSummaryCell("O LINNER Req." . $orderimg3, $cjrInput('txtTORequired', $layer['required']), $shortage($layer));
		echo cjrSummaryCell("O LINNER Stk." . $orderimg3, $cjrInput('txtOuterStock', $layer['stock']));
		echo cjrSummaryCell("O Booking." . $orderimg3, $cjrInput('txtOBooking', $layer['booking']));
		echo $spacer;
		echo cjrSummaryCell("O Liner ReqTrim.(KG)" . $orderimg3, $cjrInput('txtOReqTrim', $layer['trim']));
		echo "</div>";

		echo "<div class='row no-gutters'>";
		echo $spacer;
		echo cjrSummaryCell("Total.Req", $cjrInput('txtTotalReq', round($totalReq)));
		echo "</div>";
	} else {
		echo cjrSummaryCell("Total.Req", $cjrInput('txtTotalReq', round($totalReq)));
		echo "</div>";
	}

	echo "</div>"; // .summary-totals

	echo "</div>"; // #container (was left open in the PHP 5 version)
}
