<?php

include_once "./Modules/TestQuestionPool/classes/class.assQuestion.php";
include_once "./Modules/Test/classes/inc.AssessmentConstants.php";

/**
 * Class for JSMEQuestion Question
 *
 * @author Yves Annanias <yves.annanias@llz.uni-halle.de>
 * @author Christoph Jobst <cjobst@wifa.uni-leipzig.de>
 * @version	$Id:  $
 * @ingroup ModulesTestQuestionPool
 */
class assJSMEQuestion extends assQuestion
{
	var $plugin = null;
	
	// options for jsme-applet
	var $optionString = "nosearchinchiKey nopaste ";
	var $sampleSolution = "";
	// for manual correction
	var $smilesSolution = "";
	//SVG
	var $svg = "";
	
	/**
	* assJSMEQuestion constructor
	*
	* The constructor takes possible arguments an creates an instance of the assJSMEQuestion object.
	*
	* @param string $title A title string to describe the question
	* @param string $comment A comment string to describe the question
	* @param string $author A string containing the name of the questions author
	* @param integer $owner A numerical ID to identify the owner/creator
	* @param string $question The question string of the single choice question
	* @access public
	* @see assQuestion:assQuestion()
	*/
	function __construct(
		$title = "",
		$comment = "",
		$author = "",
		$owner = -1,
		$question = ""	
	)
	{
		// needed for excel export
		$this->getPlugin()->loadLanguageModule();

		parent::__construct($title, $comment, $author, $owner, $question);
	}
	
	/**
	 * Get the plugin object
	 *
	 * @return object The plugin object
	 */
	public function getPlugin() {
		if ($this->plugin == null)
		{
			include_once "./Services/Component/classes/class.ilPlugin.php";
			$this->plugin = ilPlugin::getPluginObject(IL_COMP_MODULE, "TestQuestionPool", "qst", "assJSMEQuestion");
				
		}
		return $this->plugin;
	}
	
	/**
	* Returns true, if question is complete for use
	*
	* @return boolean True, if the TemplateQuestion question is complete for use, otherwise false
	* @access public
	*/
	function isComplete()
	{
		//Add SMILES-String as requirement
		if ( (strlen($this->getTitle())) and ($this->author) and ($this->question) and ($this->getMaximumPoints() > 0) )		
		{
			return true;
		}
		else
		{
			return false;
		}
	}	
	
	function setOptionString($options){
		$this->optionString = $options;
	}
	
	function setSampleSolution($solution){
		$this->sampleSolution = $solution;
	}

	function setSmilesSolution($smilesSolution){
		$this->smilesSolution = $smilesSolution;
	}

	function setSvg($svg){
		$this->svg = $svg;
	}
	
	function getOptionString()
	{
		return $this->optionString;
	}
	
	function getSampleSolution(){
		return $this->sampleSolution;
	}

	function getSmilesSolution(){
		return $this->smilesSolution;
	}

	function getSvg(){
		return $this->svg;
	}

