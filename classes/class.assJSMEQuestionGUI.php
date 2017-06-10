<?php
include_once "./Modules/TestQuestionPool/classes/class.assQuestionGUI.php";
include_once "./Modules/Test/classes/inc.AssessmentConstants.php";

/**
 * The assJSMEQuestionGUI class encapsulates the GUI representation
 * for Question-Type-Plugin.
 *
 * @author Yves Annanias <yves.annanias@llz.uni-halle.de>
 * @version	$Id: $
 * @ingroup 	ModulesTestQuestionPool
 *
 * @ilctrl_iscalledby assJSMEQuestionGUI: ilObjQuestionPoolGUI, ilObjTestGUI, ilQuestionEditGUI, ilTestExpressPageObjectGUI
 */
class assJSMEQuestionGUI extends assQuestionGUI
{	
	var $plugin = null;
	
	/**
	* assJSMEQuestionGUI constructor
	*
	* The constructor takes possible arguments and creates an instance of the assJSMEQuestionGUI object.
	*
	* @param integer $id The database id of a question object
	* @access public
	*/
	public function __construct($id = -1)
	{
		parent::__construct();
		include_once "./Services/Component/classes/class.ilPlugin.php";
		$this->plugin = ilPlugin::getPluginObject(IL_COMP_MODULE, "TestQuestionPool", "qst", "assJSMEQuestion");
		$this->plugin->includeClass("class.assJSMEQuestion.php");
		$this->object = new assJSMEQuestion();
		$this->newUnitId = null;
		if ($id >= 0)
		{
			$this->object->loadFromDb($id);
		}
	}

	
	/**
	 * Command: edit the question
	 */
	public function editQuestion()
	{
		$this->initQuestionForm();
		$this->getQuestionTemplate();
		$this->tpl->setVariable("QUESTION_DATA", $this->form->getHTML());
	}
	
	/**
	 * Command: save the question
	 */
	public function save()
	{
		// assQuestionGUI::save() 
		// - calls writePostData
		// - redirects after successful saving
		// - otherwise does nothing
		parent::save();
		
		// question couldn't be saved
		$this->form->setValuesByPost();
		$this->getQuestionTemplate();
		$this->tpl->setVariable("QUESTION_DATA", $this->form->getHTML());
	}
	
	/**
	 * Command: save and show page editor
	 */
	public function saveEdit()
	{
		// assQuestionGUI::saveEdit() 
		// - calls writePostData
		// - redirects after successful saving
		// - otherwise does nothing
		parent::saveEdit();
		
		// question couldn't be saved
		$this->form->setValuesByPost();
		$this->getQuestionTemplate();
		$this->tpl->setVariable("QUESTION_DATA", $this->form->getHTML());
	}

	/**
	* Creates an output of the edit form for the question
	*
	* @param	boolean		add a new booking to the form
	*/
	private function initQuestionForm()
	{
		include_once("./Services/Form/classes/class.ilPropertyFormGUI.php");
		$form = new ilPropertyFormGUI();
		$form->setFormAction($this->ctrl->getFormAction($this));
		$form->setTitle($this->outQuestionType());
		$form->setMultipart(FALSE);
		$form->setTableWidth("100%");
		$form->setId("assJSMEQuestion");

		// title, author, description, question, working time (assessment mode)
		$this->addBasicQuestionFormProperties($form);

		if ($this->object->getId())
		{
			$hidden = new ilHiddenInputGUI("", "ID");
			$hidden->setValue($this->object->getId());
			$form->addItem($hidden);
		}

		// points
		$plugin = $this->object->getPlugin();
		$points = new ilNumberInputGUI($plugin->txt("points"), "points");
		$points->setSize(3);
		$points->setMinValue(0);
		$points->allowDecimals(1);
		$points->setRequired(true);
		$points->setValue($this->object->getPoints());
		$form->addItem($points);
		
		// optionString for the JSME-Applet
		include_once("./Services/Form/classes/class.ilTextInputGUI.php");
		$optionString = new ilTextInputGUI($plugin->txt("optionString"), "optionString");		
		$optionString->setValue($this->object->getOptionString());				
		$form->addItem($optionString);
		
		// JSME-Applet for sampleSolution
		include_once("./Services/Form/classes/class.ilCustomInputGUI.php");
		$sampleSolution = new ilCustomInputGUI($plugin->txt("sampleSolution"), "sampleSolution");
		$template = $this->getQuestionOutput("", $this->object->getOptionString(), $this->object->getSampleSolution(), $this->object->getSmilesSolution(), $this->object->getSvg());
		$sampleSolution->setHtml($template->get());
		$form->addItem($sampleSolution);												


		$this->populateTaxonomyFormSection($form);
		$this->addQuestionFormCommandButtons($form);
		$this->form = $form;
	}

