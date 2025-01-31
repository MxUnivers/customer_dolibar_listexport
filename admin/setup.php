<?php
/* <one line to give the program's name and a brief idea of what it does.>
 * Copyright (C) 2017 AXeL <contact.axel.dev@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * 	\file		admin/listexportimport.php
 * 	\ingroup	listexportimport
 * 	\brief		This file is an example module setup page
 * 				Put some comments here
 */

// Load Dolibarr environment
if (false === (@include '../../main.inc.php')) { // From htdocs directory
    require '../../../main.inc.php'; // From "custom" directory
}

global $db, $conf, $langs, $user;

// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
require_once '../lib/listexportimport.lib.php';
require_once '../class/listexportimport.class.php';

// Translations
$langs->load("admin");
$langs->load("listexportimport@listexportimport");

// Access control
if (! $user->admin) {
    accessforbidden();
}

// Parameters
$action = GETPOST('action', 'alpha');
$id = GETPOST('id');//, 'int');

$form = new Form($db);
$liststatic = new ListExportImport($db);

/*
 * Actions
 */
if (preg_match('/set_(.*)/',$action,$reg))
{
	$code = $reg[1];
    $value = GETPOST($code);
    if (is_array($value) && !empty($value)) {
        $value = join(',', $value);
    }
	if (dolibarr_set_const($db, $code, $value, 'chaine', 0, '', $conf->entity) > 0)
	{
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else
	{
		dol_print_error($db);
	}
}

else if (preg_match('/del_(.*)/',$action,$reg))
{
	$code=$reg[1];
	if (dolibarr_del_const($db, $code, 0) > 0)
	{
		Header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else
	{
		dol_print_error($db);
	}
}

else if ($action == 'enable')
{
        $liststatic->enable($id);
}

else if ($action == 'disable')
{
        $liststatic->disable($id);
}

else if ($action == 'up' && $id > 0)
{
	$liststatic->up($id);
}

else if ($action == 'down' && $id > 0)
{
	$liststatic->down($id);
}

/*
 * View
 */
$page_name = "Setup";
llxHeader('', $langs->trans($page_name));

// Subheader
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">' . $langs->trans("BackToModuleList") . '</a>';
print_fiche_titre($langs->trans($page_name), $linkback, 'title_setup');

// Configuration header
$head = listexportimportAdminPrepareHead();
dol_fiche_head(
    $head,
    'settings',
    $langs->trans("Module513000Name"),
    0,
    "listexportimport@listexportimport"
);

// Setup page goes here
$form = new Form($db);

print load_fiche_titre($langs->trans("ListExportImportSettings"), '', 'title_setup');

$var=true;
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameters").'</td>'."\n";
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="100">'.$langs->trans("Value").'</td>'."\n";
print '</tr>'."\n";

// Use compact mode
$var=!$var;
print '<tr '.$bc[$var].'><td>';
print $langs->trans("UseCompactMode");
print '</td>';
print '<td align="center">&nbsp;</td>';
print '<td align="right">';
if (empty($conf->global->LIST_EXPORT_IMPORT_USE_COMPACT_MODE))
{
    print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_LIST_EXPORT_IMPORT_USE_COMPACT_MODE&amp;LIST_EXPORT_IMPORT_USE_COMPACT_MODE=1">'.img_picto($langs->trans("Disabled"),'switch_off').'</a>';
}
else
{
    print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_LIST_EXPORT_IMPORT_USE_COMPACT_MODE&amp;LIST_EXPORT_IMPORT_USE_COMPACT_MODE=0">'.img_picto($langs->trans("Enabled"),'switch_on').'</a>';
}
print '&nbsp;&nbsp;&nbsp;';
print '</td></tr>';

// Enable free list
$var=!$var;
print '<tr '.$bc[$var].'><td>';
print $langs->trans("EnableFreeList");
print '&nbsp; (<strong>'.$langs->trans("ForTestPurposeOnly").'</strong>) ';
print '</td>';
print '<td align="center">&nbsp;</td>';
print '<td align="right">';
if (empty($conf->global->LIST_EXPORT_IMPORT_ENABLE_FREE_LIST))
{
    print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_LIST_EXPORT_IMPORT_ENABLE_FREE_LIST&amp;LIST_EXPORT_IMPORT_ENABLE_FREE_LIST=1">'.img_picto($langs->trans("Disabled"),'switch_off').'</a>';
}
else
{
    print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_LIST_EXPORT_IMPORT_ENABLE_FREE_LIST&amp;LIST_EXPORT_IMPORT_ENABLE_FREE_LIST=0">'.img_picto($langs->trans("Enabled"),'switch_on').'</a>';
}
print '&nbsp;&nbsp;&nbsp;';
print '</td></tr>';

// CSV Export Separator
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("CSVExportDataSeparator").'</td>';
print '<td align="right" colspan="2" width="200">';
print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'" />';
print '<input type="hidden" name="action" value="set_EXPORT_CSV_SEPARATOR_TO_USE" />';
print '<input size="3" type="text" class="flat" name="EXPORT_CSV_SEPARATOR_TO_USE" value="'.(! empty($conf->global->EXPORT_CSV_SEPARATOR_TO_USE)?$conf->global->EXPORT_CSV_SEPARATOR_TO_USE:';').'">';
print '&nbsp;&nbsp;&nbsp;';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td>';
print '</tr>';

// Print date on pdf export
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("PrintDateOnPdfExport").'</td>';
print '<td align="center">&nbsp;</td>';
print '<td align="right">';
if (empty($conf->global->LIST_EXPORT_IMPORT_PRINT_DATE_ON_PDF_EXPORT))
{
    print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_LIST_EXPORT_IMPORT_PRINT_DATE_ON_PDF_EXPORT&amp;LIST_EXPORT_IMPORT_PRINT_DATE_ON_PDF_EXPORT=1">'.img_picto($langs->trans("Disabled"),'switch_off').'</a>';
}
else
{
    print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_LIST_EXPORT_IMPORT_PRINT_DATE_ON_PDF_EXPORT&amp;LIST_EXPORT_IMPORT_PRINT_DATE_ON_PDF_EXPORT=0">'.img_picto($langs->trans("Enabled"),'switch_on').'</a>';
}
print '&nbsp;&nbsp;&nbsp;';
print '</td></tr>';

// Do Not Remove Total
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("DoNotRemoveTotal").'</td>';
print '<td align="center">&nbsp;</td>';
print '<td align="right">';
if (empty($conf->global->LIST_EXPORT_IMPORT_DONT_REMOVE_TOTAL))
{
    print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_LIST_EXPORT_IMPORT_DONT_REMOVE_TOTAL&amp;LIST_EXPORT_IMPORT_DONT_REMOVE_TOTAL=1">'.img_picto($langs->trans("Disabled"),'switch_off').'</a>';
}
else
{
    print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_LIST_EXPORT_IMPORT_DONT_REMOVE_TOTAL&amp;LIST_EXPORT_IMPORT_DONT_REMOVE_TOTAL=0">'.img_picto($langs->trans("Enabled"),'switch_on').'</a>';
}
print '&nbsp;&nbsp;&nbsp;';
print '</td></tr>';

// Delete Space From Numbers
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("DeleteSpaceFromNumbers").'</td>';
print '<td align="center">&nbsp;</td>';
print '<td align="right">';
if (empty($conf->global->LIST_EXPORT_IMPORT_DELETESPACEFROMNUMBER))
{
    print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_LIST_EXPORT_IMPORT_DELETESPACEFROMNUMBER&amp;LIST_EXPORT_IMPORT_DELETESPACEFROMNUMBER=1">'.img_picto($langs->trans("Disabled"),'switch_off').'</a>';
}
else
{
    print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_LIST_EXPORT_IMPORT_DELETESPACEFROMNUMBER&amp;LIST_EXPORT_IMPORT_DELETESPACEFROMNUMBER=0">'.img_picto($langs->trans("Enabled"),'switch_on').'</a>';
}
print '&nbsp;&nbsp;&nbsp;';
print '</td></tr>';

// Ingored lists
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("IgnoredLists").'</td>';
print '<td align="right" colspan="2" width="500">';
print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'" />';
print '<input type="hidden" name="action" value="set_LIST_EXPORT_IMPORT_IGNORED_LISTS" />';
$values = array(
    'userlist' => 'userlist',
    'agendalist' => 'agendalist',
    'memberlist' => 'memberlist',
    'mailinglist' => 'mailinglist',
    'propallist' => 'propallist',
    'invoicelist' => 'invoicelist',
    'orderlist' => 'orderlist',
    'shipmentlist' => 'shipmentlist',
    'supplierorderlist' => 'supplierorderlist',
    'supplierinvoicelist' => 'supplierinvoicelist',
    'supplierpricelist' => 'supplierpricelist',
    'holidaylist' => 'holidaylist',
    'defineholidaylist' => 'defineholidaylist',
    'inventorylist' => 'inventorylist',
    'subscriptionlist' => 'subscriptionlist',
    'accountancycustomerlist' => 'accountancycustomerlist',
    'accountancysupplierlist' => 'accountancysupplierlist',
    'accountingaccountlist' => 'accountingaccountlist',
    'emailsenderprofilelist' => 'emailsenderprofilelist',
    'contractservicelist' => 'contractservicelist',
    'thirdpartylist' => 'thirdpartylist',
    'ticketlist' => 'ticketlist',
    'expensereportlist' => 'expensereportlist',
    'interventionlist' => 'interventionlist',
    'productservicelist' => 'productservicelist',
    'servicelist' => 'servicelist',
    'productlist' => 'productlist',
    'movementlist' => 'movementlist',
    'product_lotlist' => 'product_lotlist',
    'projectlist' => 'projectlist',
    'tasklist' => 'tasklist',
    'tasktimelist' => 'tasktimelist',
    'resourcelist' => 'resourcelist',
    'customerlist' => 'customerlist',
    'prospectlist' => 'prospectlist',
    'supplierlist' => 'supplierlist',
    'websiteaccountlist' => 'websiteaccountlist',
    'supplier_proposallist' => 'supplier_proposallist',
    'groupslist' => 'groupslist',
    'contactlist' => 'contactlist',
    'paymentlist' => 'paymentlist',
    'bankaccountlist' => 'bankaccountlist',
    'banktransactionlist' => 'banktransactionlist'
);
$selected = (! empty($conf->global->LIST_EXPORT_IMPORT_IGNORED_LISTS)?explode(',', $conf->global->LIST_EXPORT_IMPORT_IGNORED_LISTS):array());
print $form->multiselectarray('LIST_EXPORT_IMPORT_IGNORED_LISTS', $values, $selected, 0, 0, '', 0, '70%');
print '&nbsp;&nbsp;&nbsp;';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td>';
print '</tr>';

print '</table><br>';

/*
 * Export formats
 */

print load_fiche_titre($langs->trans("ListExportFormats"), '', 'title_export@listexportimport');

$var=true;
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td width="250">'.$langs->trans("Formats").'</td>'."\n";
print '<td>'.$langs->trans("Description").'</td>'."\n";
//print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="100">'.$langs->trans("Active").'</td>'."\n";
print '<td align="center" width="100">'.$langs->trans("Position").'</td>'."\n";
print '</tr>'."\n";

$liststatic->getFormats('export', 0);
$num = count($liststatic->formats);
$i = 0;
$csv_from_db_id = 0;

foreach($liststatic->formats as $format)
{
    if ($format->format == 'csvfromdb') {
        $csv_from_db_id = $format->rowid;
        
        if ($liststatic->isActiveFormat('import', 'csv')) {
            $format->format = 'csv';
        }
        else {
            continue;
        }
    }
    
    // Format
    $var=!$var;
    print '<tr '.$bc[$var].'><td>';
    print img_picto($langs->trans($format->title), $format->picto.'@listexportimport', 'width="20" style="vertical-align: middle;"');
    print '&nbsp;'.strtoupper($format->format).'</td>';
    print '<td>'.$langs->trans($format->description, img_picto('', 'warning', 'style="vertical-align: middle;"')).'</td>';
    //print '<td align="center">&nbsp;</td>';
    print '<td align="center">';
    if ($format->active == 0)
    {
        print '<a href="'.$_SERVER['PHP_SELF'].'?action=enable&id='.$format->rowid.'">'.img_picto($langs->trans("Disabled"),'switch_off').'</a>';
    }
    else
    {
        print '<a href="'.$_SERVER['PHP_SELF'].'?action=disable&id='.$format->rowid.'">'.img_picto($langs->trans("Enabled"),'switch_on').'</a>';
    }
    print '</td>';
    print '<td align="center" class="linecolmove tdlineupdown">';
    if ($i > 0) {
        print '<a class="lineupdown" href="'.$_SERVER["PHP_SELF"].'?action=up&amp;id='.$format->rowid.'">';
        print img_up('default',0,'imgupforline');
	print '</a>';
    }
    if ($i < $num-1) {
        print '<a class="lineupdown" href="'.$_SERVER["PHP_SELF"].'?action=down&amp;id='.$format->rowid.'">';
        print img_down('default',0,'imgdownforline');
	print '</a>';
    }
    print '</td>';
    print '</tr>';
    
    $i++;
}

print '</table><br>';

/*
 * Import formats
 */

print load_fiche_titre($langs->trans("ListImportFormats"), '', 'title_import@listexportimport');

$var=true;
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td width="250">'.$langs->trans("Formats").'</td>'."\n";
print '<td>'.$langs->trans("Description").'</td>'."\n";
//print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="100">'.$langs->trans("Active").'</td>'."\n";
print '<td align="center" width="100">'.$langs->trans("Position").'</td>'."\n";
print '</tr>'."\n";

$liststatic->getFormats('import', 0);
$num = count($liststatic->formats);
$i = 0;

foreach($liststatic->formats as $format)
{
    // Format
    $var=!$var;
    print '<tr '.$bc[$var].'><td>';
    print img_picto($langs->trans($format->title), $format->picto.'@listexportimport', 'width="20" style="vertical-align: middle;"');
    print '&nbsp;'.strtoupper($format->format).'</td>';
    print '<td>'.$langs->trans($format->description, img_picto('', 'warning', 'style="vertical-align: middle;"')).'</td>';
    //print '<td align="center">&nbsp;</td>';
    print '<td align="center">';
    if ($format->active == 0)
    {
        print '<a href="'.$_SERVER['PHP_SELF'].'?action=enable&id='.$format->rowid.'">'.img_picto($langs->trans("Disabled"),'switch_off').'</a>';
    }
    else
    {
        if ($format->format == 'csv') { // to disable 'export to csv from db' when disabling 'import from csv' format
            print '<a href="'.$_SERVER['PHP_SELF'].'?action=disable&id='.$format->rowid.','.$csv_from_db_id.'">'.img_picto($langs->trans("Enabled"),'switch_on').'</a>';
        }
        else {
            print '<a href="'.$_SERVER['PHP_SELF'].'?action=disable&id='.$format->rowid.'">'.img_picto($langs->trans("Enabled"),'switch_on').'</a>';
        }
    }
    print '</td>';
    print '<td align="center" class="linecolmove tdlineupdown">';
    if ($i > 0) {
        print '<a class="lineupdown" href="'.$_SERVER["PHP_SELF"].'?action=up&amp;id='.$format->rowid.'">';
        print img_up('default',0,'imgupforline');
	print '</a>';
    }
    if ($i < $num-1) {
        print '<a class="lineupdown" href="'.$_SERVER["PHP_SELF"].'?action=down&amp;id='.$format->rowid.'">';
        print img_down('default',0,'imgdownforline');
	print '</a>';
    }
    print '</td>';
    print '</tr>';
    
    $i++;
}

print '</table>';

llxFooter();

$db->close();