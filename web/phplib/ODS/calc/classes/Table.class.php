<?php

require_once ('XMLElement.abstract.php');
require_once ('Fonction.class.php');
require_once ('TableCell.class.php');

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
class Table extends XMLElement {
	
	/**
	 *-------------------------------------------------------------------------------
	 * Propriétés
	 *-------------------------------------------------------------------------------
	 */
	
	/**
	 * @access 	protected
	 * @var 	string			$sheetName				Le nom de la feuille
	 */
	protected $sheetName;
	
	/**
	 * @access 	protected
	 * @var 	object			$obj					L'objet DOM de la feuille
	 */
	protected $obj;
	
	/**
	 * @access 	protected
	 * @var 	array			$cells					Les cellules remplies de quelque chose
	 */
	protected $cells;
	
	/**
	 * @access 	protected
	 * @var 	array			$pictures				Les images insérées
	 */
	protected $pictures;
	
	
	/**
	 *-------------------------------------------------------------------------------
	 * Constructeur
	 *-------------------------------------------------------------------------------
	 */

	/**
	 * Constructeur qui load un TableCell
	 *
	 * @access 	public
	 * @param 	string			$sheet					Le nom de la feuille
	 * @param 	object			$core					Objet DOM du fichier XML
	 * @param 	object			$xpath					Objet DOMXPath du fichier XML
	 * @return 	object									L'objet classe
	 */
	public function __construct($sheet = '', $core = '', $xpath = '') {
		if ($sheet && $core && $xpath) {
			$this->load('calc-table', $core, $xpath);
			$this->cells = array();
			$this->sheetName = $sheet;
			$this->root = $xpath->query('//office:spreadsheet')->item(0);
			$this->obj = $this->_addElement('table', 'table');
			$this->obj->setAttribute('table:name', Fonction::checkAttribute($this->sheetName));
		}
	}
	
	
	/**
	 *-------------------------------------------------------------------------------
	 * Méthodes publiques
	 *-------------------------------------------------------------------------------
	 */
	
	/**
	 * Méthode qui ajoute une image dans la feuille
	 *
	 * @access 	public
	 * @param 	string
	 * @return 	void
	 */
	public function addPicture($img, $bottomRightCellName, $endx, $endy, $zindex) {
		
		$this->pictures[] = $pict;	
	}
	
	/**
	 * Fonction qui retourne la cellule désirée
	 *
	 * @access 	public
	 * @param 	integer			$x						La position de la cellule en X (colonne)
	 * @param 	integer			$y						La position de la cellule en Y (ligne)
	 * @return 	object									La cellule TableCell
	 */
	public function getCell($x, $y) {
		if (is_object($x) || is_object($y)) {
			return false;	
		}
		if (isset($this->cells[$y])){
			if (isset($this->cells[$y][$x]))
				return $this->cells[$y][$x];	
		}
		// Si on arrive là, c'est que la cellule n'existe pas. Donc on la créé
		$cell = new TableCell($this, $this->core, $this->xpath);
		$cell->setPos($x, $y);
		$this->cells[$y][$x] = $cell;		
		return $cell;
	}
	
	/**
	 * Fonction qui retourne un range de cellules TableCell
	 *
	 * @access 	public
	 * @param 	integer|object	$x						La position de la cellule en X (colonne) ou un objet TableCell
	 * @param 	integer|object	$y						La position de la cellule en Y (ligne) ou un objet TableCell
	 * @param 	string			$func					Le nom d'une foncion à exécuter
	 * @param 	string|array	$data					La valeur qu'on veut utiliser dans la fonction
	 * @param 	string|array	$dataRef				La valeur qu'on veut modifier (passé en référence)
	 * @return 	array									Un tableau d'objets TableCell
	 */
	public function getRangeCells($x, $y, $func = '', $data = '', &$dataRef = '') {
		$cells = array();
		if (is_object($x) && is_object($y)) {
			$xdep = $x->getX(); $xfin = $y->getX();
			$ydep = $x->getY(); $yfin = $y->getY();
			for ($yc = $ydep; $yc <= $yfin; $yc++) {
				for ($xc = $xdep; $xc <= $xfin; $xc++) {
					$cell = $this->getCell($xc, $yc);
					$cells[] = $cell;
					if ($func)
						$this->$func($cell, $xc, $yc, $xdep, $ydep, $xfin, $yfin, $data, $dataRef);
				}
			}
		} else {
			$cell = $this->getCell($x, $y);
			$cells[] = $cell;
			if ($func)
				$this->$func($cell, $x, $y, $x, $y, $x, $y, $data, $dataRef);
		}
		return $cells;
	}
	
	/**
	 * Fonction qui retourne le nombre de lignes concernées par les traitements
	 *
	 * @access 	public
	 * @return 	integer									Le nombre de lignes du fichier
	 */
	public function getNbRowsMax() {
		$maximum = 0;
		foreach ($this->cells as $row => $obj) {
			foreach ($obj as $col => $cell) {
				$spanned = $cell->getSpannedRows();
				if ($maximum < $row + $spanned)
					$maximum = $row + $spanned;
			}	
		}
		return $maximum;
	}
	