	/**
	* Evaluates a posted edit form and writes the form data in the question object
	* (called from generic commands in assQuestionGUI)
	*
	* @return integer 	0: question can be saved / 1: form is not complete
	*/
	public function writePostData($always = false)
	{
		$this->initQuestionForm();
		if ($this->form->checkInput())
		{
            $error = '';
			
            // write the basic data
			$this->writeQuestionGenericPostData();

			$this->object->setPoints(str_replace( ",", ".", $_POST["points"] ));				
			$this->object->setOptionString($_POST["optionString"]);
			$this->object->setSampleSolution($_POST["sampleSolution"]);
			$this->object->setSmilesSolution($_POST["smilesSolution"]);
			$this->object->setSvg($_POST["svgSolution"]);
			
			// save taxonomy assignment
			$this->saveTaxonomyAssignments();

			// indicator to save the question
			return 0;

		}
		else
		{
			// indicator to show the edit form with errors
			return 1;
		}
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
	public function getTestOutput($active_id, $pass = NULL, $is_postponed = FALSE, $use_post_solutions = FALSE, $show_feedback = FALSE)
	{
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
		
		$value1_temp_array = explode('++++SVG++++', $user_solution[0]["value1"]);
		$userSampleSolution = $value1_temp_array[0];
		$userSvg = base64_decode($value1_temp_array[1]);
		
		$template = $this->getQuestionOutput($this->object->getQuestion(), $this->object->getOptionString(), $userSampleSolution, $user_solution[0]["value2"], $userSvg);
		$questionoutput = $template->get();
		$pageoutput = $this->outQuestionPage("", $is_postponed, $active_id, $questionoutput);
		return $pageoutput; 
	}
	
	
	/**
	 * Get the output for preview and test
	 */
	function getQuestionOutput($question, $options, $solution ,$smiles, $svg, $temp="output.html"){
		global $tpl;			
		$plugin       = $this->object->getPlugin();		
		$template     = $plugin->getTemplate($temp);
		$tpl->addJavaScript($plugin->getDirectory().'/templates/jsme/jsme.nocache.js');
		$template->setVariable("QUESTIONTEXT", $this->object->prepareTextareaOutput($question, TRUE));		
		$template->setVariable("MOLECULE",$solution);
		$template->setVariable("SMILES",$smiles);
		$template->setVariable("OPTIONS", $options);
		$template->setVariable("SVG", $svg);
		
		return $template;
	}	

	
	/**
	 * Get the output for question preview
	 * (called from ilObjQuestionPoolGUI)
	 * 
	 * @param boolean	show only the question instead of embedding page (true/false)
	 */
	public function getPreview($show_question_only = false, $showInlineFeedback = false)
	{
		$template = $this->getQuestionOutput($this->object->getQuestion(), $this->object->getOptionString(), "", "", "");		
		$questionoutput = $template->get();
		if(!$show_question_only)
		{
			// get page object output
			$questionoutput = $this->getILIASPage($questionoutput);
		}
		
		return $questionoutput;
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

		// generate the question output
		$solutiontemplate = new ilTemplate("tpl.il_as_tst_solution_output.html",TRUE, TRUE, "Modules/TestQuestionPool");
				
		if ($show_correct_solution)
		{			
			//$template = $this->getQuestionOutput("", $this->object->getOptionString(), $this->object->getSampleSolution(), $this->object->getSmilesSolution(), $this->object->getSvg(), "solution.html");		
			$template = $this->getQuestionOutput("", "", "", $this->object->getSmilesSolution(), $this->object->getSvg(), "solution.html");
			$template->setVariable("ID", 'S'.$this->object->getId());
			return $template->get();			
			// hier nur die Musterlösung anzeigen, da wir uns im test beim drücken von check befinden ;)
		}				
		
		$value1_temp_array = explode('++++SVG++++', $user_solution[0]["value1"]);
		$userSampleSolution = $value1_temp_array[0];
		$userSvg = base64_decode($value1_temp_array[1]);
		
		//$templateUser = $this->getQuestionOutput($this->object->getQuestion(), $this->object->getOptionString(), $userSampleSolution, $user_solution[0]["value2"], $userSvg, "solution.html");	
		$templateUser = $this->getQuestionOutput($this->object->getQuestion(), "", "", $user_solution[0]["value2"], $userSvg, "solution.html");
		$templateUser->setVariable("ID", 'U'.$this->object->getId());	
		$questionoutput = $templateUser->get();
		
		if ($show_manual_scoring && strlen($this->object->getSampleSolution()) > 0 )
		{
			//$templateSample = $this->getQuestionOutput($this->object->getPlugin()->txt("sampleSolution"), $this->object->getOptionString(), $this->object->getSampleSolution(), $this->object->getSmilesSolution(), $this->object->getSvg(), "solution.html");
			$templateSample = $this->getQuestionOutput($this->object->getPlugin()->txt("sampleSolution"), "", "", $this->object->getSmilesSolution(), $this->object->getSvg(), "solution.html");
			$templateSample->setVariable("ID", 'S'.$this->object->getId());
			$questionoutput .= "<br>" . $templateSample->get();
		}

		// add the feedback
		$feedback = ($show_feedback) ? $this->getAnswerFeedbackOutput($active_id, $pass) : "";
		if (strlen($feedback)) 
		{
			$solutiontemplate->setVariable("FEEDBACK", $feedback);
		}
		
		$solutiontemplate->setVariable("SOLUTION_OUTPUT", $questionoutput);

		$solutionoutput = $solutiontemplate->get(); 
		
	if (!$show_question_only)
		{
			// get page object output
			$solutionoutput = $this->getILIASPage($solutionoutput);
		}
		
		return $solutionoutput;
	}

	/**
	* Saves the feedback for a question
	*/
	public function saveFeedback()
	{
		include_once "./Services/AdvancedEditing/classes/class.ilObjAdvancedEditing.php";
		$errors = $this->feedback(true);
		$this->object->saveFeedbackGeneric(0, $_POST["feedback_incomplete"]);
		$this->object->saveFeedbackGeneric(1, $_POST["feedback_complete"]);
		$this->object->cleanupMediaObjectUsage();
		parent::saveFeedback();
	}

	/**
	 * Returns the answer specific feedback for the question
	 * 
	 * @param integer $active_id Active ID of the user
	 * @param integer $pass Active pass
	 * @return string HTML Code with the answer specific feedback
	 * @access public
	 */
	function getSpecificFeedbackOutput($active_id, $pass)
	{
		// Currently not supported
		$output = "";
		return $this->object->prepareTextareaOutput($output, TRUE);
	}
	
	
	/**
	* Sets the ILIAS tabs for this question type
	* called from ilObjTestGUI and ilObjQuestionPoolGUI
	*/
	public function setQuestionTabs()
	{
		global $rbacsystem, $ilTabs;
		
		$this->ctrl->setParameterByClass("ilpageobjectgui", "q_id", $_GET["q_id"]);
		include_once "./Modules/TestQuestionPool/classes/class.assQuestion.php";
		$q_type = $this->object->getQuestionType();

		if (strlen($q_type))
		{
			$classname = $q_type . "GUI";
			$this->ctrl->setParameterByClass(strtolower($classname), "sel_question_types", $q_type);
			$this->ctrl->setParameterByClass(strtolower($classname), "q_id", $_GET["q_id"]);
		}

		if ($_GET["q_id"])
		{
			if ($rbacsystem->checkAccess('write', $_GET["ref_id"]))
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
		if ($rbacsystem->checkAccess('write', $_GET["ref_id"]))
		{
			$url = "";
			if ($classname) $url = $this->ctrl->getLinkTargetByClass($classname, "editQuestion");
			$commands = $_POST["cmd"];
			// edit question properties
			$ilTabs->addTarget("edit_properties",
				$url,
				array("editQuestion", "save", "cancel", "cancelExplorer", "linkChilds", 
				"parseQuestion", "saveEdit"),
				$classname, "", $force_active);
		}

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
		if ($_GET["q_id"])
		{
			$ilTabs->addTarget("statistics",
				$this->ctrl->getLinkTargetByClass($classname, "assessment"),
				array("assessment"),
				$classname, "");
		}
		
		if (($_GET["calling_test"] > 0) || ($_GET["test_ref_id"] > 0))
		{
			$ref_id = $_GET["calling_test"];
			if (strlen($ref_id) == 0) $ref_id = $_GET["test_ref_id"];
			$ilTabs->setBackTarget($this->lng->txt("backtocallingtest"), "ilias.php?baseClass=ilObjTestGUI&cmd=questions&ref_id=$ref_id");
		}
		else
		{
			$ilTabs->setBackTarget($this->lng->txt("qpl"), $this->ctrl->getLinkTargetByClass("ilobjquestionpoolgui", "questions"));
		}
	}
}
?>