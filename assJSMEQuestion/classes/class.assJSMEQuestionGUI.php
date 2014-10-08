<?php

include_once "./Modules/TestQuestionPool/classes/class.assQuestionGUI.php";
include_once "./Modules/Test/classes/inc.AssessmentConstants.php";

/**
* The assJSMEQuestionGUI class encapsulates the GUI representation
* for Question-Type-Plugin.
*
* @author Yves Annanias <yves.annanias@llz.uni-halle.de>
* @ilctrl_iscalledby assJSMEQuestionGUI: ilObjQuestionPoolGUI, ilObjTestGUI, ilQuestionEditGUI, ilTestExpressPageObjectGUI, ilAssQuestionPageGUI
* */
class assJSMEQuestionGUI extends assQuestionGUI
{		
	
	var $plugin = null;
	
	/**
	* assJSMEQuestionGUI constructor	
	* The constructor takes possible arguments an creates an instance of the assJSMEQuestionGUI object.
	*
	* @param integer $id The database id of a TemplateQuestion question object
	* @access public
	*/
	function __construct($id = -1)
	{
		parent::__construct();
		include_once "./Services/Component/classes/class.ilPlugin.php";
		$plugin = ilPlugin::getPluginObject(IL_COMP_MODULE, "TestQuestionPool", "qst", "assJSMEQuestion");
		$plugin->includeClass("class.assJSMEQuestion.php");
		$this->object = new assJSMEQuestion();	
		if ($id >= 0)
		{
			$this->object->loadFromDb($id);
		}		
	}

	/**
	* Creates an output of the edit form for the question	
	* @access public
	*/
	function editQuestion()
	{
		global $ilDB, $tpl;					
			
		$plugin = $this->object->getPlugin();		
		
		$this->getQuestionTemplate();
		include_once("./Services/Form/classes/class.ilPropertyFormGUI.php");
		$form = new ilPropertyFormGUI();
		$form->setFormAction($this->ctrl->getFormAction($this));
		$form->setTitle($this->outQuestionType());
		$form->setMultipart(FALSE);
		$form->setTableWidth("100%");
		$form->setId("assJSMEQuestion");
		// Basiseingabefelder: title, author, description, question, working time (assessment mode)		
		$this->addBasicQuestionFormProperties($form);
		// points
		$points = new ilNumberInputGUI($plugin->txt("points"), "points");
		$points->setValue($this->object->getPoints());
		$points->setRequired(TRUE);
		$points->setSize(10);
		$points->setMinValue(0.0);
		$form->addItem($points);	
		
		// optionString for the JSME-Applet
		include_once("./Services/Form/classes/class.ilTextInputGUI.php");
		$optionString = new ilTextInputGUI($plugin->txt("optionString"), "optionString");		
		$optionString->setValue($this->object->getOptionString());				
		$form->addItem($optionString);
		
		// JSME-Applet for sampleSolution
		include_once("./Services/Form/classes/class.ilCustomInputGUI.php");
		$sampleSolution = new ilCustomInputGUI($plugin->txt("sampleSolution"), "sampleSolution");
		$template = $this->getJsmeOutputTemplate("", $this->object->getOptionString(), $this->object->getSampleSolution(), $this->object->getSmilesSolution() );
		$sampleSolution->setHtml($template->get());
		$form->addItem($sampleSolution);												
		
		$form->addCommandButton('save', $plugin->txt("save"));
		
		$this->tpl->setVariable("QUESTION_DATA", $form->getHTML());		
	}
	
	/**
	 * Save data to DB
	 */
	function save()
	{	
		$plugin = $this->object->getPlugin();
		$result = $this->writePostData();

		if($result == 1)
		{						
			ilUtil::sendFailure($plugin->txt("errorInput"), true);
			$this->editQuestion();
		}
		else
		{
			parent::save();
		}
	}
	
	/**
	* check input fields
	*/
	function checkInput()
	{		
		if ((!$_POST["title"]) or (!$_POST["author"]) or (!$_POST["question"]) or (strlen($_POST["points"]) == 0) or ($_POST["points"] < 0) )
		{			
			return FALSE;
		}	
		return TRUE;
	}