	/**
	 * Fonction qui retourne le nombre de colonnes concernées par les traitements
	 *
	 * @access 	public
	 * @return 	integer									Le nombre de colonnes du fichier
	 */
	public function getNbColsMax() {
		$maximum = 0;
		foreach ($this->cells as $row => $obj) {
			foreach ($obj as $col => $cell) {
				$spanned = $cell->getSpannedCols();
				if ($maximum < $col + $spanned)
					$maximum = $col + $spanned;
			}	
		}
		return $maximum;
	}
	
	/**
	 * Fonction qui retourne le nom de la colonne en fonction de son numéro (1 = A)
	 *
	 * @access 	public
	 * @param 	integer			$col					Le numéro de la colonne (1 = A)
	 * @return 	string									La lettre correspondante à la colonne
	 */
	public function getColumnName($col) {
		$lettres = Fonction::getLetters(true);
		if (isset($lettres[$col]))
			return $lettres[$col];
		else 
			return 0;	
	}
	
	/**
	 * Fonction insère une fonction de somme dans une cellule
	 *
	 * @access 	public
	 * @param 	string			$contenu				Le contenu de la cellule
	 * @param 	integer			$x						La position de la cellule en X (colonne)
	 * @param 	integer			$y						La position de la cellule en Y (ligne)
	 * @return 	void
	 */
	public function setFormulaSUM($contenu, $x, $y) {
		$cell = $this->getCell($x, $y);
		$this->cells[$y][$x]->setFormulaSUM($contenu);
	}
	
	/**
	 * Fonction raccourci qui ajoute un contenu pour une ou plusieurs cellules données
	 *
	 * @access 	public
	 * @param 	string			$contenu				Le contenu de la cellule
	 * @param 	integer|object	$x						La position de la cellule en X (colonne) ou un objet TableCell
	 * @param 	integer|object	$y						La position de la cellule en Y (ligne) ou un objet TableCell
	 * @return 	void
	 */
	public function setCellContent($contenu, $x, $y) {
		$cells = $this->getRangeCells($x, $y);
		foreach ($cells as $cell) {
			$cell->setContent($contenu);
		}
	}
	
	/**
	 * Fonction raccourci qui ajoute un contenu pour une ou plusieurs cellules données
	 *
	 * @access 	public
	 * @param 	string			$couleur				La couleur de fond de la cellule
	 * @param 	integer|object	$x						La position de la cellule en X (colonne) ou un objet TableCell
	 * @param 	integer|object	$y						La position de la cellule en Y (ligne) ou un objet TableCell
	 * @return 	void
	 */
	public function setCellBackgroundColor($couleur, $x, $y) {
		$cells = $this->getRangeCells($x, $y);
		foreach ($cells as $cell) {
			$cell->setBackgroundColor($couleur);
		}
	}
	
	/**
	 * Fonction qui set la couleur de la police pour une ou plusieurs cellules données
	 *
	 * @access 	public
	 * @param 	string			$data					La couleur de la police
	 * @param 	integer|object	$x						La position de la cellule en X (colonne) ou un objet TableCell
	 * @param 	integer|object	$y						La position de la cellule en Y (ligne) ou un objet TableCell
	 * @return 	void
	 */
	public function setCellColor($data, $x, $y) {
		$cells = $this->getRangeCells($x, $y);
		foreach ($cells as $cell) {
			$cell->setColor($data);
		}
	}
	
	/**
	 * Fonction qui set l'épaisseur de la font
	 *
	 * @access 	public
	 * @param 	string			$data					L'épaisseur de la font
	 * @param 	integer|object	$x						La position de la cellule en X (colonne) ou un objet TableCell
	 * @param 	integer|object	$y						La position de la cellule en Y (ligne) ou un objet TableCell
	 * @return 	void
	 */
	public function setCellFontWeight($data, $x, $y) {
		$cells = $this->getRangeCells($x, $y);
		foreach ($cells as $cell) {
			$cell->setFontWeight($data);
		}
	}
	
	/**
	 * Fonction qui set la largeur de cellule
	 *
	 * @access 	public
	 * @param 	string			$data					La définition de la largeur de cellule
	 * @param 	integer|object	$x						La position de la cellule en X (colonne) ou un objet TableCell
	 * @param 	integer|object	$y						La position de la cellule en Y (ligne) ou un objet TableCell
	 * @return 	void
	 */
	public function setCellWidth($data, $x, $y) {
		$cells = $this->getRangeCells($x, $y);
		foreach ($cells as $cell) {
			$cell->setWidth($data);
		}
	}
	
	/**
	 * Fonction qui set la hauteur de cellule
	 *
	 * @access 	public
	 * @param 	string			$data					La définition de la hauteur de cellule
	 * @param 	integer|object	$x						La position de la cellule en X (colonne) ou un objet TableCell
	 * @param 	integer|object	$y						La position de la cellule en Y (ligne) ou un objet TableCell
	 * @return 	void
	 */
	public function setCellHeight($data, $x, $y) {
		$cells = $this->getRangeCells($x, $y);
		foreach ($cells as $cell) {
			$cell->setHeight($data);
		}
	}
	
