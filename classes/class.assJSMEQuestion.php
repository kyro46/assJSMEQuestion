<?php

/**
 * Class for JSMEQuestion Question
 *
 * @author Christoph Jobst <iliasplugins.christoph.jobst@outlook.de>
 * @version	$Id:  $
 * @ingroup ModulesTestQuestionPool
 */

use ILIAS\Test\Logging\AdditionalInformationGenerator;
use ILIAS\TestQuestionPool\Questions\QuestionAutosaveable;

class assJSMEQuestion extends assQuestion implements ilObjQuestionScoringAdjustable, QuestionAutosaveable
{
    private ilPlugin $plugin;
    
	// options for jsme-applet
	var $optionString = "nosearchinchiKey nopaste ";
	var $sampleSolution = "";
	// for manual correction
	var $smilesSolution = "";
	// SVG
	var $svg = "";
	// InChI
	var $inchi = "";
	// Evaluation option (smiles or inchi for automatic scoring), 'evaloption' in DB
	// SMILES (default) = 0, InChI = 1
	var $evaluationOption = 0;
	
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
	    parent::__construct($title, $comment, $author, $owner, $question);
	    
	    try {
	        global $DIC;
	        
	        /** @var ilComponentRepository $component_repository */
	        $component_repository = $DIC["component.repository"];
	        
	        $info = null;
	        $plugin_name = 'assJSMEQuestion';
	        $info = $component_repository->getPluginByName($plugin_name);
	        
	        /** @var ilComponentFactory $component_factory */
	        $component_factory = $DIC["component.factory"];
	        
	        /** @var ilQuestionsPlugin $plugin_obj */
	        $plugin_obj = $component_factory->getPlugin($info->getId());
	        
	        if (!is_null($info) && $info->isActive()) {
	            $this->setPlugin($plugin_obj);
	        } else {
	            throw new ilPluginException($plugin_name . ' plugin is not active');
	        }
	    } catch (ilPluginException $e) {
	        global $tpl;
	        $tpl->setOnScreenMessage('failure', $e->getMessage(), true);
	    }
	    
	    // needed for excel export
	    $this->getPlugin()->loadLanguageModule();
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
	function getAdditionalTableName(): string
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
	public function getPlugin(): ilPlugin
	{
	    return $this->plugin;
	}
	
	public function setPlugin(ilPlugin $plugin): void
	{
	    $this->plugin = $plugin;
	}
	
