<?php

require_once ('XMLDocument.abstract.php');
require_once ('Fonction.class.php');

/**
 * OpenOfficeSpreadsheet est un ensemble de classes permettant de générer un document OpenOffice
 * Spreadsheet (feuille de calcul ou tableur). Ces classes contiennent un certain nombre de
 * fonctions permettant la mise en page et le remplissage de cellules. Euh, sinon c'est tout.
 * Mais il y a de quoi faire, notamment au niveau des classes Settings et Styles, mais ça
 * viendra (peut-être) plus tard.
 *
 * Sinon, c'est gratuit, c'est sympa, et même si ça ne sert pas à grand chose, ça sert quand
 * même à quelque chose. Donc finalement, c'est cool. Alors enjoy!
 *
 * @package		OpenOfficeGeneration
 * @version		0.1
 * @copyright	(C) 2006 Tafel. All rights reserved
 * @license		http://www.gnu.org/copyleft/lesser.html LGPL License
 * @author		Tafel <fab_tafelmak@hotmail.com>
 *
 * Programme sous licence GPL. Toute reproduction, même patielle, est autorisée, avec ou sans le
 * consentement du programmeur principal (avec, c'est mieux, quand même ;) ...)
 */
class Settings extends XMLDocument {
	
	/**
	 *-------------------------------------------------------------------------------
	 * Propriétés
	 *-------------------------------------------------------------------------------
	 */	
	
	
	/**
	 *-------------------------------------------------------------------------------
	 * Constructeur
	 *-------------------------------------------------------------------------------
	 */	
	
	/**
	 * Constructeur de classe
	 *
	 * @access 	public
	 * @param 	string			$path_save				Le chemin vers le dossier de sauvegarde
	 * @param 	string			$path_templates			Le chemin vers les templates
	 * @param 	boolean			$format_output			True pour un affichage joli du XML
	 * @param 	boolean			$white_space			True pour préserver les espaces blancs
	 * @return 	object									L'objet de classe
	 */
	public function __construct($path_save, $path_templates, $format_output, $white_space) {
		$fileName = 'settings.xml';
		$this->load($fileName, $path_save, $path_templates, $format_output, $white_space);
	}	
	
	
	/**
	 *-------------------------------------------------------------------------------
	 * Méthodes publiques
	 *-------------------------------------------------------------------------------
	 */
	
	/**
	 * Fonction qui sauvegarde le flux dans un fichier
	 *
	 * @access 	public
	 * @return 	void
	 */
	public function blu() {
		
	}
	
}

?>
