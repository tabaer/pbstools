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
abstract class XMLElement {
	
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
	 * @var 	string			$element				Le type d'élément
	 */
	protected $element;
	
	/**
	 * @access	protected
	 * @var 	object			$root					L'élément duquel part les autres
	 */
	protected $root;
	
	/**
	 * @access	protected
	 * @var 	array			$nameSpaces				Les espaces de nom pour chaque entité
	 */
	protected $nameSpaces;
	
	
	/**
	 *-------------------------------------------------------------------------------
	 * Méthodes publiques
	 *-------------------------------------------------------------------------------
	 */
	
	/**
	 * Fonction qui renseigne les propriétés et load le document XML
	 *
	 * @access 	public
	 * @param 	string			$element				Le type d'élément créé
	 * @param 	object			$core					Objet DOM du fichier XML
	 * @param 	object			$xpath					Objet DOMXPath du fichier XML
	 * @return 	void
	 */
	public function load($element, $core, $xpath) {
		$this->element = $element;
		$this->core    = $core;
		$this->xpath   = $xpath;
		$this->root    = $this->core->documentElement;
		$this->nameSpaces = Fonction::getNamespace();
	}
	
	/**
	 * Fonction qui retourne le flux XML du fichier
	 *
	 * @access 	public
	 * @param 	boolean			$xmp					True pour afficher le flux entre des balises <xmp>
	 * @return 	string									Le flux XML du fichier
	 */
	public function saveXML($xmp = false) {
		if ($xmp) {
			$str = '<xmp>';
			$str .= $this->core->saveXML();
			$str .= '</xmp>';	
		} else {
			$str = $this->core->saveXML();
		}
		return $str;
	}
	
	
	/**
	 *-------------------------------------------------------------------------------
	 * Méthodes getters et setters
	 *-------------------------------------------------------------------------------
	 */
	
	/**
	 * Fonction qui retourne le type d'élément
	 *
	 * @access 	public
	 * @return 	string									Le type d'élément
	 */
	public function getElementType() {
		return $this->element;	
	}
	
	
	/**
	 *-------------------------------------------------------------------------------
	 * Méthodes privées
	 *-------------------------------------------------------------------------------
	 */
	
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
