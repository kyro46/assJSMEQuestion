<?php

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
    protected $plugin = null;
    
	// options for jsme-applet
	var $optionString = "nosearchinchiKey nopaste ";
	var $sampleSolution = "";
	// for manual correction
	var $smilesSolution = "";
	//SVG
	var $svg = "";
	
	/**
	 * Constructor
	 *
	 * The constructor takes possible arguments and creates an instance of the question object.
	 *
	 * @param string $title A title string to describe the question
	 * @param string $comment A comment string to describe the question
	 * @param string $author A string containing the name of the questions author
	 * @param integer $owner A numerical ID to identify the owner/creator
	 * @param string $question Question text
	 * @access public
	 *
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
	 * Returns the question type of the question
	 *
	 * @return string The question type of the question
	 */
	public function getQuestionType() : string
	{
	    return "assJSMEQuestion";
	}
	
	/**
	 * Returns the names of the additional question data tables
	 *
	 * All tables must have a 'question_fi' column.
	 * Data from these tables will be deleted if a question is deleted
	 *
	 * @return mixed 	the name(s) of the additional tables (array or string)
	 */
	function getAdditionalTableName()
	{
	    return "il_qpl_qst_jsme_data";
	}
	
	/**
	 * Collects all texts in the question which could contain media objects
	 * which were created with the Rich Text Editor
	 */
	protected function getRTETextWithMediaObjects(): string
	{
	    $text = parent::getRTETextWithMediaObjects();
	    
	    // eventually add the content of question type specific text fields
	    // ..
	    
	    return (string) $text;
	}
	
	/**
	 * Get the plugin object
	 *
	 * @return object The plugin object
	 */
	public function getPlugin()
	{
	    global $DIC;
	    
	    if ($this->plugin == null)
	    {
	        /** @var ilComponentFactory $component_factory */
	        $component_factory = $DIC["component.factory"];
	        $this->plugin = $component_factory->getPlugin('assJSMEQuestion');
	    }
	    return $this->plugin;
	}
	
	/**
	 * Returns true, if the question is complete
	 *
	 * @return boolean True, if the question is complete for use, otherwise false
	 */
	public function isComplete(): bool
	{
		//Add SMILES-String as requirement
	    if(!empty($this->title) && !empty($this->author) && !empty($this->question) && $this->getMaximumPoints() > 0)
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
	 * @param	string		$original_id
	 * @access 	public
	 * @see assQuestion::saveToDb()
	 */
	function saveToDb($original_id = ''): void
	{
	    global $DIC;
	    $ilDB = $DIC->database();
	    
		// save the basic data (implemented in parent)
		// a new question is created if the id is -1
		// afterwards the new id is set
		if ($original_id == '') {
		    $this->saveQuestionDataToDb();
		} else {
		    $this->saveQuestionDataToDb($original_id);
		}
		
		//Maybe use $ilDB->replace() instead?
		$affectedRows = $ilDB->manipulateF("DELETE FROM il_qpl_qst_jsme_data WHERE question_fi = %s", 
			array("integer"),
			array($this->getId())
		);
		$affectedRows = $ilDB->manipulateF("INSERT INTO il_qpl_qst_jsme_data (question_fi, option_string, solution, smiles, svg) VALUES (%s, %s, %s, %s, %s)", 
				array("integer", "text", "text", "text", "clob"),
				array(
					$this->getId(),
				    $this->getOptionString(),
				    $this->getSampleSolution(),
				    $this->getSmilesSolution(),
				    $this->getSvg()
				)
		);
			
		parent::saveToDb();
	}

	
	/**
	 * Loads a question object from a database
	 * This has to be done here (assQuestion does not load the basic data)!
	 *
	 * @param integer $question_id A unique key which defines the question in the database
	 * @see assQuestion::loadFromDb()
	 */
	public function loadFromDb($question_id) : void
	{
	    global $DIC;
	    $ilDB = $DIC->database();
	    
		$result = $ilDB->query("SELECT qpl_questions.* FROM qpl_questions WHERE question_id = "
				. $ilDB->quote($question_id, 'integer'));

		if ($result->numRows() > 0) {
		    $data = $ilDB->fetchAssoc($result);
		    $this->setId($question_id);
		    $this->setObjId($data['obj_fi']);
		    $this->setOriginalId($data['original_id']);
		    $this->setOwner($data['owner']);
		    $this->setTitle((string) $data['title']);
		    $this->setAuthor($data['author']);
		    $this->setPoints($data['points']);
		    $this->setComment((string) $data['description']);
		    //$this->setSuggestedSolution((string) $data["solution_hint"]); // removed from qpl_questions
		    
		    $this->setQuestion(ilRTE::_replaceMediaObjectImageSrc((string) $data['question_text'], 1));
		    try {
		        $this->setLifecycle(ilAssQuestionLifecycle::getInstance($data['lifecycle']));
		    } catch (ilTestQuestionPoolInvalidArgumentException $e) {
		        $this->setLifecycle(ilAssQuestionLifecycle::getDraftInstance());
		    }
		    
		    try
		    {
		        $this->setAdditionalContentEditingMode($data['add_cont_edit_mode']);
		    }
		    catch(ilTestQuestionPoolException $e)
		    {
		    }
		    
    		$resultCheck= $ilDB->queryF("SELECT option_string, solution, smiles, svg FROM il_qpl_qst_jsme_data WHERE question_fi = %s", array('integer'), array($question_id));
    		if($ilDB->numRows($resultCheck) == 1)
    		{
    			$data = $ilDB->fetchAssoc($resultCheck);
    			$this->setOptionString($data["option_string"]);
    			$this->setSampleSolution($data["solution"]);
    			$this->setSmilesSolution($data["smiles"]);	
    			$this->setSvg($data["svg"]);
    		}
		

		}
		
		parent::loadFromDb($question_id);
	}	

	/**
	 * Duplicates a question
	 * This is used for copying a question to a test
	 *
	 * @access public
	 */
	function duplicate($for_test = true, $title = "", $author = "", $owner = "", $testObjId = null) : int
	{
	    if ($this->getId() <= 0)
	    {
	        // The question has not been saved. It cannot be duplicated
	        return 0;
	    }

		// make a real clone to keep the object unchanged
		$clone = clone $this;
							
		$original_id = assQuestion::_getOriginalId($this->getId());
		$clone->setId(-1);

		if( (int) $testObjId > 0 )
		{
		    $clone->setObjId($testObjId);
		}
		
		if (!empty($title))
		{
		    $clone->setTitle($title);
		}
		if (!empty($author))
		{
		    $clone->setAuthor($author);
		}
		if (!empty($owner))
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
	 * @param integer	$target_questionpool_id
	 * @param string	$title
	 *
	 * @return void|integer Id of the clone or nothing.
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
		if (!empty($title))
		{
			$clone->setTitle($title);
		}
				
		// save the clone data
		$clone->saveToDb();
		
		// copy question page content
		$clone->copyPageOfQuestion($original_id);
		// copy XHTML media objects
		$clone->copyXHTMLMediaObjectsOfQuestion($original_id);

		// call the event handler for copy
		$clone->onCopy($source_questionpool_id, $original_id, $clone->getObjId(), $clone->getId());

		return $clone->getId();
	}
	
	/**
	 * Create a new original question in a question pool for a test question
	 * @param int $targetParentId			id of the target question pool
	 * @param string $targetQuestionTitle
	 * @return int|void
	 */
	public function createNewOriginalFromThisDuplicate($targetParentId, $targetQuestionTitle = "")
	{
	    if ($this->id <= 0)
	    {
	        // The question has not been saved. It cannot be duplicated
	        return;
	    }
	    	    
	    $sourceQuestionId = $this->id;
	    $sourceParentId = $this->getObjId();
	    
	    // make a real clone to keep the object unchanged
	    $clone = clone $this;
	    $clone->setId(-1);
	    
	    $clone->setObjId($targetParentId);
	    
	    if (!empty($targetQuestionTitle))
	    {
	        $clone->setTitle($targetQuestionTitle);
	    }
	    
	    $clone->saveToDb();
	    // copy question page content
	    $clone->copyPageOfQuestion($sourceQuestionId);
	    // copy XHTML media objects
	    $clone->copyXHTMLMediaObjectsOfQuestion($sourceQuestionId);
	    
	    $clone->onCopy($sourceParentId, $sourceQuestionId, $clone->getObjId(), $clone->getId());
	    
	    return $clone->getId();
	}

	/**
	 * Synchronize a question with its original
	 * You need to extend this function if a question has additional data that needs to be synchronized
	 *
	 * @access public
	 */
	function syncWithOriginal() : void
	{
	    parent::syncWithOriginal();
	}

	/**
	 * Get the submitted user input as a serializable value
	 *
	 * @return mixed user input (scalar, object or array)
	 */
	protected function getSolutionSubmit()
	{
	    $value1 = isset($_POST['sampleSolution']) ? trim(ilUtil::stripSlashes($_POST['sampleSolution'])) : null;
	    $value2 = isset($_POST['smilesSolution']) ? trim(ilUtil::stripSlashes($_POST['smilesSolution'])) : null;
	    $value3 = isset($_POST['svgSolution'])    ? trim(ilUtil::stripSlashes(base64_encode($_POST['svgSolution']))) : null;
	    $value4 = NULL;
	    
	    return array(
	        'value1' => empty($value1)? null : (string) $value1,
	        'value2' => empty($value2)? null : (string) $value2,
	        'value3' => empty($value3)? null : (string) $value3,
	        'value4' => empty($value4)? null : (string) $value4,
	    );
	}
	
	/**
	 * Get a stored solution for a user and test pass
	 * This is a wrapper to provide the same structure as getSolutionSubmit()
	 *
	 * @param int 	$active_id		active_id of hte user
	 * @param int	$pass			number of the test pass
	 * @param bool	$authorized		get the authorized solution
	 *
	 * @return	array	('value1' => string|null, 'value2' => string|null, 'value3' => string|null, 'value4' => string|null)
	 */
	public function getSolutionStored($active_id, $pass, $authorized = null)
	{
	    // This provides an array with records from tst_solution
	    // The example question should only store one record per answer
	    // Other question types may use multiple records with value1/value2 in a key/value style
	    if (isset($authorized))
	    {
	        // this provides either the authorized or intermediate solution
	        $solutions = $this->getSolutionValues($active_id, $pass, $authorized);
	    }
	    else
	    {
	        // this provides the solution preferring the intermediate
	        // or the solution from the previous pass
	        $solutions = $this->getTestOutputSolutions($active_id, $pass);
	    }
	    
	    
	    if (empty($solutions))
	    {
	        // no solution stored yet
	        $value1 = null;
	        $value2 = null;
	        $value3 = null;
	        $value4 = null;
	    }
	    else
	    {
	        // If the process locker isn't activated in the Test and Assessment administration
	        // then we may have multiple records due to race conditions
	        // In this case the last saved record wins
	        
	        // JSME stores 4 instead of 2 values in the solution table in consecutive records!
	        $solution_svg_inchi = end($solutions); // the generated image and the yet unused InChI
	        $solution_code_smiles = prev($solutions); // the internal molecule code and the SMILES-representation

	        $value1 = $solution_code_smiles['value1'];
	        $value2 = $solution_code_smiles['value2'];
	        $value3 = $solution_svg_inchi['value1'];
	        $value4 = $solution_svg_inchi['value2'];
	        
	    }
	    
	    return array(
	        'value1' => empty($value1)? null : (string) $value1,
	        'value2' => empty($value2)? null : (string) $value2,
	        'value3' => empty($value3)? null : (string) $value3,
	        'value4' => empty($value4)? null : (string) $value4,
	    );
	}
	
	/**
	 * Calculate the reached points for a submitted user input
	 *
	 * @return  float	reached points
	 */
	public function calculateReachedPointsforSolution($solution)
	{
	    //Apply patch to prevent doublegrading see Mantis 110%Testresult-Bugs
	    if( $this->getSmilesSolution() == $solution["value2"] )
	    {
	        $points = $this->getMaximumPoints();
	    } else {
	        $points = 0;
	    }
	    
	    return $points;
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
		
		$solution = $this->getSolutionStored($active_id, $pass, $authorizedSolution);
		return $this->calculateReachedPointsForSolution($solution);
	}	

	/**
	 * Saves the learners input of the question to the database
	 *
	 * @param 	integer $test_id The database id of the test containing this question
	 * @return 	boolean Indicates the save status (true if saved successful, false otherwise)
	 * @access 	public
	 * @see 	assQuestion::saveWorkingData()
	 */
	function saveWorkingData($active_id, $pass = NULL, $authorized = true) : bool
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
		
		
		// Log whether the user entered values
		if (ilObjAssessmentFolder::_enabledAssessmentLogging())
		{
		    assQuestion::logAction($this->lng->txtlng(
		        'assessment',
		        $entered_values ? 'log_user_entered_values' : 'log_user_not_entered_values',
		        ilObjAssessmentFolder::_getLogLanguage()
		        ),
		        $active_id,
		        $this->getId()
		        );
		}
		return true;
	}
	
	/**
	 * Reworks the allready saved working data if neccessary
	 *
	 * @access protected
	 * @param integer $active_id
	 * @param integer $pass
	 * @param boolean $obligationsAnswered
	 */
	protected function reworkWorkingData($active_id, $pass, $obligationsAnswered, $authorized)
	{
	    // normally nothing needs to be reworked
	}
	
	/**
	 * Creates an Excel worksheet for the detailed cumulated results of this question
	 *
	 * @access public
	 * @see assQuestion::setExportDetailsXLS()
	 */
	public function setExportDetailsXLS(ilAssExcelFormatHelper $worksheet, int $startrow, int $active_id, int $pass): int
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
}
?>