	/**
	* Evaluates a posted edit form and writes the form data in the question object	
	* @return integer A positive value, if one of the required fields wasn't set, else 0
	*/
	function writePostData($always = false)
	{		
		$this->editQuestion();
		if ($this->checkInput())
		{			
			$this->object->setTitle($_POST["title"]);
			$this->object->setAuthor($_POST["author"]);
			$this->object->setComment($_POST["comment"]);
			$this->object->setOwner($_POST["owner"]);
			$this->object->setPoints($_POST["points"]);				
			include_once "./Services/AdvancedEditing/classes/class.ilObjAdvancedEditing.php";			
			$this->object->setQuestion($_POST["question"]);
			$this->object->setEstimatedWorkingTime(
				$_POST["Estimated"]["hh"],
				$_POST["Estimated"]["mm"],
				$_POST["Estimated"]["ss"]
			);							
			$this->object->setOptionString($_POST["optionString"]);
			$this->object->setSampleSolution($_POST["sampleSolution"]);
			$this->object->setSmilesSolution($_POST["smilesSolution"]);
																
			return 0;
		}
		else
		{
			return 1;
		}
	}		
	
	/**
	 * Get the output for preview and test
	 */
	function getJsmeOutputTemplate($question, $options, $solution ,$smiles, $temp="output.html"){
		global $tpl;			
		$plugin       = $this->object->getPlugin();		
		$template     = $plugin->getTemplate($temp);
		$tpl->addJavaScript($plugin->getDirectory().'/templates/jsme/jsme.nocache.js');
		$template->setVariable("QUESTIONTEXT", $this->object->prepareTextareaOutput($question, TRUE));		
		$template->setVariable("MOLECULE",$solution);
		$template->setVariable("SMILES",$smiles);
		$template->setVariable("OPTIONS", $options);		
		return $template;
	}		
	
	/**
	 * Get the output for question preview
	 * (called from ilObjQuestionPoolGUI)
	 * 
	 * @param boolean	show only the question instead of embedding page (true/false)
	 */
	function getPreview($show_question_only = FALSE)
	{		
		$template = $this->getJsmeOutputTemplate($this->object->getQuestion(), $this->object->getOptionString(), "", "");		
		$questionoutput = $template->get();
		if(!$show_question_only)
		{
			// get page object output
			$questionoutput = $this->getILIASPage($questionoutput);
		}
		
		return $questionoutput;
	}

	/**
	 * Show the question in Test mode
	 * (called from ilTestOutputGUI)
	 * 
	 * @param string $formaction			The action link for the form
	 * @param integer $active_id			The active user id
	 * @param integer $pass					The test pass
	 * @param boolean $is_postponed			Question is postponed
	 * @param boolean $use_post_solutions	Use post solutions
	 * @param boolean $show_feedback		Show a feedback
	 */
	function outQuestionForTest($formaction, $active_id, $pass = NULL, $is_postponed = FALSE, $use_post_solutions = FALSE, $show_feedback = FALSE)
	{		
		$test_output = $this->getTestOutput($active_id, $pass, $is_postponed, $use_post_solutions, $show_feedback); 
		$this->tpl->setVariable("QUESTION_OUTPUT", $test_output);
		$this->tpl->setVariable("FORMACTION", $formaction);	
	}
	
	/**
	 * Get the HTML output of the question for a test
	 * 
	 * @param integer $active_id			The active user id
	 * @param integer $pass					The test pass
	 * @param boolean $is_postponed			Question is postponed
	 * @param boolean $use_post_solutions	Use post solutions
	 * @param boolean $show_feedback		Show a feedback
	 * @return string
	 */	
	function getTestOutput($active_id, $pass = NULL, $is_postponed = FALSE, $use_post_solutions = FALSE, $show_feedback = FALSE)
	{
		global $tpl;
		// get the solution of the user for the active pass or from the last pass if allowed
		$user_solution = array();
		if ($active_id)
		{
			include_once "./Modules/Test/classes/class.ilObjTest.php";
			if (!ilObjTest::_getUsePreviousAnswers($active_id, true))
			{
				if (is_null($pass)) $pass = ilObjTest::_getPass($active_id);
			}
			$user_solution =& $this->object->getSolutionValues($active_id, $pass);
			if (!is_array($user_solution)) 
			{
				$user_solution = array();
			}
		}
		
		$template = $this->getJsmeOutputTemplate($this->object->getQuestion(), $this->object->getOptionString(), $user_solution[0]["value1"], $user_solution[0]["value2"]);		
		$questionoutput = $template->get();
		$pageoutput = $this->outQuestionPage("", $is_postponed, $active_id, $questionoutput);
		return $pageoutput;		
	}

