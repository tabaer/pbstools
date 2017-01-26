<?php

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
abstract class XMLDocument {
	
	/**
	 *-------------------------------------------------------------------------------
	 * Propriétés
	 *-------------------------------------------------------------------------------
	 */	
	
	/**
	 * @access	protected
	 * @var 	object			$core					L'objet DOMDocument du document
	 */
	protected $core;
	
	/**
	 * @access	protected
	 * @var 	object			$xpath					L'objet DOMXPath du document
	 */
	protected $xpath;
	
	/**
	 * @access	protected
	 * @var 	string			$pathTemplates			Le chemin vers les templates
	 */
	protected $pathTemplates;
	
	/**
	 * @access	protected
	 * @var 	string			$pathSave				Le chemin vers le dossier de sauvegarde
	 */
	protected $pathSave;
	
	/**
	 * @access	protected
	 * @var 	string			$fileName				Le nom du fichier XML
	 */
	protected $fileName;
	
	/**
	 * @access	protected
	 * @var 	array			$nameSpaces				Les espaces de nom pour chaque entité
	 */
	protected $nameSpaces;
	
	/**
	 * @access	protected
	 * @var 	object			$root					L'élément de base duquel s'inséreront tous les autres
	 */
	protected $root;
	
	
	/**
	 *-------------------------------------------------------------------------------
	 * Méthodes publiques
	 *-------------------------------------------------------------------------------
	 */
	
	/**
	 * Fonction qui renseigne les propriétés et load le document XML
	 *
	 * @access 	public
	 * @param 	string			$file_name				Le nom du fichier XML à loader
	 * @param 	string			$path_save				Le chemin vers le dossier de sauvegarde
	 * @param 	string			$path_templates			Le chemin vers le dossier de templates
	 * @param 	boolean			$format_output			True pour un affichage joli du XML
	 * @param 	boolean			$white_space			True pour préserver les espaces blancs
	 * @return 	void
	 */
	public function load($file_name, $path_save, $path_templates, $format_output, $white_space) {
		try {
			$this->fileName         = $file_name;
			$this->pathTemplates    = Fonction::removeLastSlash($path_templates);
			$this->pathSave         = Fonction::removeLastSlash($path_save);
			// Création du document XML
			$this->core = new DOMDocument;
			// Set des options d'affichage
			$this->core->preserveWhiteSpace = $white_space;
			$this->core->formatOutput       = $format_output;
			// Load du fichier
			if (!@$this->core->load($this->pathTemplates.'/'.$this->fileName)) {
				throw new Exception('Le fichier n\'a pas été chargé');	
			}
			$this->xpath = new DOMXPath($this->core);
			$this->root  = $this->core->documentElement; 
			// Enregistrement des names space
			$this->nameSpaces = Fonction::getNamespace();
		} catch (Exception $e) {
			throw $e;	
		}
	}
	
	/**
	 * Fonction qui retourne le flux XML du fichier
	 *
	 * @access 	public
	 * @param 	boolean			$xmp					True pour afficher le flux entre des balises <xmp>
	 * @return 	string									Le flux XML du fichier
	 */
	public function saveXML($xmp = false) {
		$this->_beforeSave();
		if ($xmp) {
			$str = '<xmp>';
			$str .= $this->core->saveXML();
			$str .= '</xmp>';	
		} else {
			$str = $this->core->saveXML();
		}
		$this->_afterSave();
		return $str;
	}
	
	/**
	 * Fonction qui sauvegarde le flux dans un fichier. Créé le répertoire s'il n'existe pas
	 *
	 * @access 	public
	 * @return 	boolean									True en cas de succès, false sinon
	 */
	public function saveFile() {
		if (!is_dir($this->pathSave))
			mkdir($this->pathSave, 0777);
		if ($this->_beforeSave()){
			$this->core->save($this->pathSave.'/'.$this->fileName);
			return $this->_afterSave();
		} else {
			return false;
		}
	}
	
	
	/**
	 *-------------------------------------------------------------------------------
	 * Méthodes getters et setters
	 *-------------------------------------------------------------------------------
	 */
	
	/**
	 * Fonction qui retourne le nom du dossier généré pour la sauvegarde
	 *
	 * @access 	public
	 * @return 	string									Le nom du dossier généré
	 */
	public function getGeneratedDirName() {
		return $this->pathSave;	
	}
	
	/**
	 * Fonction qui retourne le nom du dossier de templates
	 *
	 * @access 	public
	 * @return 	string									Le nom du dossier de templates
	 */
	public function getTemplatesDirName() {
		return $this->pathTemplates;	
	}
	
	/**
	 * Fonction qui retourne le nom du fichier XML
	 *
	 * @access 	public
	 * @return 	string									Le nom du fichier XML
	 */
	public function getFileName() {
		return $this->fileName;	
	}
	
	
	/**
	 *-------------------------------------------------------------------------------
	 * Méthodes privées
	 *-------------------------------------------------------------------------------
	 */
	
	/**
	 * Fonction qui est appelée juste avant de sauvegarder le fichier XML
	 *
	 * @access 	protected
	 * @return 	boolean									Doit retourner true pour faire la sauvegarde
	 */
	protected function _beforeSave() {
		return true;
	}
	
	/**
	 * Fonction qui est appelée juste après de sauvegarder le fichier XML
	 *
	 * @access 	protected
	 * @return 	boolean									Doit retourner true pour faire la sauvegarde
	 */
	protected function _afterSave() {
		return true;
	}
	
	/**
	 * Fonction qui ajoute un élément
	 *
	 * @access 	protected
	 * @param 	string			$type					Le type de namespace de l'élément
	 * @param 	string			$element				Le nom de l'élément
	 * @param 	string			$str					La valeur de l'élément
	 * @param 	object			$parent					Le DOMElement parent de celui qu'on créé
	 * @return 	object									Le DOMElement créé
	 */
	protected function _addElement($type, $element, $str = null, $parent = null) {
		try {
			if ($str)
				$new = $this->core->createElementNS($this->nameSpaces[$type], $type.':'.$element, Fonction::checkString($str));
			else 
				$new = $this->core->createElementNS($this->nameSpaces[$type], $type.':'.$element);
			if (!$parent)
				$this->root->appendChild($new);
			else 
				$parent->appendChild($new);
			return $new;
		} catch (Exception $e) {
			throw $e;	
		}
	}
	
	/**
	 * Fonction qui ajoute ou modifie un élément
	 *
	 * @access 	protected
	 * @param 	string			$type					Le type de namespace de l'élément
	 * @param 	string			$element				Le nom de l'élément
	 * @param 	string			$str					La valeur de l'élément
	 * @param 	object			$parent					Le DOMElement parent de celui qu'on créé
	 * @return 	object									Le DOMElement créé
	 */
	protected function _setElement($type, $element, $str = null, $parent = null) {
		try {
			$elem = $this->xpath->query('//'.$type.':'.$element);
			if ($elem->length == 0)
				return $this->_addElement($type, $element, $str, $parent);
			else {
				$pos = 0;
				$el = $elem->item($pos);
				$txt = $this->core->createTextNode($str);
				$el->replaceChild($txt, $el->firstChild);
				return $el;	
			}
		} catch (Exception $e) {
			throw $e;	
		}
	}
	
	
}

?>
