<?php
/* $Revision: 1.0 $ */

include('includes/session.inc');
$title = _('Assignment of Cash to Petty Cash Tab');
include('includes/header.inc');

if (isset($_POST['SelectedTabs'])){
	$SelectedTabs = strtoupper($_POST['SelectedTabs']);
} elseif (isset($_GET['SelectedTabs'])){
	$SelectedTabs = strtoupper($_GET['SelectedTabs']);
}

if (isset($_POST['SelectedIndex'])){
	$SelectedIndex = $_POST['SelectedIndex'];
} elseif (isset($_GET['SelectedIndex'])){
	$SelectedIndex = $_GET['SelectedIndex'];
}

if (isset($_POST['Days'])){
	$Days = $_POST['Days'];
} elseif (isset($_GET['Days'])){
	$Days = $_GET['Days'];
}

if (isset($_POST['Cancel'])) {
	unset($SelectedTabs);
	unset($SelectedIndex);
	unset($Days);
	unset($_POST['Amount']);
	unset($_POST['Notes']);
	unset($_POST['Receipt']);
}

if (isset($Errors)) {
	unset($Errors);
}

$Errors = array();

if (isset($_POST['process'])) {
	//initialise no input errors assumed initially before we test
	$InputError = 0;
	$i=1;

	if ($SelectedTabs=='') {
		$InputError = 1;
		prnMsg('<br />' . _('You Must First Select a Petty Cash Tab To Assign Cash'),'error');
		$Errors[$i] = 'TabSelect';
		$i++;
		unset($SelectedTabs);
	}
}

if (isset($_POST['Go'])) {
	$InputError = 0;
	$i=1;
	if ($Days<=0) {
		$InputError = 1;
		prnMsg('<br />' . _('The number of days must be a positive number'),'error');
		$Errors[$i] = 'Days';
		$i++;
		$Days=30;
	}
}

if (isset($_POST['submit'])) {
	//initialise no input errors assumed initially before we test
	$InputError = 0;

	echo '<p class="page_title_text"><img src="'.$rootpath.'/css/'.$theme.'/images/money_add.png" title="' . _('Search') . '" alt="" />' . ' ' . $title. '</p>';

	/* actions to take once the user has clicked the submit button
	ie the page has called itself with some user input */

	$i=1;

	if ($_POST['Amount']==0) {
		$InputError = 1;
		prnMsg('<br />' . _('An Amount must be input'),'error');
		$Errors[$i] = 'TabCode';
		$i++;
	}

	$sqlLimit = "SELECT tablimit
					FROM pctabs
					WHERE tabcode='" . $SelectedTabs . "'";

	$ResultLimit = DB_query($sqlLimit,$db);
	$Limit=DB_fetch_array($ResultLimit);

	if (($_POST['CurrentAmount']+$_POST['Amount'])>$Limit['tablimit']){
		prnMsg('<br />' . _('The balance after this assignment would be greater than the specified limit for this PC tab'),'warning');
	}

	if ($InputError !=1 AND isset($SelectedIndex) ) {

		$sql = "UPDATE pcashdetails
					SET date = '".FormatDateForSQL($_POST['Date'])."',
					amount = '" . filter_currency_input($_POST['Amount']) . "',
					authorized = '0000-00-00',
					notes = '" . $_POST['Notes'] . "',
					receipt = '" . $_POST['Receipt'] . "'
				WHERE counterindex = '" . $SelectedIndex . "'";
		$msg = _('Assignment of cash to PC Tab ') . ' ' . $SelectedTabs . ' ' .  _('has been updated');

	} elseif ($InputError !=1 ) {
		// Add new record on submit
		$sql = "INSERT INTO pcashdetails
						(counterindex,
						tabcode,
						date,
						codeexpense,
						amount,
						authorized,
						posted,
						notes,
						receipt)
					VALUES ('',
						'" . $_POST['SelectedTabs'] . "',
						'".FormatDateForSQL($_POST['Date'])."',
						'ASSIGNCASH',
						'" .filter_currency_input($_POST['Amount']) . "',
						authorized = '0000-00-00',
						'0',
						'" . $_POST['Notes'] . "',
						'" . $_POST['Receipt'] . "'
					)";
		$msg = _('Assignment of cash to PC Tab ') . ' ' . $_POST['SelectedTabs'] .  ' ' . _('has been created');
		unset($_POST['SelectedTabs']);
		unset($_POST['Date']);
		unset($_POST['Amount']);
		unset($_POST['Notes']);
		unset($_POST['Receipt']);
	}

	if ( $InputError !=1) {
		//run the SQL from either of the above possibilites
		$result = DB_query($sql,$db);
		prnMsg($msg,'success');
		unset($_POST['SelectedExpense']);
		unset($_POST['Amount']);
		unset($_POST['Notes']);
		unset($_POST['Receipt']);
	}

} elseif ( isset($_GET['delete']) ) {

	echo '<p class="page_title_text"><img src="'.$rootpath.'/css/'.$theme.'/images/money_add.png" title="' . _('Search') . '" alt="" />' . ' ' . $title. '</p>';
	$sql="DELETE FROM pcashdetails
		WHERE counterindex='" . $SelectedIndex . "'";
	$ErrMsg = _('The assignment of cash record could not be deleted because');
	$result = DB_query($sql,$db,$ErrMsg);
	prnMsg(_('Assignment of cash to PC Tab ') .  ' ' . $SelectedTabs  . ' ' . _('has been deleted') ,'success');
	unset($_GET['delete']);
}

