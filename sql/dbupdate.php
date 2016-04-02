<#1>
<?php
	// Trage JSME-Frage als neuen Fragetyp ein, wenn es diesen noch nicht gibt
	$res = $ilDB->queryF("SELECT * FROM qpl_qst_type WHERE type_tag = %s",
		array('text'),
		array('assJSMEQuestion')
	);
	if ($res->numRows() == 0)
	{
		$res = $ilDB->query("SELECT MAX(question_type_id) maxid FROM qpl_qst_type");
		$data = $ilDB->fetchAssoc($res);
		$max = $data["maxid"] + 1;

		$affectedRows = $ilDB->manipulateF("INSERT INTO qpl_qst_type (question_type_id, type_tag, plugin) VALUES (%s, %s, %s)", 
			array("integer", "text", "integer"),
			array($max, 'assJSMEQuestion', 1)
		);
	}
?>
<#2>
<?php
	// speichere angegebenes hintergrundbild
	$fields = array(
			'question_fi'	=> array('type' => 'integer', 'length' => 4, 'notnull' => true ),
			'option_string' => array('type' => 'text', 'length' => 200, 'fixed' => false, 'notnull' => false ),
			'solution'      => array('type' => 'clob', 'notnull' => false )
	);
	$ilDB->createTable("il_qpl_qst_jsme_data", $fields);
	$ilDB->addPrimaryKey("il_qpl_qst_jsme_data", array("question_fi"));	
?>
<#3>
<?php
    if(!$ilDB->tableColumnExists('il_qpl_qst_jsme_data', 'smiles'))
    {
        $ilDB->addTableColumn('il_qpl_qst_jsme_data', 'smiles', array(
                'type' => 'text',
                'length' => 200,
                'notnull' => false,
            )
        );
    }
?>