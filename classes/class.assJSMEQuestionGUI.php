<?php

/**
 * The assJSMEQuestionGUI class encapsulates the GUI representation
 * for Question-Type-Plugin.
 *
 * @author Christoph Jobst <iliasplugins.christoph.jobst@outlook.de>
 * @version	$Id: $
 * @ingroup 	ModulesTestQuestionPool
 *
 * @ilctrl_iscalledby assJSMEQuestionGUI: ilObjQuestionPoolGUI, ilObjTestGUI, ilQuestionEditGUI, ilTestExpressPageObjectGUI
 * @ilctrl_calls assJSMEQuestionGUI: ilFormPropertyDispatchGUI
 */
class assJSMEQuestionGUI extends assQuestionGUI implements ilGuiQuestionScoringAdjustable
{	
    /**
     * @var assJSMEQuestionPlugin	The plugin object
     */
	var $plugin = null;
	
	/**
	 * @var assJSMEQuestion	The question object
	 */
	public assQuestion $object;
	
	public function getPlugin(): ilPlugin
	{
	    return $this->plugin;
	}
	
	public function setPlugin(ilPlugin $plugin): void
	{
	    $this->plugin = $plugin;
	}
	
	/**
	 * Constructor
	 *
	 * @param integer $id The database id of a question object
	 * @access public
	 */
	public function __construct($id = -1)
	{
	    global $tpl;
	    
	    parent::__construct();
	    
	    // init the plugin object
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
	    
	    $this->object = new assJSMEQuestion();
	    if ($id >= 0)
	    {
	        $this->object->loadFromDb($id);
	    }
	}
	
	/**
	* Creates an output of the edit form for the question
	*
	* @param bool $checkonly
	* @param bool $is_save_cmd
	* @return bool
	*/
	public function editQuestion(
	    bool $checkonly = false,
	    ?bool $is_save_cmd = null
	    ): bool 
	{
	    global $ilDB;
	    
	    $save = $is_save_cmd ?? $this->isSaveCommand();
	    $plugin = $this->object->getPlugin();
	    
	    $this->getQuestionTemplate();
	    $form = new ilPropertyFormGUI();
	    $this->editForm = $form;
	    
	    $form->setFormAction($this->ctrl->getFormAction($this));
	    $form->setTitle($this->plugin->txt("questionType"));
	    $form->setMultipart(FALSE);
	    $form->setTableWidth("100%");
	    $form->setId("assJSMEQuestion");

	    // Baseinput: title, author, description, question, working time (assessment mode)
	    $this->addBasicQuestionFormProperties($form);
	    
	    // TODO Evaluate
	    if ($this->object->getId())
	    {
	        $hidden = new ilHiddenInputGUI("", "ID");
	        $hidden->setValue($this->object->getId());
	        $form->addItem($hidden);
	    }
	    
	    $this->populateQuestionSpecificFormPart($form);
	    $this->populateAnswerSpecificFormPart($form);
	    
	    $this->populateTaxonomyFormSection($form);
	    $this->addQuestionFormCommandButtons($form);
	    
	    $errors = false;
	    
	    if ($save)
	    {
	        $form->setValuesByPost();
	        $errors = !$form->checkInput();
	        $form->setValuesByPost(); // again, because checkInput now performs the whole stripSlashes handling and we need this if we don't want to have duplication of backslashes
	        if ($errors) $checkonly = false;
	    }
	    
	    if (!$checkonly)
	    {
	        $this->tpl->setVariable("QUESTION_DATA", $form->getHTML());
	    }
	    return $errors;
	}