if (!isset($SelectedTabs)){

	/* It could still be the second time the page has been run and a record has been selected for modification - SelectedTabs will exist because it was sent with the new call. If its the first time the page has been displayed with no parameters
	then none of the above are true and the list of sales types will be displayed with
	links to delete or edit each. These will call the same page again and allow update/input
	or deletion of the records*/
	echo '<p class="page_title_text"><img src="'.$rootpath.'/css/'.$theme.'/images/money_add.png" title="' . _('Search') . '" alt="" />' . ' ' . $title. '</p>';

	echo '<form method="post" action="' . $_SERVER['PHP_SELF'] . '">';
	echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
	echo '<br /><table class="selection">'; //Main table

	echo '<tr><td>' . _('Petty Cash Tab To Assign Cash') . ':</td><td><select name="SelectedTabs">';

	DB_free_result($result);
	$SQL = "SELECT tabcode
		FROM pctabs
		WHERE assigner ='" . $_SESSION['UserID'] . "'
		ORDER BY tabcode";

	$result = DB_query($SQL,$db);

	echo '<option value=""></option>';
	while ($myrow = DB_fetch_array($result)) {
		if (isset($_POST['SelectTabs']) and $myrow['tabcode']==$_POST['SelectTabs']) {
			echo '<option selected="True" value="' . $myrow['tabcode'] . '">' . $myrow['tabcode'] . '</option>';
		} else {
			echo '<option value="' . $myrow['tabcode'] . '">' . $myrow['tabcode'] . '</option>';
		}
	}

	echo '</select></td></tr>';
   	echo '</td></tr></table>'; // close main table
	echo '<br /><div class="centre"><input type="submit" name="process" value="' . _('Accept') . '" />
			<input type="submit" name="Cancel" value="' . _('Cancel') . '" /></div>';
	echo '</form>';

} elseif (isset($SelectedTabs)) {

	$SQL = "SELECT currency
		FROM pctabs
		WHERE tabcode ='" . $SelectedTabs . "'";

	$result = DB_query($SQL,$db);
	$myrow=DB_fetch_array($result);
	$Currency=$myrow['currency'];

	if (!isset($_POST['submit'])) {
		echo '<p class="page_title_text"><img src="'.$rootpath.'/css/'.$theme.'/images/money_add.png" title="' . _('Search') . '" alt="" />' . ' ' . $title. '</p>';
	}
	echo '<div class="centre"><a href="' . $_SERVER['PHP_SELF'] . '">' . _('Select Another Petty Cash Tab ') . '</a></div>';

	if (! isset($_GET['edit']) OR isset ($_POST['Go'])){

		if (isset($_POST['Cancel'])) {
			unset($_POST['Amount']);
			unset($_POST['Date']);
			unset($_POST['Notes']);
			unset($_POST['Receipt']);
		}

		if(!isset ($Days)){
			$Days=30;
		 }
		$sql = "SELECT codeexpense,
						authorized,
						date,
						amount,
						notes,
						receipt,
						counterindex
				FROM pcashdetails
				WHERE tabcode='" . $SelectedTabs . "'
				AND date >=DATE_SUB(CURDATE(), INTERVAL '".$Days."' DAY)
				ORDER BY date, counterindex ASC";

		$result = DB_query($sql,$db);

		echo '<br /><table class="selection">';
		echo '<tr><th colspan="8"><font color="#616161" size="2">' . _('Details Of Petty Cash Tab ') . '' .$SelectedTabs. '</form></th></tr>';
		echo '<form method="post" action="' . $_SERVER['PHP_SELF'] . '">';
		echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
		echo '<tr><th colspan="8">' . _('Detail Of PC Tab Movements For Last ') .': ';
		echo '<input type="hidden" name="SelectedTabs" value="' . $SelectedTabs . '" />';
		echo '<input type="text" class="number" name="Days" value="' . $Days  . '" maxlength="3" size="4" /> Days ';
		echo '<input type="submit" name="Go" value="' . _('Go') . '" />';
		echo '</th></tr></form>';
		echo '<tr>
				<th>' . _('Date') . '</th>
				<th>' . _('Expense Code') . '</th>
				<th>' . _('Amount') . '</th>
				<th>' . _('Authorised') . '</th>
				<th>' . _('Notes') . '</th>
				<th>' . _('Receipt') . '</th>
			</tr>';

		$k=0; //row colour counter

		while ($myrow = DB_fetch_array($result)) {
			if ($k==1){
				echo '<tr class="EvenTableRows">';
				$k=0;
			} else {
				echo '<tr class="OddTableRows">';
				$k=1;
			}

			$sqldes="SELECT description
					FROM pcexpenses
					WHERE codeexpense='". $myrow['codeexpense'] . "'";

			$ResultDes = DB_query($sqldes,$db);
			$Description=DB_fetch_array($ResultDes);

			if (!isset($Description['0'])){
				$Description['0']='ASSIGNCASH';
			}

			if (($myrow['authorized'] == '0000-00-00') and ($Description['0'] == 'ASSIGNCASH')){
				// only cash assignations NOT authorized can be modified or deleted
				if ($myrow['authorized']=='0000-00-00') {
					$AuthorisedDate= _('Unauthorised');
				} else {
					$AuthorisedDate=ConvertSQLDate($myrow['authorized']);
				}
				echo '<td>'.ConvertSQLDate($myrow['date']).'</td>
					<td>'.$Description['0'].'</td>
					<td class="number">'.locale_money_format($myrow['amount'],$Currency).'</td>
					<td>'.$AuthorisedDate.'</td>
					<td>'.$myrow['notes'].'</td>
					<td>'.$myrow['receipt'].'</td>
					<td><a href="'.$_SERVER['PHP_SELF'] . '?SelectedIndex='.$myrow['counterindex'].'&SelectedTabs=' .
						$SelectedTabs . '&Days=' . $Days . '&edit=yes">' . _('Edit') . '</td>
					<td><a href="'.$_SERVER['PHP_SELF'] . '?SelectedIndex='.$myrow['counterindex'].'&SelectedTabs=' .
						$SelectedTabs . '&Days=' . $Days . '&delete=yes" onclick=\'return confirm("' .
							_('Are you sure you wish to delete this code and the expense it may have set up?') . '");\'>' .
								_('Delete') . '</td>
					</tr>';
			}else{
				echo '<td>'.ConvertSQLDate($myrow['date']).'</td>
					<td>'.$Description['0'].'</td>
					<td class="number">'.locale_money_format($myrow['amount'],$Currency).'</td>
					<td>'.ConvertSQLDate($myrow['authorized']).'</td>
					<td>'.$myrow['notes'].'</td>
					<td>'.$myrow['receipt'].'</td>
					</tr>';
			}
		}
		//END WHILE LIST LOOP

		$sqlamount="SELECT sum(amount)
					FROM pcashdetails
					WHERE tabcode='".$SelectedTabs."'";

		$ResultAmount = DB_query($sqlamount,$db);
		$Amount=DB_fetch_array($ResultAmount);

		if (!isset($Amount['0'])) {
			$Amount['0']=0;
		}

		echo '<tr><td colspan="2" class="number"><b>' . _('Current balance') . ':</b></td>
			<td class="number">'.locale_money_format($Amount['0'],$Currency).'</td></tr>';

		echo '</table>';

	}

	if (! isset($_GET['delete'])) {

		if (!isset($Amount['0'])) {
			$Amount['0']=0;
		}

		echo '<form method="post" action="' . $_SERVER['PHP_SELF'] . '">';
		echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
		echo '<br /><table class="selection">'; //Main table
		if (isset($_GET['SelectedIndex'])) {
			echo '<tr><th colspan="2"><font color="#616161" size="3">'._('Update Cash Assignment').'</font></th></tr>';
		} else {
			echo '<tr><th colspan="2"><font color="#616161" size="3">'._('New Cash Assignment').'</font></th></tr>';
		}
		if ( isset($_GET['edit'])) {

			$sql = "SELECT date,
							codeexpense,
							amount,
							notes,
							receipt
						FROM pcashdetails
						WHERE counterindex='".$SelectedIndex."'";

			$result = DB_query($sql, $db);
			$myrow = DB_fetch_array($result);

			$_POST['Date'] = ConvertSQLDate($myrow['date']);
			$_POST['SelectedExpense'] = $myrow['codeexpense'];
			$_POST['Amount']  = $myrow['amount'];
			$_POST['Notes']  = $myrow['notes'];
			$_POST['Receipt']  = $myrow['receipt'];

			echo '<input type="hidden" name="SelectedTabs" value="' . $SelectedTabs . '" />';
			echo '<input type="hidden" name="SelectedIndex" value="' . $SelectedIndex. '" />';
			echo '<input type="hidden" name="CurrentAmount" value="' . $Amount[0]. '" />';
			echo '<input type="hidden" name="Days" value="' .$Days. '" />';
		}

/* Ricard: needs revision of this date initialization */
		if (!isset($_POST['Date'])) {
			$_POST['Date']=Date($_SESSION['DefaultDateFormat']);
		}

		echo '<tr><td>' . _('Cash Assignation Date') . ':</td>';

		echo '<td><input type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="Date" size="10" maxlength="10" value="' . $_POST['Date'] . '" /></td></tr>';


		if (!isset($_POST['Amount'])) {
			$_POST['Amount']=0;
		}

		echo '<tr><td>' . _('Amount') . ':</td><td><input type="text" class="number" name="Amount" size="12" maxlength="11" value="' . $_POST['Amount'] . '" /></td></tr>';

		if (!isset($_POST['Notes'])) {
			$_POST['Notes']='';
		}

		echo '<tr><td>' . _('Notes') . ':</td><td><input type="text" name="Notes" size="50" maxlength="49" value="' . $_POST['Notes'] . '" /></td></tr>';

		if (!isset($_POST['Receipt'])) {
			$_POST['Receipt']='';
		}

		echo '<tr><td>' . _('Receipt') . ':</td><td><input type="text" name="Receipt" size="50" maxlength="49" value="' . $_POST['Receipt'] . '" /></td></tr>';

		echo '<input type="hidden" name="CurrentAmount" value="' . $Amount['0']. '" />';
		echo '<input type="hidden" name="SelectedTabs" value="' . $SelectedTabs . '" />';
		echo '<input type="hidden" name="Days" value="' .$Days. '" />';

		echo '</td></tr></table>'; // close main table

		echo '<br /><div class="centre">
					<input type="submit" name="submit" value="' . _('Accept') . '" />
					<input type="submit" name="Cancel" value="' . _('Cancel') . '" /></div>';

		echo '</form>';

	} // end if user wish to delete

}

include('includes/footer.inc');
?>