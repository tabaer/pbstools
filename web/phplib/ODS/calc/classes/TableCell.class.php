<?php

require_once ('XMLElement.abstract.php');
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
 * consentement du programmeur principal (avec, c'est plus sympa, quand même ;) ...)
 */
class TableCell extends XMLElement {
	
	/**
	 *-------------------------------------------------------------------------------
	 * Propriétés
	 *-------------------------------------------------------------------------------
	 */
	
	/**
	 * @access 	protected
	 * @var 	object			$parent					L'objet Table parent
	 */
	protected $parent;
	
	/**
	 * @access 	protected
	 * @var 	string			$type					Le type de contenu de la cellule
	 */
	protected $type;
	
	/**
	 * @access 	protected
	 * @var 	string			$styleName				Le nom du style de la cellule et de la colonne et ligne associée
	 */
	protected $styleName;
	
	/**
	 * @access 	protected
	 * @var 	string			$formula				La description de la formule
	 */
	protected $formula;
	
	/**
	 * @access 	protected
	 * @var 	integer			$posx					La position de la cellule en X (colonne)
	 */
	protected $posx;
	
	/**
	 * @access 	protected
	 * @var 	integer			$posy					La position de la cellule en Y (ligne)
	 */
	protected $posy;
	
	/**
	 * @access 	protected
	 * @var 	integer			$width					La largeur de la cellule
	 */
	protected $width;
	
	/**
	 * @access 	protected
	 * @var 	integer			$height					La hauteur de la cellule
	 */
	protected $height;
	
	/**
	 * @access 	protected
	 * @var 	string			$contenu				Le contenu de la cellule
	 */
	protected $contenu;
	
	/**
	 * @access 	protected
	 * @var 	string			$backgroundColor		La couleur de fond de la cellule
	 */
	protected $backgroundColor;
	
	/**
	 * @access 	protected
	 * @var 	string			$borderTop				La définition de la bordure du haut
	 */
	protected $borderTop;
	
	/**
	 * @access 	protected
	 * @var 	string			$borderLeft				La définition de la bordure de gauche
	 */
	protected $borderLeft;
	
	/**
	 * @access 	protected
	 * @var 	string			$borderBottom			La définition de la bordure du bas
	 */
	protected $borderBottom;
	
	/**
	 * @access 	protected
	 * @var 	string			$borderRight			La définition de la bordure de droite
	 */
	protected $borderRight;
	
	/**
	 * @access 	protected
	 * @var 	string			$fontWeight				Le gras de la police
	 */
	protected $fontWeight;
	
	/**
	 * @access 	protected
	 * @var 	string			$fontStyle				Le style de la police (italique ou autre)
	 */
	protected $fontStyle;
	
	/**
	 * @access 	protected
	 * @var 	string			$fontSize				La taille de la police
	 */
	protected $fontSize;
	
	/**
	 * @access 	protected
	 * @var 	string			$fontFamily				La famille de la police
	 */
	protected $fontFamily;
	
	/**
	 * @access 	protected
	 * @var 	string			$textAlign				L'alignement du texte
	 */
	protected $textAlign;
	
	/**
	 * @access 	protected
	 * @var 	string			$verticalAlign			L'alignement vertical du texte
	 */
	protected $verticalAlign;
	
	/**
	 * @access 	protected
	 * @var 	string			$color					La couleur du texte
	 */
	protected $color;
	
	/**
	 * @access 	protected
	 * @var 	integer			$spannedRows			Le nombre de lignes fusionnées depuis la cellule
	 */
	protected $spannedRows;
	
	/**
	 * @access 	protected
	 * @var 	integer			$spannedCols			Le nombre de colonnes fusionnées depuis la cellule
	 */
	protected $spannedCols;
	
	
	/**
	 *-------------------------------------------------------------------------------
	 * Constructeur
	 *-------------------------------------------------------------------------------
	 */

	/**
	 * Constructeur qui load un TableCell
	 *
	 * @access 	public
	 * @param 	object			$parent					L'objet Table parent
	 * @param 	object			$core					Objet DOM du fichier XML
	 * @param 	object			$xpath					Objet DOMXPath du fichier XML
	 * @return 	object									L'objet classe
	 */
	public function __construct($parent = '', $core = '', $xpath = '') {
		if ($parent && $core && $xpath) {
			$this->load('table-cell', $core, $xpath);
			$this->root = $core;
			$this->parent = $parent;
		}
	}
	
	
	/**
	 *-------------------------------------------------------------------------------
	 * Méthodes statiques
	 *-------------------------------------------------------------------------------
	 */
	
	/**
	 * Fonction qui calcule le nombre de ligne entre 2 cellules
	 *
	 * @access 	static
	 * @param 	object			$cell1					Une cellule TableCell
	 * @param 	object			$cell2					Une cellule TableCell
	 * @return 	integer									Le nombre de lignes entre les 2 cellules
	 */
	static function getNbRows($cell1, $cell2) {
		$nbRows = $cell1->getY() - $cell2->getY();
		if ($nbRows < 0)
			$nbRows *= -1;
		return $nbRows;
	}
	
	/**
	 * Fonction qui calcule le nombre de colonnes entre 2 cellules
	 *
	 * @access 	static
	 * @param 	object			$cell1					Une cellule TableCell
	 * @param 	object			$cell2					Une cellule TableCell
	 * @return 	integer									Le nombre de colonnes entre les 2 cellules
	 */
	static function getNbCols($cell1, $cell2) {
		$nbCols = $cell1->getX() - $cell2->getX();
		if ($nbCols < 0)
			$nbCols *= -1;
		return $nbCols;
	}
	
	
	/**
	 *-------------------------------------------------------------------------------
	 * Méthodes publiques de type et de formules
	 *-------------------------------------------------------------------------------
	 */
	
	/**
	 * Fonction qui set le type de contenu. S'il s'agit d'une formule, $this->formula sera modifié en conséquence
	 *
	 * @access 	public
	 * @return 	void
	 */
	public function setType() {
		$contenu = $this->contenu;
		$type = 'string';
		if (is_float($contenu) || is_numeric($contenu) || is_int($contenu))
			$type = 'float';
		// Check s'il s'agit d'une formule
		if (strpos($contenu, '=SUM') !== false) {
			$type = $this->formulaSUM($contenu);
		}
		$this->type = $type;
		return $type;
	}
	
	/**
	 * Fonction qui set comme contenu de cellule une somme (par exemple A1:B6;C10)
	 *
	 * @access 	public
	 * @param 	string			$formula				La formule (par exemple 'A1:B6;C10')
	 * @return 	void
	 */
	public function setFormulaSUM($formula) {
		$f = (strpos($formula, '=SUM(') === false) ? '=SUM('.$formula.')' : $formula;
		// On sauve la formule
		$f = str_replace('(', '([.', $f); $f = str_replace(')', '])', $f);
		$f = str_replace(';', '];[.', $f); $f = str_replace(':', ':.', $f);
		// On en extrait les données
		$formula = ltrim($formula, '=SUM(');
		$formula = rtrim($formula, ')');
		$l = Fonction::getLetters(true);
		// On calcule le résultat
		$somme = 0;
		$blocs = explode(';', $formula);
		$cellules = $this->parent->getCells();
		foreach ($blocs as $bloc) {
			$cells = explode(':', $bloc);
			$tab = array();
			foreach ($cells as $cell) {
				$cols = ''; $row = '';
				for ($i = 0; $i < strlen($cell); $i++) {
					if (is_int($cell[$i]) || is_float($cell[$i]) || is_numeric($cell[$i])) $row .= $cell[$i];
					else $cols .= $cell[$i];
				}
				// On chope la colonne en numérique
				$col = array_search(strtolower($cols), $l);
				$tab[] = $cellules[$row][$col];
			}
			// S'il n'y a qu'un élément, on additionne son contenu
			if (count($tab) == 1) 
				$somme += $tab[0]->getContent() * 1;	
			else 
				$cells = $this->parent->getRangeCells($tab[0], $tab[1], '_setSum', '', $somme);	
		}
		// On set les propriétés
		$this->formula = $f;
		$this->contenu = $somme;
		$this->type = 'float';
		return $this->type;
	}
	
	
	/**
	 *-------------------------------------------------------------------------------
	 * Méthodes publiques
	 *-------------------------------------------------------------------------------
	 */
	
	/**
	 * Fonction qui set la position de la cellule
	 *
	 * @access 	public
	 * @param 	integer			$x						La position de la cellule en X (colonne)
	 * @param 	integer			$y						La position de la cellule en Y (ligne)
	 * @return 	void
	 */
	public function setPos($x, $y) {
		$this->setPosX($x);	
		$this->setPosY($y);
	}
	
	/**
	 * Fonction qui set les dimensions de la cellule
	 *
	 * @access 	public
	 * @param 	integer			$width					La largeur de la cellule
	 * @param 	integer			$height					La hauteur de la cellule
	 * @return 	void
	 */
	public function setDimensions($width, $height) {
		$this->setwidth($x);	
		$this->setheight($y);
	}
	
	/**
	 * Fonction qui set les bordures de la cellule (0.002cm solid #000000)
	 *
	 * @access 	public
	 * @param 	string			$data					La définition de toutes les bordures
	 * @param 	string			$dataLR					La définition des bordures de droite et gauche
	 * @return 	void
	 */
	public function setBorder($data, $dataLR = '') {
		$this->setBorderTop($data);	
		$this->setBorderBottom($data);
		$borderLR = ($dataLR != '') ? $dataLR : $data;
		$this->setBorderLeft($borderLR);	
		$this->setBorderRight($borderLR);	
	}
	
	/**
	 * Fonction qui set le gras de la police à "bold"
	 *
	 * @access 	public
	 * @return 	void
	 */
	public function setFontBold() {
		$this->setFontWeight('bold');
	}
	
	/**
	 * Fonction qui set le style de la police à "italic"
	 *
	 * @access 	public
	 * @return 	void
	 */
	public function setFontItalic() {
		$this->setFontStyle('italic');
	}
	
	/**
	 * Fonction qui set l'alignement du texte à "centré"
	 *
	 * @access 	public
	 * @return 	void
	 */
	public function setTextCenter() {
		$this->setTextAlign('center');
	}
	
	/**
	 * Fonction qui retourne le nom de la cellule
	 *
	 * @access 	public
	 * @return 	string									Le nom de la cellule
	 */
	public function getName() {
		return 'ce'.$this->getX().$this->getY();
	}
	
	/**
	 * Fonction qui retourne le nom de la cellule
	 *
	 * @access 	public
	 * @param 	string			$type					'col' ou 'row'
	 * @return 	string									Le nom de la cellule
	 */
	public function getFreeName($type) {
		switch ($type) {
			case 'col':
				$retour = 'ce'.($this->getX() - 1).$this->getY();
				break;
			case 'row':
				$retour = 'ce'.$this->getX().($this->getY() - 1);
				break;
			default:
				$retour = 'ce'.$this->getX().$this->getY();
		}
		return $retour;
	}
	
	
	/**
	 *-------------------------------------------------------------------------------
	 * Méthodes getters et setters
	 *-------------------------------------------------------------------------------
	 */
	
	/**
	 * Fonction qui set le contenu de la cellule
	 *
	 * @access 	public
	 * @param 	string			$contenu				Le contenu de la cellule
	 * @param 	boolean			$set_type				True pour setter le type de contenu
	 * @return 	void
	 */
	public function setContent($contenu, $set_type = true) {
		$this->contenu = $contenu;
		if ($set_type)
			$this->type = $this->setType();
	}
	
	/**
	 * Fonction qui set le nom du style et de la colonne et ligne associée
	 *
	 * @access 	public
	 * @param 	array			$style					Le nom des styles de la cellule et de la colonne et ligne associée
	 * @return 	void
	 */
	public function setStyleName($style) {
		$this->styleName = $style;
	}
	
	/**
	 * Fonction qui set la couleur de fond de la cellule
	 *
	 * @access 	public
	 * @param 	string			$couleur				La couleur de fond
	 * @return 	void
	 */
	public function setBackgroundColor($couleur) {
		$this->backgroundColor = $couleur;
	}
	
	/**
	 * Fonction qui set la bordure du haut de la cellule (1px solid #000000)
	 *
	 * @access 	public
	 * @param 	string			$data					La définition de la bordure du haut
	 * @return 	void
	 */
	public function setBorderTop($data) {
		$this->borderTop = $data;	
	}
	
	/**
	 * Fonction qui set la bordure de gauche de la cellule (1px solid #000000)
	 *
	 * @access 	public
	 * @param 	string			$data					La définition de la bordure de gauche
	 * @return 	void
	 */
	public function setBorderLeft($data) {
		$this->borderLeft = $data;	
	}
	
	/**
	 * Fonction qui set la bordure du bas de la cellule (1px solid #000000)
	 *
	 * @access 	public
	 * @param 	string			$data					La définition de la bordure du bas
	 * @return 	void
	 */
	public function setBorderBottom($data) {
		$this->borderBottom = $data;	
	}
	
	/**
	 * Fonction qui set la bordure de droite de la cellule (1px solid #000000)
	 *
	 * @access 	public
	 * @param 	string			$data					La définition de la bordure de droite
	 * @return 	void
	 */
	public function setBorderRight($data) {
		$this->borderRight = $data;	
	}
	
	/**
	 * Fonction qui set la position de la cellule en X (colonne)
	 *
	 * @access 	public
	 * @param 	integer			$x						La position de la cellule en X (colonne)
	 * @return 	void
	 */
	public function setPosX($x) {
		$this->posx = $x;
	}
	
	/**
	 * Fonction qui set la position de la cellule en Y (ligne)
	 *
	 * @access 	public
	 * @param 	integer			$y						La position de la cellule en Y (ligne)
	 * @return 	void
	 */
	public function setPosY($y) {
		$this->posy = $y;	
	}
	
	/**
	 * Fonction qui set la hauteur de la cellule
	 *
	 * @access 	public
	 * @param 	integer			$height					La hauteur de la cellule
	 * @return 	void
	 */
	public function setHeight($height) {
		$this->height = $height;
	}
	
	/**
	 * Fonction qui set la largeur de la cellule
	 *
	 * @access 	public
	 * @param 	integer			$width					La largeur de la cellule
	 * @return 	void
	 */
	public function setWidth($width) {
		$this->width = $width;
	}
	
	/**
	 * Fonction qui set le gras de la police
	 *
	 * @access 	public
	 * @param 	string			$weight					Le gras de la police
	 * @return 	void
	 */
	public function setFontWeight($weight) {
		$this->fontWeight = $weight;
	}
	
	/**
	 * Fonction qui set le style de la police
	 *
	 * @access 	public
	 * @param 	string			$style					Le style de la police
	 * @return 	void
	 */
	public function setFontStyle($style) {
		$this->fontStyle = $style;
	}
	
	/**
	 * Fonction qui set la taille de la police
	 *
	 * @access 	public
	 * @param 	string			$size					La taille de la police
	 * @return 	void
	 */
	public function setFontSize($size) {
		$this->fontSize = $size;
	}
	
	/**
	 * Fonction qui set la famille de la police
	 *
	 * @access 	public
	 * @param 	string			$family					La famille de la police
	 * @return 	void
	 */
	public function setFontFamily($family) {
		$this->fontFamily = $family;
	}
	
	/**
	 * Fonction qui set l'alignement du texte
	 *
	 * @access 	public
	 * @param 	string			$align					L'alignement du texte
	 * @param 	boolean			$left_right				True pour dire que le texte se lit de gauche à droite
	 * @return 	void
	 */
	public function setTextAlign($align, $left_right = true) {
		if ($left_right) {
			if ($align == 'left') $align = 'start';
			if ($align == 'right') $align = 'end';
		} else {
			if ($align == 'right') $align = 'start';
			if ($align == 'left') $align = 'end';
		}
		$this->textAlign = $align;
	}
	
	/**
	 * Fonction qui set l'alignement vertical du texte
	 *
	 * @access 	public
	 * @param 	string			$align					L'alignement vertical du texte
	 * @return 	void
	 */
	public function setVerticalAlign($align) {
		if ($align == 'center') $align = 'middle';
		$this->verticalAlign = $align;
	}
	
	/**
	 * Fonction qui set la couleur du texte
	 *
	 * @access 	public
	 * @param 	string			$coul					La couleur du texte
	 * @return 	void
	 */
	public function setColor($coul) {
		$this->color = $coul;
	}
	
	/**
	 * Fonction qui set le nombre de lignes à fusionner depuis la cellule (cellule courante comprise)
	 *
	 * @access 	public
	 * @param 	integer			$spannedRows			Le nombre de lignes à fusionner depuis la cellule
	 * @return 	void
	 */
	public function setSpannedRows($spannedRows) {
		$this->spannedRows = $spannedRows;
	}
	
	/**
	 * Fonction qui set le nombre de lignes à fusionner depuis la cellule (cellule courante comprise)
	 *
	 * @access 	public
	 * @param 	integer			$spannedCols			Le nombre de colonnes à fusionner depuis la cellule
	 * @return 	void
	 */
	public function setSpannedCols($spannedCols) {
		$this->spannedCols = $spannedCols;
	}
	
	/**
	 * Fonction qui retourne le type de contenu de la cellule
	 *
	 * @access 	public
	 * @return 	string									Le type de contenu de la cellule
	 */
	public function getType() {
		return $this->type;	
	}
	
	/**
	 * Fonction qui retourne le nom du style utilisé et de la colonne et ligne associée
	 *
	 * @access 	public
	 * @return 	string									Le nom du style de la cellule et de la colonne et ligne associée
	 */
	public function getStyleName() {
		return $this->styleName;	
	}
	
	/**
	 * Fonction qui retourne le contenu de la cellule
	 *
	 * @access 	public
	 * @return 	string									Le contenu de la cellule
	 */
	public function getContent() {
		return $this->contenu;	
	}
	
	/**
	 * Fonction qui set la couleur de fond de la cellule
	 *
	 * @access 	public
	 * @return 	string									La couleur de fond
	 */
	public function getBackgroundColor() {
		return $this->backgroundColor;
	}
	
	/**
	 * Fonction qui récupère la bordure du haut de la cellule
	 *
	 * @access 	public
	 * @return 	void
	 */
	public function getBorderTop() {
		return $this->borderTop;
	}
	
	/**
	 * Fonction qui récupère la bordure de gauche de la cellule
	 *
	 * @access 	public
	 * @return 	void
	 */
	public function getBorderLeft() {
		return $this->borderLeft;
	}
	
	/**
	 * Fonction qui récupère la bordure du bas de la cellule
	 *
	 * @access 	public
	 * @return 	void
	 */
	public function getBorderBottom() {
		return $this->borderBottom;
	}
	
	/**
	 * Fonction qui récupère la bordure de droite de la cellule
	 *
	 * @access 	public
	 * @return 	void
	 */
	public function getBorderRight() {
		return $this->borderRight;
	}
	
	/**
	 * Fonction qui retourne la position de la cellule en X (colonne)
	 *
	 * @access 	public
	 * @return 	integer									La position de la cellule en X (colonne)
	 */
	public function getX() {
		return $this->posx;	
	}
	
	/**
	 * Fonction qui retourne la position de la cellule en Y (ligne)
	 *
	 * @access 	public
	 * @return 	integer									La position de la cellule en Y (ligne)
	 */
	public function getY() {
		return $this->posy;	
	}
	
	/**
	 * Fonction qui retourne la largeur de la cellule
	 *
	 * @access 	public
	 * @return 	integer									La largeur de la cellule
	 */
	public function getWidth() {
		return $this->width;	
	}
	
	/**
	 * Fonction qui retourne la hauteur de la cellule
	 *
	 * @access 	public
	 * @return 	integer									La hauteur de la cellule
	 */
	public function getHeight() {
		return $this->height;	
	}
	
	/**
	 * Fonction qui retourne le gras de la police
	 *
	 * @access 	public
	 * @return 	string									Le gras de la police
	 */
	public function getFontWeight() {
		return $this->fontWeight;
	}
	
	/**
	 * Fonction qui retourne le style de la police
	 *
	 * @access 	public
	 * @return 	string									Le style de la police
	 */
	public function getFontStyle() {
		return $this->fontStyle;
	}
	
	/**
	 * Fonction qui retourne la taille de la police
	 *
	 * @access 	public
	 * @return 	string									La taille de la police
	 */
	public function getFontSize() {
		return $this->fontSize;
	}
	
	/**
	 * Fonction qui retourne la famille de la police
	 *
	 * @access 	public
	 * @return 	string									La famille de la police
	 */
	public function getFontFamily() {
		return $this->fontFamily;
	}
	
	/**
	 * Fonction qui retourne l'alignement du texte
	 *
	 * @access 	public
	 * @return 	string									L'alignement du texte
	 */
	public function getTextAlign() {
		return $this->textAlign;
	}
	
	/**
	 * Fonction qui retourne l'alignement vertical du texte
	 *
	 * @access 	public
	 * @return 	string									L'alignement vertical du texte
	 */
	public function getVerticalAlign() {
		return $this->verticalAlign;
	}
	
	/**
	 * Fonction qui retourne la couleur du texte
	 *
	 * @access 	public
	 * @return 	string									La couleur du texte
	 */
	public function getColor() {
		return $this->color;
	}
	
	/**
	 * Fonction qui retourne le nombre de lignes à fusionner depuis la cellule (cellule courante comprise)
	 *
	 * @access 	public
	 * @return 	integer									Le nombre de lignes à fusionner depuis la cellule
	 */
	public function getSpannedRows() {
		return $this->spannedRows;
	}
	
	/**
	 * Fonction qui retourne le nombre de lignes à fusionner depuis la cellule (cellule courante comprise)
	 *
	 * @access 	public
	 * @return 	integer									Le nombre de colonnes à fusionner depuis la cellule
	 */
	public function getSpannedCols() {
		return $this->spannedCols;
	}
	
	/**
	 * Fonction qui retourne la formule de la cellule
	 *
	 * @access 	public
	 * @return 	string									La formule de la cellule
	 */
	public function getFormula() {
		return $this->formula;
	}
	
	
	/**
	 *-------------------------------------------------------------------------------
	 * Méthodes privées
	 *-------------------------------------------------------------------------------
	 */
	
}

?>
