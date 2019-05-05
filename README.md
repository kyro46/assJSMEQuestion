# assJSMEQuestion
JSME-Questiontypeplugin for ILIAS 5.4

For ILIAS 4.3 to 5.3 see the [**Releases**](https://github.com/kyro46/assJSMEQuestion/releases) and the according branches.

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

To display the inline-SVGs in PDF this plugin needs the **PhantomJS-renderer** in ILIAS 5.3 and 5.4, or any other SVG-compatible renderer except TCPDF. 

Automatic scoring with comparison of SMILE-Code IS implemented, but (due to SMILES-Notation) not valid for molecules with stereo features. Please be cautious.
Activate manual scoring for better control.

### Credits ###
* Development of the plugin-draft for ILIAS 4.4 by Yves Annanias, University Halle, 2014
* Further development by Christoph Jobst, University Halle and Leipzig