	/**
	 * Evaluates a posted edit form and writes the form data in the question object
	 *
	 * @param bool $always
	 * @return integer A positive value, if one of the required fields wasn't set, else 0
	 */
	protected function writePostData($always = false): int
	{
	    $hasErrors = (!$always) ? $this->editQuestion(true) : false;
	    if (!$hasErrors)
		{
            // write the basic data
			$this->writeQuestionGenericPostData();

			$this->object->setPoints((int) $_POST["points"]);
			$this->object->setOptionString($_POST["optionString"]);
			$this->object->setSampleSolution($_POST["sampleSolution"]);
			$this->object->setSmilesSolution($_POST["smilesSolution"]);
			$this->object->setSvg($_POST["svgSolution"]);
			$this->object->setInchiSolution($_POST["inchiSolution"]);
			$this->object->setEvaluationOption($_POST["evalOption"]);
			
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
	 * Get the output for preview and test
	 */
	function getQuestionOutput($question, $options, $solution ,$smiles, $svg, $inchi, $temp="output.html")
	{
	    global $DIC, $tpl;
	    
	    $DIC->globalScreen()->layout()->meta()->addJs('Customizing/global/plugins/Modules/TestQuestionPool/Questions/assJSMEQuestion//templates/default/jsme/96E40B969193BD74B8A621486920E79C.cache.js');
	    $DIC->globalScreen()->layout()->meta()->addJs('Customizing/global/plugins/Modules/TestQuestionPool/Questions/assJSMEQuestion/templates/default/jsme/jsme.nocache.js');
	    	    
	    $template = new ilTemplate($temp, true, true, 'public/Customizing/global/plugins/Modules/TestQuestionPool/Questions/assJSMEQuestion');
	    $template->setVariable("QUESTIONTEXT", self::prepareTextareaOutput($question, TRUE));
	    $template->setVariable("MOLECULE",$solution);
	    $template->setVariable("SMILES",$smiles);
	    $template->setVariable("INCHI",$inchi);
	    $template->setVariable("OPTIONS", $options);
	    $template->setVariable("SVG", $svg);
	    
	    return $template;
	}	
	
	/**
	 * Get the HTML output of the question for a test
	 * (this function could be private)
	 *
	 * @param integer $active_id			           The active user id
	 * @param integer $pass					           The test pass
	 * @param boolean $is_question_postponed           Question is postponed
	 * @param boolean $user_post_solutions	           Use post solutions
	 * @param boolean $show_specific_inline_feedback   Show a feedback
	 * @return string
	 */
	public function getTestOutput(
	    int $active_id,
	    int $pass,
	    bool $is_question_postponed = false,
	    array|bool $user_post_solutions = false,
	    bool $show_specific_inline_feedback = false
	    ): string
	{
	    global $DIC; $tpl;
		// get the solution of the user for the active pass or from the last pass if allowed
		if (is_null($pass))
		{
		    $pass = ilObjTest::_getPass($active_id);
		}
		
		$user_solution = array();
		$user_solution = $this->object->getSolutionStored($active_id, $pass, null);
		
		if (!is_array($user_solution))
		{
		    $user_solution = array();
		}
		
		$userSampleSolution = $user_solution["value1"];
		$userSmiles = $user_solution["value2"];
		$userSvg = base64_decode($user_solution["value3"] ?? '');
		$userInchi = $user_solution["value4"];
		
		$template = $this->getQuestionOutput($this->object->getQuestion(), $this->object->getOptionString(), $userSampleSolution, $userSmiles, $userSvg, $userInchi);
		$questionoutput = $template->get();
		$pageoutput = $this->outQuestionPage("", $is_question_postponed, $active_id, $questionoutput);
		return $pageoutput; 
	}

	/**
	 * Get the output for question preview
	 * (called from ilObjQuestionPoolGUI)
	 *
	 * @param boolean	$show_question_only 	show only the question instead of embedding page (true/false)
	 * @param boolean	$show_inline_feedback
	 * @return string
	 */
	public function getPreview(bool $show_question_only = false, bool $show_inline_feedback = false): string
	{
	    if( is_object($this->getPreviewSession()) )
	    {
	        $solution = $this->getPreviewSession()->getParticipantsSolution();
	    }
	    else
	    {
	        $solution = array('value1' => null, 'value2' => null, 'value3' => null, 'value4' => null);
	    }
	    
		$template = $this->getQuestionOutput($this->object->getQuestion(), $this->object->getOptionString(), "", "", "", "");		
		
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
	 * @param integer $active_id             The active user id
	 * @param integer $pass                  The test pass
	 * @param boolean $graphicalOutput       Show visual feedback for right/wrong answers
	 * @param boolean $result_output         Show the reached points for parts of the question
	 * @param boolean $show_question_only    Show the question without the ILIAS content around
	 * @param boolean $show_feedback         Show the question feedback
	 * @param boolean $show_correct_solution Show the correct solution instead of the user solution
	 * @param boolean $show_manual_scoring   Show specific information for the manual scoring output
	 * @param bool    $show_question_text
	 
	 * @return string solution output of the question as HTML code
	 */
	function getSolutionOutput(
	    int $active_id,
	    ?int $pass = null,
	    bool $graphical_output = false,
	    bool $result_output = false,
	    bool $show_question_only = true,
	    bool $show_feedback = false,
	    bool $show_correct_solution = false,
	    bool $show_manual_scoring = false,
	    bool $show_question_text = true,
	    bool $show_inline_feedback = true
	): string
	{
		global $tpl;
		// get the solution of the user for the active pass or from the last pass if allowed
		$user_solution = array();
		if (($active_id > 0) && (!$show_correct_solution))
		{
			// get the solutions of a user
			// deviation from standard: passing NULL for authorized allows usage of autosaves in results and manual scoring
		    $user_solution = $this->object->getSolutionStored($active_id, $pass, null);
		    if (!is_array($user_solution)) 
			{
				$user_solution = array();
			}
		} else {			
		    $user_solution = array(
		        'value1' => null,
		        'value2' => null,
		        'value3' => null,
		        'value4' => null,
		    );
		}						
		
		$userSampleSolution = $user_solution["value1"];
		$userSvg = base64_decode($user_solution["value3"] ?? '');

		if($userSvg== '' || $userSvg== null) {
		    $userSvg = $this->object->getPlugin()->txt("old_plugin_solution");
		} else {
		    $userSvg = substr_replace($userSvg, "The PDF engine can't handle inline SVG." . substr($userSvg, -6), -6);
		}

		if($this->object->getSvg()== '' || $this->object->getSvg()== null) {
		    $sampleSvg = $this->object->getPlugin()->txt("old_plugin_question");
		} else {
			$sampleSvg = $this->object->getSvg();
			$sampleSvg = substr_replace($sampleSvg, "The PDF engine can't handle inline SVG." . substr($sampleSvg, -6), -6);
		}

		// generate the question output
		$solutiontemplate = new ilTemplate("tpl.il_as_tst_solution_output.html", true, true, "components/ILIAS/TestQuestionPool");
		
		if ($show_correct_solution)
		{			
		    $template = $this->getQuestionOutput("", "", "", $this->object->getSmilesSolution(), $sampleSvg, $this->object->getInchiSolution(), "solution.html");
			$template->setVariable("ID", 'S'.$this->object->getId());
			return $template->get();			
			// hier nur die Musterlösung anzeigen, da wir uns im test beim drücken von check befinden
		}

		$templateUser = $this->getQuestionOutput($this->object->getQuestion(), "", "", $user_solution["value2"], $userSvg, $user_solution["value4"], "solution.html");
		$templateUser->setVariable("ID", 'U'.$this->object->getId());
		$questionoutput = $templateUser->get();
		
		if ($show_manual_scoring && strlen($this->object->getSampleSolution()) > 0 )
		{
		    $templateSample = $this->getQuestionOutput($this->object->getPlugin()->txt("sampleSolution"), "", "", $this->object->getSmilesSolution(), $sampleSvg,  $this->object->getInchiSolution(), "solution.html");
			$templateSample->setVariable("ID", 'S'.$this->object->getId());
			$questionoutput .= "<br>" . $templateSample->get();
		}

		// add the feedback
		$feedback = ($show_feedback && !$this->isTestPresentationContext()) ? $this->getGenericFeedbackOutput($active_id, $pass) : "";
		if (strlen($feedback))
		{
		    $cssClass = ( $this->hasCorrectSolution($active_id, $pass) ?
		        ilAssQuestionFeedback::CSS_CLASS_FEEDBACK_CORRECT : ilAssQuestionFeedback::CSS_CLASS_FEEDBACK_WRONG
		        );
		    
		    $solutiontemplate->setVariable("ILC_FB_CSS_CLASS", $cssClass);
		    $solutiontemplate->setVariable("FEEDBACK", self::prepareTextareaOutput( $feedback, true ));
		    
		}
		$solutiontemplate->setVariable("SOLUTION_OUTPUT", $questionoutput);
		
		$solutionoutput = $solutiontemplate->get();
		if(!$show_question_only)
		{
		    // get page object output
		    $solutionoutput = $this->getILIASPage($solutionoutput);
		}
		return $solutionoutput;
	}
	
	/**
	 * Returns the answer specific feedback for the question
	 *
	 * @param array $userSolution Array with the user solutions
	 * @return string HTML Code with the answer specific feedback
	 * @access public
	 */
	public function getSpecificFeedbackOutput($userSolution): string
	{
	    // By default no answer specific feedback is defined
	    $output = '';
	    return self::prepareTextareaOutput($output, TRUE);
	}
	
	
	/**
	 * Sets the ILIAS tabs for this question type
	 * called from ilObjTestGUI and ilObjQuestionPoolGUI
	 */
	public function setQuestionTabs(): void
	{
	    parent::setQuestionTabs();
	}

	/**
	 * Adds the question specific forms parts to a question property form gui.
	 */
	public function populateQuestionSpecificFormPart(ilPropertyFormGUI $form): ilPropertyFormGUI
	{
	    $plugin = $this->object->getPlugin();
	    
	    $form->setTitle($this->plugin->txt("questionType"));
	    
	    //Start Question specific
	    // points
	    $points = new ilNumberInputGUI($plugin->txt("points"), "points");
	    $points->setSize(3);
	    $points->setMinValue(0);
	    $points->allowDecimals(1);
	    $points->setRequired(true);
	    $points->setValue($this->object->getPoints());
	    $form->addItem($points);
	    
	    return $form;
	}
	
	/**
	 * Extracts the question specific values from the request and applies them
	 * to the data object.
	 */
	public function writeQuestionSpecificPostData(ilPropertyFormGUI $form): void
	{
	    $this->object->setPoints($this->request_data_collector->float('points'));
	}
	
	/**
	 * Returns a list of postvars which will be suppressed in the form output when used in scoring adjustment.
	 * The form elements will be shown disabled, so the users see the usual form but can only edit the settings, which
	 * make sense in the given context.
	 *
	 * E.g. array('cloze_type', 'image_filename')
	 *
	 * @return string[]
	 */
	public function getAfterParticipationSuppressionQuestionPostVars(): array
	{
	    return [];
	}
	
	public function populateAnswerSpecificFormPart(\ilPropertyFormGUI $form): ilPropertyFormGUI
	{
	    $plugin = $this->object->getPlugin();
	    
	    // SMILES (0) or InChI (1) for evaluation
	    $radioGroup = new ilRadioGroupInputGUI($plugin->txt("label_evalOption"), 'evalOption');
	    $radioGroup->setInfo($plugin->txt("info_evalOption"));
	    $radioGroup->setValue((string) ((int) ($this->object->getEvaluationOption())));
	    $modeSMILES = new ilRadioOption(
	        $plugin->txt("label_evalOption_smiles"),
	        '0'
	        );
	    $modeInChI = new ilRadioOption(
	        $plugin->txt("label_evalOption_inchi"),
	        '1'
	        );
	    $radioGroup->addOption($modeSMILES);
	    $radioGroup->addOption($modeInChI);
	    $form->addItem($radioGroup);
	    
	    // optionString for the JSME-Applet
	    $optionString = new ilTextInputGUI($plugin->txt("optionString"), "optionString");
	    $optionString->setValue($this->object->getOptionString());
	    $optionString->setInfo($plugin->txt("options_hint"));
	    $form->addItem($optionString);
	    
	    // JSME-Applet for sampleSolution
	    $sampleSolution = new ilCustomInputGUI($plugin->txt("sampleSolution"), "sampleSolution");
	    $template = $this->getQuestionOutput("", $this->object->getOptionString(), $this->object->getSampleSolution(), $this->object->getSmilesSolution(), $this->object->getSvg(), $this->object->getInchiSolution());
	    $sampleSolution->setHtml($template->get());
	    $form->addItem($sampleSolution);
	         
	    return $form;
	}
	
	public function writeAnswerSpecificPostData(ilPropertyFormGUI $form): void
	{
	    #not needed for JSME
	}
	
	/**
	 * Returns a list of postvars which will be suppressed in the form output when used in scoring adjustment.
	 * The form elements will be shown disabled, so the users see the usual form but can only edit the settings, which
	 * make sense in the given context.
	 *
	 * E.g. array('cloze_type', 'image_filename')
	 *
	 * @return string[]
	 */
	public function getAfterParticipationSuppressionAnswerPostVars(): array
	{
	    return [];
	}
	
	public function populateCorrectionsFormProperties(ilPropertyFormGUI $form): void
	{
	    $this->populateQuestionSpecificFormPart($form);
	}
	
	/**
	 * @param ilPropertyFormGUI $form
	 */
	public function saveCorrectionsFormProperties(ilPropertyFormGUI $form): void
	{
	    $this->object->setPoints((float) str_replace(',', '.', $form->getInput('points')));
	    // TODO let user change more inputs?
	}
	
	public function prepareReprintableCorrectionsForm(ilPropertyFormGUI $form): void
	{
	    #not needed for JSME
	}
}
?>