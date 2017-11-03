# assJSMEQuestion
JSME-Questiontypeplugin for ILIAS 5.2.x and 5.3.beta

For ILIAS 4.3, 4.4, 5.0 and 5.1 see the [**Releases**](https://github.com/kyro46/assJSMEQuestion/releases)

### Questiontype that allows the creation of molecules ###

Using the free JSME-Editor, the questiontype allows to create molecules with an easy to use interface.

[**JSME-Editor Homepage**](http://peter-ertl.com/jsme/)
[**JSME-Editor Demo**](http://peter-ertl.com/jsme/JSME_2017-02-26/JSME.html)

### Usage ###

Install the plugin

```bash
mkdir -p Customizing/global/plugins/Modules/TestQuestionPool/Questions  
cd Customizing/global/plugins/Modules/TestQuestionPool/Questions
git clone https://github.com/kyro46/assJSMEQuestion.git
```
and activate it in the ILIAS-Admin-GUI.  

To finally display the inline-SVGs this plugin needs the PhantomJS-renderer in ILIAS 5.3+.

Automatic scoring with comparison of SMILE-Code IS implemented, but (due to SMILES-Notation) not valid for molecules with stereo features. Please be cautious.
Activate manual scoring for better control.

### Known Issues ###

* Fixed in ILIAS 5.3 - PDF-generation for the "Test Archive File" does not show the Javascriptapplet, so the question/solution is not shown.

### Credits ###
* Development of the plugin-draft for ILIAS 4.4 by Yves Annanias, University Halle, 2014
* Further development by Christoph Jobst, University Halle, 2014/2015/2016/2017