	/**
	 * Saves a question object to a database
	 * 
	 * @param	string		original id
	 * @access 	public
	 * @see assQuestion::saveToDb()
	 */
	function saveToDb($original_id = "")
	{
		global $ilDB, $ilLog;
		$this->saveQuestionDataToDb($original_id);			
		
		//Maybe use $ilDB->replace() instead?
		$affectedRows = $ilDB->manipulateF("DELETE FROM il_qpl_qst_jsme_data WHERE question_fi = %s", 
			array("integer"),
			array($this->getId())
		);
		$affectedRows = $ilDB->manipulateF("INSERT INTO il_qpl_qst_jsme_data (question_fi, option_string, solution, smiles, svg) VALUES (%s, %s, %s, %s, %s)", 
				array("integer", "text", "text", "text", "clob"),
				array(
					$this->getId(),
					$this->optionString,
					$this->sampleSolution,
					$this->smilesSolution,
					$this->svg
				)
		);
			
		parent::saveToDb($original_id);
	}

	
	/**
	* Load a assJSMEQuestion object from a database
	*
	* @param integer $question_id A unique key which defines the question in the database
	* @see assQuestion::loadFromDb()
	*/
	public function loadFromDb($question_id)
	{
		global $ilDB;					

		$result = $ilDB->query("SELECT qpl_questions.* FROM qpl_questions WHERE question_id = "
				. $ilDB->quote($question_id, 'integer'));

		$data = $ilDB->fetchAssoc($result);
		$this->setId($question_id);
		$this->setTitle($data["title"]);
		$this->setComment($data["description"]);
		$this->setSuggestedSolution($data["solution_hint"]);
		$this->setOriginalId($data["original_id"]);
		$this->setObjId($data["obj_fi"]);
		$this->setAuthor($data["author"]);
		$this->setOwner($data["owner"]);
		$this->setPoints($data["points"]);

		include_once("./Services/RTE/classes/class.ilRTE.php");
		$this->setQuestion(ilRTE::_replaceMediaObjectImageSrc($data["question_text"], 1));
		$this->setEstimatedWorkingTime(substr($data["working_time"], 0, 2), substr($data["working_time"], 3, 2), substr($data["working_time"], 6, 2));
		
		$resultCheck= $ilDB->queryF("SELECT option_string, solution, smiles, svg FROM il_qpl_qst_jsme_data WHERE question_fi = %s", array('integer'), array($question_id));
		if($ilDB->numRows($resultCheck) == 1)
		{
			$data = $ilDB->fetchAssoc($resultCheck);
			$this->setOptionString($data["option_string"]);
			$this->setSampleSolution($data["solution"]);
			$this->setSmilesSolution($data["smiles"]);	
			$this->setSvg($data["svg"]);
		}
		
		try
		{
			$this->setAdditionalContentEditingMode($data['add_cont_edit_mode']);
		}
		catch(ilTestQuestionPoolException $e)
		{
		}
		
		parent::loadFromDb($question_id);
	}	

	/**
	 * Duplicates a question
	 * This is used for copying a question to a test
	 *
	 * @access public
	 */
	function duplicate($for_test = true, $title = "", $author = "", $owner = "", $testObjId = null)
	{
		if ($this->getId() <= 0)
		{
			// The question has not been saved. It cannot be duplicated
			return;
		}

		// make a real clone to keep the object unchanged
		$clone = clone $this;
							
		$original_id = assQuestion::_getOriginalId($this->getId());
		$clone->setId(-1);

		if( (int) $testObjId > 0 )
		{
			$clone->setObjId($testObjId);
		}

		if ($title)
		{
			$clone->setTitle($title);
		}
		if ($author)
		{
			$clone->setAuthor($author);
		}
		if ($owner)
		{
			$clone->setOwner($owner);
		}		
		
		if ($for_test)
		{
			$clone->saveToDb($original_id, false);
		}
		else
		{
			$clone->saveToDb('', false);
		}		

		// copy question page content
		$clone->copyPageOfQuestion($this->getId());
		// copy XHTML media objects
		$clone->copyXHTMLMediaObjectsOfQuestion($this->getId());

		// call the event handler for duplication
		$clone->onDuplicate($this->getObjId(), $this->getId(), $clone->getObjId(), $clone->getId());

		return $clone->getId();
	}

	/**
	 * Copies a question
	 * This is used when a question is copied on a question pool
	 *
	 * @access public
	 */
	function copyObject($target_questionpool_id, $title = "")
	{
		if ($this->getId() <= 0)
		{
			// The question has not been saved. It cannot be duplicated
			return;
		}

		// make a real clone to keep the object unchanged
		$clone = clone $this;
				
		$original_id = assQuestion::_getOriginalId($this->getId());
		$source_questionpool_id = $this->getObjId();
		$clone->setId(-1);
		$clone->setObjId($target_questionpool_id);
		if ($title)
		{
			$clone->setTitle($title);
		}
				
		// save the clone data
		$clone->saveToDb('', false);

		// copy question page content
		$clone->copyPageOfQuestion($original_id);
		// copy XHTML media objects
		$clone->copyXHTMLMediaObjectsOfQuestion($original_id);

		// call the event handler for copy
		$clone->onCopy($source_questionpool_id, $original_id, $clone->getObjId(), $clone->getId());

		return $clone->getId();
	}

