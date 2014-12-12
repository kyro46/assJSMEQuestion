<?php

include_once "./Modules/TestQuestionPool/classes/class.assQuestion.php";
include_once "./Modules/Test/classes/inc.AssessmentConstants.php";

/**
 * Class for JSMEQuestion Question
 *
 * @author Yves Annanias <yves.annanias@llz.uni-halle.de>
 * @version	$Id:  $
 * @ingroup ModulesTestQuestionPool
 */
class assJSMEQuestion extends assQuestion
{
	private $plugin;	
	// options for jsme-applet
	var $optionString = "";
	// for manuel correction
	var $sampleSolution = "";
	var $smilesSolution = "";
	
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
		parent::__construct($title, $comment, $author, $owner, $question);		
		$this->plugin = null;
	}
	
	/**
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
		if ( (strlen($this->getTitle())) and ($this->author) and ($this->question) and ($this->getMaximumPoints() >= 0) )		
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

	/**
	* Load a assJSMEQuestion object from a database
	*
	* @param object $db A pear DB object
	* @param integer $question_id A unique key which defines the TemplateQuestion test in the database
	* @access public
	*/
	function loadFromDb($question_id)
	{
		global $ilDB;					

		$result = $ilDB->queryF("SELECT qpl_questions.* FROM qpl_questions WHERE question_id = %s",
			array('integer'),
			array($question_id)
		);
		if($ilDB->numRows($result) == 1)
		{
			$data = $ilDB->fetchAssoc($result);
			$this->setId($question_id);
			$this->setObjId($data["obj_fi"]);
			$this->setTitle($data["title"]);
			$this->setComment($data["description"]);
			//$this->setSuggestedSolution($data["solution_hint"]);
			$this->setOriginalId($data["original_id"]);			
			$this->setAuthor($data["author"]);
			$this->setOwner($data["owner"]);
			$this->setPoints($data["points"]);			

			include_once("./Services/RTE/classes/class.ilRTE.php");
			$this->setQuestion(ilRTE::_replaceMediaObjectImageSrc($data["question_text"], 1));
			$this->setEstimatedWorkingTime(substr($data["working_time"], 0, 2), substr($data["working_time"], 3, 2), substr($data["working_time"], 6, 2));			
		}
		
		$resultCheck= $ilDB->queryF("SELECT option_string, solution, smiles FROM il_qpl_qst_jsme_data WHERE question_fi = %s", array('integer'), array($question_id));
		if($ilDB->numRows($resultCheck) == 1)
		{
			$data = $ilDB->fetchAssoc($resultCheck);
			$this->setOptionString($data["option_string"]);
			$this->setSampleSolution($data["solution"]);
			$this->setSmilesSolution($data["smiles"]);		
		}
					
		parent::loadFromDb($question_id);
	}	

	/**
	* Saves a assJSMEQuestion object to a database
	*
	* @access public
	*/
	function saveToDb($original_id = "")
	{
		global $ilDB, $ilLog;
		$this->saveQuestionDataToDb($original_id);			
		
		$affectedRows = $ilDB->manipulateF("DELETE FROM il_qpl_qst_jsme_data WHERE question_fi = %s", 
			array("integer"),
			array($this->getId())
		);
		$affectedRows = $ilDB->manipulateF("INSERT INTO il_qpl_qst_jsme_data (question_fi, option_string, solution, smiles) VALUES (%s, %s, %s, %s)", 
				array("integer", "text", "text", "text"),
				array(
					$this->getId(),
					$this->optionString,
					$this->sampleSolution,
					$this->smilesSolution
				)
		);
			
		parent::saveToDb($original_id);
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
	* Duplicates an assJSMEQuestion	
	* @access public
	*/
	function duplicate($for_test = true, $title = "", $author = "", $owner = "", $testObjId = null)
	{
		if ($this->id <= 0)
		{
			// The question has not been saved. It cannot be duplicated
			return;
		}
		// duplicate the question in database
		$this_id = $this->getId();
		
		if( (int)$testObjId > 0 )
		{
			$thisObjId = $this->getObjId();
		}
		
		$clone = $this;
		include_once ("./Modules/TestQuestionPool/classes/class.assQuestion.php");
		$original_id = assQuestion::_getOriginalId($this->id);
		$clone->id = -1;
		
		if( (int)$testObjId > 0 )
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
			$clone->saveToDb($original_id);
		}
		else
		{
			$clone->saveToDb();
		}

		// copy question page content
		$clone->copyPageOfQuestion($this_id);
		// copy XHTML media objects
		$clone->copyXHTMLMediaObjectsOfQuestion($this_id);
		// duplicate the generic feedback
		//$clone->duplicateGenericFeedback($this_id);		

		$clone->onDuplicate($this_id);

		return $clone->getId();
	}

	/**
	* Copies an assJSMEQuestion object
	*
	* @access public
	*/
	function copyObject($target_questionpool, $title = "")
	{
		if ($this->getId() <= 0)
		{
			// The question has not been saved. It cannot be duplicated
			return;
		}
		$clone = $this;
		include_once ("./Modules/TestQuestionPool/classes/class.assQuestion.php");
		$original_id = assQuestion::_getOriginalId($this->getId());
		$clone->id = -1;
		$source_questionpool = $this->getObjId();
		$clone->setObjId($target_questionpool);
		if ($title)
		{
			$clone->setTitle($title);
		}
		$clone->saveToDb();

		// copy question page content
		$clone->copyPageOfQuestion($original_id);
		// copy XHTML media objects
		$clone->copyXHTMLMediaObjectsOfQuestion($original_id);
		// duplicate the generic feedback
		//$clone->duplicateGenericFeedback($original_id);

		$clone->onCopy($this->getObjId(), $this->getId());
		return $clone->getId();
	}	

	/**
	 * Returns the points, a learner has reached answering the question
	 * The points are calculated from the given answers including checks
	 * for all special scoring options in the test container.
	 *
	 * @param integer $user_id The database ID of the learner
	 * @param integer $test_id The database Id of the test containing the question
	 * @param boolean $returndetails (deprecated !!)
	 * @access public
	 */
	function calculateReachedPoints($active_id, $pass = NULL, $returndetails = FALSE)
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

		if( $this->smilesSolution == $resultrow["value2"] )
			{
			$points = $this->getMaximumPoints();
			}	
			
		return $points;
	}	
	
	/**
	* Saves the learners input of the question to the database
	*
	* @param integer $test_id The database id of the test containing this question
    * @return boolean Indicates the save status (true if saved successful, false otherwise)
	* @access public
	* @see $answers
	*/
	function saveWorkingData($active_id, $pass = NULL)
	{
		global $ilDB;
		global $ilUser;
		if (is_null($pass))
		{
			include_once "./Modules/Test/classes/class.ilObjTest.php";
			$pass = ilObjTest::_getPass($active_id);
		}

		$affectedRows = $ilDB->manipulateF("DELETE FROM tst_solutions WHERE active_fi = %s AND question_fi = %s AND pass = %s",
			array(
				"integer", 
				"integer",
				"integer"
			),
			array(
				$active_id,
				$this->getId(),
				$pass
			)
		);

		$entered_values = false;		
		$value = $_POST['sampleSolution'];
		$value2 = $_POST['smilesSolution'];
		
		$result = $ilDB->queryF("SELECT test_fi FROM tst_active WHERE active_id = %s",
			array('integer'),
			array($active_id)
		);
		$test_id = 0;
		if ($result->numRows() == 1)
		{
			$row = $ilDB->fetchAssoc($result);
			$test_id = $row["test_fi"];
		}
		
		if (strlen($value) > 0)
		{			
			$entered_values = true;
			$next_id = $ilDB->nextId("tst_solutions");
			$affectedRows = $ilDB->insert("tst_solutions", array(
				"solution_id" => array("integer", $next_id),
				"active_fi" => array("integer", $active_id),
				"question_fi" => array("integer", $this->getId()),
				"value1" => array("clob", $value),
				"value2" => array("clob", $value2),
				"pass" => array("integer", $pass),
				"tstamp" => array("integer", time())
			));					
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
	protected function reworkWorkingData($active_id, $pass, $obligationsAnswered)
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
		return "";
	}
	
	/**
	* Returns the name of the answer table in the database
	*
	* @return string The answer table name
	* @access public
	*/
	function getAnswerTableName()
	{
		return "";
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
	* @param object $worksheet Reference to the parent excel worksheet
	* @param object $startrow Startrow of the output in the excel worksheet
	* @param object $active_id Active id of the participant
	* @param object $pass Test pass
	* @param object $format_title Excel title format
	* @param object $format_bold Excel bold format
	* @param array $eval_data Cumulated evaluation data
	* @access public
	*/
	public function setExportDetailsXLS(&$worksheet, $startrow, $active_id, $pass, &$format_title, &$format_bold)
	{	
		include_once ("./Services/Excel/classes/class.ilExcelUtils.php");
		$solution = $this->getSolutionValues($active_id, $pass);
		$worksheet->writeString($startrow, 0, ilExcelUtils::_convert_text($this->lng->txt($this->getQuestionType())), $format_title);
		$worksheet->writeString($startrow, 1, ilExcelUtils::_convert_text($this->getTitle()), $format_title);
		$i = 1;
		/*
		$worksheet->writeString($startrow + $i, 0, $solutionvalue["value1"], $format_bold);
		$worksheet->writeString($startrow + $i, 1, $solutionvalue["value2"]);		
		$i++;					
		*/
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
