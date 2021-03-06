<?php

/* $Id$*/

/* $Revision: 1.12 $ */

$title = _('Stock Location Transfer Docket Reprint');
include('includes/session.inc');

include('includes/PDFStarter.php');

if (isset($_POST['TransferNo'])) {
	$_GET['TransferNo']=$_POST['TransferNo'];
}

if (!isset($_GET['TransferNo'])){

	include ('includes/header.inc');
	echo '<p class="page_title_text"><img src="'.$rootpath.'/css/'.$theme.'/images/maintenance.png" title="' . _('Search') . '" alt="" />' . ' ' . _('Reprint transfer docket').'</p><br />';
	echo '<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?">';
	echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
	echo '<table class="selection"><tr><td>'._('Transfer docket to reprint').'</td>';
	echo '<td><input type="text" class="number" size="10" name="TransferNo" /></td></tr></table><br />';
	echo '<div class="centre"><button type="submit" name="Print">' . _('Create PDF') .'</button></div><br />';
	include ('includes/footer.inc');
	exit;
}

$pdf->addInfo('Title', _('Inventory Location Transfer BOL') );
$pdf->addInfo('Subject', _('Inventory Location Transfer BOL') . ' # ' . $_GET['TransferNo']);
$FontSize=10;
$PageNumber=1;
$line_height=30;

$ErrMsg = _('An error occurred retrieving the items on the transfer'). '.' . '<br />'. _('This page must be called with a location transfer reference number').'.';
$DbgMsg = _('The SQL that failed while retrieving the items on the transfer was');
$sql = "SELECT loctransfers.reference,
			   loctransfers.stockid,
			   stockmaster.description,
			   loctransfers.shipqty,
			   loctransfers.shipdate,
			   loctransfers.shiploc,
			   locations.locationname as shiplocname,
			   loctransfers.recloc,
			   locationsrec.locationname as reclocname
			   FROM loctransfers
			   INNER JOIN stockmaster ON loctransfers.stockid=stockmaster.stockid
			   INNER JOIN locations ON loctransfers.shiploc=locations.loccode
			   INNER JOIN locations AS locationsrec ON loctransfers.recloc = locationsrec.loccode
			   WHERE loctransfers.reference='" . $_GET['TransferNo'] . "'";

$result = DB_query($sql,$db, $ErrMsg, $DbgMsg);

If (DB_num_rows($result)==0){

	include ('includes/header.inc');
	prnMsg(_('The transfer reference selected does not appear to be set up') . ' - ' . _('enter the items to be transferred first'),'error');
	include ('includes/footer.inc');
	exit;
}

$TransferRow = DB_fetch_array($result);

include ('includes/PDFStockLocTransferHeader.inc');
$line_height=30;
$FontSize=10;

do {

	$LeftOvers = $pdf->addTextWrap($Left_Margin,$YPos,100,$FontSize,$TransferRow['stockid'], 'left');
	$LeftOvers = $pdf->addTextWrap(150,$YPos,200,$FontSize,$TransferRow['description'], 'left');
	$LeftOvers = $pdf->addTextWrap(350,$YPos,60,$FontSize,$TransferRow['shipqty'], 'right');

	$pdf->line($Left_Margin, $YPos-2,$Page_Width-$Right_Margin, $YPos-2);

	$YPos -= $line_height;

	if ($YPos < $Bottom_Margin + $line_height) {
		$PageNumber++;
		include('includes/PDFStockLocTransferHeader.inc');
	}

} while ($TransferRow = DB_fetch_array($result));

$pdf->OutputD($_SESSION['DatabaseName'] . '_StockLocTrfShipment_' . date('Y-m-d') . '.pdf');//UldisN
$pdf->__destruct(); //UldisN
?>