	/**
	 * Synchronize a question with its original
	 * You need to extend this function if a question has additional data that needs to be synchronized
	 * 
	 * @access public
	 */
	function syncWithOriginal()
	{
		parent::syncWithOriginal();
	}

	/**
	 * Returns the points, a learner has reached answering the question
	 * The points are calculated from the given answers.
	 *
	 * @param integer $active 	The Id of the active learner
	 * @param integer $pass 	The Id of the test pass
	 * @param boolean $returndetails (deprecated !!)
	 * @return integer/array $points/$details (array $details is deprecated !!)
	 * @access public
	 * @see  assQuestion::calculateReachedPoints()
	 */
	function calculateReachedPoints($active_id, $pass = NULL, $authorizedSolution = true, $returndetails = false)
	{
        if( $returndetails )
        {
            throw new ilTestException('return details not implemented for '.__METHOD__);
        }
		
		global $ilDB;
		
		if (is_null($pass))
		{
			$pass = $this->getSolutionMaxPass($active_id);
		}

		$query = "SELECT value2 FROM tst_solutions "
            . " WHERE active_fi = %s AND question_fi = %s AND pass = %s ";
			
        $result = $ilDB->queryF($query,
            array('integer','integer','integer'),
            array($active_id, $this->getId(), $pass)
        );
		$resultrow = $ilDB->fetchAssoc($result);

		//Apply patch to prevent doublegrading see Mantis 110%Testresult-Bugs
		if( $this->smilesSolution == $resultrow["value2"] )
			{
			$points = $this->getMaximumPoints();
			}	
			
		return $points;
	}	

	/**
	* Returns the maximum points, a learner can reach answering the question
	* @access public
	* @see $points
	*/
	function getMaximumPoints()
	{		
		return $this->points;
	}
	
	/**
	* Saves the learners input of the question to the database
	*
	* @param integer $test_id The database id of the test containing this question
    * @return boolean Indicates the save status (true if saved successful, false otherwise)
	* @access public
	* @see $answers
	*/
	function saveWorkingData($active_id, $pass = NULL, $authorized = true)
	{
		global $ilDB;
		global $ilUser;
		if (is_null($pass))
		{
			include_once "./Modules/Test/classes/class.ilObjTest.php";
			$pass = ilObjTest::_getPass($active_id);
		}

		$ilDB->manipulateF("DELETE FROM tst_solutions WHERE active_fi = %s AND question_fi = %s AND pass = %s",
			array(
				"integer", "integer", "integer"),
			array(
				$active_id,	$this->getId(),	$pass)
		);

		$entered_values = false;		
		$value1_solution = $_POST['sampleSolution'];
		$value2_smiles = $_POST['smilesSolution'];
		$value3_svg = base64_encode($_POST['svgSolution']);
        $value4_InChI = null;        
		
        if (strlen($value1_solution) > 0)
		{	
		    $entered_values = true;
		    $this->saveCurrentSolution($active_id, $pass, $value1_solution, $value2_smiles, $authorized);
		    $this->saveCurrentSolution($active_id, $pass, $value3_svg, $value4_InChI, $authorized); 
		}
		
		if ($entered_values)
		{
			include_once ("./Modules/Test/classes/class.ilObjAssessmentFolder.php");
			if (ilObjAssessmentFolder::_enabledAssessmentLogging())
			{
				$this->logAction($this->lng->txtlng("assessment", "log_user_entered_values", ilObjAssessmentFolder::_getLogLanguage()), $active_id, $this->getId());				
			}
		}
		else
		{
			include_once ("./Modules/Test/classes/class.ilObjAssessmentFolder.php");
			if (ilObjAssessmentFolder::_enabledAssessmentLogging())
			{
				$this->logAction($this->lng->txtlng("assessment", "log_user_not_entered_values", ilObjAssessmentFolder::_getLogLanguage()), $active_id, $this->getId());
			}
		}		
		return true;
	}
	