	/**
	* Get the question solution output
	*
	* @param integer $active_id The active user id
	* @param integer $pass The test pass
	* @param boolean $graphicalOutput Show visual feedback for right/wrong answers
	* @param boolean $result_output Show the reached points for parts of the question
	* @param boolean $show_question_only Show the question without the ILIAS content around
	* @param boolean $show_feedback Show the question feedback
	* @param boolean $show_correct_solution Show the correct solution instead of the user solution
	* @param boolean $show_manual_scoring Show specific information for the manual scoring output
	* @return The solution output of the question as HTML code
	*/
	function getSolutionOutput( 
		$active_id,
		$pass = NULL,
		$graphicalOutput = FALSE,
		$result_output = FALSE,
		$show_question_only = TRUE,
		$show_feedback = FALSE,
		$show_correct_solution = FALSE,
		$show_manual_scoring = FALSE,
		$show_question_text = TRUE
	)
	{
		global $tpl;
		// get the solution of the user for the active pass or from the last pass if allowed
		$user_solution = array();
		if (($active_id > 0) && (!$show_correct_solution))
		{
			// get the solutions of a user
			$user_solution =& $this->object->getSolutionValues($active_id, $pass);
			if (!is_array($user_solution)) 
			{
				$user_solution = array();
			}
		} else {			
			$user_solution = array();
		}						
		
		if ($show_correct_solution)
		{			
			$template = $this->getJsmeOutputTemplate("", $this->object->getOptionString(), $this->object->getSampleSolution(), $this->object->getSmilesSolution(), "solution.html");		
			$template->setVariable("ID", 'S'.$this->object->getId());
			return $template->get();			
			// hier nur die Musterlösung anzeigen, da wir uns im test beim drücken von check befinden ;)
		}				
		
		// generate the question output
		$solutiontemplate = new ilTemplate("tpl.il_as_tst_solution_output.html",TRUE, TRUE, "Modules/TestQuestionPool");
		
		$templateUser = $this->getJsmeOutputTemplate($this->object->getQuestion(), $this->object->getOptionString(), $user_solution[0]["value1"], $user_solution[0]["value2"], "solution.html");	
		$templateUser->setVariable("ID", 'U'.$this->object->getId());	
		$questionoutput = $templateUser->get();
		
		if ($show_manual_scoring && strlen($this->object->getSampleSolution()) > 0 )
		{
			$templateSample = $this->getJsmeOutputTemplate($this->object->getPlugin()->txt("sampleSolution"), $this->object->getOptionString(), $this->object->getSampleSolution(), $this->object->getSmilesSolution(), "solution.html");
			$templateSample->setVariable("ID", 'S'.$this->object->getId());
			$questionoutput .= "<br>" . $templateSample->get();
		}

		$feedback = '';
		if($show_feedback)
		{
			$fb = $this->getGenericFeedbackOutput($active_id, $pass);
			$feedback .=  strlen($fb) ? $fb : '';
			
			$fb = $this->getSpecificFeedbackOutput($active_id, $pass);
			$feedback .=  strlen($fb) ? $fb : '';
		}
		if (strlen($feedback)) $solutiontemplate->setVariable("FEEDBACK", $this->object->prepareTextareaOutput($feedback, true));
		
		$solutiontemplate->setVariable("SOLUTION_OUTPUT", $questionoutput);

		$solutionoutput = $solutiontemplate->get(); 
		
		if (!$show_question_only)
		{
			// get page object output
			$solutionoutput = '<div class="ilc_question_Standard">'.$solutionoutput.'</div>';
		}
		
		return $solutionoutput;
	}