	/**
	 * Fonction qui set les bordures de(s) la cellule(s) (0.002cm solid #000000)
	 *
	 * @access 	public
	 * @param 	string			$data					La définition de toutes les bordures
	 * @param 	integer|object	$x						La position de la cellule en X (colonne) ou un objet TableCell
	 * @param 	integer|object	$y						La position de la cellule en Y (ligne) ou un objet TableCell
	 * @return 	void
	 */
	public function setCellBorder($data, $x, $y) {
		$cells = $this->getRangeCells($x, $y);
		foreach ($cells as $cell) {
			$cell->setBorder($data);
		}
	}
	
	/**
	 * Fonction qui set les bordures du tour de la cellule ou du bloc de cellules (0.002cm solid #000000)
	 *
	 * @access 	public
	 * @param 	string			$data					La définition de toutes les bordures du tour
	 * @param 	integer|object	$x						La position de la cellule en X (colonne) ou un objet TableCell
	 * @param 	integer|object	$y						La position de la cellule en Y (ligne) ou un objet TableCell
	 * @return 	void
	 */
	public function setCellBorderAround($data, $x, $y) {
		$cells = $this->getRangeCells($x, $y, '_setCellBorderAround', $data);
	}
	
	
	/**
	 *-------------------------------------------------------------------------------
	 * Méthodes getters et setters
	 *-------------------------------------------------------------------------------
	 */	
	
	/**
	 * Fonction qui retourne le nom de la feuille
	 *
	 * @access 	public
	 * @return 	string									Le nom de la feuille
	 */
	public function getName() {
		return $this->sheetName;	
	}
	
	/**
	 * Fonction qui retourne les cellules remplies
	 *
	 * Format du tableau : $cells[$col][$row] = object TableCell
	 *
	 * @access 	public
	 * @return 	array									Les cellules remplies
	 */
	public function getCells() {
		return $this->cells;	
	}
	
	/**
	 * Fonction qui retourne le DOM de la feuille
	 *
	 * @access 	public
	 * @return 	array									Le DOM de la feuille
	 */
	public function getXML() {
		return $this->obj;	
	}
	
	/**
	 * Fonction qui retourne les images insérées
	 *
	 * @access 	public
	 * @return 	array									Les images insérées
	 */
	public function getPictures() {
		return $this->pictures;	
	}
	
	
	/**
	 *-------------------------------------------------------------------------------
	 * Méthodes privées
	 *-------------------------------------------------------------------------------
	 */	
	
	/**
	 * Fonction qui est exécutée via getRangeCell pour mettre des bordures au bons endroits
	 *
	 * @access 	protected
	 * @param 	object			$cell					L'objet TableCell
	 * @param 	integer			$x						La position de la cellule en X (colonne)
	 * @param 	integer			$y						La position de la cellule en Y (ligne)
	 * @param 	integer			$xdep					La position de la 1ere cellule en X (coin haut gauche)
	 * @param 	integer			$ydep					La position de la 1ere cellule en Y (coin haut gauche)
	 * @param 	integer			$xfin					La position de la dernière cellule en X (coin bas droite)
	 * @param 	integer			$yfin					La position de la dernière cellule en Y (coin bas droite)
	 * @param 	string|array	$data					La valeur qu'on veut insérer, ajouter
	 * @param 	string|array	$dataRef				La valeur qu'on veut modifier (passé en référence)
	 * @return 	void
	 */
	protected function _setCellBorderAround($cell, $x, $y, $xdep, $ydep, $xfin, $yfin, $data, &$dataRef) {
		if ($y == $ydep)
			$cell->setBorderTop($data);
		if ($x == $xdep)
			$cell->setBorderLeft($data);
		if ($y == $yfin)
			$cell->setBorderBottom($data);
		if ($x == $xfin)
			$cell->setBorderRight($data);
	}
	
	/**
	 * Fonction qui est exécutée via getRangeCell pour sommer des contenus numériques
	 *
	 * @access 	protected
	 * @param 	object			$cell					L'objet TableCell
	 * @param 	integer			$x						La position de la cellule en X (colonne)
	 * @param 	integer			$y						La position de la cellule en Y (ligne)
	 * @param 	integer			$xdep					La position de la 1ere cellule en X (coin haut gauche)
	 * @param 	integer			$ydep					La position de la 1ere cellule en Y (coin haut gauche)
	 * @param 	integer			$xfin					La position de la dernière cellule en X (coin bas droite)
	 * @param 	integer			$yfin					La position de la dernière cellule en Y (coin bas droite)
	 * @param 	string|array	$data					La valeur qu'on veut insérer, ajouter
	 * @param 	string|array	$dataRef				La valeur qu'on veut modifier (passé en référence)
	 * @return 	void
	 */
	protected function _setSum($cell, $x, $y, $xdep, $ydep, $xfin, $yfin, $data, &$dataRef) {
		$dataRef += $cell->getContent() * 1;
	}
	
}

?>