	/**
	 * Reworks the allready saved working data if neccessary
	 *
	 * @abstract
	 * @access protected
	 * @param integer $active_id
	 * @param integer $pass
	 * @param boolean $obligationsAnswered
	 */
	protected function reworkWorkingData($active_id, $pass, $obligationsAnswered, $authorized)
	{
		// nothing to rework!		
	}

	/**
	* Returns the question type of the question
	*
	* @return integer The question type of the question
	* @access public
	*/
	function getQuestionType()
	{
		return "assJSMEQuestion";
	}
	
	/**
	* Returns the name of the additional question data table in the database
	*
	* @return string The additional table name
	* @access public
	*/
	function getAdditionalTableName()
	{
		return "il_qpl_qst_jsme_data";
	}
	
	/**
	* Collects all text in the question which could contain media objects
	* which were created with the Rich Text Editor
	*/
	function getRTETextWithMediaObjects()
	{
		$text = parent::getRTETextWithMediaObjects();
		return $text;
	}
	
	/**
	 * Creates an Excel worksheet for the detailed cumulated results of this question
	 *
	 * @access public
	 * @see assQuestion::setExportDetailsXLS()
	 */
	public function setExportDetailsXLS($worksheet, $startrow, $active_id, $pass)
	{
		
		global $lng;
		parent::setExportDetailsXLS($worksheet, $startrow, $active_id, $pass);

		$solutions = $this->getSolutionValues($active_id, $pass);
		
		$i = 1;
		$worksheet->setCell($startrow + $i, 0, $this->lng->txt($this->plugin->txt("label_value2")));
		$worksheet->setBold($worksheet->getColumnCoord(0) . ($startrow + $i));
		
		if (strlen($solutions[0]["value2"]))
		{
			$worksheet->setCell($startrow + $i, 1, $solutions[0]["value2"]);		
		}
		$i++;
		
		return $startrow + $i + 1;
	}
		
	/**
	* Creates a question from a QTI file
	*
	* Receives parameters from a QTI parser and creates a valid ILIAS question object
	*
	* @param object $item The QTI item object
	* @param integer $questionpool_id The id of the parent questionpool
	* @param integer $tst_id The id of the parent test if the question is part of a test
	* @param object $tst_object A reference to the parent test object
	* @param integer $question_counter A reference to a question counter to count the questions of an imported question pool
	* @param array $import_mapping An array containing references to included ILIAS objects
	* @access public
	*/
	function fromXML(&$item, &$questionpool_id, &$tst_id, &$tst_object, &$question_counter, &$import_mapping)
	{
		$this->getPlugin()->includeClass("import/qti12/class.assJSMEQuestionImport.php");
		$import = new assJSMEQuestionImport($this);
		$import->fromXML($item, $questionpool_id, $tst_id, $tst_object, $question_counter, $import_mapping);
	}
	
	/**
	* Returns a QTI xml representation of the question and sets the internal
	* domxml variable with the DOM XML representation of the QTI xml representation
	*
	* @return string The QTI xml representation of the question
	* @access public
	*/
	function toXML($a_include_header = true, $a_include_binary = true, $a_shuffle = false, $test_output = false, $force_image_references = false)
	{
		$this->getPlugin()->includeClass("export/qti12/class.assJSMEQuestionExport.php");
		$export = new assJSMEQuestionExport($this);
		return $export->toXML($a_include_header, $a_include_binary, $a_shuffle, $test_output, $force_image_references);
	}
}
?>