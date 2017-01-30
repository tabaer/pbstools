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
class Meta extends XMLDocument {
	
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
		$fileName = 'meta.xml';
		$this->load($fileName, $path_save, $path_templates, $format_output, $white_space);
		$this->root = $this->xpath->query('//office:meta')->item(0);
		// Ajout de l'élément "generator" qui spécifie qui a généré le fichier
		$this->setGenerator('PHP-OpenOffice 2 open source script');
	}	
	
	
	/**
	 *-------------------------------------------------------------------------------
	 * Méthodes publiques
	 *-------------------------------------------------------------------------------
	 */
		
	/**
	 * Ajoute un meta de mot-clé
	 *
	 * La string peut contenir plusieurs mot-clé séparés par des espaces. Il peut
	 * également s'agir d'un tableau
	 *
	 * @access 	public
	 * @param 	string|array	$keyword				Un mot clé ou plusieurs dans un tableau
	 * @return 	void
	 */
	public function addKeyWord($keyword) {
		if (is_array($keyword))
			$keywords = implode(' ', $keyword);
		else 
			$keywords = $keyword;
		/*// On vérifie si le noeud de mots-clé est créé ou non
		$keywordNodes = $this->xpath->query('//meta:keywords');
		if ($keywordNodes->length == 0)
			$keywordNode = $this->_addMetaElement('keywords');
		else 
			$keywordNode = $keywordNodes->item(0);
		// On insère tous les mots-clé au noeud de mots-clé
		foreach ($keywords as $word){
			$this->_addMetaElement('keyword', $word, $keywordNode);
		}*/
		$this->_addMetaElement('keyword', $keywords);
	}
	
	/**
	 * Ajoute un meta utilisateur particulier
	 *
	 * @access 	public
	 * @param 	string			$attr					L'attribut du meta
	 * @param 	string			$str					Le contenu du meta
	 * @return 	void
	 */
	public function addUserDefined($attr, $str) {
		$new = $this->_addMetaElement('user-defined', $str);
		$new->setAttribute('meta:name', Fonction::checkAttribute($attr));
	}
	
	/**
	 * Ajoute l'élément qui spécifie qui a généré le fichier
	 *
	 * @access 	public
	 * @param 	string			$generator				Le nom de qui a généré le fichier
	 * @return 	void
	 */
	public function setGenerator($generator) {
		$this->_setMetaElement('generator', $generator);
	}
	
	/**
	 * Ajoute le nom de la personne qui a initialement créé le fichier
	 *
	 * @access 	public
	 * @param 	string			$creator				Le nom de la personne qui a créé le fichier
	 * @return 	void
	 */
	public function setInitialCreator($creator) {
		$this->_setMetaElement('initial-creator', $creator);
	}
	
	/**
	 * Ajoute la date de création du fichier (2003-08-29T09:54:26,50)
	 *
	 * @access 	public
	 * @param 	string			$dt						La date de création du fichier
	 * @return 	void
	 */
	public function setInitialCreationDate($dt) {
		$this->_setMetaElement('creation-date', $dt);
	}
	
	/**
	 * Ajoute le nombre de fois que le document a été édité
	 *
	 * @access 	public
	 * @param 	integer			$cycle					Le nombre de fois que le document a été édité
	 * @return 	void
	 */
	public function setEditingCycles($cycle) {
		$this->_setMetaElement('editing-cycles', $cycle);
	}
	
	/**
	 * Ajoute le temps passé à éditer le fichier, tous cycles confondus (P{d}T09{H}54{M}26,50{S})
	 *
	 * @access 	public
	 * @param 	string			$duration				Le temps d'édition d'un fichier
	 * @return 	void
	 */
	public function setEditingDuration($duration) {
		$this->_setMetaElement('editing-duration', $duration);
	}
		
	/**
	 * Ajoute le titre. Apparaît en haut dans la barre de titre
	 *
	 * @access 	public
	 * @param 	string			$title					Le titre du fichier
	 * @return 	void
	 */
	public function setTitle($title) {
		$this->_setDublinElement('title', $title);
	}
		
	/**
	 * Ajoute des mots-clé ou phrases-clé décrivant le contenu du document
	 *
	 * @access 	public
	 * @param 	string			$subject				Le ou les mots-clé à ajouter
	 * @return 	void
	 */
	public function setSubject($subject) {
		$this->_setDublinElement('subject', $subject);
	}
		
	/**
	 * Ajoute une description
	 *
	 * @access 	public
	 * @param 	string			$description			La description
	 * @return 	void
	 */
	public function setDescription($description) {
		$this->_setDublinElement('description', $description);
	}
		
	/**
	 * Ajoute un créateur. Représente la dernière personne à avoir modifié le fichier (oui oui)
	 *
	 * @access 	public
	 * @param 	string			$creator				Le nom du créateur
	 * @return 	void
	 */
	public function setCreator($creator) {
		$this->_setDublinElement('creator', $creator);
	}
		
	/**
	 * Ajoute la date de modification (oui oui) en lien avec le créateur (2003-08-29T09:54:26,50)
	 *
	 * @access 	public
	 * @param 	string			$dt						La date de modification
	 * @return 	void
	 */
	public function setCreationDate($dt) {
		$this->_setDublinElement('date', $dt);
	}
		
	/**
	 * Ajoute la langue du document
	 *
	 * @access 	public
	 * @param 	string			$language				La langue du document
	 * @return 	void
	 */
	public function setLanguage($language) {
		$this->_setDublinElement('language', $language);
	}
	
	
	/**
	 *-------------------------------------------------------------------------------
	 * Méthodes privées
	 *-------------------------------------------------------------------------------
	 */
	
	/**
	 * Fonction qui ajoute ou modifie un élément signé comme étant "Dublin Core"
	 *
	 * @access 	protected
	 * @param 	string			$element				L'élément Dublin Core Element
	 * @param 	string			$str					La valeur de l'élément
	 * @param 	object			$parent					Le DOMElement parent de celui qu'on créé
	 * @return 	object									Le DOMElement créé ou modifié
	 */
	protected function _setDublinElement($element, $str = null, $parent = null) {
		return $this->_setElement('dc', $element, $str, $parent);
	}
	
	/**
	 * Fonction qui ajoute ou remplace un élément signé comme étant un élément meta
	 *
	 * @access 	protected
	 * @param 	string			$element				Le nom de l'élément
	 * @param 	string			$str					La valeur de l'élément
	 * @param 	object			$parent					Le DOMElement parent de celui qu'on créé
	 * @return 	object									Le DOMElement créé ou modifié
	 */
	protected function _setMetaElement($element, $str = null, $parent = null) {
		return $this->_setElement('meta', $element, $str, $parent);
	}
	
	/**
	 * Fonction qui ajoute un élément signé comme étant un élément meta
	 *
	 * @access 	protected
	 * @param 	string			$element				Le nom de l'élément
	 * @param 	string			$str					La valeur de l'élément
	 * @param 	object			$parent					Le DOMElement parent de celui qu'on créé
	 * @return 	object									Le DOMElement créé
	 */
	protected function _addMetaElement($element, $str = null, $parent = null) {
		return $this->_addElement('meta', $element, $str, $parent);
	}
	
}

?>