	/**
	* Saves the feedback for the question
	*
	* @access public
	*/
	function saveFeedback()
	{		
		include_once "./Services/AdvancedEditing/classes/class.ilObjAdvancedEditing.php";
		$this->object->saveFeedbackGeneric(0, $_POST["feedback_incomplete"]);
		$this->object->saveFeedbackGeneric(1, $_POST["feedback_complete"]);
		$this->object->cleanupMediaObjectUsage();
		parent::saveFeedback();
	}

	/**	
	 * Sets the ILIAS tabs for this question type
	 * @access public
	 */
	function setQuestionTabs()
	{
		global $rbacsystem, $ilTabs;

		$this->ctrl->setParameterByClass("ilAssQuestionPageGUI", "q_id", $_GET["q_id"]);
		include_once "./Modules/TestQuestionPool/classes/class.assQuestion.php";
		$q_type = $this->object->getQuestionType();

		if(strlen($q_type))
		{
			$classname = $q_type . "GUI";
			$this->ctrl->setParameterByClass(strtolower($classname), "sel_question_types", $q_type);
			$this->ctrl->setParameterByClass(strtolower($classname), "q_id", $_GET["q_id"]);
		}

		if($_GET["q_id"])
		{
			if($rbacsystem->checkAccess('write', $_GET["ref_id"]))
			{
				// edit page
				$ilTabs->addTarget("edit_content",
					$this->ctrl->getLinkTargetByClass("ilAssQuestionPageGUI", "edit"),
					array("edit", "insert", "exec_pg"),
					"", "", $force_active);
			}

			// edit page
			$ilTabs->addTarget("preview",
				$this->ctrl->getLinkTargetByClass("ilAssQuestionPageGUI", "preview"),
				array("preview"),
				"ilAssQuestionPageGUI", "", $force_active);
		}

		$force_active = false;
		if($rbacsystem->checkAccess('write', $_GET["ref_id"]))
		{
			$url = "";
		
			if($classname) $url = $this->ctrl->getLinkTargetByClass($classname, "editQuestion");
			$commands = $_POST["cmd"];
			if(is_array($commands))
			{
				foreach($commands as $key => $value)
				{
					if(preg_match("/^suggestrange_.*/", $key, $matches))
					{
						$force_active = true;
					}
				}
			}
			// edit question properties
			$ilTabs->addTarget("edit_properties",
				$url,
				array(
					"editQuestion", "save", "cancel", "addSuggestedSolution",
					"cancelExplorer", "linkChilds", "removeSuggestedSolution",
					"saveEdit", "suggestRange"
				),
				$classname, "", $force_active);						
		}
		/*
		if($_GET["q_id"])
		{
			$ilTabs->addTarget("feedback",
				$this->ctrl->getLinkTargetByClass($classname, "feedback"),
				array("feedback", "saveFeedback"),
				$classname, "");
		}
		*/
		// add tab for question feedback within common class assQuestionGUI
		$this->addTab_QuestionFeedback($ilTabs);
		// add tab for question hint within common class assQuestionGUI
		$this->addTab_QuestionHints($ilTabs);

		if ($_GET["q_id"])
		{
			$ilTabs->addTarget("solution_hint",
				$this->ctrl->getLinkTargetByClass($classname, "suggestedsolution"),
				array("suggestedsolution", "saveSuggestedSolution", "outSolutionExplorer", "cancel", 
				"addSuggestedSolution","cancelExplorer", "linkChilds", "removeSuggestedSolution"
				),
				$classname, 
				""
			);
		}

		// Assessment of questions sub menu entry
		if($_GET["q_id"])
		{
			$ilTabs->addTarget("statistics",
				$this->ctrl->getLinkTargetByClass($classname, "assessment"),
				array("assessment"),
				$classname, "");
		}

		if(($_GET["calling_test"] > 0) || ($_GET["test_ref_id"] > 0))
		{
			$ref_id = $_GET["calling_test"];
			if(strlen($ref_id) == 0) $ref_id = $_GET["test_ref_id"];
			$ilTabs->setBackTarget($this->lng->txt("backtocallingtest"), "ilias.php?baseClass=ilObjTestGUI&cmd=questions&ref_id=$ref_id");
		}
		else
		{
			$ilTabs->setBackTarget($this->lng->txt("qpl"), $this->ctrl->getLinkTargetByClass("ilobjquestionpoolgui", "questions"));
		}
	}	
	