	/**
	 * Returns true, if the question is complete
	 *
	 * @return boolean True, if the question is complete for use, otherwise false
	 */
	public function isComplete(): bool
	{
	    if(!empty($this->title) && !empty($this->author) && !empty($this->question) && $this->getMaximumPoints() > 0)
	    {
	        // If only the evalOption was changed to InChI but the corresponding InChI-STRING is missing, the qst is not complete
	        if (empty($this->getInchiSolution()) && $this->getEvaluationOption() == 1) {
	            return false;
	        } else {
	            return true;
	        }
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
	
	function setInchiSolution($inchi){
	    $this->inchi = $inchi;
	}
	
	function setEvaluationOption($evaluationOption){
	    $this->evaluationOption = $evaluationOption;
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
	
	function getInchiSolution(){
	    return $this->inchi;
	}
	
	function getEvaluationOption(){
	    return $this->evaluationOption;
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
		$affectedRows = $ilDB->manipulateF("INSERT INTO il_qpl_qst_jsme_data (question_fi, option_string, solution, smiles, svg, inchi, evaloption) VALUES (%s, %s, %s, %s, %s, %s, %s)", 
				array("integer", "text", "text", "text", "clob", "text", "integer"),
				array(
					$this->getId(),
				    $this->getOptionString(),
				    $this->getSampleSolution(),
				    $this->getSmilesSolution(),
				    $this->getSvg(),
				    $this->getInchiSolution(),
				    $this->getEvaluationOption()
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
		    
    		$resultCheck= $ilDB->queryF("SELECT option_string, solution, smiles, svg, inchi, evaloption FROM il_qpl_qst_jsme_data WHERE question_fi = %s", array('integer'), array($question_id));
    		if($ilDB->numRows($resultCheck) == 1)
    		{
    			$data = $ilDB->fetchAssoc($resultCheck);
    			$this->setOptionString($data["option_string"]);
    			$this->setSampleSolution($data["solution"]);
    			$this->setSmilesSolution($data["smiles"]);	
    			$this->setSvg($data["svg"]);
    			$this->setInchiSolution($data["inchi"]);
    			$this->setEvaluationOption($data["evaloption"]);
    		}
		}
		
		parent::loadFromDb($question_id);
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
	    $value4 = isset($_POST['inchiSolution']) ? trim(ilUtil::stripSlashes($_POST['inchiSolution'])) : null;;
	    
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
	        $last  = end($solutions);  // presumably the generated image and InChI
	        $prev  = prev($solutions); // presumably the internal molecule code and the SMILES-representation
	        
	        if ((isset($last['value2']) && str_starts_with($last['value2'], 'InChI='))||   // Only for new Questions in plugin versions 9.1 or 10.0+
	            (isset($last['value1']) && str_starts_with($last['value1'], 'PHN2Zy')))    // SVG as base64 always starts this way
	        {
	            $solution_svg_inchi     = $last;
	            $solution_code_smiles   = $prev;
	        } else {
	            $solution_svg_inchi     = $prev;
	            $solution_code_smiles   = $last;
	        }
	        
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
	    $points = 0;
	    
	    $useInchi = ($this->getEvaluationOption() === 1); // 0 for SMILES
	    $inchiSolution = $this->getInchiSolution();
	    $smilesSolution = $this->getSmilesSolution();
	    
	    $userInchi = $solution["value4"] ?? null;
	    $userSmiles = $solution["value2"] ?? null;
	    
	    if ($useInchi) {
	        // InChI evaluation with fallback
	        if (!empty($inchiSolution) && !empty($userInchi)) {
	            // compare InChI sample and user input
	            if ($inchiSolution === $userInchi) {
	                $points = $this->getMaximumPoints();
	            }
	        } else {
	            // Fallback: old qst or answer with no InChI sample or InChI user input, so try SMILES
	            if (!empty($smilesSolution) && !empty($userSmiles) && $smilesSolution === $userSmiles) {
	                $points = $this->getMaximumPoints();
	            }
	        }
	    } else {
	        // SMILIES evaluation
	        if (!empty($smilesSolution) && !empty($userSmiles) && $smilesSolution === $userSmiles) {
	            $points = $this->getMaximumPoints();
	        }
	    }
	    
	    return $points;
	}
	
	/**
	 * Returns the points, a learner has reached answering the question
	 * The points are calculated from the given answers.
	 *
	 * @param integer $active_id 	The Id of the active learner
	 * @param ?integer $pass 	The Id of the test pass
	 * @param boolean $authorizedSolution
	 * @return float $points
	 * @access public
	 * @see  assQuestion::calculateReachedPoints()
	 */
	public function calculateReachedPoints(int $active_id, ?int $pass = null, bool $authorized_solution = true): float
    {
		global $ilDB;
		
		if (is_null($pass))
		{
			$pass = $this->getSolutionMaxPass($active_id);
		}
		
		$solution = $this->getSolutionStored($active_id, $pass, $authorized_solution);
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
	public function saveWorkingData(
	    int $active_id,
	    ?int $pass = null,
	    bool $authorized = true
	    ): bool 
	{
        if ($pass === null) {
            $pass = ilObjTest::_getPass($active_id);
        }
        
        $answer = $this->getSolutionSubmit();
        $this->getProcessLocker()->executeUserSolutionUpdateLockOperation(
            function () use ($answer, $active_id, $pass, $authorized) {
                $this->removeCurrentSolution($active_id, $pass, $authorized);
                
                if (strlen($answer['value1']) > 0 && $answer['value1'] <> '0 0') // '0 0' is set by the JSME-Editor "clear canvas" action as internal representation
                    // value1: editor representation, value2: SMILES, value3: SVG, value4: InChI
                    $this->saveCurrentSolution($active_id, $pass, $answer['value1'], $answer['value2'], $authorized); // TODO - Intruduce STEPS
                    $this->saveCurrentSolution($active_id, $pass, $answer['value3'], $answer['value4'], $authorized); 
                }
            );
        
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
	 * Returns the name of the answer table in the database
	 *
	 * @return string The answer table name
	 * @access public
	 */
	public function getAnswerTableName(): array|string
	{
	    return "";
	}
	
	/**
	 * Creates an Excel worksheet for the detailed cumulated results of this question
	 *
	 * @access public
	 * @see assQuestion::setExportDetailsXLS()
	 */
	public function setExportDetailsXLSX(ilAssExcelFormatHelper $worksheet, int $startrow, int $col, int $active_id, int $pass) : int
	{
	    parent::setExportDetailsXLSX($worksheet, $startrow, $col, $active_id, $pass);
		
		global $lng;
		$solutions = $this->getSolutionValues($active_id, $pass);
		
		$i = 1;
		$worksheet->setCell($startrow + $i, 0, $this->lng->txt($this->plugin->txt("label_value2")));
		$worksheet->setBold($worksheet->getColumnCoord(0) . ($startrow + $i));
    
		if ($this->getEvaluationOption() === 0)
		{
		    if (strlen($solutions[0]["value2"]))
		    {
		        $worksheet->setCell($startrow + $i, 1, $solutions[0]["value2"]);
		    }
		    $i++;
		    
		    return $startrow + $i + 1;
		}
		
		if ($this->getEvaluationOption() === 1)
		{
		    if (strlen($solutions[1]["value2"]))
		    {
		        $worksheet->setCell($startrow + $i, 1, $solutions[1]["value2"]);
		    }
		    $i++;
		    
		    return $startrow + $i + 1;
		}
	}
	
	// Generic log
	public function toLog(AdditionalInformationGenerator $additional_info) : array
	{
	    return [
	        AdditionalInformationGenerator::KEY_QUESTION_TYPE => (string) $this->getQuestionType(),
	        AdditionalInformationGenerator::KEY_QUESTION_TITLE => $this->getTitleForHTMLOutput(),
	        AdditionalInformationGenerator::KEY_QUESTION_TEXT => $this->formatSAQuestion($this->getQuestion()),
	        AdditionalInformationGenerator::KEY_QUESTION_REACHABLE_POINTS => $this->getPoints(),
	        AdditionalInformationGenerator::KEY_FEEDBACK => [
	            AdditionalInformationGenerator::KEY_QUESTION_FEEDBACK_ON_INCOMPLETE => $this->formatSAQuestion($this->feedbackOBJ->getGenericFeedbackTestPresentation($this->getId(), false)),
	            AdditionalInformationGenerator::KEY_QUESTION_FEEDBACK_ON_COMPLETE => $this->formatSAQuestion($this->feedbackOBJ->getGenericFeedbackTestPresentation($this->getId(), true))
	        ]
	    ];
	}
	
	// JSME -> SMILES, InChI to Log
	public function solutionValuesToLog(AdditionalInformationGenerator $additional_info, array $solution_values) : string
	{
	    if (!array_key_exists(0, $solution_values)
	        || !array_key_exists('value2', $solution_values[0]))
	    {
	            return '';
	     }
	       
	     $last  = end($solution_values);  // presumably the generated image and InChI
	     $prev  = prev($solution_values); // presumably the internal molecule code and the SMILES-representation
	     
	     if ((isset($last['value2']) && str_starts_with($last['value2'], 'InChI='))||
	         (isset($last['value1']) && str_starts_with($last['value1'], 'PHN2Zy')))
	     {
	         $solution_svg_inchi     = $last;
	         $solution_code_smiles   = $prev;
	     } else {
	         $solution_svg_inchi     = $prev;
	         $solution_code_smiles   = $last;
	     }

	     $value1 = $solution_code_smiles['value1'];
	     $value2 = $solution_code_smiles['value2'];
	     $value3 = $solution_svg_inchi['value1'];
	     $value4 = $solution_svg_inchi['value2'];
	        
	        
        return $this->refinery->string()->stripTags()->transform(
            html_entity_decode('SMILES=' . $value2 . ' + ' . $value4)
            );;
	}
	
	// JSME -> SMILES, InChI to Log
	public function solutionValuesToText(array $solution_values) : string
	{
	    if (!array_key_exists(0, $solution_values)
	        || !array_key_exists('value2', $solution_values[0]))
	    {
	        return '';
	    }
	    
	    $last  = end($solution_values);  // presumably the generated image and InChI
	    $prev  = prev($solution_values); // presumably the internal molecule code and the SMILES-representation
	    
	    if ((isset($last['value2']) && str_starts_with($last['value2'], 'InChI='))||
	        (isset($last['value1']) && str_starts_with($last['value1'], 'PHN2Zy')))
	    {
	        $solution_svg_inchi     = $last;
	        $solution_code_smiles   = $prev;
	    } else {
	        $solution_svg_inchi     = $prev;
	        $solution_code_smiles   = $last;
	    }
	    
	    $value1 = $solution_code_smiles['value1'];
	    $value2 = $solution_code_smiles['value2'];
	    $value3 = $solution_svg_inchi['value1'];
	    $value4 = $solution_svg_inchi['value2'];
	    
	    
	    return $this->refinery->string()->stripTags()->transform(
	        html_entity_decode('SMILES=' . $value2 . ' + ' . $value4)
	        );;
	}
	
	/**
	 * Saves a record to the question types additional data table.
	 *
	 * @return mixed
	 */
	public function saveAdditionalQuestionDataToDb()
	{
	    // nothing to save for JSME
	    return 0;
	}
	
}
?>