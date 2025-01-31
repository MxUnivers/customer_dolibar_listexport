<?php

/**
 * Class ActionsListExportImport
 */
class ActionsListExportImport
{
    /**
     * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
     */
    public $results = array();

    /**
     * @var string String displayed by executeHook() immediately after return
     */
    public $resprints;

    /**
     * @var array Errors
     */
    public $errors = array();


    /**
     * printCommonFooter
     *
     * @param   array()		 $parameters	 Hook metadatas (context, etc...)
     * @param   CommonObject	&$object		The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string		  &$action		Current action (if set). Generally create or edit or null
     * @param   HookManager	 $hookmanager	Hook manager propagated to allow calling another hook
     * @return  int							 < 0 on error, 0 on success, 1 to replace standard code
     */
    public function printCommonFooter($parameters, &$object, &$action, $hookmanager)
    {
        global $db, $langs, $conf, $user;

        $optioncss = GETPOST('optioncss', 'alpha');
        $is_list = strpos($parameters['context'], 'list') !== false && $optioncss != 'print';

        $ignored_lists = (!empty($conf->global->LIST_EXPORT_IMPORT_IGNORED_LISTS) ? explode(',', $conf->global->LIST_EXPORT_IMPORT_IGNORED_LISTS) : array());
        foreach ($ignored_lists as $key => $list) {
            if (strpos($parameters['context'], $list) !== false) {
                $is_list = false;
                break;
            }
        }

        if ($is_list) {
            if ($user->rights->listexportimport->export || $user->rights->listexportimport->import) {
                $langs->load('listexportimport@listexportimport');

                $pathtojs = array(
                    dol_buildpath('/listexportimport/js/FileSaver.min.js', 1),
                    dol_buildpath('/listexportimport/js/listexport.js.php', 1),
                    dol_buildpath('/listexportimport/js/listimport.js.php', 1),
                    dol_buildpath('/listexportimport/js/jspdf.min.js', 1),
                    dol_buildpath('/listexportimport/js/jspdf.plugin.autotable.min.js', 1),
                    dol_buildpath('/listexportimport/js/html2canvas.min.js', 1)
                );

                $pathtocss = array();

                dol_include_once('listexportimport/lib/listexportimport.lib.php');
                dol_include_once('listexportimport/class/listexportimport.class.php');

                $list = new ListExportImport($db);

                $more_buttons = array(
                    array('picto' => 'sql_delete.png', 'title' => 'FreeList', 'alt' => 'free', 'class' => 'import', 'active' => ($conf->global->LIST_EXPORT_IMPORT_ENABLE_FREE_LIST && $user->admin))
                );

                if ($conf->global->LIST_EXPORT_IMPORT_USE_COMPACT_MODE) {
                    $pathtocss[] = dol_buildpath('/listexportimport/css/listexportimport.css.php', 1);

                    if ($user->rights->listexportimport->export) {
                        $list->getFormats('export');
                        $download = '&nbsp;&nbsp;&nbsp;';
                        $download .= getCompactedButtons($list->formats, $langs->trans('ListExport'), dol_buildpath('/listexportimport/img/export.png', 1));
                    }

                    if ($user->rights->listexportimport->import) {
                        $list->getFormats('import');
                        if (count($list->formats) > 0) {
                            $download .= '&nbsp;&nbsp;&nbsp;';
                            $download .= getCompactedButtons($list->formats, $langs->trans('ListImport'), dol_buildpath('/listexportimport/img/import.png', 1), $more_buttons);
                        }
                    }
                } else {
                    $download = '&nbsp;';
                    $list->getFormats();

                    // List export/import formats buttons
                    foreach ($list->formats as $format) {
                        if ($format->active) {
                            if (($format->type == 'export' && $user->rights->listexportimport->export) || ($format->type == 'import' && $user->rights->listexportimport->import)) {
                                $download .= '&nbsp;' . getButton(dol_buildpath('/listexportimport/img/' . $format->picto, 1), $langs->trans($format->title), $format->format, $format->type);
                            }

                        }
                    }

                    // ✅ Ajouter un bouton "Export Comptabilité"
                    $download .= '&nbsp;' . getButton(
                        dol_buildpath('/listexportimport/img/accounting.png', 1),
                        // Icône
                        $langs->trans('Export Comptabilité'),
                        // Texte du bouton
                        'comptabilite',
                        // Identifiant pour JavaScript
                        'export' // Classe CSS
                    );


                    // More buttons
                    foreach ($more_buttons as $button) {
                        if ($button['active']) {
                            if (($button['class'] == 'export' && $user->rights->listexportimport->export) || ($button['class'] == 'import' && $user->rights->listexportimport->import)) {
                                $download .= '&nbsp;' . getButton(dol_buildpath('/listexportimport/img/' . $button['picto'], 1), $langs->trans($button['title']), $button['alt'], $button['class']);
                            }
                        }
                    }


                }

                // add import file input
                if ($user->rights->listexportimport->import) {
                    $download .= '<input type="file" class="hidden" style="display: none;" id="import-file-input" accept=".sql"/>';
                }

                $socid = GETPOST('socid');
                if (empty($socid))
                    $socid = 0;

                // Inclusion des fichiers CSS
                foreach ($pathtocss as $css) {
                    echo '<link rel="stylesheet" type="text/css" href="' . $css . '">' . "\n";
                }
                // Inclusion des fichiers JS (bibliothèques)
                foreach ($pathtojs as $js) {
                    echo '<script type="text/javascript" language="javascript" src="' . $js . '"></script>' . "\n";
                }
                ?>
                                <script type="text/javascript" language="javascript">
                                    $(document).ready(function () {
                                        var $form = $('div.fiche form').first(); // Les formulaire de liste n'ont pas tous les même name

                                        // Case of task list into project
                                        <?php if (strpos($parameters['context'], 'projecttasklist') !== false) { ?>
                                                $('#id-right > form#searchFormList div.titre').first().append('<?php echo $download; ?>');
                                        <?php } else { ?>
                                                $('div.fiche div.titre').first().append('<?php echo $download; ?>'); // Il peut y avoir plusieurs titre dans la page
                                        <?php } ?>

                                        $(document).click(function () {
                                            $('.dropdown-click .dropdown-content').removeClass('show');
                                        });

                                        $('.drop-btn').click(function (e) {
                                            e.stopPropagation();
                                            $('.dropdown-click .dropdown-content').removeClass('show');
                                            $(this).next().addClass('show');
                                        });

                                        $(".import").on('click', function (event) {
                                            var $self = $(this);
                                            var $format = $self.attr("title");

                                            if ($format == 'free') {
                                                data = {};
                                                data.action = 'free_sql';
                                                data.url = $form.attr('action');

                                                $('#dialogforpopup').html('<?php echo img_picto('', 'info', ' style="vertical-align: middle;"') . '&nbsp;' . addslashes($langs->trans('ConfirmFreeList')); ?>');
                                                $('#dialogforpopup').dialog({
                                                    title: '<?php echo $langs->trans('FreeList'); ?>',
                                                    autoOpen: true,
                                                    open: function () {
                                                        $(this).parent().find("button.ui-button:eq(2)").focus();
                                                    },
                                                    resizable: false,
                                                    height: "200",
                                                    width: "500",
                                                    modal: true,
                                                    closeOnEscape: false,
                                                    buttons: {
                                                        "Yes": function () {
                                                            // Envoi de la requête HTTP en mode synchrone
                                                            $.ajax({
                                                                url: '<?php echo dol_buildpath('/listexportimport/ajax/ajax.php', 1); ?>',
                                                                type: 'post',
                                                                data: data,
                                                                async: false
                                                            }).done(function (response) {
                                                                //console.log(response);
                                                                if (response.length > 0 && response != 'success') {
                                                                    $('#dialogforpopup').dialog('close');
                                                                    alert(response);
                                                                } else {
                                                                    var options = "";
                                                                    var pageyes = $form.attr('action');
                                                                    var urljump = pageyes + (pageyes.indexOf("?") < 0 ? "?" : "") + options;
                                                                    //alert(urljump);
                                                                    if (pageyes.length > 0) {
                                                                        location.href = urljump;
                                                                    } else {
                                                                        $('#dialogforpopup').dialog('close');
                                                                    }
                                                                }
                                                            });
                                                        },
                                                        "No": function () {
                                                            var options = "";
                                                            var pageno = "";
                                                            var urljump = pageno + (pageno.indexOf("?") < 0 ? "?" : "") + options;
                                                            //alert(urljump);
                                                            if (pageno.length > 0) {
                                                                location.href = urljump;
                                                            }
                                                            $('#dialogforpopup').dialog('close');
                                                        }
                                                    }
                                                });
                                            } else if ($format == 'sql') {
                                                // Sql import
                                                $('#import-file-input').attr("accept", ".sql").attr("name", "sql");
                                                $('#import-file-input').click();
                                            } else if ($format == 'csv') {
                                                // Csv import
                                                $('#import-file-input').attr("accept", ".csv").attr("name", "csv");
                                                $('#import-file-input').click();
                                            }
                                        });
                                        $('#import-file-input').change(function () {
                                            var fileinput = this;
                                            var filetype = $(fileinput).attr('name');
                                            var filename = $(fileinput).val();
                                            var $popup_message = '';

                                            switch (filetype) {
                                                case 'csv':
                                                    $popup_message = '<?php echo $langs->trans('FileImportationInProgress', 'CSV'); ?>';
                                                    break;
                                                case 'sql':
                                                    $popup_message = '<?php echo $langs->trans('FileImportationInProgress', 'SQL'); ?>';
                                                    break;
                                            }

                                            $('#dialogforpopup').html($popup_message);
                                            $('#dialogforpopup').dialog({
                                                title: '<?php echo $langs->trans('ListImport'); ?>',
                                                buttons: {},
                                                open: function (event, ui) {
                                                    // Importation du fichier
                                                    var ajax_url = '<?php echo dol_buildpath('/listexportimport/ajax/ajax.php', 1); ?>';
                                                    readFile(fileinput.files[0], filetype, $form.attr('action'), filename, ajax_url);
                                                }
                                            });
                                        });
                                        $(".export").on('click', function (event) {
                                            var $self = $(this);
                                            var $format = $self.attr("title");
                                            var $listname = $(document).find("title").text();
                                            var $filename = $listname != '' ? $listname : 'export';
                                            var $popup_message = '';

                                            // Get popup message & Add filename extension
                                            switch ($format) {
                                                case 'csvfromdb':
                                                case 'csv':
                                                    $popup_message = '<?php echo $langs->trans('FileGenerationInProgress', 'CSV'); ?>';
                                                    $filename = $filename + '.csv'; //$filename += '.csv';
                                                    break;
                                                case 'pdf':
                                                    $popup_message = '<?php echo $langs->trans('FileGenerationInProgress', 'PDF'); ?>';
                                                    $filename = $filename + '.pdf'; //$filename += '.pdf';
                                                    break;
                                                case 'png':
                                                    $popup_message = '<?php echo $langs->trans('FileGenerationInProgress', 'PNG'); ?>';
                                                    $filename = $filename + '.png'; //$filename += '.png';
                                                    break;
                                                case 'sql':
                                                    $popup_message = '<?php echo $langs->trans('FileGenerationInProgress', 'SQL'); ?>';
                                                    $filename = $filename + '.sql'; //$filename += '.sql';
                                                    break;
                                                default:
                                                    $popup_message = '<?php echo $langs->trans('FileGenerationInProgress'); ?>';
                                            }

                                            // Sql/Csv from db export
                                            if ($format == 'sql' || $format == 'csvfromdb') {
                                                data = {};
                                                data.action = $format == 'sql' ? 'export_sql' : 'export_csv_from_db';
                                                data.url = $form.attr('action');

                                                $('#dialogforpopup').html($popup_message);
                                                $('#dialogforpopup').dialog({
                                                    title: '<?php echo $langs->trans('ListExport'); ?>',
                                                    buttons: {},
                                                    open: function (event, ui) {
                                                        // Envoi de la requête HTTP en mode synchrone
                                                        $.ajax({
                                                            url: '<?php echo dol_buildpath('/listexportimport/ajax/ajax.php', 1); ?>',
                                                            type: 'post',
                                                            data: data,
                                                            async: false
                                                        }).done(function (exported_data) {
                                                            //console.log(exported_data);
                                                            var args = [$format, exported_data, $filename];
                                                            exportTableToFile.apply($self, args);

                                                            $('#dialogforpopup').dialog('close');
                                                        });
                                                    }
                                                });
                                            }
                                            // Png export
                                            else if ($format == 'png') {
                                                $('#dialogforpopup').html($popup_message);
                                                $('#dialogforpopup').dialog({
                                                    title: '<?php echo $langs->trans('ListExport'); ?>',
                                                    buttons: {},
                                                    open: function (event, ui) {
                                                        var args = ['table.liste', $filename];
                                                        exportTableToPNG.apply($self, args);

                                                        //$('#dialogforpopup').dialog('close');
                                                    }
                                                });
                                            } 
                            
                            
                            
                //                             else if ($format == 'comptabilite') {
                //     var $popup_message = 'Génération du fichier de comptabilité en cours...';
                //     var $filename = 'export_comptabilite.pdf';

                //     $('#dialogforpopup').html($popup_message);
                //     $('#dialogforpopup').dialog({
                //         title: 'Export Comptabilité',
                //         buttons: {},
                //         open: function (event, ui) {
                //             var args = [$table.first(), $filename];
                //             exportComptabiliteToPDF.apply($self, args);
                //         }
                //     });
                // }
                            
                            
                            
                                            else {
                                                // Récupération des données du formulaire de filtre et transformation en objet
                                                var data = objectifyForm($form.serializeArray());

                                                // Pas de limite, on veut télécharger la liste totale
                                                data.limit = 10000000;
                                                data.socid = <?php echo $socid; ?>;

                                                $('#dialogforpopup').html($popup_message);
                                                $('#dialogforpopup').dialog({
                                                    title: '<?php echo $langs->trans('ListExport'); ?>',
                                                    buttons: {},
                                                    open: function (event, ui) {
                                                        // Envoi de la requête HTTP en mode synchrone
                                                        $.ajax({
                                                            url: $form.attr('action'),
                                                            type: $form.attr('method'),
                                                            data: data,
                                                            async: false
                                                        }).done(function (html) {
                                                            // Récupération de la table html qui nous intéresse
                                                            var $table = $(html).find('table.liste,table#listtable');
                                                            var has_search_button = $table.has('input[name="button_search"],th.maxwidthsearch').length;

                                                            // Nettoyage de la table avant conversion en CSV
                                                            // Suppression des filtres de la liste
                                                            $table.find('tr.liste_titre_filter').remove(); // >= 6.0
                                                            $table.find('tr:has(td.liste_titre)').remove(); // < 6.0

                                                            // Suppression des éléments ignorés / à ne pas exporter
                                                            $table.find('th.do_not_export, td.do_not_export').remove();

                                                            // Suppression de la dernière colonne qui contient seulement les loupes des filtres
                                                            if (has_search_button) {
                                                                $table.find('th:last-child, td:last-child').each(function (index) {
                                                                    $(this).find('dl').remove();
                                                                    if ($(this).closest('table').hasClass('liste')) $(this).remove();
                                                                });
                                                            }

                                                            // Suppression de la ligne TOTAL en pied de tableau
                                                            <?php if (empty($conf->global->LIST_EXPORT_IMPORT_DONT_REMOVE_TOTAL)) { ?>
                                                                    $table.find('tr.liste_total').remove();
                                                            <?php } ?>

                                                            // Suppression des espaces pour les nombres
                                                            <?php if (!empty($conf->global->LIST_EXPORT_IMPORT_DELETESPACEFROMNUMBER)) { ?>
                                                                    $table.find('td').each(function (e) {
                                                                        var nbWthtSpace = $(this).text().replace(/ /g, '').replace(/\xa0/g, '');
                                                                        var commaToPoint = nbWthtSpace.replace(',', '.');
                                                                        if ($.isNumeric(commaToPoint)) $(this).html(nbWthtSpace);
                                                                    });
                                                            <?php } ?>

                                                            // Remplacement des sous-table par leur valeur text(), notamment pour la ref dans les listes de propales, factures...
                                                            $table.find('td > table').map(function (i, cell) {
                                                                $cell = $(cell);
                                                                $cell.html($cell.text());
                                                            });

                                                            // Generation
                                                            switch ($format) {
                                                                case 'csv':
                                                                    // Transformation de la table liste en CSV + téléchargement
                                                                    var args = [$table.first(), $filename]; // .first() to avoid conflits with other tables like the volume calculator table for example
                                                                    exportTableToCSV.apply($self, args);
                                                                    break;
                                                                case 'pdf':

                                                                    // recupération des information du tittre du coument en question
                                                                    let titreElement = document.querySelector('.titre.inline-block');
                                                                    let texteDocument = "";
                                                                    // Vérifier si l'élément existe et récupérer son texte
                                                                    if (titreElement) {
                                                                        texteDocument = titreElement.childNodes[0].textContent.trim();
                                                                        console.log(texteDocument);
                                                                    }

                                                                    // recupérer tio des date de comptabilité 
                                                                    // Récupérer les dates depuis les champs input
                                                                    let dateStartElement = document.getElementById('search_date_start');
                                                                    let dateEndElement = document.getElementById('search_date_end');

                                                                    // Vérifier si les éléments existent avant d'extraire la valeur
                                                                    let dateStart = dateStartElement ? "Periode du : " + dateStartElement.value : "";
                                                                    let dateEnd = dateEndElement ? "Au : " + dateEndElement.value : "";
                                                                    // Afficher les valeurs dans la console
                                                                    console.log("Date de début :", dateStart);
                                                                    console.log("Date de fin :", dateEnd);



                                                                    //exportTableToPDF($table);//, $filename);
                                                                    // Only pt supported (not mm or in)
                                                                    var doc = new jsPDF('l', 'pt'); // 'p' for a vertical orientation & 'l' for an horizontal orientation
                                                                    <?php if ($conf->global->LIST_EXPORT_IMPORT_PRINT_DATE_ON_PDF_EXPORT) { ?>
                                                                            var today = new Date();
                                                                            var date = 'd/m/Y'.replace('Y', today.getFullYear())
                                                                                .replace('m', today.getMonth() + 1)
                                                                                .replace('d', today.getDate());
                                                                            var width = doc.internal.pageSize.width;
                                                                    <?php } ?>
                                                                    // convertion de l'image en base64
                                                                    <?php
                                                                    $imageUrl = 'https://www.infosoluces.ci/wp-content/uploads/2021/04/infosoluces-logo-ci-1.png';
                                                                    $imageData = base64_encode(file_get_contents($imageUrl));
                                                                    $imageBase64_convert_php = 'data:image/png;base64,' . $imageData;
                                                                    ?>
                                                                    // Convertir l'image en base64
                                                                    var imageBase64_convert = '<?php echo $imageBase64_convert_php; ?>'; // Injecter l'image en base64 depuis PHP

                                                                    // Récupérer l'élément par sa classe



                                                                    var width = doc.internal.pageSize.width;
                                                                    // Ajouter l'image au PDF
                                                                    doc.addImage(imageBase64_convert, 'PNG', width - Number(width - 50), 5, 40, 40); // x, y, largeur, hauteur
                                                                    doc.setFontSize(8);
                                                                    // 
                                                                    // Fonction pour convertir une URL d'image en base64


                                                                    // Ajouter les informations supplémentaires dans l'entête


                                                                    //  filtre de date en question
                                                                    // if (dateStart ) {
                                                                    doc.setFontSize(10);
                                                                    // ✅ **Aligner les dates à droite**
                                                                    var textRightX = Number(width - width / 5); // Position tout à droite
                                                                    doc.text(textRightX, 40, dateStart);
                                                                    doc.text(textRightX, 55, dateEnd);
                                                                    // }





                                                                    // Récupérer la largeur de la page
                                                                    var maxWidth = width * 0.8; // Largeur maximale pour le texte avant retour à la ligne
                                                                    var startY = 30; // Position verticale du titre
                                                                    // Définir la police et la taille du texte
                                                                    doc.setFontSize(15);
                                                                    doc.setFont("helvetica", "bold");
                                                                    // Utiliser splitTextToSize pour gérer les longs textes
                                                                    var textLines = doc.splitTextToSize(texteDocument, maxWidth);
                                                                    // Calculer la hauteur totale du texte (utile pour centrer verticalement si besoin)
                                                                    var lineHeight = 20; // Hauteur d'une ligne de texte
                                                                    var textHeight = textLines.length * lineHeight;
                                                                    // Définir la position Y de départ
                                                                    var textY = startY;
                                                                    // Centrer chaque ligne de texte horizontalement
                                                                    textLines.forEach((line, index) => {
                                                                        var textWidth = doc.getTextWidth(line); // Obtenir la largeur du texte actuel
                                                                        var textX = (width - textWidth) / 2; // Calculer la position X pour le centrer

                                                                        doc.text(line, textX, textY + (index * lineHeight));
                                                                    });



                                                                    doc.setFontSize(12);
                                                                    doc.text(width - Number(width - 80), 30, 'INFOSOLUCES');
                                                                    var res = doc.autoTableHtmlToJson($table.get(0));
                                                                    doc.autoTable(res.columns, res.data, {
                                                                        startY: 90,
                                                                        includeHiddenHtml: true, // Inclut les lignes masquées
                                                                        rowPageBreak: 'avoid', // Empêche la coupure des lignes sur plusieurs pages
                                                                        // theme: 'grid', // Affichage en mode tableau structuré
                                                                        styles: {
                                                                            fontSize: 8,
                                                                            overflow: 'linebreak'
                                                                        }
                                                                    });
                                                                    // Vérifier si la table existe
                                                                    console.log($table.html());
                                                                    console.log($table.find('.trforbreak').length);


                                                                    //doc.output('dataurlnewwindow');
                                                                    doc.save($filename);
                                                                    break;



                                                                    case 'comptabilite':
    // ✅ Récupération du titre du document
    let titreElementCompta = document.querySelector('.titre.inline-block');
    let texteDocumentCompta = titreElementCompta ? titreElementCompta.childNodes[0].textContent.trim() : '';

    // ✅ Récupération des dates
    let dateStartElementCompta = document.getElementById('search_date_start');
    let dateEndElementCompta = document.getElementById('search_date_end');

    let dateStartCompta = dateStartElementCompta ? "Période du : " + dateStartElementCompta.value : "";
    let dateEndCompta = dateEndElementCompta ? "Au : " + dateEndElementCompta.value : "";

    console.log("Date de début :", dateStartCompta);
    console.log("Date de fin :", dateEndCompta);

    // ✅ Création du document PDF
    var docCompta = new jsPDF('l', 'pt');

    // ✅ Gestion de l'image
    var imageBase64Compta = '<?php echo $imageBase64_convert_php; ?>';
    var widthCompta = docCompta.internal.pageSize.width;
    docCompta.addImage(imageBase64Compta, 'PNG', widthCompta - Number(widthCompta - 50), 5, 40, 40);

    // ✅ Ajout des dates à droite
    var textRightXCompta = Number(widthCompta - widthCompta / 5);
    docCompta.setFontSize(10);
    docCompta.text(textRightXCompta, 40, dateStartCompta);
    docCompta.text(textRightXCompta, 55, dateEndCompta);

    // ✅ Centrage du titre
    var maxWidthCompta = widthCompta * 0.8;
    var textLinesCompta = docCompta.splitTextToSize(texteDocumentCompta, maxWidthCompta);
    var textYCompta = 30;
    var lineHeightCompta = 20;

    docCompta.setFontSize(15);
    docCompta.setFont("helvetica", "bold");

    textLinesCompta.forEach((line, index) => {
        var textWidth = docCompta.getTextWidth(line);
        var textX = (widthCompta - textWidth) / 2;
        docCompta.text(line, textX, textYCompta + (index * lineHeightCompta));
    });

    // ✅ Ajout du tableau comptabilité
    docCompta.setFontSize(12);
    docCompta.text(widthCompta - Number(widthCompta - 80), 30, 'INFOSOLUCES');
    
    var resCompta = docCompta.autoTableHtmlToJson($table.get(0));
    docCompta.autoTable(resCompta.columns, resCompta.data, {
        startY: 90,
        includeHiddenHtml: true,
        rowPageBreak: 'avoid',
        styles: { fontSize: 8, overflow: 'linebreak' }
    });

    // ✅ Sauvegarde du document
    docCompta.save($filename);
    break;

                                                                    




                                                                    // format compatbilite
                                                                    






                                                                /*case 'png':
                                                                    var args = [$table, $filename];
                                                                    exportTableToPNGFromHTML.apply($self, args);
                                                                    return;//break;*/
                                                            }

                                                            $('#dialogforpopup').dialog('close');
                                                        });
                                                    }
                                                });
                                            } // fin else, if ($format == 'sql')
                                        });
                                    });
                                </script>
                                <?php
            } // end if ($user->rights->listexportimport->export || $user->rights->listexportimport->import)
        }

        return 0;
    }
}
