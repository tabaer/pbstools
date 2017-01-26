<?php

require_once ('XMLDocument.abstract.php');
require_once ('Fonction.class.php');
require_once ('Table.class.php');

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
class Content extends XMLDocument {
	
	/**
	 *-------------------------------------------------------------------------------
	 * Propriétés
	 *-------------------------------------------------------------------------------
	 */	
	
	/**
	 * @access 	protected
	 * @var 	array			$sheets					Les feuilles du document
	 */
	protected $sheets;
	
	
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
		try {
			$fileName = 'content.xml';
			$this->load($fileName, $path_save, $path_templates, $format_output, $white_space);
			$this->root = $this->core->documentElement;
			$this->sheets = array();
		} catch (Exception $e) {
			throw $e;	
		}
	}	
	
	
	/**
	 *-------------------------------------------------------------------------------
	 * Méthodes publiques
	 *-------------------------------------------------------------------------------
	 */
	
	/**
	 * Fonction qui créé une nouvelle feuille
	 *
	 * @access 	public
	 * @param 	string			$sheet					Le nom de la feuille
	 * @return 	object									L'objet Sheet
	 */
	public function addSheet($sheet) {
		try {
			$obj = new Table($sheet, $this->core, $this->xpath);
			$this->sheets[] = $obj;
			return $obj;
		} catch (Exception $e) {
			throw $e;	
		}
	}
	
	/**
	 * Fonction qui retourne un tableau contenant les feuilles portant le nom voulu
	 *
	 * Si aucune feuille n'est trouvée, retourne un tableau vide
	 *
	 * @access 	public
	 * @param 	string			$sheet					Le nom de la feuille cherchée
	 * @param 	boolean			$solo_no_array			True pour retourner directement l'objet s'il n'y a qu'une feuille
	 * @return 	array									Le tableau d'objets Sheet
	 */
	public function getSheetsByName($name, $solo_no_array = false) {
		$tab = array();
		foreach ($this->sheets as $sheet) {
			if ($sheet->getName() == $name)
				$tab[] = $sheet;
		}
		if ($solo_no_array) {
			if (count($tab) == 1)
				return $tab[0];
		}
		return $tab;
	}
	
	/**
	 * Fonction qui retourne la feuille de l'index spécifié. La 1ère feuille a l'index 0
	 *
	 * @access 	public
	 * @param 	integer			$index					La position de la feuille
	 * @return 	object									L'objet Sheet
	 */
	public function getSheetByIndex($index) {
		return (isset($this->sheet[$index])) ? $this->sheet[$index] : false;
	}
	
	
	/**
	 *-------------------------------------------------------------------------------
	 * Redéfinition de méthodes
	 *-------------------------------------------------------------------------------
	 */
	
	/**
	 * Fonction qui est appelée juste avant de sauvegarder le fichier XML
	 *
	 * @access 	protected
	 * @return 	boolean									Doit retourner true pour faire la sauvegarde
	 */
	protected function _beforeSave() {
		$styles = array(
			'col' => array(), 'colused' => array(),
			'row' => array(), 'rowused' => array(),
			'cel' => array(), 'celused' => array(),
			'tab' => array(), 'tabused' => array()
		);
		$sheetsCols = array();
		$fonts = array();
		$maxCol = 0;
		$colDef = 'co1';
		// 1er parsage, récupération des styles, nb de lignes max et nb de colonnes max
		foreach ($this->sheets as $key => $sheet){
			$cells = $sheet->getCells();
			$cols = array();
			foreach ($cells as $row => $obj) {
				foreach ($obj as $col => $cell) {
					$used = $this->_getUsedCellStyles($cell, $styles);
					$cell->setStyleName($used);
					$fonts[] = $cell->getFontFamily();
					if (!isset($cols[$col]) || $cols[$col] == '' || ($used['col'] != $colDef))
						$cols[$col] = $used['col'];
				}
			}
			if ($maxCol < $sheet->getNbColsMax()) {
				$maxCol = $sheet->getNbColsMax();
			}
			$sheetsCols[$key] = Fonction::array_count_followed_values($cols, $colDef, $maxCol);
		}
		$this->_insertFontFamilies($fonts);
		$this->_insertStyles($styles);
		// 2e parsage, insertion des cellules
		foreach ($this->sheets as $key => $sheet){
			$cells = $sheet->getCells();
			foreach ($sheetsCols[$key] as $col) {
				$tbCol = $this->_addTableElement('table-column', null, $sheet->getXML());
				$tbCol->setAttribute('table:style-name', $col['value']);
				if ($col['nb'] > 1)
					$tbCol->setAttribute('table:number-columns-repeated', $col['nb']);
			}
			$rowCourant = 0;
			ksort($cells);
			foreach ($cells as $row => $obj) {
				ksort($obj);
				$cell = current($obj);
				$st = $cell->getStyleName();
				if ($rowCourant < $row - 1) {
					$tbRow = $this->_addTableElement('table-row', null, $sheet->getXML());
					$tbRow->setAttribute('table:number-rows-repeated', ($row - $rowCourant - 1));
					$tbCell = $this->_addTableElement('table-cell', null, $tbRow);
					$tbCell->setAttribute('table:style-name', 'ce1');
				}
				$tbRow = $this->_addTableElement('table-row', null, $sheet->getXML());
				$tbRow->setAttribute('table:style-name', $st['row']);
				$colCourant = 0;
				foreach ($obj as $col => $cell) {
					$st = $cell->getStyleName();
					// On regarde s'il y a des cellules vides entre deux
					if ($colCourant < $col - 1) {
						$tbCell = $this->_addTableElement('table-cell', null, $tbRow);
						$tbCell->setAttribute('table:number-columns-repeated', ($col - $colCourant - 1));
					}
					// On ajoute la cellule de contenu
					$tbCell = $this->_addTableElement('table-cell', null, $tbRow);
					$tbCell->setAttribute('table:style-name', $st['cel']);
					if ($cell->getFormula() != '') {
						$tbCell->setAttribute('table:formula', 'oooc:'.$cell->getFormula());
					}
					if ($cell->getSpannedRows() > 1) {
						$tbCell->setAttribute('table:number-rows-spanned', $cell->getSpannedRows());
					}
					if ($cell->getSpannedCols() > 1) {
						$tbCell->setAttribute('table:number-columns-spanned', $cell->getSpannedCols());
					}
					if ($cell->getType() == 'float') {
						$tbCell->setAttribute('office:value-type', $cell->getType());
						$tbCell->setAttribute('office:value', $cell->getContent());
					}
					if ($cell->getContent() != '') {
						$content = $this->_addTextElement('p', $cell->getContent(), $tbCell);
					}
					$colCourant = $col;
				}
				$rowCourant = $row;
			}		
		}
		return true;		
	}
		
	
	/**
	 *-------------------------------------------------------------------------------
	 * Méthodes privées
	 *-------------------------------------------------------------------------------
	 */
	
	/**
	 * Fonction qui insère les noeuds des fonts
	 *
	 * @access 	protected
	 * @param 	array			$fonts					Les divers noms des fonts voulues
	 * @return 	void
	 */
	protected function _insertFontFamilies($fonts) {
		$fontFace = $this->xpath->query('//office:font-face-decls')->item(0);
		$fonts = array_unique($fonts);
		foreach ($fonts as $font) {
			$style = $this->_addStyleElement('font-face', '', $fontFace);
			$style->setAttribute('style:name', $font);
			$style->setAttribute('svg:font-family', $font);
		}
	}
	
	/**
	 * Fonction qui insère les neuds des syles
	 *
	 * @access 	protected
	 * @param 	array			$styles					Le tableau des styles à insérer
	 * @return	void
	 */
	protected function _insertStyles($styles) {
		$automaticStyles = $this->xpath->query('//office:automatic-styles')->item(0);
		foreach ($styles['cel'] as $elem) {
			$t = null; $s = null; $p = null;
			$style = $this->_addStyleElement('style', null, $automaticStyles);
			$style->setAttribute('style:name', $elem['name']);
			$style->setAttribute('style:family', 'table-cell');
			$style->setAttribute('style:parent-style-name', 'Defaut');
			if ($elem['backgroundColor'] != '') {
				$s = (!$s) ? $this->_addStyleElement('table-cell-properties', null, $style) : $s;
				$s->setAttribute('fo:background-color', $elem['backgroundColor']);
			}
			if ($elem['borderTop'] != '') {
				$s = (!$s) ? $this->_addStyleElement('table-cell-properties', null, $style) : $s;
				$s->setAttribute('fo:border-top', $elem['borderTop']);
			}
			if ($elem['borderLeft'] != '') {
				$s = (!$s) ? $this->_addStyleElement('table-cell-properties', null, $style) : $s;
				$s->setAttribute('fo:border-left', $elem['borderLeft']);
			}
			if ($elem['borderBottom'] != '') {
				$s = (!$s) ? $this->_addStyleElement('table-cell-properties', null, $style) : $s;
				$s->setAttribute('fo:border-bottom', $elem['borderBottom']);
			}
			if ($elem['borderRight'] != '') {
				$s = (!$s) ? $this->_addStyleElement('table-cell-properties', null, $style) : $s;
				$s->setAttribute('fo:border-right', $elem['borderRight']);
			}
			if ($elem['fontStyle'] != '') {
				$t = (!$t) ? $this->_addStyleElement('text-properties', null, $style) : $t;
				$t->setAttribute('fo:font-style', $elem['fontStyle']);
			}
			if ($elem['fontSize'] != '') {
				$t = (!$t) ? $this->_addStyleElement('text-properties', null, $style) : $t;
				$t->setAttribute('fo:font-size', $elem['fontSize']);
			}
			if ($elem['fontFamily'] != '') {
				$t = (!$t) ? $this->_addStyleElement('text-properties', null, $style) : $t;
				$t->setAttribute('style:font-name', $elem['fontFamily']);
			}
			if ($elem['fontWeight'] != '') {
				$t = (!$t) ? $this->_addStyleElement('text-properties', null, $style) : $t;
				$t->setAttribute('fo:font-weight', $elem['fontWeight']);
			}
			if ($elem['color'] != '') {
				$t = (!$t) ? $this->_addStyleElement('text-properties', null, $style) : $t;
				$t->setAttribute('fo:color', $elem['color']);
			}
			if ($elem['textAlign'] != '') {
				$s = (!$s) ? $this->_addStyleElement('table-cell-properties', null, $style) : $s;
				$s->setAttribute('style:text-align-source', 'fix');
				$s->setAttribute('style:repeat-content', 'false');
				$p = (!$p) ? $this->_addStyleElement('paragraph-properties', null, $style) : $p;
				$p->setAttribute('fo:text-align', $elem['textAlign']);
			}
			if ($elem['verticalAlign'] != '') {
				$s = (!$s) ? $this->_addStyleElement('table-cell-properties', null, $style) : $s;
				$s->setAttribute('style:vertical-align', $elem['verticalAlign']);
			}
		}
		foreach ($styles['col'] as $elem) {
			$style = $this->_addStyleElement('style', null, $automaticStyles);
			$style->setAttribute('style:name', $elem['name']);
			$style->setAttribute('style:family', 'table-column');
			$tbCol = $this->_addStyleElement('table-column-properties', null, $style);
			if ($elem['width']) {
				$tbCol->setAttribute('fo:break-before', 'auto');
				$tbCol->setAttribute('style:column-width', $elem['width']);
			}
		}
		foreach ($styles['row'] as $elem) {
			$style = $this->_addStyleElement('style', null, $automaticStyles);
			$style->setAttribute('style:name', $elem['name']);
			$style->setAttribute('style:family', 'table-row');
			$tbRow = $this->_addStyleElement('table-row-properties', null, $style);
			if ($elem['height']) {
				$tbRow->setAttribute('style:row-height', $elem['height']);
				$tbRow->setAttribute('fo:break-before', 'auto');
				$tbRow->setAttribute('style:use-optimal-row-height', 'false');
			}
		}
	}
	
	/**
	 * Fonction qui check quels sont les styles à insérer
	 *
	 * @access 	protected
	 * @param 	object			$cell					L'objet TableCell
	 * @param 	array			$styles					Le tableau des styles passé en référence
	 * @return	array									Le nom des styles utilisés pour la cellule
	 */
	protected function _getUsedCellStyles($cell, &$styles) {
		// Styles relatifs aux cellules (+2 parce qu'on commence au style ce2)
		$str = '';
		$str .= ($cell->getBackgroundColor()) ? $cell->getBackgroundColor() : '';
		$str .= ($cell->getBorderBottom()) ? $cell->getBorderBottom() : '';
		$str .= ($cell->getBorderLeft()) ? $cell->getBorderLeft() : '';
		$str .= ($cell->getBorderRight()) ? $cell->getBorderRight() : '';
		$str .= ($cell->getBorderTop()) ? $cell->getBorderTop() : '';
		$str .= ($cell->getFontStyle()) ? $cell->getFontStyle() : '';
		$str .= ($cell->getFontSize()) ? $cell->getFontSize() : '';
		$str .= ($cell->getFontFamily()) ? $cell->getFontFamily() : '';
		$str .= ($cell->getFontWeight()) ? $cell->getFontWeight() : '';
		$str .= ($cell->getTextAlign()) ? $cell->getTextAlign() : '';
		$str .= ($cell->getVerticalAlign()) ? $cell->getVerticalAlign() : '';
		$str .= ($cell->getColor()) ? $cell->getColor() : '';
		if (!in_array($str, $styles['celused']) && $str != '') {
			$used['cel'] = 'ce'.(count($styles['cel']) + 2);
			$styles['celused'][] = $str;
			$styles['cel'][] = array(
				'name' => $used['cel'],
				'backgroundColor' => $cell->getBackgroundColor(),
				'borderBottom' => $cell->getBorderBottom(),
				'borderLeft' => $cell->getBorderLeft(),
				'borderRight' => $cell->getBorderRight(),
				'borderTop' => $cell->getBorderTop(),
				'color' => $cell->getColor(),
				'fontStyle' => $cell->getFontStyle(),
				'fontSize' => $cell->getFontSize(),
				'fontFamily' => $cell->getFontFamily(),
				'fontWeight' => $cell->getFontWeight(),
				'textAlign' => $cell->getTextAlign(),
				'verticalAlign' => $cell->getVerticalAlign()
			);
		} else {
			$used['cel'] = ($str != '') ? 'ce'.(array_search($str, $styles['celused']) + 2) : 'ce1';
		}
		// Styles relatifs aux lignes (+2 parce qu'on commence au style ro2)
		$str = '';
		$str .= ($cell->getHeight()) ? $cell->getHeight() :	'';
		if (!in_array($str, $styles['rowused']) && $str != '') {
			$used['row'] = 'ro'.(count($styles['row']) + 2);
			$styles['rowused'][] = $str;
			$styles['row'][] = array(
				'name' => $used['row'],
				'height' => $cell->getHeight()
			);
		} else {
			$used['row'] = ($str != '') ? 'ro'.(array_search($str, $styles['rowused']) + 2) : 'ro1';
		}
		// Styles relatifs aux colonnes (+2 parce qu'on commence au style co2)
		$str = '';
		$str .= ($cell->getWidth()) ? $cell->getWidth() : '';
		if (!in_array($str, $styles['colused']) && $str != '') {
			$used['col'] = 'co'.(count($styles['col']) + 2);
			$styles['colused'][] = $str;
			$styles['col'][] = array(
				'name' => $used['col'],
				'width' => $cell->getWidth()
			);
		} else {
			$used['col'] = ($str != '') ? 'co'.(array_search($str, $styles['colused']) + 2) : 'co1';
		}
		return $used;
	}
	
	/**
	 * Fonction qui ajoute un élément signé comme étant un élément table
	 *
	 * @access 	protected
	 * @param 	string			$element				Le nom de l'élément
	 * @param 	string			$str					La valeur de l'élément
	 * @param 	object			$parent					Le DOMElement parent de celui qu'on créé
	 * @return 	object									Le DOMElement créé
	 */
	protected function _addTableElement($element, $str = null, $parent = null) {
		try {
			return $this->_addElement('table', $element, $str, $parent);
		} catch (Exception $e) {
			throw $e;	
		}
	}
	
	/**
	 * Fonction qui ajoute un élément signé comme étant un élément style
	 *
	 * @access 	protected
	 * @param 	string			$element				Le nom de l'élément
	 * @param 	string			$str					La valeur de l'élément
	 * @param 	object			$parent					Le DOMElement parent de celui qu'on créé
	 * @return 	object									Le DOMElement créé
	 */
	protected function _addStyleElement($element, $str = null, $parent = null) {
		try {
			return $this->_addElement('style', $element, $str, $parent);
		} catch (Exception $e) {
			throw $e;	
		}
	}
	
	/**
	 * Fonction qui ajoute un élément signé comme étant un élément text
	 *
	 * @access 	protected
	 * @param 	string			$element				Le nom de l'élément
	 * @param 	string			$str					La valeur de l'élément
	 * @param 	object			$parent					Le DOMElement parent de celui qu'on créé
	 * @return 	object									Le DOMElement créé
	 */
	protected function _addTextElement($element, $str = null, $parent = null) {
		try {
			return $this->_addElement('text', $element, $str, $parent);
		} catch (Exception $e) {
			throw $e;	
		}
	}
	
	/**
	 * Fonction qui ajoute un élément signé comme étant un élément number
	 *
	 * @access 	protected
	 * @param 	string			$element				Le nom de l'élément
	 * @param 	string			$str					La valeur de l'élément
	 * @param 	object			$parent					Le DOMElement parent de celui qu'on créé
	 * @return 	object									Le DOMElement créé
	 */
	protected function _addNumberElement($element, $str = null, $parent = null) {
		try {
			return $this->_addElement('number', $element, $str, $parent);
		} catch (Exception $e) {
			throw $e;	
		}
	}

}

?>