	/**
	 * Returns the answer specific feedback for the question
	 * 
	 * @param integer $active_id Active ID of the user
	 * @param integer $pass Active pass
	 * @return string HTML Code with the answer specific feedback
	 * @access public
	 */
	public function getSpecificFeedbackOutput($active_id, $pass)
	{
		return "";
	}
	
	public function getAnswerFeedbackOutput($active_id, $pass)
	{
		include_once "./Modules/Test/classes/class.ilObjTest.php";
		$manual_feedback = ilObjTest::getManualFeedback($active_id, $this->object->getId(), $pass);
		if (strlen($manual_feedback))
		{
			return $manual_feedback;
		}
		$correct_feedback = $this->object->getFeedbackGeneric(1);
		$incorrect_feedback = $this->object->getFeedbackGeneric(0);
		if (strlen($correct_feedback.$incorrect_feedback))
		{
			$reached_points = $this->object->calculateReachedPoints($active_id, $pass);
			$max_points = $this->object->getMaximumPoints();
			if ($reached_points == $max_points)
			{
				$output .= $correct_feedback;
			}
			else
			{
				$output .= $incorrect_feedback;
			}
		}
		$test = new ilObjTest($this->object->active_id);
		return $this->object->prepareTextareaOutput($output, TRUE);		
	}
	
	/**
	* Creates the output of the feedback page for a TemplateQuestion question
	*
	* @access public
	*/
	function feedback($checkonly = false)
	{		
		$save = (strcmp($this->ctrl->getCmd(), "saveFeedback") == 0) ? TRUE : FALSE;
		include_once("./Services/Form/classes/class.ilPropertyFormGUI.php");
		$form = new ilPropertyFormGUI();
		$form->setFormAction($this->ctrl->getFormAction($this));
		$form->setTitle('feedback_answers');
		$form->setTableWidth("100%");
		$form->setId("feedback");

		$complete = new ilTextAreaInputGUI("feedback_complete_solution", "feedback_complete");
		$complete->setValue($this->object->prepareTextareaOutput($this->object->getFeedbackGeneric(1)));
		$complete->setRequired(false);
		$complete->setRows(10);
		$complete->setCols(80);
		if (!$this->getPreventRteUsage())
		{
			$complete->setUseRte(true);
		}
		include_once "./Services/AdvancedEditing/classes/class.ilObjAdvancedEditing.php";
		$complete->setRteTags(ilObjAdvancedEditing::_getUsedHTMLTags("assessment"));
		$complete->addPlugin("latex");
		$complete->addButton("latex");
		$complete->addButton("pastelatex");
		$complete->setRTESupport($this->object->getId(), "qpl", "assessment", null, false, '3.4.7');
		$form->addItem($complete);

		$incomplete = new ilTextAreaInputGUI("feedback_incomplete_solution", "feedback_incomplete");
		$incomplete->setValue($this->object->prepareTextareaOutput($this->object->getFeedbackGeneric(0)));
		$incomplete->setRequired(false);
		$incomplete->setRows(10);
		$incomplete->setCols(80);
		if (!$this->getPreventRteUsage())
		{
			$incomplete->setUseRte(true);
		}
		include_once "./Services/AdvancedEditing/classes/class.ilObjAdvancedEditing.php";
		$incomplete->setRteTags(ilObjAdvancedEditing::_getUsedHTMLTags("assessment"));
		$incomplete->addPlugin("latex");
		$incomplete->addButton("latex");
		$incomplete->addButton("pastelatex");
		$incomplete->setRTESupport($this->object->getId(), "qpl", "assessment", null, false, '3.4.7');
		$form->addItem($incomplete);

		global $ilAccess;
		if ($ilAccess->checkAccess("write", "", $_GET['ref_id']) || $this->getSelfAssessmentEditingMode())
		{
			$form->addCommandButton("saveFeedback", "save");
		}
		if ($save)
		{
			$form->setValuesByPost();
			$errors = !$form->checkInput();
			$form->setValuesByPost(); // again, because checkInput now performs the whole stripSlashes handling and we need this if we don't want to have duplication of backslashes
		}
		if (!$checkonly) $this->tpl->setVariable("ADM_CONTENT", $form->getHTML());
		return $errors;
	}
}
?>
